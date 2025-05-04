<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if bookings table exists
$table_check = $conn->query("SHOW TABLES LIKE 'bookings'");
if ($table_check->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Bookings table does not exist']);
    exit;
}

$query = "SELECT b.booking_id, b.event_id, b.ticket_count, b.total_price, b.event_date, 
                 e.title, e.venue, e.image_url, e.category_id, c.name as category_name
          FROM bookings b
          JOIN events e ON b.event_id = e.event_id
          LEFT JOIN categories c ON e.category_id = c.category_id
          WHERE b.user_id = ?
          ORDER BY b.event_date DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
if (!$stmt->bind_param('i', $user_id)) {
    echo json_encode(['success' => false, 'message' => 'Bind param failed: ' . $stmt->error]);
    exit;
}
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
    exit;
}
$result = $stmt->get_result();
if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Get result failed: ' . $stmt->error]);
    exit;
}

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

echo json_encode([
    'success' => true,
    'bookings' => $bookings
]);
?> 