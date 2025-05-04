<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get query parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$view = isset($_GET['view']) ? $_GET['view'] : 'grid';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$price = isset($_GET['price']) ? $_GET['price'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';

// Set items per page
$itemsPerPage = 12;

// Build query
$query = "SELECT e.*, o.name as organizer_name 
          FROM events e 
          JOIN organizers o ON e.organizer_id = o.id 
          WHERE e.status = 'active'";

$params = [];
$types = '';

// Add filters
if ($category) {
    $query .= " AND e.category = ?";
    $params[] = $category;
    $types .= 's';
}

if ($date) {
    switch ($date) {
        case 'today':
            $query .= " AND DATE(e.date) = CURDATE()";
            break;
        case 'week':
            $query .= " AND e.date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $query .= " AND e.date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)";
            break;
        case 'year':
            $query .= " AND e.date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 YEAR)";
            break;
    }
}

if ($price) {
    switch ($price) {
        case '0-1000':
            $query .= " AND e.price <= 1000";
            break;
        case '1000-5000':
            $query .= " AND e.price BETWEEN 1000 AND 5000";
            break;
        case '5000-10000':
            $query .= " AND e.price BETWEEN 5000 AND 10000";
            break;
        case '10000+':
            $query .= " AND e.price > 10000";
            break;
    }
}

if ($location) {
    $query .= " AND e.location LIKE ?";
    $params[] = "%$location%";
    $types .= 's';
}

// Get total count for pagination
$countQuery = str_replace("e.*, o.name as organizer_name", "COUNT(*)", $query);
$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$totalItems = $result->fetch_row()[0];
$totalPages = ceil($totalItems / $itemsPerPage);

// Add pagination
$offset = ($page - 1) * $itemsPerPage;
$query .= " ORDER BY e.date ASC LIMIT ? OFFSET ?";
$params[] = $itemsPerPage;
$params[] = $offset;
$types .= 'ii';

// Execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch events
$events = [];
while ($row = $result->fetch_assoc()) {
    // Format date
    $row['date'] = date('Y-m-d H:i:s', strtotime($row['date']));
    
    // Get event image
    $imageQuery = "SELECT image_url FROM event_images WHERE event_id = ? LIMIT 1";
    $imgStmt = $conn->prepare($imageQuery);
    $imgStmt->bind_param('i', $row['id']);
    $imgStmt->execute();
    $imgResult = $imgStmt->get_result();
    $row['image'] = $imgResult->fetch_assoc()['image_url'] ?? 'default-event.jpg';
    
    // Check if event is favorited by user
    $favoriteQuery = "SELECT COUNT(*) as is_favorite FROM favorites WHERE user_id = ? AND event_id = ?";
    $favStmt = $conn->prepare($favoriteQuery);
    $favStmt->bind_param('ii', $_SESSION['user_id'], $row['id']);
    $favStmt->execute();
    $favResult = $favStmt->get_result();
    $row['is_favorite'] = $favResult->fetch_assoc()['is_favorite'] > 0;
    
    $events[] = $row;
}

// Return response
header('Content-Type: application/json');
echo json_encode([
    'events' => $events,
    'totalPages' => $totalPages,
    'currentPage' => $page
]);
?> 