<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Use correct path to config.php in templates/php/admin directory
require_once __DIR__ . '/config.php';

try {
    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Check if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows == 0) {
        throw new Exception('Users table does not exist');
    }

    // Get total user count
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if (!$result) {
        throw new Exception('Error counting users: ' . $conn->error);
    }

    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'user_count' => $row['count']
    ]);

} catch (Exception $e) {
    error_log("Error in test_db.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 