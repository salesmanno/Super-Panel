<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['tenant_admin_id'], $_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Ikke autentisert']);
    exit;
}

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/entity_form_fields.php';
require_once __DIR__.'/../includes/event_form_submission.php';
require_once __DIR__.'/../includes/BarcodeService.php';

try {
    if (($_SERVER['REQUEST_METHOD']??'') !== 'POST') {
        throw new InvalidArgumentException('Kun POST tillatt');
    }
    $data=json_decode(file_get_contents('php://input'),true);
    if (!is_array($data)) throw new InvalidArgumentException('Ugyldige deltakeropplysninger');
    entityFormRequireCsrf($data);

    $companyId=(string)$_SESSION['company_id'];
    $adminId=(string)$_SESSION['tenant_admin_id'];
    $eventId=is_string($data['event_id']??null)?trim($data['event_id']):'';
    $first=is_string($data['attendee_first_name']??null)?trim($data['attendee_first_name']):'';
    $last=is_string($data['attendee_last_name']??null)?trim($data['attendee_last_name']):'';
    $email=is_string($data['attendee_email']??null)?trim($data['attendee_email']):'';
    $phone=is_string($data['attendee_phone']??null)?trim($data['attendee_phone']):'';

    if ($eventId==='') throw new InvalidArgumentException('Mangler arrangement');
    if ($first==='' || $last==='') throw new InvalidArgumentException('Fornavn og etternavn er påkrevd');
    if (mb_strlen($first)>255 || mb_strlen($last)>255) throw new InvalidArgumentException('Navnet er for langt');
    if (mb_strlen($email)>255 || ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL))) {
        throw new InvalidArgumentException('E-postadressen er ugyldig');
    }
    if (mb_strlen($phone)>100) throw new InvalidArgumentException('Telefonnummeret er for langt');
    if (!is_bool($data['checked_in']??null)) throw new InvalidArgumentException('Ugyldig innsjekkingsstatus');
    $checkedIn=$data['checked_in'];

    $db=getDBConnection();
    $actor=entityFormRequireModuleAccess($db,$companyId,'event');
    $db->beginTransaction();

    $event=entityFormContext($db,$companyId,'event',$eventId,true);
    $timing=$db->prepare('SELECT start_date,ticket_price FROM events_eventdata WHERE id=? AND company_id=? FOR UPDATE');
    $timing->execute([$eventId,$companyId]);
    $eventTiming=$timing->fetch(PDO::FETCH_ASSOC);
    $today=(new DateTimeImmutable('now',new DateTimeZone('Europe/Oslo')))->format('Y-m-d');
    if (!$eventTiming) {
        throw new OutOfBoundsException('Arrangement ikke funnet');
    }
    if ($checkedIn && (empty($eventTiming['start_date']) || substr($eventTiming['start_date'],0,10)>$today)) {
        throw new InvalidArgumentException('Innsjekking åpner på arrangementsdatoen.');
    }
    $ticketPrice=max(0,(float)$eventTiming['ticket_price']);
    if ($checkedIn && $ticketPrice>0) throw new InvalidArgumentException('Registrer deltakeren først og bekreft betalingen ved innsjekking.');
    $notes=eventManualGuestFormNotes(
        $db,
        $companyId,
        $eventId,
        $first,
        $last,
        $email!==''?$email:null,
        $phone!==''?$phone:null,
        $data
    );

    $checkinId=bin2hex(random_bytes(16));
    $barcode=(new BarcodeService())->generateEAN13($checkinId,$eventId);
    $insert=$db->prepare('
        INSERT INTO events_checkin
            (id,event_id,membership_plan_id,ticket_price,payment_status,payment_method,
             checked_in,check_in_time,checked_in_by,barcode,notes,created_at,updated_at)
        VALUES (?,?,NULL,?,\'pending\',NULL,?,IF(?=1,NOW(),NULL),IF(?=1,?,NULL),?,?,NOW(),NOW())
    ');
    $checkedValue=$checkedIn?1:0;
    $insert->execute([
        $checkinId,
        $eventId,
        $ticketPrice,
        $checkedValue,
        $checkedValue,
        $checkedValue,
        $checkedIn?$adminId:null,
        $barcode,
        $notes,
    ]);

    $fullName=trim($first.' '.$last);
    tenantAuditChange(
        $db,
        $companyId,
        'events',
        'EVENT_PARTICIPANT_MANUAL_CREATE',
        'events_checkin',
        $checkinId,
        mb_substr((string)$event['name'].' / '.$fullName,0,255),
        [],
        [
            'id'=>$checkinId,
            'event_id'=>$eventId,
            'membership_plan_id'=>null,
            'ticket_price'=>$ticketPrice,
            'payment_status'=>'pending',
            'payment_method'=>null,
            'checked_in'=>$checkedIn?1:0,
        ],
        [],
        $actor
    );

    $db->commit();
    echo json_encode([
        'success'=>true,
        'message'=>'Deltakeren er lagt til',
        'checkin_id'=>$checkinId,
        'checked_in'=>$checkedIn,
        'payment_status'=>'pending',
        'ticket_price'=>$ticketPrice,
    ],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) $db->rollBack();
    if ($error instanceof DomainException) {
        http_response_code(403);
        $message=$error->getMessage();
    } elseif ($error instanceof TenantAuditException) {
        http_response_code(403);
        $message=$error->getMessage();
    } elseif ($error instanceof OutOfBoundsException) {
        http_response_code(404);
        $message='Arrangement ikke funnet';
    } elseif ($error instanceof InvalidArgumentException) {
        http_response_code(400);
        $message=$error->getMessage();
    } else {
        http_response_code(500);
        $message='Kunne ikke lagre deltakeren';
        error_log('Manual event participant creation failed: '.$error->getMessage());
    }
    echo json_encode(['success'=>false,'message'=>$message],JSON_UNESCAPED_UNICODE);
}
