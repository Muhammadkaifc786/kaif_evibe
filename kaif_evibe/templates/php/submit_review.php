<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connection.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit a review']);
    exit;
}

// Get POST data
$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
$rating = isset($_POST['rating']) ? floatval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// Validate input
if ($event_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating']);
    exit;
}

if (empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Please write a review']);
    exit;
}

try {
    // Check if user has already reviewed this event
    $checkReview = $conn->prepare("SELECT review_id FROM reviews WHERE event_id = ? AND user_id = ?");
    $checkReview->execute([$event_id, $_SESSION['user_id']]);
    $existingReview = $checkReview->fetch(PDO::FETCH_ASSOC);
    
    if ($existingReview) {
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this event']);
        exit;
    }
    
    // Insert new review
    $insertReview = $conn->prepare("INSERT INTO reviews (event_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $insertReview->execute([$event_id, $_SESSION['user_id'], $rating, $comment]);
    
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
    
} catch (Exception $e) {
    error_log("Error in submit_review.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error submitting review: ' . $e->getMessage()]);
}
?> 