<?php
session_start();
session_write_close();

if (!isset($_SESSION['tenant_admin_id'])) {
    http_response_code(403);
    exit('Ikke autentisert');
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$allowedFields = [
    'first_name'      => 'Fornavn',
    'last_name'       => 'Etternavn',
    'email'           => 'E-post',
    'phone'           => 'Telefon',
    'address'         => 'Adresse',
    'member_code'     => 'Medlemsnummer',
    'membership_type' => 'Medlemskapsplan',
    'join_date'       => 'Innmeldingsdato',
    'expiry_date'     => 'Utløpsdato',
    'payment_status'  => 'Betalingsstatus',
];

$requestedFields = isset($_GET['fields']) ? explode(',', $_GET['fields']) : array_keys($allowedFields);
$selectedFields = array_values(array_filter($requestedFields, fn($f) => isset($allowedFields[$f])));

if (empty($selectedFields)) {
    $selectedFields = array_keys($allowedFields);
}

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $stmt = $db->prepare("
        SELECT
            mm.member_code,
            mm.join_date,
            mm.expiration_date AS expiry_date,
            mm.payment_status,
            c.first_name,
            c.last_name,
            c.email,
            c.phone,
            c.address_street AS address,
            p.name AS membership_type
        FROM membership_members mm
        LEFT JOIN tenant_customers c ON mm.customer_id = c.id
        LEFT JOIN membership_plans p ON mm.membership_plan_id = p.id
        WHERE mm.company_id = ?
          AND (mm.cancellation_active IS NULL OR mm.cancellation_active = 0)
          AND mm.payment_status = 'paid'
          AND mm.expiration_date > CURDATE()
        ORDER BY c.last_name ASC, c.first_name ASC
    ");
    $stmt->execute([$companyId]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Aktive medlemmer');

    $dateFields = ['join_date', 'expiry_date'];

    $colIdx = 1;
    foreach ($selectedFields as $field) {
        $colLetter = Coordinate::stringFromColumnIndex($colIdx);
        $cell = $colLetter . '1';
        $sheet->setCellValue($cell, $allowedFields[$field]);
        $sheet->getStyle($cell)->getFont()->setBold(true);
        $colIdx++;
    }

    $rowIdx = 2;
    foreach ($members as $member) {
        $colIdx = 1;
        foreach ($selectedFields as $field) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $value = $member[$field] ?? '';
            if (in_array($field, $dateFields) && !empty($value)) {
                $dt = DateTime::createFromFormat('Y-m-d', $value);
                if ($dt) {
                    $value = $dt->format('d.m.Y');
                }
            }
            $sheet->setCellValueExplicit($colLetter . $rowIdx, (string)$value, DataType::TYPE_STRING);
            $colIdx++;
        }
        $rowIdx++;
    }

    for ($i = 1; $i <= count($selectedFields); $i++) {
        $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
    }

    $filename = 'medlemsliste_' . date('Y-m-d') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    error_log("Excel export error: " . $e->getMessage());
    http_response_code(500);
    exit('Feil ved eksport av medlemsliste.');
}
