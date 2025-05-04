<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User not logged in");
    }

    // Query to get unread message counts for each event
    $sql = "SELECT 
                m.event_id,
                COUNT(*) as unread_count
            FROM messages m
            WHERE m.receiver_id = ?
            AND m.is_read = 0
            GROUP BY m.event_id";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        throw new Exception("Error fetching message counts: " . mysqli_error($conn));
    }

    $message_counts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $message_counts[$row['event_id']] = (int)$row['unread_count'];
    }

    echo json_encode([
        'success' => true,
        'message_counts' => $message_counts
    ]);

} catch (Exception $e) {
    error_log("Error in get_event_message_counts.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 