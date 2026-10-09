<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');
if (empty($_SESSION['tenant_admin_id']) || empty($_SESSION['company_id'])) {
    http_response_code(401); echo json_encode(['success'=>false,'message'=>'Ikke autentisert']); exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405); echo json_encode(['success'=>false,'message'=>'Metoden støttes ikke']); exit;
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/tenant_notifications.php';
require_once __DIR__ . '/../includes/rental_reservation_codes.php';
try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) throw new InvalidArgumentException('Ugyldig forespørsel.');
    rentalRequireDecisionCsrf($input);
    $id = $input['id'] ?? null;
    $action = $input['action'] ?? null;
    if (!is_string($id) || $id === '' || strlen($id) > 36 || !in_array($action, ['prepare','save'], true)) {
        throw new InvalidArgumentException('Ugyldig handling.');
    }
    $db = getDBConnection();
    $company = (string)$_SESSION['company_id'];
    rentalRequireAccess($db, $company);
    $actor = tenantAuditActor($db, $company);
    $work = static function() use ($db, $company, $id, $action, $input, $actor): array {
        $db->beginTransaction();
        if ($action === 'prepare') {
            $proposal = rentalCodeProposal(rentalCodeContext($db, $company, $id));
            $proposal['actor_id'] = $actor['id'];
            $db->commit();
            $_SESSION['rental_code_proposals'] = array_filter($_SESSION['rental_code_proposals'] ?? [],
                static fn($p) => ($p['expires'] ?? 0) >= time());
            $_SESSION['rental_code_proposals'] = array_slice($_SESSION['rental_code_proposals'], -19, null, true);
            $token = bin2hex(random_bytes(24));
            $_SESSION['rental_code_proposals'][$token] = $proposal;
            return ['success'=>true, 'token'=>$token, 'code'=>$proposal['code'],
                'assigned'=>$proposal['assigned'], 'matches'=>$proposal['matches']];
        }
        $token = $input['token'] ?? null;
        $physical = $input['physical_changed'] ?? null;
        if (!is_string($token) || !is_bool($physical)) throw new InvalidArgumentException('Bekreft handlingen først.');
        $proposal = $_SESSION['rental_code_proposals'][$token] ?? [];
        rentalSaveCodeProposal($db, $company, $id, $proposal, $physical, $actor);
        $db->commit();
        unset($_SESSION['rental_code_proposals'][$token]);
        return ['success'=>true, 'message'=>$physical ? 'Koden er lagret og fysisk bytte bekreftet.' : 'Koden er tildelt reservasjonen.'];
    };
    $result = $db instanceof AuditedPDO ? $db->withExplicitAudit($work) : $work();
    echo json_encode($result);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    $safe = $e instanceof TenantAuditException || $e instanceof InvalidArgumentException || $e instanceof DomainException;
    http_response_code($e instanceof TenantAuditException ? 403 : ($e instanceof DomainException ? ($e->getCode() ?: 409) : ($safe ? 400 : 500)));
    if (!$safe) error_log('Rental reservation code action failed'); // Never log code values/SQL.
    echo json_encode(['success'=>false,'message'=>$safe ? $e->getMessage() : 'Kunne ikke lagre koden. Prøv igjen.']);
}
