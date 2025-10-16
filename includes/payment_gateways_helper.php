<?php
function ensurePaymentGatewaysExist($companyId, $createdBy = null) {
    $db = getDBConnection();
    
    // Sjekk om selskapet har gateways
    $stmt = $db->prepare("SELECT COUNT(*) FROM economy_payment_gateways WHERE company_id = ?");
    $stmt->execute([$companyId]);
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Opprett standard gateways med Kontant aktivert
        $stmt = $db->prepare("
            INSERT INTO economy_payment_gateways (company_id, gateway_name, display_name, is_active, test_mode, created_by) 
            VALUES 
                (?, 'cash', 'Kontant', TRUE, FALSE, ?),
                (?, 'stripe', 'Stripe', FALSE, TRUE, ?),
                (?, 'vipps', 'Vipps', FALSE, TRUE, ?),
                (?, 'klarna', 'Klarna', FALSE, TRUE, ?),
                (?, 'paypal', 'PayPal', FALSE, TRUE, ?)
        ");
        $stmt->execute([
            $companyId, $createdBy,
            $companyId, $createdBy,
            $companyId, $createdBy,
            $companyId, $createdBy,
            $companyId, $createdBy
        ]);
    }
}
