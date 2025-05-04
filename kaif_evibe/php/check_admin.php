<?php
require_once __DIR__ . '/session_config.php';
header('Content-Type: application/json');

// Check if user is logged in as admin
echo json_encode([
    'success' => isAdmin(),
    'role' => getUserRole(),
    'user_id' => getUserId()
]);
?>