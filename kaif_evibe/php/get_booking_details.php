<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Booking ID is required']);
    exit;
}

$bookingId = $_GET['id'];

try {
    // Get booking details
    $stmt = $pdo->prepare("
        SELECT b.*, e.title, e.date, e.time, e.location, e.description,
               o.name as organizer_name, o.email as organizer_email
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        JOIN organizers o ON e.organizer_id = o.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$bookingId, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['error' => 'Booking not found']);
        exit;
    }

    // Get event image
    $stmt = $pdo->prepare("
        SELECT image_url
        FROM event_images
        WHERE event_id = ?
        LIMIT 1
    ");
    $stmt->execute([$booking['event_id']]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    $booking['image_url'] = $image ? $image['image_url'] : '/kaif_evibe/templates/images/default-event.jpg';

    // Get booking tickets
    $stmt = $pdo->prepare("
        SELECT bt.*, tt.name
        FROM booking_tickets bt
        JOIN ticket_types tt ON bt.ticket_type_id = tt.id
        WHERE bt.booking_id = ?
    ");
    $stmt->execute([$bookingId]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response data
    $response = [
        'id' => $booking['id'],
        'created_at' => $booking['created_at'],
        'status' => $booking['status'],
        'payment_method' => $booking['payment_method'],
        'event' => [
            'id' => $booking['event_id'],
            'title' => $booking['title'],
            'date' => $booking['date'],
            'time' => $booking['time'],
            'location' => $booking['location'],
            'description' => $booking['description'],
            'image_url' => $booking['image_url'],
            'organizer_name' => $booking['organizer_name'],
            'organizer_email' => $booking['organizer_email']
        ],
        'tickets' => $tickets
    ];

    // Return booking data
    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    error_log($e->getMessage());
} 