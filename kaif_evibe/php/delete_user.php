<?php
// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/delete_user_errors.log');

// Include session configuration
require_once 'session_config.php';
require_once 'config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$userId = (int)$data['id'];

try {
    // First check if user exists
    $checkSql = "SELECT id FROM users WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Prepare SQL statement to delete user
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete user: " . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found or already deleted']);
        exit;
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Error in delete_user.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete user: ' . $e->getMessage()
    ]);
}
?> 