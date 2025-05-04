<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

session_start();
// Use correct path to config.php in templates/php directory
require_once __DIR__ . '/../../templates/php/config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Get total ticket count (from bookings table)
    $sql = "SELECT COUNT(*) as count FROM bookings";
    $result = $conn->query($sql);
    
    if ($result) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'count' => $row['count']
        ]);
    } else {
        throw new Exception('Error fetching ticket count: ' . $conn->error);
    }
} catch (Exception $e) {
    error_log("Error in get_ticket_count.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching ticket count: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 