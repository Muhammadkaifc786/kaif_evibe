<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');

// Start session
session_start();

// Get the absolute path to the config file
$config_path = __DIR__ . '/../../templates/php/config.php';
if (!file_exists($config_path)) {
    error_log("Config file not found at: " . $config_path);
    echo json_encode(['success' => false, 'message' => 'Config file not found at: ' . $config_path]);
    exit;
}

require_once $config_path;

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get POST data
$raw_data = file_get_contents('php://input');
$data = json_decode($raw_data, true);

// Debug log
error_log("Raw POST data: " . $raw_data);
error_log("Decoded data: " . print_r($data, true));

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data: ' . json_last_error_msg()]);
    exit;
}

$event_id = $data['event_id'] ?? null;
$status = $data['status'] ?? null;

if (!$event_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate status
$valid_statuses = ['pending', 'approved', 'rejected'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

try {
    // Check if event exists
    $check_sql = "SELECT event_id, status FROM events WHERE event_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        throw new Exception("Error preparing check statement: " . $conn->error);
    }
    $check_stmt->bind_param("i", $event_id);
    if (!$check_stmt->execute()) {
        throw new Exception("Error executing check statement: " . $check_stmt->error);
    }
    $result = $check_stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Event not found");
    }
    $current_event = $result->fetch_assoc();
    $check_stmt->close();

    // Only update if the status is different
    if ($current_event['status'] !== $status) {
        // Update event status
        $update_sql = "UPDATE events SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE event_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            throw new Exception("Error preparing update statement: " . $conn->error);
        }
        $update_stmt->bind_param("si", $status, $event_id);
        if (!$update_stmt->execute()) {
            throw new Exception("Error executing update statement: " . $update_stmt->error);
        }
        $update_stmt->close();

        // Get the updated event data
        $select_stmt = $conn->prepare("SELECT status FROM events WHERE event_id = ?");
        if (!$select_stmt) {
            throw new Exception("Error preparing select statement: " . $conn->error);
        }
        $select_stmt->bind_param("i", $event_id);
        if (!$select_stmt->execute()) {
            throw new Exception("Error executing select statement: " . $select_stmt->error);
        }
        $result = $select_stmt->get_result();
        $updated_event = $result->fetch_assoc();
        $select_stmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully',
            'event_id' => $event_id,
            'new_status' => $updated_event['status']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Status is already set to ' . $status,
            'event_id' => $event_id,
            'new_status' => $status
        ]);
    }

} catch (Exception $e) {
    error_log("Error in update_event_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?> 