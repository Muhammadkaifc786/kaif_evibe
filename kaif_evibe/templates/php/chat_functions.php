<?php
session_start();
require_once 'db_connection.php';

// Function to send a message
function sendMessage($sender_id, $receiver_id, $event_id, $message) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, event_id, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $sender_id, $receiver_id, $event_id, $message);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message_id' => $stmt->insert_id];
    } else {
        return ['success' => false, 'error' => $stmt->error];
    }
}

// Function to get messages for a user
function getMessages($user_id, $event_id = null) {
    global $conn;
    
    $query = "SELECT m.*, 
              u1.fullname as sender_name, 
              u2.fullname as receiver_name,
              e.title as event_title
              FROM messages m
              JOIN users u1 ON m.sender_id = u1.id
              JOIN users u2 ON m.receiver_id = u2.id
              JOIN events e ON m.event_id = e.event_id
              WHERE (m.sender_id = ? OR m.receiver_id = ?)";
    
    $params = [$user_id, $user_id];
    $types = "ii";
    
    if ($event_id) {
        $query .= " AND m.event_id = ?";
        $params[] = $event_id;
        $types .= "i";
    }
    
    $query .= " ORDER BY m.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    return $messages;
}

// Function to mark messages as read
function markMessagesAsRead($user_id, $event_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE messages SET is_read = TRUE 
                           WHERE receiver_id = ? AND event_id = ? AND is_read = FALSE");
    $stmt->bind_param("ii", $user_id, $event_id);
    
    return $stmt->execute();
}

// Function to get unread message count
function getUnreadMessageCount($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages 
                           WHERE receiver_id = ? AND is_read = FALSE");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}
?> 