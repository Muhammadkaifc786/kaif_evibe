<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Fetch events directly from the events table
    $query = "SELECT e.event_id, e.title, e.description, 
                     DATE_FORMAT(e.event_date, '%M %d, %Y') as formatted_date,
                     e.venue, e.ticket_price as price, e.image_url,
                     c.name as category,
                     u.fullname as organizer_name,
                     COALESCE(AVG(r.rating), 0) as rating,
                     COUNT(r.review_id) as rating_count
              FROM events e
              LEFT JOIN categories c ON e.category_id = c.category_id
              LEFT JOIN users u ON e.organizer_id = u.id
              LEFT JOIN reviews r ON e.event_id = r.event_id
              GROUP BY e.event_id
              ORDER BY e.event_date DESC
              LIMIT 5";
    
    error_log("Executing carousel query: " . $query);
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception("Error fetching events: " . mysqli_error($conn));
    }
    
    $events = [];
    while ($event = mysqli_fetch_assoc($result)) {
        error_log("Processing event for carousel: " . print_r($event, true));
        // Handle image URL exactly as in get_all_events.php
        $imageUrl = $event['image_url'];
        if ($imageUrl && !str_starts_with($imageUrl, '/kaif_evibe/')) {
            if (!str_starts_with($imageUrl, '/')) {
                $imageUrl = '/kaif_evibe/templates/' . $imageUrl;
            } else {
                $imageUrl = '/kaif_evibe' . $imageUrl;
            }
        }
        if (!$imageUrl) {
            $imageUrl = '/kaif_evibe/templates/images/default-event.jpg';
        }
        $event['image_url'] = $imageUrl;
        
        // Add a description field for the carousel
        if (!isset($event['description']) || empty($event['description'])) {
            $event['description'] = "Date: {$event['formatted_date']} | Venue: {$event['venue']}";
        }
        
        $events[] = $event;
    }
    
    error_log("Total events found for carousel: " . count($events));
    
    echo json_encode([
        'success' => true,
        'images' => $events
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_carousel_images.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 