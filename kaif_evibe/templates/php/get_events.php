<?php
require_once 'db_connection.php';
session_start();

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    
    if ($event_id <= 0) {
        throw new Exception('Invalid event ID');
    }

    // Get event details
    $stmt = $conn->prepare("
        SELECT e.*, c.name as category_name 
        FROM events e
        LEFT JOIN categories c ON e.category_id = c.category_id
        WHERE e.event_id = ? AND e.organizer_id = ?
    ");
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("ii", $event_id, $_SESSION['user_id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Event not found or unauthorized');
    }
    
    $event = $result->fetch_assoc();
    
    // Format date for input field
    $event['event_date'] = date('Y-m-d\TH:i', strtotime($event['event_date']));
    
    echo json_encode([
        'success' => true,
        'event' => $event
    ]);

} catch (Exception $e) {
    error_log('Error in get_events.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 