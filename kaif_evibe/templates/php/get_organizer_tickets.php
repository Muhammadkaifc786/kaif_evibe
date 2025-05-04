<?php
// Include database connection
require_once 'db_connection.php';

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an organizer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'organizer') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Get the organizer ID
$organizer_id = $_SESSION['user_id'];

try {
    // Query to get all bookings for the organizer's events
    $query = "
        SELECT 
            b.booking_id,
            b.ticket_id,
            b.user_id,
            b.event_id,
            b.ticket_count,
            b.total_price,
            b.booking_date,
            e.title AS event_name,
            e.date AS event_date,
            e.time AS event_time,
            e.venue,
            e.category,
            u.username AS user_name,
            u.email AS user_email,
            u.phone AS user_phone
        FROM 
            bookings b
        JOIN 
            events e ON b.event_id = e.event_id
        JOIN 
            users u ON b.user_id = u.id
        WHERE 
            e.organizer_id = :organizer_id
        ORDER BY 
            b.booking_date DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':organizer_id', $organizer_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process the data to format it correctly
    $tickets = [];
    foreach ($bookings as $booking) {
        // Determine ticket status based on event date
        $eventDate = strtotime($booking['event_date']);
        $currentDate = time();
        
        $status = 'valid'; // Default status
        
        // If event date has passed, mark as expired
        if ($eventDate < $currentDate) {
            $status = 'expired';
        }
        
        // Format the dates
        $formattedEventDate = date('F j, Y', strtotime($booking['event_date']));
        $formattedBookingDate = date('F j, Y', strtotime($booking['booking_date']));
        
        // Format price
        $formattedPrice = number_format($booking['total_price'], 2);
        
        $tickets[] = [
            'booking_id' => $booking['booking_id'],
            'ticket_id' => $booking['ticket_id'],
            'event_id' => $booking['event_id'],
            'user_id' => $booking['user_id'],
            'status' => $status,
            'event_name' => $booking['event_name'],
            'event_date' => $formattedEventDate,
            'event_time' => $booking['event_time'],
            'venue' => $booking['venue'],
            'category' => $booking['category'],
            'user_name' => $booking['user_name'],
            'user_email' => $booking['user_email'],
            'user_phone' => $booking['user_phone'],
            'ticket_count' => $booking['ticket_count'],
            'ticket_type' => 'Standard', // You would need to add this column to your bookings table
            'price' => $formattedPrice,
            'total_price' => '$' . $formattedPrice,
            'booking_date' => $formattedBookingDate
        ];
    }
    
    echo json_encode([
        'success' => true,
        'tickets' => $tickets
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching tickets: ' . $e->getMessage()
    ]);
}

// Close the connection
$conn = null;
?> 