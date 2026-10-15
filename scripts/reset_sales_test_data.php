<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$options = getopt('', ['help', 'company:', 'apply', 'expected:', 'confirm:', 'offline-confirmed', 'full-backup-confirmed', 'backup-dir:']);
if (isset($options['help']) || empty($options['company'])) {
    echo <<<'HELP'
Sales TESTDATA — separat OFFLINE vedlikehold, aldri for reelle salg.

Forhåndskontroll (kun lesing):
  php scripts/reset_sales_test_data.php --company=UUID

Nullstilling krever at HELE appen og alle database-/betalings-/e-postjobber er
stoppet, full databasebackup og en privat mappe UTENFOR webroten med chmod 0700:
  php scripts/reset_sales_test_data.php --company=UUID --apply \
    --expected=FINGERPRINT --confirm="RESET TEST SALES UUID" \
    --offline-confirmed --full-backup-confirmed --backup-dir=/privat/backupmappe

STOPP: journal-/kvitteringssperrer fjernes midlertidig på databasenivå.
Verktøyet tar en ekstra rad-/skjemabackup før sletting og gjenoppretter sperrene.
Ingen produkter, feltoppsett, andre moduler eller andre bedrifter slettes.
Ingen Vipps-kall/refusjoner. Ventende/uklare/eldre ordre stopper operasjonen.
Les docs/sales-test-reset.md før bruk. Ikke last backup inn i nettleseren.

HELP;
    exit(isset($options['help']) ? 0 : 1);
}

require_once __DIR__.'/lib/SalesTestReset.php';
try {
    if (!is_string($options['company']) || !preg_match('/^[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}$/D', $options['company'])) {
        throw new RuntimeException('Oppgi en gyldig company_id med små bokstaver. Ingen databaseforbindelse åpnet.');
    }
    if (isset($options['apply']) && (!isset($options['offline-confirmed']) ||
        !isset($options['full-backup-confirmed']) || empty($options['expected']) || empty($options['confirm']) || empty($options['backup-dir']))) {
        throw new RuntimeException('Mangler bekreftelser/backupmappe. Bruk --help. Ingen endringer utført.');
    }
    require_once __DIR__.'/../config/database.php';
    $reset = new SalesTestReset(getDBConnection(), (string)$options['company']);
    $result = isset($options['apply'])
        ? $reset->apply((string)$options['expected'], (string)$options['confirm'],
            isset($options['offline-confirmed']), (string)$options['backup-dir'])
        : SalesTestReset::report($reset->snapshot());
    echo json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
} catch (Throwable $e) {
    // PDO errors may include schema details; do not print connection credentials or row data.
    fwrite(STDERR, ($e instanceof PDOException
        ? 'Databasekontroll/nullstilling feilet. Hold systemet offline hvis --apply ble brukt; kontroller privat backup/status og slettesperrene.'
        : $e->getMessage()).PHP_EOL);
    exit(1);
}
