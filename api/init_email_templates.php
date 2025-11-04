<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$companyId = $_SESSION['company_id'];

try {
    $db = getDBConnection();
    
    // Standard email templates
    $templates = [
        [
            'template_key' => 'sale_receipt',
            'name' => 'Salgskvittering',
            'subject' => 'Kvittering for kjøp hos {{company_name}}',
            'body' => '
                <h2>Takk for ditt kjøp!</h2>
                <p>Hei {{customer_name}},</p>
                <p>Vi bekrefter mottak av din betaling på <strong>{{amount}} NOK</strong>.</p>
                <p><strong>Ordre-ID:</strong> {{order_id}}<br>
                <strong>Betalingsmetode:</strong> {{payment_method}}<br>
                <strong>Dato:</strong> {{date}}</p>
                <p>Med vennlig hilsen,<br>{{company_name}}</p>
            '
        ],
        [
            'template_key' => 'event_registration',
            'name' => 'Arrangementspåmelding',
            'subject' => 'Bekreftelse: Påmelding til {{event_name}}',
            'body' => '
                <h2>Velkommen til {{event_name}}!</h2>
                <p>Hei {{customer_name}},</p>
                <p>Vi bekrefter din påmelding til <strong>{{event_name}}</strong>.</p>
                <p><strong>Dato:</strong> {{event_date}}<br>
                <strong>Tid:</strong> {{event_time}}<br>
                <strong>Sted:</strong> {{event_location}}</p>
                <p>Vi gleder oss til å se deg!</p>
                <p>Med vennlig hilsen,<br>{{company_name}}</p>
            '
        ],
        [
            'template_key' => 'membership_welcome',
            'name' => 'Velkommen som medlem',
            'subject' => 'Velkommen som medlem i {{company_name}}!',
            'body' => '
                <h2>Velkommen som medlem!</h2>
                <p>Hei {{customer_name}},</p>
                <p>Vi ønsker deg velkommen som medlem i {{company_name}}!</p>
                <p><strong>Medlemskap:</strong> {{membership_type}}<br>
                <strong>Gyldig fra:</strong> {{start_date}}<br>
                <strong>Gyldig til:</strong> {{end_date}}</p>
                <p>Som medlem får du tilgang til alle våre tjenester og fordeler.</p>
                <p>Med vennlig hilsen,<br>{{company_name}}</p>
            '
        ],
        [
            'template_key' => 'rental_confirmation',
            'name' => 'Utleiebekreftelse',
            'subject' => 'Bekreftelse: Utleie av {{item_name}}',
            'body' => '
                <h2>Utleiebekreftelse</h2>
                <p>Hei {{customer_name}},</p>
                <p>Vi bekrefter utleie av <strong>{{item_name}}</strong>.</p>
                <p><strong>Fra:</strong> {{start_date}}<br>
                <strong>Til:</strong> {{end_date}}<br>
                <strong>Pris:</strong> {{amount}} NOK</p>
                <p>Vennligst hent {{item_name}} på avtalte tid.</p>
                <p>Med vennlig hilsen,<br>{{company_name}}</p>
            '
        ],
        [
            'template_key' => 'service_booking',
            'name' => 'Servicebekreftelse',
            'subject' => 'Bekreftelse: {{service_name}}',
            'body' => '
                <h2>Booking bekreftet</h2>
                <p>Hei {{customer_name}},</p>
                <p>Vi bekrefter din booking av <strong>{{service_name}}</strong>.</p>
                <p><strong>Dato:</strong> {{date}}<br>
                <strong>Tid:</strong> {{time}}<br>
                <strong>Varighet:</strong> {{duration}} minutter</p>
                <p>Vi ser frem til å betjene deg!</p>
                <p>Med vennlig hilsen,<br>{{company_name}}</p>
            '
        ]
    ];
    
    $db->beginTransaction();
    $inserted = 0;
    
    foreach ($templates as $template) {
        // Check if template already exists
        $stmt = $db->prepare("SELECT id FROM email_templates WHERE company_id = ? AND template_key = ?");
        $stmt->execute([$companyId, $template['template_key']]);
        
        if (!$stmt->fetch()) {
            $id = bin2hex(random_bytes(18));
            $stmt = $db->prepare("
                INSERT INTO email_templates (
                    id, company_id, template_key, name, subject_template, body_template, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, TRUE)
            ");
            $stmt->execute([
                $id,
                $companyId,
                $template['template_key'],
                $template['name'],
                $template['subject'],
                $template['body']
            ]);
            $inserted++;
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "$inserted nye e-postmaler opprettet"
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Init email templates error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Serverfeil: ' . $e->getMessage()]);
}
