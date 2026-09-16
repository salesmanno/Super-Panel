<?php
/**
 * Small, dependency-free boundary for the development Rental preview.
 * This file must never be used by the production authentication flow.
 */
require_once __DIR__ . '/rental_customer_fields.php';

function rentalPreviewIsBlocked(): bool
{
    return getenv('REPLIT_DEPLOYMENT') === '1';
}

function rentalPreviewUsernameIsValid(string $username): bool
{
    return hash_equals('rental-preview', $username);
}

function rentalPreviewPasswordIsValid(string $password): bool
{
    $expected = getenv('RENTAL_PREVIEW_PASSWORD');
    return is_string($expected) && $expected !== '' && hash_equals($expected, $password);
}

function rentalPreviewCsrfIsValid(?string $stored, ?string $submitted): bool
{
    return is_string($stored) && $stored !== ''
        && is_string($submitted) && $submitted !== ''
        && hash_equals($stored, $submitted);
}

function rentalPreviewRateKey(string $username, ?string $remoteAddress = null): string
{
    $address = $remoteAddress ?? (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return hash('sha256', $address . "\0" . strtolower(trim($username)));
}

function rentalPreviewRateFile(?string $basePath = null): string
{
    $directory = $basePath ?: sys_get_temp_dir();
    if (!is_dir($directory)) {
        @mkdir($directory, 0700, true);
    }
    return rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'salesman-rental-preview-rate.json';
}

function rentalPreviewRateState(string $username, ?string $remoteAddress = null, ?string $basePath = null, ?int $currentTime = null): array
{
    $now = $currentTime ?? time();
    $file = rentalPreviewRateFile($basePath);
    $lockHandle = @fopen($file . '.lock', 'c');
    if (!$lockHandle || !flock($lockHandle, LOCK_EX)) {
        if (is_resource($lockHandle)) fclose($lockHandle);
        return ['failures' => 0, 'first_failure' => $now, 'last_failure' => $now, 'locked_until' => 0];
    }
    $handle = @fopen($file, 'c+');
    if (!$handle) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        return ['failures' => 0, 'first_failure' => $now, 'last_failure' => $now, 'locked_until' => 0];
    }
    rewind($handle);
    $decoded = json_decode((string)stream_get_contents($handle), true);
    $state = is_array($decoded) ? $decoded : [];
    foreach ($state as $key => $entry) {
        $last = (int)($entry['last_failure'] ?? 0);
        $lockedUntil = (int)($entry['locked_until'] ?? 0);
        if ($last + 600 <= $now && $lockedUntil <= $now) unset($state[$key]);
    }
    if (count($state) > 256) {
        uasort($state, static fn(array $a, array $b): int => ((int)($b['last_failure'] ?? 0)) <=> ((int)($a['last_failure'] ?? 0)));
        $state = array_slice($state, 0, 256, true);
    }
    $key = rentalPreviewRateKey($username, $remoteAddress);
    $result = $state[$key] ?? ['failures'=>0, 'first_failure'=>$now, 'last_failure'=>$now, 'locked_until'=>0];
    fclose($handle);
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    return $result;
}

function rentalPreviewRateUpdate(string $username, callable $update, ?string $remoteAddress = null, ?string $basePath = null, ?int $currentTime = null): array
{
    $now = $currentTime ?? time();
    $file = rentalPreviewRateFile($basePath);
    $lockHandle = @fopen($file . '.lock', 'c');
    if (!$lockHandle || !flock($lockHandle, LOCK_EX)) {
        if (is_resource($lockHandle)) fclose($lockHandle);
        return ['failures'=>0, 'first_failure'=>$now, 'last_failure'=>$now, 'locked_until'=>0];
    }
    $handle = @fopen($file, 'c+');
    if (!$handle) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        return ['failures'=>0, 'first_failure'=>$now, 'last_failure'=>$now, 'locked_until'=>0];
    }
    rewind($handle);
    $state = json_decode((string)stream_get_contents($handle), true);
    $state = is_array($state) ? $state : [];
    foreach ($state as $key => $entry) {
        if ((int)($entry['last_failure'] ?? 0) + 600 <= $now && (int)($entry['locked_until'] ?? 0) <= $now) unset($state[$key]);
    }
    $key = rentalPreviewRateKey($username, $remoteAddress);
    $entry = $update($state[$key] ?? ['failures'=>0, 'first_failure'=>$now, 'last_failure'=>$now, 'locked_until'=>0], $now);
    $state[$key] = $entry;
    if (count($state) > 256) {
        uasort($state, static fn(array $a, array $b): int => ((int)($b['last_failure'] ?? 0)) <=> ((int)($a['last_failure'] ?? 0)));
        $state = array_slice($state, 0, 256, true);
    }
    $temporary = $file . '.tmp';
    file_put_contents($temporary, json_encode($state), LOCK_EX);
    chmod($temporary, 0600);
    rename($temporary, $file);
    fclose($handle);
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    return $entry;
}

