<?php
session_start();
header('Content-Type: application/json');

$response = [
    'logged_in' => false,
    'role' => null,
    'user_id' => null
];

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $response['logged_in'] = true;
    $response['role'] = $_SESSION['role'];
    $response['user_id'] = $_SESSION['user_id'];
}

echo json_encode($response);
?> 