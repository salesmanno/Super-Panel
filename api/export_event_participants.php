<?php
declare(strict_types=1);

session_start();
header('Cache-Control: no-store, private');

try {
    if (!isset($_SESSION['tenant_admin_id'], $_SESSION['company_id'])) {
        http_response_code(401);
        throw new InvalidArgumentException('Innloggingen har utløpt.');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        throw new InvalidArgumentException('Kun POST tillatt.');
    }

    require_once __DIR__.'/../vendor/autoload.php';
    require_once __DIR__.'/../config/database.php';
    require_once __DIR__.'/../includes/EventCheckin.php';

    $input=json_decode(file_get_contents('php://input'),true,32,JSON_THROW_ON_ERROR);
    if (!is_array($input)) throw new InvalidArgumentException('Ugyldig forespørsel.');
    entityFormRequireCsrf($input);

    $format=$input['format']??'';
    if (!in_array($format,['csv','xlsx'],true)) throw new InvalidArgumentException('Ugyldig filtype.');
    $eventId=is_string($input['event_id']??null)?trim($input['event_id']):'';
    if ($eventId==='' || !is_array($input['participant_ids']??null)
        || count($input['participant_ids'])<1 || count($input['participant_ids'])>50000
        || !is_bool($input['checked_in']??null)) {
        throw new InvalidArgumentException('Ugyldig deltakerutvalg.');
    }
    $ids=[];
    foreach($input['participant_ids'] as $id) {
        if (!is_string($id) || !preg_match('/^[a-zA-Z0-9-]{1,64}$/D',$id) || in_array($id,$ids,true)) {
            throw new InvalidArgumentException('Ugyldig deltakerutvalg.');
        }
        $ids[]=$id;
    }

    $db=getDBConnection();
    $companyId=(string)$_SESSION['company_id'];
    $actor=entityFormRequireModuleAccess($db,$companyId,'event');
    $service=new EventCheckin($db,$companyId,$actor);
    $loaded=$service->load($eventId);
    $byId=[];
    foreach($loaded['people'] as $person) $byId[(string)$person['id']]=$person;
    $people=[];
    foreach($ids as $id) {
        if (!isset($byId[$id]) || (int)$byId[$id]['checked_in']!==(int)$input['checked_in']) {
            http_response_code(409);
            throw new InvalidArgumentException('Deltakerlisten er endret. Oppdater listen og prøv igjen.');
        }
        $people[]=$byId[$id];
    }

    $rows=[];
    foreach($people as $person) {
        $price=max(0,(float)$person['ticket_price']);
        $payment=$price<=0?'Gratis':(!empty($person['has_valid_ledger'])?'Betalt':
            (!empty($person['pending_reference'])?'Pågår':'Ubetalt'));
        $rows[]=[
            'price'=>$price,
            'email'=>(string)($person['customer_email']??''),
            'phone'=>(string)($person['customer_phone']??''),
            'last_name'=>(string)($person['last_name']??''),
            'first_name'=>(string)($person['first_name']??''),
            'payment'=>$payment,
            'checked_in'=>(int)$person['checked_in']===1?'Ja':'Nei',
        ];
    }

    $slug=trim((string)preg_replace('/[^a-z0-9]+/i','-',(string)$loaded['event']['title']),'-');
    $slug=$slug!==''?strtolower($slug):'arrangement';
    $filename='deltakere-'.$slug.'-'.date('Y-m-d').'.'.$format;

    if ($format==='csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        $output=fopen('php://output','wb');
        fwrite($output,"\xEF\xBB\xBF");
        fputcsv($output,['Billettpris (NOK)','E-post','Telefon','Etternavn','Fornavn','Betalingsstatus','Innsjekket'],';','"','\\');
        foreach($rows as $row) {
            $text=[$row['email'],$row['phone'],$row['last_name'],$row['first_name'],$row['payment'],$row['checked_in']];
            foreach($text as &$value) if (preg_match('/^[\s]*[=+\-@]/u',$value)) $value="'".$value;
            unset($value);
            fputcsv($output,[
                number_format($row['price'],2,'.',''),
                $text[0],$text[1],$text[2],$text[3],$text[4],$text[5],
            ],';','"','\\');
        }
        fclose($output);
        exit;
    }

    $spreadsheet=new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet=$spreadsheet->getActiveSheet();
    $sheet->setTitle('Deltakere');
    $sheet->fromArray(['Billettpris (NOK)','E-post','Telefon','Etternavn','Fornavn','Betalingsstatus','Innsjekket'],null,'A1');
    foreach($rows as $index=>$row) {
        $line=$index+2;
        $sheet->setCellValue('A'.$line,$row['price']);
        foreach(['email'=>'B','phone'=>'C','last_name'=>'D','first_name'=>'E','payment'=>'F','checked_in'=>'G'] as $key=>$column) {
            $sheet->setCellValueExplicit($column.$line,$row[$key],\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
    }
    $lastRow=count($rows)+1;
    $sheet->getStyle('A1:G1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
    $sheet->getStyle('A1:G1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FF315443');
    if($lastRow>1) {
        $sheet->getStyle('A2:A'.$lastRow)->getNumberFormat()->setFormatCode('#,##0.00 "kr"');
        $sheet->getStyle('A1:G'.$lastRow)->getBorders()->getBottom()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR)
            ->getColor()->setARGB('FFE4EAE2');
        $sheet->setAutoFilter('A1:G'.$lastRow);
    }
    foreach(['A'=>20,'B'=>30,'C'=>18,'D'=>24,'E'=>24,'F'=>22,'G'=>16] as $column=>$width) {
        $sheet->getColumnDimension($column)->setWidth($width);
    }
    $sheet->freezePane('A2');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $writer=new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Throwable $error) {
    if (!headers_sent()) {
        if (http_response_code()===200) http_response_code($error instanceof DomainException?403:422);
        header('Content-Type: application/json; charset=utf-8');
    }
    if (!($error instanceof InvalidArgumentException) && !($error instanceof DomainException)) {
        error_log('Event participant export failed: '.get_class($error));
        http_response_code(500);
        $message='Deltakerlisten kunne ikke eksporteres.';
    } else {
        $message=$error->getMessage();
    }
    echo json_encode(['success'=>false,'message'=>$message],JSON_UNESCAPED_UNICODE);
}