function rentalPreviewRateIsLocked(string $username, ?string $remoteAddress = null, ?string $basePath = null, ?int $currentTime = null): bool
{
    $now = $currentTime ?? time();
    return (int)rentalPreviewRateState($username, $remoteAddress, $basePath, $now)['locked_until'] > $now;
}

function rentalPreviewRateRecordFailure(string $username, ?string $remoteAddress = null, ?string $basePath = null, ?int $currentTime = null): array
{
    return rentalPreviewRateUpdate($username, static function (array $entry, int $now): array {
        if ((int)($entry['last_failure'] ?? 0) + 600 <= $now) $entry = ['failures'=>0, 'first_failure'=>$now, 'last_failure'=>$now, 'locked_until'=>0];
        $entry['failures'] = (int)$entry['failures'] + 1;
        $entry['last_failure'] = $now;
        if ($entry['failures'] >= 5) $entry['locked_until'] = $now + 600;
        return $entry;
    }, $remoteAddress, $basePath, $currentTime);
}

function rentalPreviewRateClear(string $username, ?string $remoteAddress = null, ?string $basePath = null, ?int $currentTime = null): void
{
    rentalPreviewRateUpdate($username, static function (array $entry, int $now): array {
        return ['failures'=>0, 'first_failure'=>$now, 'last_failure'=>$now, 'locked_until'=>0];
    }, $remoteAddress, $basePath, $currentTime);
}

function rentalPreviewRoute(string $method, string $path): ?string
{
    if (strtoupper($method) !== 'GET') return null;
    $path = parse_url($path, PHP_URL_PATH) ?: $path;
    $allowed = [
        '/api/get_rental_objects.php' => 'objects',
        '/api/get_rental_contracts.php' => 'contracts',
        '/api/get_rental_schedule.php' => 'schedule',
        '/api/get_rental_results.php' => 'results',
        '/api/get_customers_simple.php' => 'customers',
    ];
    return $allowed[$path] ?? null;
}

function rentalPreviewSessionActive(): bool
{
    return !empty($_SESSION['rental_preview_authenticated'])
        && $_SESSION['rental_preview_authenticated'] === true;
}

function rentalPreviewEndpointCanServe(): bool
{
    return !rentalPreviewIsBlocked() && rentalPreviewSessionActive();
}

function rentalPreviewCookieSecure(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''), 2)[0])) === 'https';
}

