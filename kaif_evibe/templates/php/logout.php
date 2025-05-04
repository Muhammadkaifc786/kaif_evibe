<?php
session_start();
header('Content-Type: application/json');

// Store user info before destroying session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Return success response with user info
echo json_encode([
    "success" => true,
    "message" => "Logged out successfully",
    "user_id" => $user_id,
    "role" => $role
]);
?> 