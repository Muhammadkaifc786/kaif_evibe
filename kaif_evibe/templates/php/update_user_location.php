<?php
session_start();
require_once 'db_connection.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Peshawar coordinates
$peshawar_lat = 34.012385;
$peshawar_lng = 71.578746;

try {
    // Update user's location
    $query = "UPDATE users SET latitude = ?, longitude = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([$peshawar_lat, $peshawar_lng, $user_id]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Location updated to Peshawar coordinates',
            'latitude' => $peshawar_lat,
            'longitude' => $peshawar_lng
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update location'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 