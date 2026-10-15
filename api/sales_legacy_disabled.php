<?php
declare(strict_types=1);
// Old endpoints used incompatible cart/transaction structures or bypassed the
// activated payment, CSRF, stock and online-visibility rules. No DDL or fallback.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
http_response_code(410);
echo json_encode(['success'=>false,'message'=>'Denne eldre salgsfunksjonen er erstattet. Bruk Kasse eller Produkter og lager i Sales.'],JSON_UNESCAPED_UNICODE);
exit;
