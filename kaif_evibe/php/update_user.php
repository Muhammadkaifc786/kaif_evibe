<?php
// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/update_user_errors.log');

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

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

// Validate required fields
$required_fields = ['id', 'fullname', 'email', 'role'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Prepare SQL statement to update user
    $sql = "UPDATE users SET 
            fullname = ?, 
            email = ?, 
            contact_number = ?, 
            address = ?, 
            role = ? 
            WHERE id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssi",
        $data['fullname'],
        $data['email'],
        $data['contact_number'],
        $data['address'],
        $data['role'],
        $data['id']
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update user: " . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found or no changes made']);
        exit;
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Error in update_user.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to update user',
        'message' => $e->getMessage()
    ]);
}
?> 