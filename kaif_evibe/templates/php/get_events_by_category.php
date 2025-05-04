<?php
require_once 'config.php';

// Set header to JSON
header('Content-Type: application/json');

try {
    // Get category ID from request
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
    
    // Debug log
    error_log("Category ID received: " . $category_id);
    error_log("GET parameters: " . print_r($_GET, true));
    
    // If category_id is 0, return all events (no status filter)
    if ($category_id === 0) {
        $sql = "SELECT e.event_id, e.title, e.description, e.event_date, e.venue, 
                       e.ticket_price as price, e.image_url,
                       c.name as category_name,
                       u.fullname as organizer_name,
                       COUNT(DISTINCT r.review_id) as rating_count,
                       COALESCE(AVG(r.rating), 0) as rating
                FROM events e
                LEFT JOIN categories c ON e.category_id = c.category_id
                LEFT JOIN users u ON e.organizer_id = u.id
                LEFT JOIN event_reviews r ON e.event_id = r.event_id
                GROUP BY e.event_id
                ORDER BY e.event_date ASC";
                
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }
    } else {
        // Query to get events by specific category (no status filter)
        $sql = "SELECT e.event_id, e.title, e.description, e.event_date, e.venue, 
                       e.ticket_price as price, e.image_url,
                       c.name as category_name,
                       u.fullname as organizer_name,
                       COUNT(DISTINCT r.review_id) as rating_count,
                       COALESCE(AVG(r.rating), 0) as rating
                FROM events e
                LEFT JOIN categories c ON e.category_id = c.category_id
                LEFT JOIN users u ON e.organizer_id = u.id
                LEFT JOIN event_reviews r ON e.event_id = r.event_id
                WHERE e.category_id = ?
                GROUP BY e.event_id
                ORDER BY e.event_date ASC";
                
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }
        
        $stmt->bind_param("i", $category_id);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Error executing statement: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $events = [];
    
    while ($row = $result->fetch_assoc()) {
        // Format the date
        $row['formatted_date'] = date('F j, Y', strtotime($row['event_date']));
        $events[] = $row;
    }
    
    // Debug log
    error_log("Number of events found: " . count($events));
    
    echo json_encode([
        "success" => true,
        "events" => $events
    ]);
    
} catch (Exception $e) {
    // Debug log
    error_log("Error in get_events_by_category.php: " . $e->getMessage());
    
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

// Close connection
$conn->close();
?> 