<?php
// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/get_user_errors.log');

// Include session configuration
require_once 'session_config.php';
require_once 'config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get user ID from request
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

try {
    // Prepare SQL statement to fetch user
    $sql = "SELECT id, fullname, email, contact_number, address, role, created_at FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_user.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch user',
        'message' => $e->getMessage()
    ]);
}
?> 