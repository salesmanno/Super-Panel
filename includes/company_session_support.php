<?php
declare(strict_types=1);

/** Safe reason codes only: never put a username, cookie or driver message here. */
final class CompanySessionFailure extends DomainException {
    public function __construct(
        public readonly string $reason,
        int $status = 401,
        public readonly bool $invalidatesSession = true
    ) {
        parent::__construct('Innloggingen kunne ikke kontrolleres.', $status);
    }
}

function companySessionLog(Throwable $error, string $phase): void {
    $reason = $error instanceof CompanySessionFailure ? $error->reason
        : ($error instanceof PDOException ? 'database_unavailable' : 'server_unavailable');
    $sqlState = $error instanceof PDOException ? (string)$error->getCode() : '';
    if (!preg_match('/^[A-Z0-9]{5}$/D', $sqlState)) $sqlState = 'none';
    error_log('Company authentication; phase='.$phase.'; reason='.$reason.'; sqlstate='.$sqlState);
}

function companySessionStart(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    // Native warnings can contain session identifiers and filesystem paths.
    if (!@session_start()) {
        throw new CompanySessionFailure('php_session_unavailable', 503, false);
    }
}

/** Verify storage before committing the new database session / issuing success. */
function companySessionPersist(): void {
    $expected = $_SESSION;
    $id = session_id();
    if (!@session_write_close()
        || !@session_start(['use_cookies'=>false, 'cache_limiter'=>'', 'read_and_close'=>true])
        || session_id() !== $id || ($_SESSION ?? null) !== $expected) {
        throw new CompanySessionFailure('php_session_not_persisted', 503, false);
    }
}

function companySessionLoginError(Throwable $error): string {
    if (!$error instanceof CompanySessionFailure) return 'session_expired';
    return match ($error->reason) {
        'missing_identity', 'missing_session_id' => 'session_missing',
        'account_changed', 'company_changed', 'employee_changed' => 'access_changed',
        'database_changed' => 'database_changed',
        default => 'session_expired',
    };
}

/** Fail closed, but do not destroy a valid login during an outage or busy lock. */
function companySessionRespondFailure(Throwable $error, ?PDO $db, string $phase, bool $json = false): never {
    companySessionLog($error, $phase);
    $invalid = $error instanceof CompanySessionFailure && $error->invalidatesSession;
    $status = $error instanceof CompanySessionFailure ? $error->getCode() : 503;
    $loginError = companySessionLoginError($error);
    header('Cache-Control: no-store');
    if ($invalid) {
        // A delayed response from an OLD request must not expire the NEW cookie.
        companyLoginClearSession($db, false);
    }
    if ($json) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        if (!$invalid) header('Retry-After: 5');
        echo json_encode([
            'success'=>false,
            'error'=>$invalid ? 'session_expired' : 'session_unavailable',
            'login_error'=>$invalid ? $loginError : null,
            'message'=>$invalid ? 'Logg inn på nytt.' : 'Innloggingen kunne ikke kontrolleres. Prøv igjen.',
        ]);
        exit;
    }
    if ($invalid) {
        header('Location: /index.php?error='.$loginError);
        exit;
    }
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Retry-After: 5');
    $message = $status === 409
        ? 'En annen forespørsel pågår. Prøv igjen om noen sekunder.'
        : 'Serveren kunne ikke kontrollere innloggingen akkurat nå. Prøv igjen om litt.';
    $retry = $phase === 'login_transition' ? '/pages/login_transition.php' : '/pages/company_dashboard.php';
    echo '<!doctype html><html lang="no"><meta charset="utf-8">'
        .'<meta name="viewport" content="width=device-width,initial-scale=1">'
        .'<title>Innlogging midlertidig utilgjengelig</title>'
        .'<main role="alert"><h1>Innloggingen kunne ikke kontrolleres</h1><p>'.$message.'</p>'
        .'<p><a href="'.$retry.'">Prøv igjen</a> · <a href="/index.php">Til innlogging</a></p></main></html>';
    exit;
}
