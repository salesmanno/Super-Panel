<?php
declare(strict_types=1);

/**
 * Public marketing images only. Files are temporary; only the returned external
 * URL is saved by the existing module's normal create/update operation.
 */
final class ImageUpload
{
    public const MAX_BYTES = 10485760;
    public const MAX_PIXELS = 12000000;
    public const FORMATS = ['image/jpeg', 'image/png', 'image/webp'];
    public const MODULES = ['sales', 'rental', 'events', 'membership', 'announcements', 'reservation', 'company', 'website'];

    public static function requireContext(string $module, string $purpose): void
    {
        if (!in_array($module, self::MODULES, true)) {
            throw new InvalidArgumentException('Ugyldig modul for bildeopplasting.');
        }
        if (!in_array($purpose, ['image', 'logo', 'favicon'], true)) {
            throw new InvalidArgumentException('Ugyldig bildetype.');
        }
        if ($purpose !== 'image' && !in_array($module, ['company', 'website'], true)) {
            throw new InvalidArgumentException('Logo og nettstedsikon må lastes opp fra firmainnstillingene.');
        }
    }

    public static function authorize(PDO $db, string $company, string $module): void
    {
        require_once __DIR__ . '/entity_form_fields.php';
        $actor = tenantAuditActor($db, $company);
        if (in_array($module, ['company', 'website'], true)) {
            if (empty($_SESSION['ditt_nettsted_unlocked']) || (int)($_SESSION['ditt_nettsted_expires'] ?? 0) <= time()) {
                throw new TenantAuditException('Lås opp firmainnstillingene før du laster opp bilder.');
            }
            $module = 'ditt_nettsted';
        }
        // Announcements is a shared dashboard function available to signed-in
        // administrators, not a separately licensed module in this application.
        if ($module === 'announcements') {
            return;
        }
        if (!empty($_SESSION['company_auth_version'])) {
            require_once __DIR__ . '/company_login.php';
            if (!in_array($module, companyLoginModuleKeys($db, $actor['id']), true)) {
                throw new TenantAuditException('Modulen er ikke tilgjengelig i valgt bedrift.');
            }
        }
        $query = $db->prepare('SELECT sm.id FROM system_modules sm
            JOIN tenant_admin_modules tam ON tam.module_id=sm.id
            WHERE tam.tenant_admin_id=? AND sm.module_key=? AND sm.is_active=1');
        $query->execute([$actor['id'], $module]);
        if (!$query->fetchColumn()) {
            throw new TenantAuditException('Du har ikke tilgang til denne modulen.');
        }
    }

    public static function token(): string
    {
        $scope = (string)$_SESSION['tenant_admin_id'] . '|' . (string)$_SESSION['company_id'];
        if (($_SESSION['image_upload_scope'] ?? '') !== $scope || empty($_SESSION['image_upload_csrf'])) {
            $_SESSION['image_upload_scope'] = $scope;
            $_SESSION['image_upload_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['image_upload_csrf'];
    }

    public static function requirePost(array $input): void
    {
        $token = $input['csrf_token'] ?? null;
        $company = $input['company_id'] ?? null;
        if (!is_string($company) || $company !== (string)($_SESSION['company_id'] ?? '')) {
            throw new DomainException('Bedriften er endret. Last skjemaet på nytt.');
        }
        if (!is_string($token) || !hash_equals(self::token(), $token)) {
            throw new DomainException('Sikkerhetskoden er utløpt. Last skjemaet på nytt.');
        }
    }

    public static function reserveAttempt(): void
    {
        $now = time();
        $attempts = array_values(array_filter($_SESSION['image_upload_attempts'] ?? [], static fn($t) => is_int($t) && $t > $now - 600));
        if (count($attempts) >= 20) {
            throw new OverflowException('For mange bildeopplastinger. Vent noen minutter og prøv igjen.');
        }
        $attempts[] = $now;
        $_SESSION['image_upload_attempts'] = $attempts;
    }

    /** Returns a newly encoded temporary file; caller MUST unlink it in finally. */
    public static function prepare(string $source, string $purpose = 'image'): array
    {
        if (!is_file($source) || filesize($source) === 0 || filesize($source) > self::MAX_BYTES) {
            throw new InvalidArgumentException('Bildet må være mellom 1 byte og 10 MB.');
        }
        if (!extension_loaded('gd') || !class_exists('finfo')) {
            throw new RuntimeException('Serveren trenger PHP GD og Fileinfo for å behandle bilder.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($source);
        $size = @getimagesize($source);
        if (!in_array($mime, self::FORMATS, true) || !$size || ($size['mime'] ?? '') !== $mime) {
            throw new InvalidArgumentException('Velg et gyldig JPEG-, PNG- eller WebP-bilde. Andre filtyper støttes ikke.');
        }
        if ($size[0] < 1 || $size[1] < 1 || $size[0] > 12000 || $size[1] > 12000 || $size[0] * $size[1] > self::MAX_PIXELS) {
            throw new InvalidArgumentException('Bildet er for stort i piksler. Velg et bilde på høyst 12 megapiksler.');
        }
        $memory = trim((string)ini_get('memory_limit'));
        if (preg_match('/^(\d+)\s*([KMG]?)$/i', $memory, $match)) {
            $limit = (int)$match[1] * match (strtoupper($match[2])) { 'G' => 1073741824, 'M' => 1048576, 'K' => 1024, default => 1 };
            if ($limit > 0 && memory_get_usage(true) + $size[0] * $size[1] * 12 + 16777216 > $limit) {
                throw new InvalidArgumentException('Bildet krever mer minne enn serveren har tilgjengelig. Velg et mindre bilde.');
            }
        }
        $decode = match ($mime) {
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/webp' => 'imagecreatefromwebp',
        };
        if (!function_exists($decode)) {
            throw new RuntimeException('Serverens bildebehandling støtter ikke dette bildeformatet.');
        }
        $image = @$decode($source);
        if (!$image) {
            throw new InvalidArgumentException('Bildet kunne ikke leses. Velg en annen bildefil.');
        }
        $canvas = null;
        $path = null;
        try {
            // Honor phone orientation before removing EXIF (including GPS data).
            if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
                $exif = @exif_read_data($source, 'IFD0');
                $orientation = (int)($exif['Orientation'] ?? 1);
                if (in_array($orientation, [2, 4, 5, 7], true)) {
                    imageflip($image, in_array($orientation, [2, 5, 7], true) ? IMG_FLIP_HORIZONTAL : IMG_FLIP_VERTICAL);
                }
                $rotation = match ($orientation) { 3 => 180, 5, 6 => -90, 7, 8 => 90, default => 0 };
                if ($rotation !== 0) {
                    $rotated = imagerotate($image, $rotation, 0);
                    if (!$rotated) {
                        throw new RuntimeException('Bildet kunne ikke roteres.');
                    }
                    imagedestroy($image);
                    $image = $rotated;
                }
            }
            $maxEdge = match ($purpose) { 'favicon' => 256, 'logo' => 800, default => 1600 };
            $scale = min(1, $maxEdge / max(imagesx($image), imagesy($image)));
            $width = max(1, (int)round(imagesx($image) * $scale));
            $height = max(1, (int)round(imagesy($image) * $scale));
            $canvas = imagecreatetruecolor($width, $height);
            if (!$canvas) {
                throw new RuntimeException('Bildet kunne ikke behandles.');
            }
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
            imagecopyresampled($canvas, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
            // PNG preserves transparency and works as favicon without SVG/ICO input.
            $asPng = $purpose !== 'image' || $mime === 'image/png';
            $path = tempnam(sys_get_temp_dir(), 'salesman-image-');
            if ($path === false) {
                throw new RuntimeException('Midlertidig bildebehandling er ikke tilgjengelig.');
            }
            chmod($path, 0600);
            $ok = $asPng ? imagepng($canvas, $path, 7) : imagejpeg($canvas, $path, 85);
            if (!$ok || filesize($path) === 0 || filesize($path) > self::MAX_BYTES) {
                throw new RuntimeException('Bildet kunne ikke klargjøres for opplasting.');
            }
            return ['path' => $path, 'mime' => $asPng ? 'image/png' : 'image/jpeg',
                'extension' => $asPng ? 'png' : 'jpg', 'width' => $width, 'height' => $height];
        } catch (Throwable $e) {
            if (is_string($path) && is_file($path)) {
                unlink($path);
            }
            throw $e;
        } finally {
            imagedestroy($image);
            if ($canvas) {
                imagedestroy($canvas);
            }
        }
    }

    public static function upload(array $prepared, string $key, ?callable $transport = null): array
    {
        if (trim($key) === '') {
            throw new RuntimeException('Bildeopplasting er ikke konfigurert. Serveransvarlig må sette IMGBB_API_KEY.');
        }
        if ($transport === null) {
            $transport = static function (array $image, string $secret): array {
                if (!extension_loaded('curl')) {
                    throw new RuntimeException('Serveren trenger PHP cURL for bildeopplasting.');
                }
                $body = '';
                $curl = curl_init('https://api.imgbb.com/1/upload');
                curl_setopt_array($curl, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => ['key' => $secret,
                        'image' => new CURLFile($image['path'], $image['mime'], bin2hex(random_bytes(16)) . '.' . $image['extension'])],
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_TIMEOUT => 45,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_WRITEFUNCTION => static function ($handle, string $part) use (&$body): int {
                        if (strlen($body) + strlen($part) > 131072) {
                            return 0;
                        }
                        $body .= $part;
                        return strlen($part);
                    },
                ]);
                try {
                    $ok = curl_exec($curl);
                    $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    if ($ok === false) {
                        throw new RuntimeException('Bildeverten svarte ikke. Prøv igjen senere.');
                    }
                    return ['code' => $code, 'data' => json_decode($body, true)];
                } finally {
                    curl_close($curl);
                }
            };
        }
        $response = $transport($prepared, $key);
        $data = $response['data'] ?? null;
        if (($response['code'] ?? 0) < 200 || ($response['code'] ?? 0) >= 300 || !is_array($data) || ($data['success'] ?? false) !== true) {
            throw new RuntimeException('ImgBB avviste opplastingen. Kontroller serverens oppsett og prøv igjen.');
        }
        // Only return a direct public image URL. Never expose the deletion token.
        $url = $data['data']['url'] ?? null;
        $parts = is_string($url) ? parse_url($url) : false;
        if (!$parts || ($parts['scheme'] ?? '') !== 'https' || strtolower($parts['host'] ?? '') !== 'i.ibb.co'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])
            || isset($parts['query']) || isset($parts['fragment']) || preg_match('/[\x00-\x20]/', $url)
            || strlen($url) > 255 || !filter_var($url, FILTER_VALIDATE_URL)
            || !preg_match('~^/[A-Za-z0-9/_-]+\.(?:png|jpe?g|webp)$~iD', $parts['path'] ?? '')) {
            throw new RuntimeException('Bildeverten returnerte ikke en gyldig direkte bilde-URL.');
        }
        return ['url' => $url, 'width' => $prepared['width'], 'height' => $prepared['height']];
    }
}
