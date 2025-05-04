<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

function sendJsonResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

if (!isset($_GET['search'])) {
    sendJsonResponse(false, 'Search query is required');
}

$searchQuery = '%' . $_GET['search'] . '%';


try {
    $query = "SELECT e.*, c.name as category_name 
              FROM events e 
              JOIN categories c ON e.category_id = c.category_id 
              WHERE e.title LIKE ? AND e.status = 'approved'
              ORDER BY e.event_date ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$searchQuery]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the events data
    $formattedEvents = array_map(function($event) {
        return [
        'event_id' => $events['event_id'],
        'status' => $events['status'],
        'title' => $events['title'],
        
        'category' => $events['category_name'],
        'event_date' => $events['event_date'],
        'formatted_date' => $formattedDate,
        'venue' => $events['venue'],
        'ticket_price' => $events['ticket_price'],
        'formatted_price' => $formattedPrice,
        'total_tickets' => $events['total_tickets'],
        'available_tickets' => $events['available_tickets'],
        'image_url' => $events['image_url'],
        'google_maps_link' => $events['google_maps_link']s
        ];
    }, $events);
    
    sendJsonResponse(true, 'Events found', $formattedEvents);
    
} catch (Exception $e) {
    error_log("Error in search_events.php: " . $e->getMessage());
    sendJsonResponse(false, 'Error searching events');
}
?> 