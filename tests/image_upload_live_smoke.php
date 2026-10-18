<?php
declare(strict_types=1);

// Explicit opt-in: uploads two generated, non-personal public test images.
// Does not touch the application database or disclose credentials/delete URLs.
require_once __DIR__.'/../includes/ImageUpload.php';
if (PHP_SAPI !== 'cli' || !in_array('--upload-generated-public-images', $argv, true)) {
    fwrite(STDERR, "Opt in with --upload-generated-public-images. Uses the server's configured ImgBB key.\n");
    exit(2);
}
if (!is_string(getenv('IMGBB_API_KEY')) || trim((string)getenv('IMGBB_API_KEY')) === '') {
    fwrite(STDERR, "IMGBB_API_KEY is not configured for this PHP process.\n");
    exit(2);
}
$source = tempnam(sys_get_temp_dir(), 'image-smoke-');
$prepared = null;
$exitCode = 0;
try {
    foreach (['photo', 'favicon'] as $kind) {
        $canvas = imagecreatetruecolor($kind === 'photo' ? 2400 : 512, $kind === 'photo' ? 1200 : 512);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 235, 244, 246, $kind === 'favicon' ? 127 : 0));
        imagefilledrectangle($canvas, 64, 64, 200, 200, imagecolorallocatealpha($canvas, 16, 116, 126, 0));
        if ($kind === 'photo') imagejpeg($canvas, $source, 85);
        else imagepng($canvas, $source);
        imagedestroy($canvas);
        $prepared = ImageUpload::prepare($source, $kind === 'photo' ? 'image' : 'favicon');
        $result = ImageUpload::upload($prepared, (string)getenv('IMGBB_API_KEY'));
        if (array_keys($result) !== ['url', 'width', 'height']) throw new RuntimeException('Unexpected upload response fields.');
        $curl = curl_init($result['url']);
        $bytes = '';
        curl_setopt_array($curl, [
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_WRITEFUNCTION => static function ($handle, string $part) use (&$bytes): int {
                if (strlen($bytes) + strlen($part) > ImageUpload::MAX_BYTES) return 0;
                $bytes .= $part;
                return strlen($part);
            },
        ]);
        try {
            if (curl_exec($curl) === false || (int)curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200) {
                throw new RuntimeException('The public direct image URL could not be loaded.');
            }
        } finally { curl_close($curl); }
        $size = @getimagesizefromstring($bytes);
        if (!$size || $size[0] !== $prepared['width'] || $size[1] !== $prepared['height'] || $size['mime'] !== $prepared['mime']) {
            throw new RuntimeException('The hosted image did not retain the prepared dimensions/format.');
        }
        if ($kind === 'favicon') {
            $image = imagecreatefromstring($bytes);
            $alpha = imagecolorat($image, 0, 0) >> 24;
            imagedestroy($image);
            if ($alpha !== 127) throw new RuntimeException('Transparent favicon lost its alpha channel.');
        }
        echo "Live ImgBB verified: {$kind}, {$size[0]}x{$size[1]}, {$size['mime']}, public direct URL returned; no database writes.\n";
        unlink($prepared['path']); $prepared = null;
    }
} catch (Throwable $e) {
    // Only our upload adapter's controlled exceptions are safe to display.
    fwrite(STDERR, get_class($e) === RuntimeException ? $e->getMessage()."\n" : "Live image upload check failed.\n");
    $exitCode = 1;
} finally {
    if (is_array($prepared) && is_file($prepared['path'])) unlink($prepared['path']);
    if (is_file($source)) unlink($source);
}
exit($exitCode);
