<?php
// Initialize session if not already started
session_start();

// Set headers to return JSON
header('Content-Type: application/json');

// For testing, let's set a user ID
$_SESSION['user_id'] = 8; // Use a user ID from your database that has bookings

// Get user ID
$user_id = $_SESSION['user_id'];

// Connect to database
require_once 'db_connection.php';

try {
    $response = [
        'success' => true,
        'viewed_events' => [],
        'booked_events' => [],
        'attended_events' => [],
        'viewed_count' => 0,
        'booked_count' => 0,
        'attended_count' => 0
    ];
    
    // Get booked events
    $booked_sql = "
        SELECT b.booking_id, b.ticket_id, b.user_id, b.event_id, b.ticket_count, b.total_price, b.booking_date,
               e.title, e.event_date as formatted_date, e.venue, c.name as category, 
               u.fullname as organizer_name, 
               COALESCE((SELECT AVG(rating) FROM reviews WHERE event_id = e.event_id), 0) as rating,
               COALESCE((SELECT COUNT(*) FROM reviews WHERE event_id = e.event_id), 0) as rating_count,
               e.ticket_price as price, e.image_url
        FROM bookings b
        JOIN events e ON b.event_id = e.event_id
        LEFT JOIN users u ON e.organizer_id = u.id
        LEFT JOIN categories c ON e.category_id = c.category_id
        WHERE b.user_id = :user_id
        ORDER BY b.booking_date DESC
        LIMIT 50
    ";
    
    try {
        $booked_stmt = $conn->prepare($booked_sql);
        $booked_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $booked_stmt->execute();
        $booked_result = $booked_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['booked_events'] = $booked_result;
        $response['booked_count'] = count($booked_result);
        
        // Add debug information
        $response['debug'] = [
            'user_id' => $user_id,
            'query' => $booked_sql,
            'tables' => getTablesList($conn)
        ];
    } catch (PDOException $e) {
        // Table might not exist, ignore this error
        $response['booked_count'] = 0;
        $response['error'] = 'Error fetching booked events: ' . $e->getMessage();
    }

    // Return the event history data
    echo json_encode($response);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} finally {
    // PDO connection doesn't need explicit close
    $conn = null;
}

function getTablesList($conn) {
    $tables = $conn->query("SHOW TABLES");
    $tableList = [];
    while ($row = $tables->fetch(PDO::FETCH_NUM)) {
        $tableList[] = $row[0];
    }
    return $tableList;
}
?> 