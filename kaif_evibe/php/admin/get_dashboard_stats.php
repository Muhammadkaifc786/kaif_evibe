<?php
header('Content-Type: application/json');

try {
    // Connect to the database
    $pdo = new PDO(
        'mysql:host=localhost;dbname=evibe_db',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Get total users
    $users_query = "SELECT COUNT(*) as total FROM users";
    $users_result = $pdo->query($users_query)->fetch(PDO::FETCH_ASSOC);
    $total_users = $users_result['total'];

    // Get total categories
    $categories_query = "SELECT COUNT(*) as total FROM categories";
    $categories_result = $pdo->query($categories_query)->fetch(PDO::FETCH_ASSOC);
    $total_categories = $categories_result['total'];

    // Get total events
    $events_query = "SELECT COUNT(*) as total FROM events";
    $events_result = $pdo->query($events_query)->fetch(PDO::FETCH_ASSOC);
    $total_events = $events_result['total'];

    // Get total tickets sold
    $tickets_query = "SELECT COALESCE(SUM(ticket_count), 0) as total_sold FROM bookings";
    $tickets_result = $pdo->query($tickets_query)->fetch(PDO::FETCH_ASSOC);
    $total_tickets_sold = $tickets_result['total_sold'];

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => (int)$total_users,
            'total_categories' => (int)$total_categories,
            'total_events' => (int)$total_events,
            'total_tickets_sold' => (int)$total_tickets_sold
        ]
    ]);

} catch (PDOException $e) {
    error_log('Database error in get_dashboard_stats.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>