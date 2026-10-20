<?php
// Retired writer: the old full-replacement API bypassed the company's delegation boundary.
// All changes now go through company_users.php with supervisor, linked staff and CSRF checks.
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
http_response_code(410);
echo json_encode(['success' => false,
    'message' => 'Bruk «Administrer firmabrukere» i profilmenyen for å endre modultilganger.']);
