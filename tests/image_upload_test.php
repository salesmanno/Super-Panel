<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/ImageUpload.php';

function imageCheck(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException('FAIL: '.$message);
}
function imageReject(callable $call, string $message): void {
    try { $call(); } catch (Throwable $e) { return; }
    throw new RuntimeException('FAIL: expected rejection: '.$message);
}
$dir = sys_get_temp_dir().'/image-upload-tests-'.bin2hex(random_bytes(8));
mkdir($dir, 0700);
$outputs = [];
try {
    $canvas = imagecreatetruecolor(3200, 900);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 12, 130, 160));
    imagejpeg($canvas, $dir.'/photo.jpg');
    imagedestroy($canvas);
    // An appended payload is not preserved by GD's decode/re-encode.
    file_put_contents($dir.'/photo.jpg', "\nPRIVATE-METADATA-PAYLOAD", FILE_APPEND);
    $photo = ImageUpload::prepare($dir.'/photo.jpg');
    $outputs[] = $photo['path'];
    imageCheck($photo['width'] === 1600 && $photo['height'] === 450, 'photo downsized proportionally');
    imageCheck(!str_contains(file_get_contents($photo['path']), 'PRIVATE-METADATA-PAYLOAD'), 'metadata/payload stripped');
    imageCheck((new finfo(FILEINFO_MIME_TYPE))->file($photo['path']) === 'image/jpeg', 'clean image output');

    $canvas = imagecreatetruecolor(900, 600);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
    imagepng($canvas, $dir.'/logo.png');
    imagedestroy($canvas);
    foreach (['logo'=>800, 'favicon'=>256] as $purpose=>$width) {
        $logo = ImageUpload::prepare($dir.'/logo.png', $purpose);
        $outputs[] = $logo['path'];
        imageCheck($logo['width'] === $width && $logo['mime'] === 'image/png', $purpose.' dimensions/PNG');
        $decoded = imagecreatefrompng($logo['path']);
        imageCheck((imagecolorat($decoded, 0, 0) >> 24) === 127, 'alpha preserved');
        imagedestroy($decoded);
    }
    $small = imagecreatetruecolor(48, 32);
    imagewebp($small, $dir.'/photo.webp');
    imagedestroy($small);
    $webp = ImageUpload::prepare($dir.'/photo.webp');
    $outputs[] = $webp['path'];
    imageCheck($webp['width'] === 48 && $webp['height'] === 32, 'small images never upscaled; WebP supported');
    file_put_contents($dir.'/fake.jpg', '<?php echo "unsafe";');
    file_put_contents($dir.'/fake.png', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
    file_put_contents($dir.'/empty.jpg', '');
    foreach (['fake.jpg', 'fake.png', 'empty.jpg'] as $file) {
        imageReject(fn()=>ImageUpload::prepare($dir.'/'.$file), $file);
    }
    $large = imagecreatetruecolor(4000, 4000);
    imagepng($large, $dir.'/too-large.png');
    imagedestroy($large);
    imageReject(fn()=>ImageUpload::prepare($dir.'/too-large.png'), 'pixel limit checked before decoding');
    file_put_contents($dir.'/too-many-bytes.jpg', str_repeat('x', ImageUpload::MAX_BYTES + 1));
    imageReject(fn()=>ImageUpload::prepare($dir.'/too-many-bytes.jpg'), 'byte limit');
    foreach (ImageUpload::MODULES as $module) ImageUpload::requireContext($module, 'image');
    imageReject(fn()=>ImageUpload::requireContext('unrecognized', 'image'), 'unknown module');
    imageReject(fn()=>ImageUpload::requireContext('sales', 'favicon'), 'purpose scoped');
    ImageUpload::requireContext('website', 'favicon');

    $_SESSION = ['tenant_admin_id'=>'admin-a', 'company_id'=>'company-a'];
    $token = ImageUpload::token();
    ImageUpload::requirePost(['csrf_token'=>$token,'company_id'=>'company-a']);
    imageReject(fn()=>ImageUpload::requirePost(['csrf_token'=>'bad','company_id'=>'company-a']), 'CSRF');
    imageReject(fn()=>ImageUpload::requirePost(['csrf_token'=>$token,'company_id'=>'company-b']), 'other company');
    $_SESSION['company_id'] = 'company-b';
    imageReject(fn()=>ImageUpload::requirePost(['csrf_token'=>$token,'company_id'=>'company-b']), 'old company token');
    for ($i=0;$i<20;$i++) ImageUpload::reserveAttempt();
    imageReject(fn()=>ImageUpload::reserveAttempt(), 'rate limit');
    $_SESSION['image_upload_attempts'] = [time()-601];
    ImageUpload::reserveAttempt();

    $good = static function(array $file,string $secret): array {
        imageCheck($secret === 'test-only-key', 'server sends configured key, not client input');
        imageCheck(is_file($file['path']), 'temporary file used');
        return ['code'=>200,'data'=>['success'=>true,'data'=>[
            'url'=>'https://i.ibb.co/ExampleHash/image.png', 'delete_url'=>'https://ibb.co/delete/never-expose']]];
    };
    $result = ImageUpload::upload($photo, 'test-only-key', $good);
    imageCheck(array_keys($result) === ['url','width','height'], 'no raw response or deletion secret returned');
    imageReject(fn()=>ImageUpload::upload($photo, '', $good), 'missing key');
    foreach ([
        ['code'=>403,'data'=>['success'=>false,'error'=>['message'=>'do not reflect provider secrets']]],
        ['code'=>200,'data'=>null],
        ['code'=>200,'data'=>['success'=>false]],
    ] as $response) imageReject(fn()=>ImageUpload::upload($photo,'test-only-key',static fn()=>$response), 'provider failure');
    foreach (['http://i.ibb.co/a/image.png','https://evil.example/image.png','https://i.ibb.co.evil.example/a.jpg',
        'https://i.ibb.co/a/image.svg','https://i.ibb.co/a/image.jpg?delete=secret','https://user@i.ibb.co/a/image.jpg',
        'https://i.ibb.co:443/a/image.jpg','https://i.ibb.co/a/image.jpg#fragment'] as $url) {
        imageReject(fn()=>ImageUpload::upload($photo,'test-only-key',static fn()=>[
            'code'=>200,'data'=>['success'=>true,'data'=>['url'=>$url]]]), 'bad direct URL');
    }
    echo "Image upload tests passed: validation, resizing, transparency, metadata removal, URL-only result, CSRF, company context, throttling, provider errors.\n";
} finally {
    foreach (array_merge($outputs, glob($dir.'/*') ?: []) as $file) if (is_file($file)) unlink($file);
    rmdir($dir);
}
