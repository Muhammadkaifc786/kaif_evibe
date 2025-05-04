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
    // Check if the event is favorited
    $sql = "SELECT COUNT(*) as is_favorite 
            FROM favorites 
            WHERE user_id = :user_id AND event_id = :event_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $user_id, 'event_id' => $event_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'is_favorite' => $result['is_favorite'] > 0
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn = null;
?> 