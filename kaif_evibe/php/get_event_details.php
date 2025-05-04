<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if event ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Event ID is required']);
    exit;
}

$eventId = $_GET['id'];

try {
    // Get event details
    $stmt = $pdo->prepare("
        SELECT e.*, o.name as organizer_name, o.email as organizer_email
        FROM events e
        JOIN organizers o ON e.organizer_id = o.id
        WHERE e.id = ? AND e.status = 'active'
    ");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        http_response_code(404);
        echo json_encode(['error' => 'Event not found']);
        exit;
    }

    // Get event image
    $stmt = $pdo->prepare("
        SELECT image_url
        FROM event_images
        WHERE event_id = ?
        LIMIT 1
    ");
    $stmt->execute([$eventId]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    $event['image_url'] = $image ? $image['image_url'] : '/kaif_evibe/templates/images/default-event.jpg';

    // Get ticket types
    $stmt = $pdo->prepare("
        SELECT id, name, price, available
        FROM ticket_types
        WHERE event_id = ?
        ORDER BY price ASC
    ");
    $stmt->execute([$eventId]);
    $event['ticket_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if event is favorited by user
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as is_favorite
        FROM favorites
        WHERE user_id = ? AND event_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $eventId]);
    $favorite = $stmt->fetch(PDO::FETCH_ASSOC);
    $event['is_favorite'] = $favorite['is_favorite'] > 0;

    // Format date and time
    $event['date'] = date('Y-m-d', strtotime($event['date']));
    $event['time'] = date('H:i:s', strtotime($event['time']));

    // Return event data
    echo json_encode($event);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    error_log($e->getMessage());
} 