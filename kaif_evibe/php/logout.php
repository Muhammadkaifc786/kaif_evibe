<?php
// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/session_errors.log');

// Include session configuration
require_once 'session_config.php';

// Set headers
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log the logout attempt
error_log("Logout attempt for user: " . ($_SESSION['user_email'] ?? 'unknown'));

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/kaif_evibe/');
}

// Destroy the session
session_destroy();

// Log successful logout
error_log("User logged out successfully");

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Logged out successfully'
]); 