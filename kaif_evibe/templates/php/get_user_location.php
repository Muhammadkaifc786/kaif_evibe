<?php
require_once 'db_connection.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');

try {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'User not logged in'
        ]);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Get user's location
    $query = "SELECT latitude, longitude FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['latitude'] && $user['longitude']) {
        echo json_encode([
            'success' => true,
            'latitude' => $user['latitude'],
            'longitude' => $user['longitude']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'User location not found'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 