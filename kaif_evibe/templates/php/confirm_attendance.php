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
    
    // First, check if the ticket exists and get its status
    $checkSql = "SELECT b.ticket_id, b.booking_id as booking_id, b.status, e.event_date as event_date 
                FROM bookings b 
                JOIN events e ON b.event_id = e.event_id
                WHERE b.ticket_id = :ticket_id";
    
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bindParam(':ticket_id', $ticketId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Ticket not found'
        ]);
        exit;
    }
    
    $ticket = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if ticket is already used
    if ($ticket['status'] === 'used') {
        echo json_encode([
            'success' => false,
            'message' => 'This ticket has already been used'
        ]);
        exit;
    }
    
    // Check if the ticket is expired
    if ($ticket['status'] === 'expired') {
        echo json_encode([
            'success' => false,
            'message' => 'This ticket is expired'
        ]);
        exit;
    }
    
    // Update the bookings status to 'used'
    $updateSql = "UPDATE bookings 
                 SET status = 'used', 
                     attendance_date = NOW() 
                 WHERE  ticket_id = :ticket_id";
                 
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bindParam(':ticket_id', $ticketId);
    $updateStmt->execute();
    
    // Check if the update was successful
    if ($updateStmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Attendance confirmed successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to confirm attendance'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 