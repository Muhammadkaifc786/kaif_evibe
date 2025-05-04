<?php
// Include database connection file
require_once 'db_connect.php';

// Set response content type to JSON
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['ticket_id']) || !isset($_POST['status'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$ticketId = $_POST['ticket_id'];
$status = $_POST['status'];
$userId = $_SESSION['user_id'];

// Validate status
$validStatuses = ['valid', 'used', 'expired'];
if (!in_array($status, $validStatuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status value'
    ]);
    exit;
}

try {
    // Create a database connection
    $conn = connectDB();
    
    // First, verify that the user is authorized to update this ticket
    // (i.e., the ticket belongs to an event organized by this user)
    $authSql = "SELECT b.booking_id 
                FROM bookings b
                JOIN events e ON b.event_id = e.event_id
                WHERE b.ticket_id = :ticket_id 
                AND e.organizer_id = :organizer_id";
    
    $authStmt = $conn->prepare($authSql);
    $authStmt->bindParam(':ticket_id', $ticketId);
    $authStmt->bindParam(':organizer_id', $userId);
    $authStmt->execute();
    
    if ($authStmt->rowCount() === 0) {
        // User is not authorized to update this ticket
        echo json_encode([
            'success' => false,
            'message' => 'You are not authorized to update this ticket'
        ]);
        exit;
    }
    
    // Update the ticket status
    $updateSql = "UPDATE bookings SET status = :status";
    
    // If status is changed to 'used', record the attendance date
    if ($status === 'used') {
        $updateSql .= ", attendance_date = NOW()";
    }
    
    $updateSql .= " WHERE ticket_id = :ticket_id";
    
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bindParam(':status', $status);
    $updateStmt->bindParam(':ticket_id', $ticketId);
    $updateStmt->execute();
    
    if ($updateStmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Ticket status updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No changes were made'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 