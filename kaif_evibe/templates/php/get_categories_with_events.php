<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Debug: Check database connection
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    // Get total event count
    $totalEventsQuery = "SELECT COUNT(*) as total FROM events WHERE status IN ('active', 'pending')";
    $totalResult = mysqli_query($conn, $totalEventsQuery);
    $totalEvents = 0;
    
    if ($totalResult) {
        $totalRow = mysqli_fetch_assoc($totalResult);
        $totalEvents = $totalRow['total'];
    }

    // Get all events for the "All Events" category
    $allEventsQuery = "SELECT e.event_id, e.title, e.description, 
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
                      WHERE e.status IN ('active', 'pending')
                      GROUP BY e.event_id
                      ORDER BY e.event_date DESC";
    
    $allEventsResult = mysqli_query($conn, $allEventsQuery);
    $allEvents = [];
    
    if ($allEventsResult) {
        while ($event = mysqli_fetch_assoc($allEventsResult)) {
            // Handle image URL
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
            
            $allEvents[] = $event;
        }
    }

    // Fetch all categories
    $categoriesQuery = "SELECT c.category_id, c.name, c.icon,
                       (SELECT COUNT(*) FROM events e WHERE e.category_id = c.category_id AND e.status IN ('active', 'pending')) as event_count
                       FROM categories c 
                       ORDER BY c.name ASC";
    
    error_log("Executing categories query: " . $categoriesQuery);
    $categoriesResult = mysqli_query($conn, $categoriesQuery);
    
    if (!$categoriesResult) {
        throw new Exception("Error fetching categories: " . mysqli_error($conn));
    }
    
    $categories = [];
    while ($category = mysqli_fetch_assoc($categoriesResult)) {
        error_log("Processing category: " . print_r($category, true));
        
        // For each category, fetch its events
        $eventsQuery = "SELECT e.event_id, e.title, e.description, 
                               DATE_FORMAT(e.event_date, '%M %d, %Y') as formatted_date,
                               e.venue, e.ticket_price as price, e.image_url,
                               u.fullname as organizer_name,
                               COALESCE(AVG(r.rating), 0) as rating,
                               COUNT(r.review_id) as rating_count
                        FROM events e
                        LEFT JOIN users u ON e.organizer_id = u.id
                        LEFT JOIN reviews r ON e.event_id = r.event_id
                        WHERE e.category_id = ? AND e.status IN ('active', 'pending')
                        GROUP BY e.event_id
                        ORDER BY e.event_date DESC
                        LIMIT 4";
        
        $stmt = mysqli_prepare($conn, $eventsQuery);
        mysqli_stmt_bind_param($stmt, "i", $category['category_id']);
        mysqli_stmt_execute($stmt);
        $eventsResult = mysqli_stmt_get_result($stmt);
        
        $events = [];
        while ($event = mysqli_fetch_assoc($eventsResult)) {
            // Handle image URL
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
            
            $events[] = $event;
        }
        
        $category['events'] = $events;
        $category['event_count'] = count($events);
        $categories[] = $category;
    }
    
    error_log("Categories found: " . count($categories));
    
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'total_events' => $totalEvents,
        'all_events' => $allEvents
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_categories_with_events.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 