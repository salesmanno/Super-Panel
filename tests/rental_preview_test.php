<?php
require_once __DIR__ . '/../includes/rental_preview.php';
function ok($v, $m) { if (!$v) { fwrite(STDERR, "FAIL: $m\n"); exit(1); } }
putenv('REPLIT_DEPLOYMENT=1'); ok(rentalPreviewIsBlocked(), 'production guard');
putenv('REPLIT_DEPLOYMENT='); putenv('RENTAL_PREVIEW_PASSWORD=secret-test-value');
ok(rentalPreviewUsernameIsValid('rental-preview'), 'username'); ok(!rentalPreviewUsernameIsValid('admin'), 'wrong username');
ok(rentalPreviewPasswordIsValid('secret-test-value'), 'password'); ok(!rentalPreviewPasswordIsValid('wrong'), 'wrong password');
ok(!rentalPreviewCsrfIsValid('token', ''), 'missing CSRF fails'); ok(!rentalPreviewCsrfIsValid('', 'token'), 'missing stored CSRF fails'); ok(rentalPreviewCsrfIsValid('token', 'token'), 'CSRF succeeds');
ok(rentalPreviewRoute('GET','/api/get_rental_objects.php') === 'objects', 'allowed GET');
ok(rentalPreviewRoute('POST','/api/get_rental_objects.php') === null, 'mutation blocked');
ok(rentalPreviewRoute('GET','/api/delete_rental_object.php') === null, 'unknown mutation blocked');
$data = rentalPreviewSyntheticData('objects'); ok($data['success'] && count($data['objects']) >= 4, 'object shape'); ok(isset($data['objects'][0]['type_data'],$data['objects'][0]['customer_fields']), 'synthetic detail shape');
$settings = $data['objects'][0]['customer_fields']; ok(isset($settings[0]['field'],$settings[0]['label'],$settings[0]['selected'],$settings[0]['required'],$settings[0]['sort_order']), 'customer settings shape');
$contracts = rentalPreviewSyntheticData('contracts')['contracts']; ok(isset($contracts[0]['rental_object_id'],$contracts[0]['rental_days']), 'contract shape');
$requests = rentalPreviewSyntheticData('requests')['requests']; ok(is_array($requests[0]['request_fields'][0]) && isset($requests[0]['request_fields'][0]['field'],$requests[0]['request_fields'][0]['label'],$requests[0]['request_fields'][0]['value']), 'request fields shape'); ok(isset($requests[0]['display_email'],$requests[0]['display_phone'],$requests[0]['logistics']), 'request display shape');
$schedule = rentalPreviewSyntheticData('schedule')['requests']; ok(isset($schedule[0]['display_email'],$schedule[0]['display_phone'],$schedule[0]['logistics']), 'schedule display shape');
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https, http'; ok(rentalPreviewCookieSecure(), 'forwarded HTTPS token');
$_SESSION = []; ok(!rentalPreviewEndpointCanServe(), 'endpoint session guard'); $_SESSION['rental_preview_authenticated'] = true; ok(rentalPreviewEndpointCanServe(), 'endpoint authenticated guard');
$rateDir = sys_get_temp_dir() . '/rental-preview-test-' . bin2hex(random_bytes(5)); mkdir($rateDir, 0700, true);
$rateUser = 'rental-preview'; $rateIp = '198.51.100.42'; $rateNow = 1700000000;
ok(!rentalPreviewRateIsLocked($rateUser, $rateIp, $rateDir, $rateNow), 'rate starts unlocked');
for ($i = 0; $i < 4; $i++) rentalPreviewRateRecordFailure($rateUser, $rateIp, $rateDir, $rateNow + $i);
ok(!rentalPreviewRateIsLocked($rateUser, $rateIp, $rateDir, $rateNow + 4), 'four failures allowed');
rentalPreviewRateRecordFailure($rateUser, $rateIp, $rateDir, $rateNow + 4);
ok(rentalPreviewRateIsLocked($rateUser, $rateIp, $rateDir, $rateNow + 5), 'five failures lock');
ok(rentalPreviewRateState($rateUser, $rateIp, $rateDir, $rateNow + 5)['failures'] === 5, 'shared server counter');
rentalPreviewRateClear($rateUser, $rateIp, $rateDir, $rateNow + 6); ok(!rentalPreviewRateIsLocked($rateUser, $rateIp, $rateDir, $rateNow + 6), 'clear rate state');
@unlink($rateDir . '/salesman-rental-preview-rate.json'); @unlink($rateDir . '/salesman-rental-preview-rate.json.lock'); @rmdir($rateDir);
echo "rental_preview_test: ok\n";