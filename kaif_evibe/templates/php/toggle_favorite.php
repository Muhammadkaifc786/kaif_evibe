<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

if (!isset($_POST['event_id'])) {
    echo json_encode(['success' => false, 'message' => 'Event ID not provided']);
    exit;
}

$user_id = $_SESSION['user_id'];
$event_id = $_POST['event_id'];

try {
    // Check if the favorite already exists
    $check_sql = "SELECT * FROM favorites WHERE user_id = :user_id AND event_id = :event_id";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute(['user_id' => $user_id, 'event_id' => $event_id]);
    $existing_favorite = $check_stmt->fetch();

    if ($existing_favorite) {
        // Remove from favorites
        $delete_sql = "DELETE FROM favorites WHERE user_id = :user_id AND event_id = :event_id";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->execute(['user_id' => $user_id, 'event_id' => $event_id]);
        echo json_encode(['success' => true, 'is_favorite' => false]);
    } else {
        // Add to favorites
        $insert_sql = "INSERT INTO favorites (user_id, event_id) VALUES (:user_id, :event_id)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->execute(['user_id' => $user_id, 'event_id' => $event_id]);
        echo json_encode(['success' => true, 'is_favorite' => true]);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn = null;
?> 