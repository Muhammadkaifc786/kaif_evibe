<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    $sql = "
        SELECT 
            e.event_id,
            e.organizer_id,
            u.fullname AS organizer_name,
            IFNULL(r.rating, 0) AS rating,
            IFNULL(r.review_count, 0) AS review_count,
            e.total_tickets,
            e.available_tickets,
            e.total_tickets - e.available_tickets AS tickets_sold,
            e.title,
            e.description,
            DATE_FORMAT(e.event_date, '%M %d, %Y') AS formatted_date,
            e.venue,
            e.ticket_price AS price,
            e.image_url,
            c.name AS category
        FROM events e
        LEFT JOIN categories c ON e.category_id = c.category_id
        LEFT JOIN users u ON e.organizer_id = u.id
        LEFT JOIN (
            SELECT event_id, AVG(rating) AS rating, COUNT(*) AS review_count
            FROM reviews
            GROUP BY event_id
        ) r ON e.event_id = r.event_id
        WHERE e.status IN ('active', 'approved')
        ORDER BY e.event_date DESC
        LIMIT 6
    ";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new Exception("Error fetching events: " . mysqli_error($conn));
    }

    $events = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = [
            'event_id' => $row['event_id'],
            'title' => $row['title'] ?? 'Untitled Event',
            'description' => $row['description'] ?? 'No description available',
            'formatted_date' => $row['formatted_date'] ?? 'Date not set',
            'venue' => $row['venue'] ?? 'Venue not specified',
            'price' => number_format((float)($row['price'] ?? 0), 2),
            'image_url' => $row['image_url'] ?: '/kaif_evibe/templates/images/default-event.jpg',
            'category' => $row['category'] ?? 'General',
            'organizer_name' => $row['organizer_name'] ?? 'Event Organizer',
            'rating' => round($row['rating'] ?? 0, 1),
            'rating_count' => (int)($row['review_count'] ?? 0),
            'tickets_sold' => (int)($row['tickets_sold'] ?? 0),
            'total_tickets' => (int)($row['total_tickets'] ?? 0),
            'available_tickets' => (int)($row['available_tickets'] ?? 0),
        ];
    }

    if (empty($events)) {
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
            'rating_count' => 0,
            'tickets_sold' => 0,
            'total_tickets' => 100,
            'available_tickets' => 100,
        ];
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'events' => $events
    ]);

} catch (Exception $e) {
    error_log("Error in get_trending_events.php: " . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while retrieving events.'
    ]);
} finally {
    ob_end_flush();
}
?>
