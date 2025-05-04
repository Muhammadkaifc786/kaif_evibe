<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ob_start(); // Start output buffering

session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Debug: Check if database connection is working
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    // Query to get featured events
    $sql = "SELECT 
                e.event_id,
                e.title,
                e.description,
                DATE_FORMAT(e.event_date, '%M %d, %Y') as formatted_date,
                e.venue,
                e.ticket_price as price,
                e.image_url,
                c.name as category
            FROM events e
            LEFT JOIN categories c ON e.category_id = c.category_id
            WHERE e.status IN ('active', 'pending')
            ORDER BY e.event_date DESC
            LIMIT 6";

    error_log("Executing SQL query: " . $sql);
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        throw new Exception("Error fetching events: " . mysqli_error($conn));
    }
    
    $events = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Debug: Print each event's data
        error_log("Event found: " . print_r($row, true));
        
        $events[] = [
            'event_id' => $row['event_id'],
            'title' => $row['title'] ?? 'Untitled Event',
            'description' => $row['description'] ?? 'No description available',
            'formatted_date' => $row['formatted_date'] ?? 'Date not set',
            'venue' => $row['venue'] ?? 'Venue not specified',
            'price' => number_format($row['price'] ?? 0, 2),
            'image_url' => $row['image_url'] ?? '/kaif_evibe/templates/images/default-event.jpg',
            'category' => $row['category'] ?? 'General',
            'organizer_name' => 'Event Organizer',
            'rating' => 0,
            'rating_count' => 0
        ];
    }

    // Debug: Check if any events were found
    if (empty($events)) {
        error_log("No events found in the database");
        // Add a sample event for testing
        $events[] = [
            'event_id' => 1,
            'title' => 'Sample Event',
            'description' => 'This is a sample event for testing',
            'formatted_date' => date('F d, Y'),
            'venue' => 'Test Venue',
            'price' => '10.00',
            'image_url' => '/kaif_evibe/templates/images/default-event.jpg',
            'category' => 'General',
            'organizer_name' => 'Test Organizer',
            'rating' => 0,
            'rating_count' => 0
        ];
    }

    $response = [
        'success' => true,
        'events' => $events
    ];
    
    error_log("Sending response: " . print_r($response, true));
    
    // Clear any previous output
    ob_clean();
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in get_featured_events.php: " . $e->getMessage());
    // Clear any previous output
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    ob_end_flush();
}
?> 