<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Function to get event details by ID
function getEventDetails($conn, $event_id) {
    $sql = "SELECT e.*, u.name as organizer_name, u.email as organizer_email 
            FROM events e 
            JOIN users u ON e.organizer_id = u.id 
            WHERE e.id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Function to get event statistics
function getEventStatistics($conn, $event_id) {
    $sql = "SELECT 
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_tickets,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_tickets,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_tickets,
                SUM(CASE WHEN status = 'approved' THEN quantity ELSE 0 END) as total_sold
            FROM ticket_requests 
            WHERE event_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Function to get event reviews
function getEventReviews($conn, $event_id) {
    $sql = "SELECT r.*, u.name as user_name 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.event_id = ? 
            ORDER BY r.created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $reviews = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $reviews[] = $row;
    }
    return $reviews;
}

// Handle API requests
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'get_details':
                if (isset($_GET['event_id'])) {
                    $event_id = $_GET['event_id'];
                    $event = getEventDetails($conn, $event_id);
                    if ($event) {
                        echo json_encode($event);
                    } else {
                        echo json_encode(["error" => "Event not found"]);
                    }
                } else {
                    echo json_encode(["error" => "Event ID not provided"]);
                }
                break;
                
            case 'get_statistics':
                if (isset($_GET['event_id'])) {
                    $event_id = $_GET['event_id'];
                    $stats = getEventStatistics($conn, $event_id);
                    echo json_encode($stats);
                } else {
                    echo json_encode(["error" => "Event ID not provided"]);
                }
                break;
                
            case 'get_reviews':
                if (isset($_GET['event_id'])) {
                    $event_id = $_GET['event_id'];
                    $reviews = getEventReviews($conn, $event_id);
                    echo json_encode($reviews);
                } else {
                    echo json_encode(["error" => "Event ID not provided"]);
                }
                break;
                
            default:
                echo json_encode(["error" => "Invalid action"]);
        }
    } else {
        echo json_encode(["error" => "No action specified"]);
    }
} else {
    echo json_encode(["error" => "Invalid request method"]);
}
?> if (isset($_GET['id'])) {
    $event_id = $_GET['id'];
    
    $sql = "SELECT * FROM events WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($event = $result->fetch_assoc()) {
        header('Content-Type: application/json');
        echo json_encode($event);
    } else {
        http_response_code(404);
        echo 'Event not found';
    }
} else {
    http_response_code(400);
    echo 'Event ID not provided';
}
?> 
