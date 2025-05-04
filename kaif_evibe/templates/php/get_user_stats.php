<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Initialize default values
    $total_bookings = 0;
    $total_favorites = 0;
    $upcoming_events = 0;

    // Check if bookings table exists and get total bookings
    $table_check = $conn->query("SHOW TABLES LIKE 'bookings'");
    if ($table_check->rowCount() > 0) {
        $bookings_sql = "SELECT COUNT(*) as total_bookings FROM bookings WHERE user_id = :user_id";
        $bookings_stmt = $conn->prepare($bookings_sql);
        $bookings_stmt->execute(['user_id' => $user_id]);
        $total_bookings = $bookings_stmt->fetch(PDO::FETCH_ASSOC)['total_bookings'];
    }

    // Check if favorites table exists and get total favorites
    $table_check = $conn->query("SHOW TABLES LIKE 'favorites'");
    if ($table_check->rowCount() > 0) {
        $favorites_sql = "SELECT COUNT(*) as total_favorites FROM favorites WHERE user_id = :user_id";
        $favorites_stmt = $conn->prepare($favorites_sql);
        $favorites_stmt->execute(['user_id' => $user_id]);
        $total_favorites = $favorites_stmt->fetch(PDO::FETCH_ASSOC)['total_favorites'];
    }

    // Check if events and bookings tables exist and get upcoming events
    $table_check = $conn->query("SHOW TABLES LIKE 'events'");
    if ($table_check->rowCount() > 0) {
        $upcoming_sql = "SELECT COUNT(*) as upcoming_events 
                        FROM events e 
                        WHERE e.event_date > NOW()";
        $upcoming_stmt = $conn->prepare($upcoming_sql);
        $upcoming_stmt->execute();
        $upcoming_events = $upcoming_stmt->fetch(PDO::FETCH_ASSOC)['upcoming_events'];
    }

    echo json_encode([
        'success' => true,
        'total_bookings' => $total_bookings,
        'total_favorites' => $total_favorites,
        'upcoming_events' => $upcoming_events
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn = null;
?> 