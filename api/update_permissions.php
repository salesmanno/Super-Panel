<?php
// No self-service escalation via the legacy permissions dialog.
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
http_response_code(410);
$message = 'Modultilganger administreres av firmaveileder under «Administrer firmabrukere» i profilmenyen.';
echo json_encode(['success' => false, 'message' => $message, 'error' => $message]);
