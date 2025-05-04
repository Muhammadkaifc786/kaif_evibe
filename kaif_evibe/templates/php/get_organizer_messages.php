<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in and is an organizer
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_organizer']) || $_SESSION['is_organizer'] != 1) {
        throw new Exception('Unauthorized access');
    }

    // Get messages for the organizer
    $query = "SELECT m.*, 
              u.fullname as sender_name,
              e.title as event_title
              FROM messages m
              JOIN users u ON m.sender_id = u.id
              JOIN events e ON m.event_id = e.event_id
              WHERE m.receiver_id = ?
              ORDER BY m.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = [
            'message_id' => $row['message_id'],
            'event_id' => $row['event_id'],
            'sender_id' => $row['sender_id'],
            'sender_name' => $row['sender_name'],
            'event_title' => $row['event_title'],
            'message' => $row['message'],
            'created_at' => $row['created_at'],
            'is_read' => $row['is_read']
        ];
    }

    // Mark messages as read
    $updateQuery = "UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0";
    $updateStmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($updateStmt);

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);

} catch (Exception $e) {
    error_log("Error in get_organizer_messages.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 