<?php
// Include database connection file
require_once 'db_connect.php';

header('Content-Type: application/json');

// Check if ticket_id is provided
if (!isset($_POST['ticket_id']) || empty($_POST['ticket_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Ticket ID is required'
    ]);
    exit;
}

try {
    // Create a database connection
    $conn = connectDB();
    
    $ticketId = $_POST['ticket_id'];
    $categoryId = isset($_POST['category_id']) ? $_POST['category_id'] : null;
    
    // Base query - updated to include the status from bookings table
    $sql = "SELECT b.*, e.title as event_title,e.event_date as event_date, e.venue as location, 
    u.fullname as attendee_name, u.email as attendee_email, b.status as booking_status,
    b.booking_id, b.ticket_count, b.total_price, b.booking_date
         FROM bookings b
     JOIN events e ON b.event_id = e.event_id
     JOIN users u ON b.user_id = u.id
     JOIN categories c ON e.category_id = c.category_id
     WHERE b.ticket_id = :ticket_id";
    
    // Add category filter if provided
    if ($categoryId) {
        $sql .= " AND e.category_id = :category_id";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':ticket_id', $ticketId);
    
    if ($categoryId) {
        $stmt->bindParam(':category_id', $categoryId);
    }
    
    $stmt->execute();
    
    // Check if ticket exists
    if ($stmt->rowCount() > 0) {
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Use the status from the bookings table
        $status = ucfirst($ticket['booking_status']); // Capitalize first letter
        
        // Format dates for display
        $eventDate = date('F d, Y', strtotime($ticket['event_date']));
        $eventTime = date('h:i A', strtotime($ticket['event_time']));
        $bookingDate = date('F d, Y', strtotime($ticket['booking_date']));
        
        // Format price
        $totalAmount = number_format($ticket['total_price'], 2);
        
        // Return ticket information
        echo json_encode([
            'success' => true,
            'ticket' => [
                'id' => $ticket['ticket_id'],
                'status' => $status,
                'attendee_name' => $ticket['attendee_name'],
                'attendee_email' => $ticket['attendee_email'],
                'event_title' => $ticket['event_title'],
                'event_date' => $eventDate,
                'event_time' => $eventTime,
                'location' => $ticket['location'],
                'ticket_count' => $ticket['ticket_count'],
                'amount' => $totalAmount,
                'booking_date' => $bookingDate
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Ticket not found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 