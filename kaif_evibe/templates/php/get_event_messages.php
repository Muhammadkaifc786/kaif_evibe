<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User not logged in");
    }

    // Get event ID from request
    $event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
    if (!$event_id) {
        throw new Exception("Event ID is required");
    }

    // Query to get messages for the event
    $sql = "SELECT 
                m.message_id,
                m.message,
                m.created_at,
                m.sender_id,
                u.fullname as sender_name,
                u.role as sender_role,
                CASE 
                    WHEN m.sender_id = ? THEN 1 
                    ELSE 0 
                END as is_sender
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.event_id = ?
            ORDER BY m.created_at ASC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        throw new Exception("Error fetching messages: " . mysqli_error($conn));
    }

    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = [
            'message_id' => $row['message_id'],
            'message' => $row['message'],
            'created_at' => $row['created_at'],
            'sender_id' => $row['sender_id'],
            'sender_name' => $row['sender_name'],
            'is_sender' => (bool)$row['is_sender'],
            'sender_role' => $row['sender_role'],
        ];
    }

    // Mark messages as read
    $update_sql = "UPDATE messages 
                   SET is_read = 1 
                   WHERE event_id = ? 
                   AND sender_id != ? 
                   AND is_read = 0";
    
    $update_stmt = mysqli_prepare($conn, $update_sql);
    if ($update_stmt) {
        mysqli_stmt_bind_param($update_stmt, "ii", $event_id, $_SESSION['user_id']);
        mysqli_stmt_execute($update_stmt);
    }

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);

} catch (Exception $e) {
    error_log("Error in get_event_messages.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 