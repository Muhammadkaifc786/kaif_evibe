<?php
// Ensure no output before headers
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to the user
ini_set('log_errors', 1); // Log errors instead

// Set JSON header
header('Content-Type: application/json');

// Set error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
});

try {
    require_once 'db_connection.php';
    
    // Start session after headers
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please login to book events');
    }

    // Get POST data
    $raw_data = file_get_contents('php://input');
    error_log("Raw POST data: " . $raw_data);
    
    $data = json_decode($raw_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    if (!$data) {
        throw new Exception('Invalid booking data');
    }

    // Get user ID from session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }
    $user_id = $_SESSION['user_id'];
    error_log("User ID from session: $user_id");

    $event_id = isset($data['event_id']) ? intval($data['event_id']) : 0;
    $ticket_quantity = isset($data['ticket_quantity']) ? intval($data['ticket_quantity']) : 0;
    $total_price = isset($data['total_price']) ? floatval($data['total_price']) : 0;

    error_log("Booking attempt - User ID: $user_id, Event ID: $event_id, Quantity: $ticket_quantity, Price: $total_price");

    // Validate input
    if ($event_id <= 0 || $ticket_quantity <= 0 || $total_price <= 0) {
        throw new Exception('Invalid booking details');
    }

    // Verify database connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Start transaction
    $conn->beginTransaction();
    error_log("Transaction started");

    try {
        // Check if event exists and has enough tickets
        $checkEvent = $conn->prepare("SELECT available_tickets, ticket_price FROM events WHERE event_id = ?");
        if (!$checkEvent->execute([$event_id])) {
            $errorInfo = $checkEvent->errorInfo();
            error_log("Failed to check event availability. PDO Error: " . print_r($errorInfo, true));
            throw new Exception('Failed to check event availability: ' . $errorInfo[2]);
        }
        $event = $checkEvent->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            error_log("Event not found with ID: $event_id");
            throw new Exception('Event not found');
        }

        if ($event['available_tickets'] < $ticket_quantity) {
            error_log("Not enough tickets available. Requested: $ticket_quantity, Available: " . $event['available_tickets']);
            throw new Exception('Not enough tickets available');
        }

        error_log("Event found with " . $event['available_tickets'] . " tickets available");

        // Generate a unique ticket ID (max 20 characters)
        $ticket_id = 'T' . substr(time(), -6) . rand(1000, 9999);
        error_log("Generated ticket ID: $ticket_id");

        // Insert booking
        $insertBooking = $conn->prepare("INSERT INTO bookings (ticket_id, user_id, event_id, ticket_count, total_price, booking_date) VALUES (?, ?, ?, ?, ?, NOW())");
        error_log("Attempting to insert booking with values: ticket_id=$ticket_id, user_id=$user_id, event_id=$event_id, ticket_count=$ticket_quantity, total_price=$total_price");
        
        try {
            if (!$insertBooking->execute([
                $ticket_id,
                $user_id,
                $event_id,
                $ticket_quantity,
                $total_price
            ])) {
                $errorInfo = $insertBooking->errorInfo();
                error_log("Failed to insert booking. PDO Error: " . print_r($errorInfo, true));
                throw new Exception('Failed to insert booking: ' . $errorInfo[2]);
            }

            error_log("Booking inserted successfully");
            
            // Get the last inserted booking ID
            $booking_id = $conn->lastInsertId();
            error_log("Last inserted booking ID: $booking_id");
            
            if ($booking_id === 0) {
                // Try to find the booking by ticket_id and user_id
                error_log("Warning: lastInsertId() returned 0. Checking if booking exists with ticket_id: $ticket_id and user_id: $user_id");
                $checkBooking = $conn->prepare("SELECT booking_id FROM bookings WHERE ticket_id = ? AND user_id = ? ORDER BY booking_id DESC LIMIT 1");
                $checkBooking->execute([$ticket_id, $user_id]);
                $existingBooking = $checkBooking->fetch(PDO::FETCH_ASSOC);
                
                if ($existingBooking) {
                    $booking_id = $existingBooking['booking_id'];
                    error_log("Found existing booking with ID: $booking_id");
                } else {
                    error_log("No booking found with ticket_id: $ticket_id and user_id: $user_id");
                    throw new Exception("Failed to retrieve booking ID after insertion");
                }
            }

            // Update available tickets
            $updateTickets = $conn->prepare("UPDATE events SET available_tickets = available_tickets - ? WHERE event_id = ?");
            error_log("Attempting to update available tickets: event_id=$event_id, quantity=$ticket_quantity");
            
            if (!$updateTickets->execute([$ticket_quantity, $event_id])) {
                $errorInfo = $updateTickets->errorInfo();
                error_log("Failed to update ticket availability. PDO Error: " . print_r($errorInfo, true));
                throw new Exception('Failed to update ticket availability: ' . $errorInfo[2]);
            }

            error_log("Ticket availability updated");

            // Commit transaction
            $conn->commit();
            error_log("Transaction committed successfully");

            // Verify the booking exists
            $verifyBooking = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ?");
            $verifyBooking->execute([$booking_id]);
            $booking = $verifyBooking->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                error_log("Booking verification failed. Booking ID: $booking_id");
                error_log("Checking all bookings in database:");
                $allBookings = $conn->query("SELECT booking_id, ticket_id, user_id, event_id FROM bookings ORDER BY booking_id DESC LIMIT 5");
                while ($row = $allBookings->fetch(PDO::FETCH_ASSOC)) {
                    error_log("Found booking: " . print_r($row, true));
                }
                throw new Exception("Failed to verify booking creation. Booking ID: $booking_id");
            }

            error_log("Booking verified successfully: " . print_r($booking, true));

            // Generate receipt URL
            $receipt_url = '/kaif_evibe/templates/php/generate_receipt.php?booking_id=' . $booking_id;
            error_log("Generated receipt URL: $receipt_url");

            // Clear any output buffer
            ob_clean();
            
            echo json_encode([
                'success' => true,
                'message' => 'Booking successful',
                'booking_id' => $booking_id,
                'receipt_url' => $receipt_url
            ]);

        } catch (Exception $e) {
            // Rollback transaction on error
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
                error_log("Transaction rolled back due to error: " . $e->getMessage());
            }
            
            error_log("Error in booking process: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            
            // Clear any output buffer
            ob_clean();
            
            echo json_encode([
                'success' => false,
                'message' => 'Error processing booking: ' . $e->getMessage()
            ]);
        }

    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
            error_log("Transaction rolled back due to error: " . $e->getMessage());
        }
        
        error_log("Error in submit_booking.php: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
        
        // Clear any output buffer
        ob_clean();
        
        echo json_encode([
            'success' => false,
            'message' => 'Error processing booking: ' . $e->getMessage()
        ]);
    }

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back due to error");
    }
    
    error_log("Error in submit_booking.php: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
    
    // Clear any output buffer
    ob_clean();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error processing booking: ' . $e->getMessage()
    ]);
}

// Ensure no output after JSON
exit;
?> 