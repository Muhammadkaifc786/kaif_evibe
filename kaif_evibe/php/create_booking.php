<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get request body
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

// Validate required fields
$requiredFields = ['event_id', 'tickets', 'personal_info', 'payment_method', 'payment_details', 'total_amount'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Create booking record
    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            user_id, event_id, full_name, email, phone,
            payment_method, payment_details, total_amount, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $data['event_id'],
        $data['personal_info']['full_name'],
        $data['personal_info']['email'],
        $data['personal_info']['phone'],
        $data['payment_method'],
        json_encode($data['payment_details']),
        $data['total_amount']
    ]);

    $bookingId = $pdo->lastInsertId();

    // Create booking tickets
    $stmt = $pdo->prepare("
        INSERT INTO booking_tickets (
            booking_id, ticket_type_id, quantity, price
        ) VALUES (?, ?, ?, ?)
    ");

    foreach ($data['tickets'] as $ticket) {
        // Get ticket price
        $priceStmt = $pdo->prepare("
            SELECT price, available
            FROM ticket_types
            WHERE id = ? AND event_id = ?
        ");
        $priceStmt->execute([$ticket['ticket_id'], $data['event_id']]);
        $ticketData = $priceStmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticketData) {
            throw new Exception("Invalid ticket type");
        }

        if ($ticketData['available'] < $ticket['quantity']) {
            throw new Exception("Not enough tickets available");
        }

        // Insert booking ticket
        $stmt->execute([
            $bookingId,
            $ticket['ticket_id'],
            $ticket['quantity'],
            $ticketData['price']
        ]);

        // Update available tickets
        $updateStmt = $pdo->prepare("
            UPDATE ticket_types
            SET available = available - ?
            WHERE id = ?
        ");
        $updateStmt->execute([$ticket['quantity'], $ticket['ticket_id']]);
    }

    // Commit transaction
    $pdo->commit();

    // Send confirmation email
    sendBookingConfirmation($data['personal_info']['email'], $bookingId);

    // Return success response
    echo json_encode([
        'success' => true,
        'booking_id' => $bookingId,
        'message' => 'Booking created successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();

    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to create booking',
        'message' => $e->getMessage()
    ]);
    error_log($e->getMessage());
}

// Function to send booking confirmation email
function sendBookingConfirmation($email, $bookingId) {
    $to = $email;
    $subject = "Booking Confirmation - EVibe";
    
    $message = "
    <html>
    <head>
        <title>Booking Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #6c5ce7; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .footer { text-align: center; padding: 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Booking Confirmation</h1>
            </div>
            <div class='content'>
                <p>Dear Customer,</p>
                <p>Thank you for your booking with EVibe. Your booking has been confirmed.</p>
                <p>Booking ID: #$bookingId</p>
                <p>You can view your booking details and download your tickets by visiting:</p>
                <p><a href='http://localhost/kaif_evibe/templates/html/booking_confirmation.html?id=$bookingId'>View Booking Details</a></p>
            </div>
            <div class='footer'>
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: noreply@evibe.com\r\n";

    mail($to, $subject, $message, $headers);
} 