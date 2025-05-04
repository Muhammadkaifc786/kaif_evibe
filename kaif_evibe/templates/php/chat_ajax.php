<?php
require_once 'config.php';

// Set header to JSON
header('Content-Type: application/json');

try {
    // Get the action from POST data
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_messages':
            // Get recent messages
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
            
            $sql = "SELECT m.*, 
                           e.title as event_title,
                           u.fullname as sender_name
                    FROM messages m
                    LEFT JOIN events e ON m.event_id = e.event_id
                    LEFT JOIN users u ON m.sender_id = u.id
                    WHERE m.receiver_id = ? OR m.is_broadcast = 1
                    ORDER BY m.created_at DESC
                    LIMIT ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $_SESSION['user_id'], $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'messages' => $messages
            ]);
            break;
            
        case 'get_unread_count':
            // Get unread message count
            $sql = "SELECT COUNT(*) as count 
                    FROM messages 
                    WHERE (receiver_id = ? OR is_broadcast = 1) AND is_read = 0";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'count' => $row['count']
            ]);
            break;
            
        default:
            throw new Exception("Invalid action");
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?> 