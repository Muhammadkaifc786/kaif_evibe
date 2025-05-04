<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Get event ID from request (support both GET and POST)
    $event_id = $_POST['event_id'] ?? $_GET['event_id'] ?? null;

    if (!$event_id) {
        throw new Exception('Event ID is required');
    }

    // Get event details
    $query = "SELECT e.*, u.fullname as organizer_name, 
              DATE_FORMAT(e.event_date, '%M %d, %Y') as formatted_date,
              c.name as category_name
              FROM events e 
              JOIN users u ON e.organizer_id = u.id 
              LEFT JOIN categories c ON e.category_id = c.category_id
              WHERE e.event_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $event = mysqli_fetch_assoc($result);

    if (!$event) {
        throw new Exception('Event not found');
    }

    // Check if user is an organizer
    $is_organizer = isset($_SESSION['is_organizer']) && $_SESSION['is_organizer'] == 1;

    // Format the response
    $response = [
        'success' => true,
        'is_organizer' => $is_organizer,
        'event' => [
            'event_id' => $event['event_id'],
            'title' => $event['title'],
            'description' => $event['description'],
            'image_url' => $event['image_url'],
            'venue' => $event['venue'],
            'event_date' => $event['event_date'],
            'formatted_date' => $event['formatted_date'],
            'event_time' => isset($event['event_time']) ? $event['event_time'] : null,
            'category_id' => $event['category_id'],
            'category_name' => $event['category_name'],
            'ticket_price' => $event['ticket_price'],
            'total_tickets' => $event['total_tickets'],
            'available_tickets' => $event['available_tickets'],
            'status' => $event['status'],
            'organizer_name' => $event['organizer_name']
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in get_event_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 