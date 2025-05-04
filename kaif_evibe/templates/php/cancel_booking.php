<?php
session_start();
require_once 'db_connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set error handler to log all errors
function errorHandler($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    return false;
}
set_error_handler('errorHandler');

header('Content-Type: application/json');

function sendJsonResponse($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Check database connection
if (!$conn) {
    error_log("Database connection failed");
    sendJsonResponse(false, 'Database connection failed');
}

if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(false, 'User not logged in');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

// Get raw POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg());
    sendJsonResponse(false, 'Invalid JSON data');
}

$bookingId = isset($data['bookingId']) ? intval($data['bookingId']) : 0;

if ($bookingId <= 0) {
    sendJsonResponse(false, 'Invalid booking ID');
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Verify booking exists and belongs to user
    $query = "SELECT booking_id FROM bookings WHERE booking_id = ? AND user_id = ?";
    error_log("Executing query: " . $query . " with booking_id: " . $bookingId . " and user_id: " . $_SESSION['user_id']);
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$bookingId, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception("Booking not found or unauthorized");
    }
    
    // Delete booking
    $deleteQuery = "DELETE FROM bookings WHERE booking_id = ? AND user_id = ?";
    error_log("Executing delete query: " . $deleteQuery . " with booking_id: " . $bookingId . " and user_id: " . $_SESSION['user_id']);
    
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->execute([$bookingId, $_SESSION['user_id']]);
    
    // Commit transaction
    $conn->commit();
    
    sendJsonResponse(true, 'Booking cancelled successfully');
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn) {
        $conn->rollBack();
    }
    error_log("Error in cancel_booking.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendJsonResponse(false, 'Error cancelling booking: ' . $e->getMessage());
}

// Close connection
$conn = null;
?> 