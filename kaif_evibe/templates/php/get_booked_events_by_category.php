<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;

try {
    // Ensure database connection is established
    if (!isset($pdo)) {
        throw new Exception("Database connection not established");
    }

    $query = "SELECT e.*, c.name as category_name, b.booking_id, b.ticket_count, b.total_price, b.booking_date
              FROM bookings b
              JOIN events e ON b.event_id = e.event_id
              JOIN categories c ON e.category_id = c.category_id
              WHERE b.user_id = :user_id";

    if ($category_id && $category_id !== 'all') {
        $query .= " AND e.category_id = :category_id";
    }

    $query .= " ORDER BY e.event_date DESC";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($category_id && $category_id !== 'all') {
        $stmt->bindParam(':category_id', $category_id);
    }

    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log the query and results for debugging
    error_log("Query executed: " . $query);
    error_log("User ID: " . $user_id);
    error_log("Category ID: " . $category_id);
    error_log("Number of events found: " . count($events));

    echo json_encode([
        'success' => true,
        'events' => $events
    ]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 