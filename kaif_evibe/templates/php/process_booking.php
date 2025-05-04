<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Get POST data
    $event_id = $_POST['event_id'] ?? null;
    $quantity = $_POST['quantity'] ?? null;
    $ticket_type = $_POST['ticket_type'] ?? null;
    $card_number = $_POST['card_number'] ?? null;
    $expiry_date = $_POST['expiry_date'] ?? null;
    $cvv = $_POST['cvv'] ?? null;

    // Validate inputs
    if (!$event_id || !$quantity || !$ticket_type || !$card_number || !$expiry_date || !$cvv) {
        throw new Exception('Missing required fields');
    }

    // Get event details
    $event_query = "SELECT e.*, u.id as organizer_id, u.fullname as organizer_name 
                    FROM events e 
                    JOIN users u ON e.organizer_id = u.id 
                    WHERE e.event_id = ?";
    $stmt = mysqli_prepare($conn, $event_query);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $event_result = mysqli_stmt_get_result($stmt);
    $event = mysqli_fetch_assoc($event_result);

    if (!$event) {
        throw new Exception('Event not found');
    }

    // Check if there are enough tickets available
    if ($event['available_tickets'] < $quantity) {
        throw new Exception('Not enough tickets available');
    }

    // Calculate total amount
    $base_price = $event['ticket_price'];
    $service_fee = 2.00;
    $total_amount = ($base_price * $quantity) + $service_fee;

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Insert into booked_events
        $booking_query = "INSERT INTO booked_events (user_id, event_id, organizer_id, total_amount, booking_date, payment_status, booking_status) 
                         VALUES (?, ?, ?, ?, NOW(), 'completed', 'confirmed')";
        $stmt = mysqli_prepare($conn, $booking_query);
        mysqli_stmt_bind_param($stmt, "iiid", $_SESSION['user_id'], $event_id, $event['organizer_id'], $total_amount);
        mysqli_stmt_execute($stmt);
        $booking_id = mysqli_insert_id($conn);

        // Generate ticket numbers and insert into tickets table
        $ticket_query = "INSERT INTO tickets (booking_id, user_id, event_id, organizer_id, ticket_number, 
                         quantity, ticket_type, price, event_date, event_time, venue, status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
        $stmt = mysqli_prepare($conn, $ticket_query);

        // Generate a unique ticket number
        $ticket_number = 'TICKET-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);

        mysqli_stmt_bind_param($stmt, "iiiisisdsss", 
            $booking_id, 
            $_SESSION['user_id'], 
            $event_id, 
            $event['organizer_id'],
            $ticket_number,
            $quantity,
            $ticket_type,
            $base_price,
            $event['event_date'],
            $event['event_time'],
            $event['venue']
        );
        mysqli_stmt_execute($stmt);

        // Update event available tickets
        $update_query = "UPDATE events SET available_tickets = available_tickets - ? WHERE event_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ii", $quantity, $event_id);
        mysqli_stmt_execute($stmt);

        // Commit transaction
        mysqli_commit($conn);

        // Update session with booking information
        $_SESSION['last_booking'] = [
            'booking_id' => $booking_id,
            'ticket_number' => $ticket_number,
            'event_id' => $event_id,
            'quantity' => $quantity
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Booking successful',
            'booking_id' => $booking_id,
            'ticket_number' => $ticket_number
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in process_booking.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 