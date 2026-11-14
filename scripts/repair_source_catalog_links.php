#!/usr/bin/env php
<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../includes/source_catalog_links.php';
$options = getopt('', ['company-id:', 'apply', 'help']);
if (isset($options['help']) || empty($options['company-id'])) {
    fwrite(STDERR, "Bruk: php scripts/repair_source_catalog_links.php --company-id=UUID [--apply]\nUten --apply foretas bare lesekontroll. Ingen tabeller opprettes.\n");
    exit(isset($options['help']) ? 0 : 2);
}
define('RUNNING_FROM_CLI', true);
require_once __DIR__.'/../config/database.php';
try {
    $result = repairSourceCatalogLinks(getDBConnection(), (string)$options['company-id'], isset($options['apply']));
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
} catch (Throwable $e) {
    error_log('Catalogue repair: '.$e->getMessage());
    fwrite(STDERR, "Etterkobling mislyktes. Se serverlogg. Ingen delvis etterkobling er beholdt.\n");
    exit(1);
}
