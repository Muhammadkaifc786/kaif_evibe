<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Fetch favorite events
    $query = "SELECT e.event_id, e.title, e.description, 
                     DATE_FORMAT(e.event_date, '%M %d, %Y') as formatted_date,
                     e.venue, e.ticket_price as price, e.image_url,
                     c.name as category,
                     u.fullname as organizer_name,
                     COALESCE(AVG(r.rating), 0) as rating,
                     COUNT(r.review_id) as rating_count
              FROM favorites f
              JOIN events e ON f.event_id = e.event_id
              LEFT JOIN categories c ON e.category_id = c.category_id
              LEFT JOIN users u ON e.organizer_id = u.id
              LEFT JOIN reviews r ON e.event_id = r.event_id
              WHERE f.user_id = ?
              GROUP BY e.event_id
              ORDER BY e.event_date ASC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        throw new Exception("Error fetching favorites: " . mysqli_error($conn));
    }
    
    $events = [];
    while ($event = mysqli_fetch_assoc($result)) {
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
        $event['rating'] = round((float)$event['rating'], 1);
        $events[] = $event;
    }
    
    echo json_encode([
        'success' => true,
        'events' => $events,
        'count' => count($events)
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_favorites.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 