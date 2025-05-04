<?php
// Initialize session if not already started
session_start();

// Set headers to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

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

    // Get viewed events
    $viewed_sql = "
        SELECT ve.*, e.title, e.event_date as formatted_date, e.venue, c.name as category, 
               u.fullname as organizer_name, 
               COALESCE(ROUND((SELECT AVG(rating) FROM reviews WHERE event_id = e.event_id), 1), 0) as rating
,
               COALESCE((SELECT COUNT(*) FROM reviews WHERE event_id = e.event_id), 0) as rating_count,
               e.ticket_price as price, e.image_url
        FROM view_events ve
        JOIN events e ON ve.event_id = e.event_id
        LEFT JOIN users u ON e.organizer_id = u.id
        LEFT JOIN categories c ON e.category_id = c.category_id
        WHERE ve.user_id = :user_id
        ORDER BY ve.viewed_at DESC
        LIMIT 50
    ";
    
    $viewed_stmt = $conn->prepare($viewed_sql);
    $viewed_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $viewed_stmt->execute();
    $viewed_result = $viewed_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['viewed_events'] = $viewed_result;
    $response['viewed_count'] = count($viewed_result);

    // Get booked events
    $booked_sql = "
        SELECT b.booking_id, b.ticket_id, b.user_id, b.event_id, b.ticket_count, b.total_price, b.booking_date,
               e.title, e.event_date as formatted_date, e.venue, c.name as category, 
               u.fullname as organizer_name, 
               COALESCE(ROUND((SELECT AVG(rating) FROM reviews WHERE event_id = e.event_id), 1), 0) as rating
,
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
    } catch (PDOException $e) {
        // Table might not exist, ignore this error
        $response['booked_count'] = 0;
        error_log("Error fetching booked events: " . $e->getMessage());
    }

    // Get attended events (assuming there's a table for this)
    $attended_sql = "
        SELECT ae.*, e.title, e.event_date as formatted_date, e.venue, c.name as category, 
               u.fullname as organizer_name, 
               COALESCE(ROUND((SELECT AVG(rating) FROM reviews WHERE event_id = e.event_id), 1), 0) as rating
,
               COALESCE((SELECT COUNT(*) FROM reviews WHERE event_id = e.event_id), 0) as rating_count,
               e.ticket_price as price, e.image_url
        FROM attended_events ae
        JOIN events e ON ae.event_id = e.event_id
        LEFT JOIN users u ON e.organizer_id = u.id
        LEFT JOIN categories c ON e.category_id = c.category_id
        WHERE ae.user_id = :user_id
        ORDER BY ae.attended_at DESC
        LIMIT 50
    ";
    
    // Only execute if the attended_events table exists
    try {
        $attended_stmt = $conn->prepare($attended_sql);
        $attended_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $attended_stmt->execute();
        $attended_result = $attended_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['attended_events'] = $attended_result;
        $response['attended_count'] = count($attended_result);
    } catch (PDOException $e) {
        // Table might not exist, ignore this error
        $response['attended_count'] = 0;
    }

    // Return the event history data
    echo json_encode($response);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} finally {
    // PDO connection doesn't need explicit close
    $conn = null;
}
?> 