<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Debug: Check database connection
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    // Get total event count
    $totalEventsQuery = "SELECT COUNT(*) as total FROM events WHERE status IN ('active', 'pending')";
    $totalResult = mysqli_query($conn, $totalEventsQuery);
    $totalEvents = 0;
    
    if ($totalResult) {
        $totalRow = mysqli_fetch_assoc($totalResult);
        $totalEvents = $totalRow['total'];
    }

    // Fetch all categories with their event counts
    $categoriesQuery = "SELECT c.category_id, c.name, c.icon,
                       (SELECT COUNT(*) FROM events e WHERE e.category_id = c.category_id AND e.status IN ('active', 'pending')) as event_count
                       FROM categories c 
                       ORDER BY c.name ASC";
    
    $categoriesResult = mysqli_query($conn, $categoriesQuery);
    
    if (!$categoriesResult) {
        throw new Exception("Error fetching categories: " . mysqli_error($conn));
    }
    
    $categories = [];
    while ($category = mysqli_fetch_assoc($categoriesResult)) {
        $categories[] = $category;
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'total_events' => $totalEvents
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_categories_dropdown.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 