function rentalPreviewSyntheticData(string $kind): array
{
    $customerSettings = static function (array $fields): array {
        $labels = getRentalCustomerFieldDefinitions();
        return array_map(static function (string $field, int $order) use ($labels): array {
            return ['field'=>$field,'label'=>$labels[$field] ?? $field,'selected'=>1,'required'=>($order < 2 ? 1 : 0),'sort_order'=>$order];
        }, $fields, array_keys($fields));
    };
    $objects = [
        ['id'=>'obj-101','object_name'=>'Fjordglimt cabin','category'=>'cabin','rental_price'=>1850,'weekend_price'=>2200,'deposit_amount'=>4000,'is_available'=>1,'display_status'=>'available','description'=>'Quiet cabin close to the water, ready for weekends and longer stays.','badges'=>['Ved vannet','Sengetøy inkludert'],'images'=>[],'type_data'=>['sqm'=>68,'bedrooms'=>3,'beds'=>6,'season_type'=>'Helår','max_guests'=>6,'facilities'=>'Peis, badstue, parkering'],'customer_fields'=>$customerSettings(['first_name','last_name','phone','email','age'])],
        ['id'=>'obj-102','object_name'=>'Mercedes Vito 2022','category'=>'vehicle','rental_price'=>1290,'saturday_price'=>1490,'sunday_price'=>1490,'deposit_amount'=>6000,'is_available'=>0,'display_status'=>'rented','description'=>'Long-wheelbase electric van with room for the crew.','badges'=>['Elektrisk','Automat'],'images'=>[],'type_data'=>['make'=>'Mercedes-Benz','model'=>'eVito','year'=>2022,'license_plate'=>'EL 48291','seats'=>3,'mileage_km'=>38200],'customer_fields'=>$customerSettings(['first_name','last_name','phone','email','street_address','city'])],
        ['id'=>'obj-103','object_name'=>'Nordlys selskapslokale','category'=>'venue','rental_price'=>3900,'weekend_price'=>4500,'deposit_amount'=>3000,'is_available'=>1,'display_status'=>'available','description'=>'Flexible room for workshops, dinners and small launches.','badges'=>['Opptil 60 personer'],'images'=>[],'type_data'=>['sqm'=>120,'capacity_seated'=>60,'capacity_standing'=>90,'kitchen_type'=>'Fullstendig'],'customer_fields'=>$customerSettings(['first_name','last_name','phone','email','postal_code','street_address','city'])],
        ['id'=>'obj-104','object_name'=>'Brenderup maskinhenger','category'=>'trailer','rental_price'=>640,'weekend_price'=>1100,'deposit_amount'=>2500,'is_available'=>1,'display_status'=>'available','description'=>'Robust trailer with ramp and cover.','badges'=>['750 kg nyttelast'],'images'=>[],'type_data'=>['trailer_type'=>'Maskinhenger','load_capacity_kg'=>750,'brakes'=>1,'year'=>2021],'customer_fields'=>$customerSettings(['first_name','last_name','phone','email','age'])],
    ];
    $customers = [
        ['id'=>'cus-1','first_name'=>'Ingrid','last_name'=>'Dahl','name'=>'Ingrid Dahl','email'=>'ingrid.dahl@example.test','phone'=>'+47 912 44 180','postal_code'=>'5003','street_address'=>'Skuteviksveien 12','city'=>'Bergen','age'=>38],
        ['id'=>'cus-2','first_name'=>'Marius','last_name'=>'Solheim','name'=>'Marius Solheim','email'=>'marius.solheim@example.test','phone'=>'+47 988 20 441','postal_code'=>'7010','street_address'=>'Kjøpmannsgata 31','city'=>'Trondheim','age'=>44],
    ];
    $contracts = [
        ['id'=>'ctr-1','rental_object_id'=>'obj-102','object_id'=>'obj-102','rental_days'=>5,'object_name'=>'Mercedes Vito 2022','customer_name'=>'Ingrid Dahl','display_customer_name'=>'Ingrid Dahl','display_email'=>'ingrid.dahl@example.test','display_phone'=>'+47 912 44 180','start_date'=>'2026-09-18','end_date'=>'2026-09-22','total_amount'=>5960,'deposit_amount'=>6000,'status'=>'active','payment_status'=>'paid','payment_method'=>'Vipps','notes'=>'Henting etter kl. 09:00'],
        ['id'=>'ctr-2','rental_object_id'=>'obj-101','object_id'=>'obj-101','rental_days'=>3,'object_name'=>'Fjordglimt cabin','customer_name'=>'Marius Solheim','display_customer_name'=>'Marius Solheim','display_email'=>'marius.solheim@example.test','display_phone'=>'+47 988 20 441','start_date'=>'2026-09-04','end_date'=>'2026-09-06','total_amount'=>4400,'deposit_amount'=>4000,'status'=>'completed','payment_status'=>'unpaid','payment_method'=>'Faktura','notes'=>''],
        ['id'=>'ctr-3','rental_object_id'=>'obj-103','object_id'=>'obj-103','rental_days'=>1,'object_name'=>'Nordlys selskapslokale','customer_name'=>'Kari Nilsen','display_customer_name'=>'Kari Nilsen','display_email'=>'kari.nilsen@example.test','display_phone'=>'+47 922 15 602','start_date'=>'2026-10-03','end_date'=>'2026-10-03','total_amount'=>3900,'deposit_amount'=>3000,'status'=>'draft','payment_status'=>'unpaid','payment_method'=>null,'notes'=>'Avventer endelig antall'],
    ];
    $requests = [
        ['id'=>'req-1','rental_object_id'=>'obj-101','object_id'=>'obj-101','object_name'=>'Fjordglimt cabin','object_category'=>'Hytte/Feriehus','display_customer_name'=>'Oda Berg','customer_name'=>'Oda Berg','display_email'=>'oda.berg@example.test','display_phone'=>'+47 901 11 223','customer_email'=>'oda.berg@example.test','customer_phone'=>'+47 901 11 223','start_date'=>'2026-09-25','end_date'=>'2026-09-27','start_time'=>'15:00:00','end_time'=>'11:00:00','rental_fee'=>4400,'created_at'=>'2026-09-01 10:11:00','status'=>'pending','request_fields'=>[['field'=>'first_name','label'=>'Fornavn','value'=>'Oda'],['field'=>'last_name','label'=>'Etternavn','value'=>'Berg'],['field'=>'phone','label'=>'Telefon','value'=>'+47 901 11 223'],['field'=>'email','label'=>'E-post','value'=>'oda.berg@example.test'],['field'=>'postal_code','label'=>'Postnummer','value'=>'0550'],['field'=>'street_address','label'=>'Gateadresse','value'=>'Sofienberggata 8'],['field'=>'city','label'=>'Poststed','value'=>'Oslo'],['field'=>'age','label'=>'Alder','value'=>'31']],'notes'=>'Ønsker sen innsjekk','logistics'=>'pickup'],
    ];
    $schedule = [
        ['id'=>'sch-1','rental_object_id'=>'obj-103','object_id'=>'obj-103','object_name'=>'Nordlys selskapslokale','display_customer_name'=>'Kari Nilsen','customer_name'=>'Kari Nilsen','display_email'=>'kari.nilsen@example.test','display_phone'=>'+47 922 15 602','start_date'=>'2026-09-19','end_date'=>'2026-09-19','start_time'=>'12:00:00','end_time'=>'23:00:00','rental_fee'=>3900,'status'=>'approved','approved'=>1,'request_fields'=>[],'logistics'=>'pickup','notes'=>''],
        ['id'=>'sch-2','rental_object_id'=>'obj-101','object_id'=>'obj-101','object_name'=>'Fjordglimt cabin','display_customer_name'=>'Marius Solheim','customer_name'=>'Marius Solheim','display_email'=>'marius.solheim@example.test','display_phone'=>'+47 988 20 441','start_date'=>'2026-10-09','end_date'=>'2026-10-11','start_time'=>'15:00:00','end_time'=>'11:00:00','rental_fee'=>4400,'status'=>'approved','approved'=>1,'request_fields'=>[],'logistics'=>'delivery','notes'=>''],
    ];
    return match ($kind) {
        'objects' => ['success'=>true,'objects'=>$objects],
        'contracts' => ['success'=>true,'contracts'=>$contracts],
        'requests' => ['success'=>true,'requests'=>$requests],
        'schedule' => ['success'=>true,'requests'=>$schedule,'schedule'=>$schedule,'rentals'=>$schedule],
        'results' => ['success'=>true,'summary'=>['total_revenue'=>14260,'total_rentals'=>8,'monthly_revenue'=>5960,'average_per_paid_rental'=>1782.50,'status_distribution'=>['active'=>2,'completed'=>5,'draft'=>1],'recent_paid_contracts'=>$contracts],'metrics'=>['total_revenue'=>14260,'total_contracts'=>8,'completed_contracts'=>5,'average_rental_value'=>1782.50,'occupancy_rate'=>64.4]],
        'customers' => ['success'=>true,'customers'=>$customers],
        default => ['success'=>false,'message'=>'Syntetisk forhåndsvisning'],
    };
}