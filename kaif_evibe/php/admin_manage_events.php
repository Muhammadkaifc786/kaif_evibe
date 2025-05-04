<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../templates/html/login.html');
    exit();
}

// Get all pending events
function getPendingEvents($conn) {
    $sql = "SELECT e.*, u.fullname as organizer_name, c.name as category_name 
            FROM events e 
            JOIN users u ON e.organizer_id = u.id 
            JOIN categories c ON e.category_id = c.category_id 
            WHERE e.status = 'pending' 
            ORDER BY e.created_at DESC";
    
    $result = $conn->query($sql);
    $events = [];
    
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    return $events;
}

// Get all events (for admin panel)
function getAllEvents($conn) {
    $sql = "SELECT e.*, u.fullname as organizer_name, c.name as category_name 
            FROM events e 
            JOIN users u ON e.organizer_id = u.id 
            JOIN categories c ON e.category_id = c.category_id 
            ORDER BY e.created_at DESC";
    
    $result = $conn->query($sql);
    $events = [];
    
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    return $events;
}

// Handle event approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $event_id = $_POST['event_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve' || $action === 'reject') {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        
        try {
            $sql = "UPDATE events SET status = ? WHERE event_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $status, $event_id);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Event ' . $action . 'd successfully'
                ]);
            } else {
                throw new Exception("Error " . $action . "ing event");
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
// Get events for display
else {
    $events = isset($_GET['view']) && $_GET['view'] === 'all' ? 
              getAllEvents($conn) : getPendingEvents($conn);
    
    echo json_encode([
        'success' => true,
        'events' => $events
    ]);
}
?> 