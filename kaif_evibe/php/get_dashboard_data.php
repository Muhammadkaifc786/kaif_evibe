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
    // Get user stats
    $stats = [
        'upcoming_events' => 0,
        'saved_events' => 0,
        'past_events' => 0
    ];

    // Get upcoming events count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        WHERE b.user_id = ? AND e.date >= CURDATE()
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['upcoming_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get saved events count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM favorites
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['saved_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get past events count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        WHERE b.user_id = ? AND e.date < CURDATE()
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['past_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get trending events (based on booking count)
    $stmt = $pdo->prepare("
        SELECT e.*, COUNT(b.id) as booking_count
        FROM events e
        LEFT JOIN bookings b ON e.id = b.event_id
        WHERE e.status = 'active' AND e.date >= CURDATE()
        GROUP BY e.id
        ORDER BY booking_count DESC
        LIMIT 6
    ");
    $stmt->execute();
    $trending_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get event images for trending events
    foreach ($trending_events as &$event) {
        $stmt = $pdo->prepare("
            SELECT image_url
            FROM event_images
            WHERE event_id = ?
            LIMIT 1
        ");
        $stmt->execute([$event['id']]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        $event['image_url'] = $image ? $image['image_url'] : '/kaif_evibe/templates/images/default-event.jpg';
    }

    // Get nearby events (hardcoded for now)
    $nearby_events = [
        [
            'id' => 1,
            'title' => 'Summer Music Festival',
            'date' => '2024-07-15',
            'location' => 'Clifton Beach, Karachi',
            'price' => 2500,
            'image_url' => '/kaif_evibe/templates/images/event1.jpg'
        ],
        [
            'id' => 2,
            'title' => 'Food Festival 2024',
            'date' => '2024-07-20',
            'location' => 'DHA, Karachi',
            'price' => 1500,
            'image_url' => '/kaif_evibe/templates/images/event2.jpg'
        ],
        [
            'id' => 3,
            'title' => 'Tech Conference 2024',
            'date' => '2024-07-25',
            'location' => 'Karachi Expo Center',
            'price' => 3000,
            'image_url' => '/kaif_evibe/templates/images/event3.jpg'
        ]
    ];

    // Get upcoming bookings
    $stmt = $pdo->prepare("
        SELECT b.*, e.title, e.date, e.time, e.location,
               COUNT(bt.id) as ticket_count
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        LEFT JOIN booking_tickets bt ON b.id = bt.booking_id
        WHERE b.user_id = ? AND e.date >= CURDATE()
        GROUP BY b.id
        ORDER BY e.date ASC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $upcoming_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get event images for upcoming bookings
    foreach ($upcoming_bookings as &$booking) {
        $stmt = $pdo->prepare("
            SELECT image_url
            FROM event_images
            WHERE event_id = ?
            LIMIT 1
        ");
        $stmt->execute([$booking['event_id']]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        $booking['event'] = [
            'image_url' => $image ? $image['image_url'] : '/kaif_evibe/templates/images/default-event.jpg',
            'title' => $booking['title'],
            'date' => $booking['date']
        ];
    }

    // Return dashboard data
    echo json_encode([
        'stats' => $stats,
        'trending_events' => $trending_events,
        'nearby_events' => $nearby_events,
        'upcoming_bookings' => $upcoming_bookings
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    error_log($e->getMessage());
} 