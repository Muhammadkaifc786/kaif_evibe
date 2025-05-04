<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Get user's bookings with event details
    $query = "
        SELECT 
            b.id,
            b.booking_date,
            b.status,
            b.total_amount,
            b.payment_method,
            e.title as event_title,
            e.location as event_location,
            e.date as event_date,
            e.time as event_time,
            ei.image_url as event_image,
            COUNT(bt.id) as ticket_count
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        LEFT JOIN event_images ei ON e.id = ei.event_id AND ei.is_primary = 1
        LEFT JOIN booking_tickets bt ON b.id = bt.booking_id
        WHERE b.user_id = ?
        GROUP BY b.id
        ORDER BY b.booking_date DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        // Determine booking status based on event date
        $eventDate = new DateTime($row['event_date']);
        $currentDate = new DateTime();
        
        if ($row['status'] === 'cancelled') {
            $status = 'cancelled';
        } elseif ($eventDate < $currentDate) {
            $status = 'past';
        } else {
            $status = 'upcoming';
        }

        // Get default image if no event image is found
        if (!$row['event_image']) {
            $row['event_image'] = '/kaif_evibe/templates/images/default-event.jpg';
        }

        $bookings[] = [
            'id' => $row['id'],
            'booking_date' => $row['booking_date'],
            'status' => $status,
            'total_amount' => $row['total_amount'],
            'payment_method' => $row['payment_method'],
            'event_title' => $row['event_title'],
            'event_location' => $row['event_location'],
            'event_date' => $row['event_date'],
            'event_time' => $row['event_time'],
            'event_image' => $row['event_image'],
            'ticket_count' => $row['ticket_count']
        ];
    }

    echo json_encode([
        'success' => true,
        'bookings' => $bookings
    ]);

} catch (Exception $e) {
    error_log('Error fetching user bookings: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch bookings',
        'message' => 'An error occurred while fetching your bookings. Please try again later.'
    ]);
}

$stmt->close();
$conn->close();
?> 