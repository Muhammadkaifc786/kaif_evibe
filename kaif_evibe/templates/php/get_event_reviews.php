<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connection.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to view reviews']);
    exit;
}

// Get event ID from request
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if ($event_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
    exit;
}

try {
    // Get reviews with user information
    $query = "SELECT r.*, u.fullname, u.email 
              FROM reviews r 
              JOIN users u ON r.user_id = u.id 
              WHERE r.event_id = ? 
              ORDER BY r.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$event_id]);
    
    $reviews = [];
    $totalRating = 0;
    $ratingCount = 0;
    $ratingDistribution = [0, 0, 0, 0, 0]; // For 1-5 stars

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $reviews[] = [
            'review_id' => $row['review_id'],
            'user_name' => $row['fullname'],
            'user_email' => $row['email'],
            'rating' => $row['rating'],
            'comment' => $row['comment'],
            'created_at' => $row['created_at']
        ];
        
        // Update rating statistics
        $totalRating += $row['rating'];
        $ratingCount++;
        $ratingDistribution[$row['rating'] - 1]++;
    }

    // Calculate average rating
    $averageRating = $ratingCount > 0 ? $totalRating / $ratingCount : 0;

    // Prepare response
    $response = [
        'success' => true,
        'reviews' => $reviews,
        'statistics' => [
            'average_rating' => round($averageRating, 1),
            'total_reviews' => $ratingCount,
            'rating_distribution' => $ratingDistribution
        ]
    ];

    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Error in get_event_reviews.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching reviews: ' . $e->getMessage()
    ]);
}
?> 