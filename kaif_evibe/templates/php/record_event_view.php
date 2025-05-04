<?php
// Initialize session if not already started
session_start();

// Set headers to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if event_id was provided
if (!isset($_POST['event_id']) || empty($_POST['event_id'])) {
    echo json_encode(['success' => false, 'message' => 'Event ID is required']);
    exit;
}

// Get user ID and event ID
$user_id = $_SESSION['user_id'];
$event_id = intval($_POST['event_id']);

// Connect to database
require_once 'db_connection.php';

try {
    // Check if this view already exists to avoid duplicates
    $check_sql = "SELECT id FROM view_events WHERE user_id = :user_id AND event_id = :event_id AND view_date = CURDATE()";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $check_stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        // View already recorded today, just update the timestamp
        $update_sql = "UPDATE view_events SET viewed_at = NOW() WHERE user_id = :user_id AND event_id = :event_id AND view_date = CURDATE()";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $update_stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $update_stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'View timestamp updated', 'updated' => true]);
    } else {
        // Insert new view record
        $insert_sql = "INSERT INTO view_events (user_id, event_id, viewed_at) VALUES (:user_id, :event_id, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $insert_stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $insert_stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'View recorded successfully', 'updated' => false]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} finally {
    // PDO connection doesn't need explicit close
    $conn = null;
}
?> 