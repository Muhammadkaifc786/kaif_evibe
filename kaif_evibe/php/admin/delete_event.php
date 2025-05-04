<?php
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get the request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate the event ID
if (!isset($data['event_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID is required']);
    exit;
}

$eventId = $data['event_id'];

try {
    // Connect to the database
    $pdo = new PDO(
        'mysql:host=localhost;dbname=evibe_db',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Delete the event
    $stmt = $pdo->prepare('DELETE FROM events WHERE event_id = ?');
    $stmt->execute([$eventId]);

    // Check if any rows were affected
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        exit;
    }

    // Return success message
    echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);

} catch (PDOException $e) {
    // Log the error
    error_log('Database error in delete_event.php: ' . $e->getMessage());
    
    // Return error message
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 