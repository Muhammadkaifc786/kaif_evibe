<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Debug logging
    error_log("Starting send_message.php");
    error_log("POST data: " . print_r($_POST, true));
    error_log("SESSION data: " . print_r($_SESSION, true));

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        error_log("User not logged in");
        throw new Exception('User not logged in');
    }

    // Get message data
    $event_id = $_POST['event_id'] ?? null;
    $message = $_POST['message'] ?? null;
    $receiver_id = $_POST['receiver_id'] ?? null;

    error_log("Event ID: " . $event_id);
    error_log("Message: " . $message);
    error_log("Receiver ID: " . $receiver_id);

    if (!$event_id || !$message) {
        error_log("Missing required fields");
        throw new Exception('Event ID and message are required');
    }

    // Get user role and verify event ownership
    $query = "SELECT u.role, e.organizer_id, e.title as event_title
              FROM users u 
              LEFT JOIN events e ON e.event_id = ? 
              WHERE u.id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $event_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    error_log("User data: " . print_r($data, true));

    if (!$data) {
        error_log("User not found in database");
        throw new Exception('User not found');
    }

    // If receiver_id is not provided, determine it based on user role
    if (!$receiver_id) {
        if ($data['role'] === 'organizer') {
            // Verify the organizer owns the event
            if ($data['organizer_id'] != $_SESSION['user_id']) {
                error_log("Organizer does not own this event");
                throw new Exception('You do not have permission to send messages for this event');
            }

            error_log("User is organizer, getting event bookings");
            // If sender is organizer, get all users who have booked this event
            $query = "SELECT DISTINCT b.user_id, u.fullname, u.email
                     FROM bookings b 
                     JOIN users u ON u.id = b.user_id
                     WHERE b.event_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $event_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $bookings = [];
            while ($booking = mysqli_fetch_assoc($result)) {
                $bookings[] = $booking;
            }
            
            error_log("Found " . count($bookings) . " bookings for event");
            
            if (empty($bookings)) {
                error_log("No bookings found for event");
                // Instead of throwing an error, we'll store the message for future users
                $query = "INSERT INTO messages (sender_id, event_id, message, is_broadcast, receiver_id) 
                         VALUES (?, ?, ?, 1, NULL)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "iis", $_SESSION['user_id'], $event_id, $message);
                
                if (!mysqli_stmt_execute($stmt)) {
                    error_log("Failed to store broadcast message: " . mysqli_error($conn));
                    throw new Exception('Failed to store message for future users');
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Message stored for future users of this event'
                ]);
                exit;
            }
            
            // Send message to all users who booked the event
            $success = true;
            foreach ($bookings as $booking) {
                error_log("Sending message to user_id: " . $booking['user_id']);
                $query = "INSERT INTO messages (sender_id, receiver_id, event_id, message) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "iiis", $_SESSION['user_id'], $booking['user_id'], $event_id, $message);
                
                if (!mysqli_stmt_execute($stmt)) {
                    error_log("Failed to insert message: " . mysqli_error($conn));
                    $success = false;
                    break;
                }
                error_log("Message sent successfully to user_id: " . $booking['user_id']);
            }
            
            if (!$success) {
                throw new Exception('Failed to send message to all users');
            }
        } else {
            error_log("User is not organizer, getting event organizer");
            // If sender is user, get the event organizer (must have role 'organizer')
            $query = "SELECT e.organizer_id, u.fullname as organizer_name 
                     FROM events e 
                     JOIN users u ON u.id = e.organizer_id AND u.role = 'organizer'
                     WHERE e.event_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $event_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $event = mysqli_fetch_assoc($result);

            error_log('Fetched event: ' . print_r($event, true));

            if (!$event || !$event['organizer_id']) {
                error_log('receiver_id is null after fetching event. Check event_id and organizer_id in the database.');
                throw new Exception('Could not find organizer for this event.');
            }
            $receiver_id = $event['organizer_id'];
            error_log('receiver_id to be used: ' . $receiver_id);

            // Insert message into database
            $query = "INSERT INTO messages (sender_id, receiver_id, event_id, message, is_broadcast) VALUES (?, ?, ?, ?, 0)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iiis", $_SESSION['user_id'], $receiver_id, $event_id, $message);
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Failed to insert message: " . mysqli_error($conn));
                throw new Exception('Failed to send message');
            }
            error_log("Message sent successfully to organizer");
        }
    } else {
        error_log("Sending to specific receiver_id: " . $receiver_id);
        // If receiver_id is provided, send to specific user
        $query = "INSERT INTO messages (sender_id, receiver_id, event_id, message) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iiis", $_SESSION['user_id'], $receiver_id, $event_id, $message);
        
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Failed to insert message: " . mysqli_error($conn));
            throw new Exception('Failed to send message');
        }
        error_log("Message sent successfully to specific receiver");
    }

    error_log("Message sending completed successfully");
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully'
    ]);

} catch (Exception $e) {
    error_log("Error in send_message.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 