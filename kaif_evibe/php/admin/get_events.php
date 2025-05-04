<?php
header('Content-Type: application/json');

try {
    // Connect to the database
    $pdo = new PDO(
        'mysql:host=localhost;dbname=evibe_db',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Debug: Log connection success
    error_log('Database connection successful');

    // Get all events with organizer details
    $query = '
        SELECT 
            e.event_id as id,
            e.title,
            e.description,
            e.event_date as date_time,
            e.venue as location,
            e.image_url,
            e.status,
            e.ticket_price,
            e.total_tickets,
            e.available_tickets,
            u.fullname as organizer_name,
            c.name as category_name
        FROM events e
        LEFT JOIN users u ON e.organizer_id = u.id
        LEFT JOIN categories c ON e.category_id = c.category_id
        ORDER BY e.event_date DESC
    ';
    
    // Debug: Log the query
    error_log('Executing query: ' . $query);
    
    $stmt = $pdo->query($query);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Log number of events found
    error_log('Number of events found: ' . count($events));

    // Process image URLs
    foreach ($events as &$event) {
        if (!empty($event['image_url'])) {
            // If the image URL doesn't start with http:// or /, prepend the base path
            if (!preg_match('/^(http:\/\/|\/)/', $event['image_url'])) {
                $event['image_url'] = '/kaif_evibe/templates/' . $event['image_url'];
            }
        } else {
            // Set default image if no image URL is provided
            $event['image_url'] = '/kaif_evibe/templates/images/default-event.jpg';
        }
    }

    // Return events data
    echo json_encode([
        'success' => true,
        'events' => $events,
        'debug' => [
            'query' => $query,
            'count' => count($events)
        ]
    ]);

} catch (PDOException $e) {
    // Log the error
    error_log('Database error in get_events.php: ' . $e->getMessage());
    
    // Return error message
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'debug' => [
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ]
    ]);
}
?> 