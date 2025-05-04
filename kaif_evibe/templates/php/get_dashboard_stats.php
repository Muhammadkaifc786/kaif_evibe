<?php
session_start();
require_once 'config.php';

// Debug session data
error_log("Session data: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    echo json_encode(["success" => false, "message" => "You must be logged in to view dashboard stats"]);
    exit();
}

// Check if user is an organizer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'organizer') {
    error_log("User is not an organizer. Role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'not set'));
    echo json_encode(["success" => false, "message" => "Only organizers can view dashboard stats"]);
    exit();
}

$organizer_id = $_SESSION['user_id'];
error_log("Organizer ID from session: " . $organizer_id);

try {
    // Get total events
    $total_events_sql = "SELECT COUNT(*) as total FROM events WHERE organizer_id = ?";
    error_log("Executing total events query: " . $total_events_sql . " with organizer_id: " . $organizer_id);
    
    $stmt = $conn->prepare($total_events_sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $organizer_id);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        error_log("Get result failed: " . $stmt->error);
        throw new Exception("Get result failed: " . $stmt->error);
    }
    
    $row = $result->fetch_assoc();
    $total_events = $row['total'] ?? 0;
    
    error_log("Total events query result: " . print_r($row, true));
    error_log("Total events for organizer_id " . $organizer_id . ": " . $total_events);

    // Get total tickets sold and total sales
    $sales_sql = "SELECT 
                    COALESCE(SUM(total_tickets - available_tickets), 0) as total_tickets_sold,
                    COALESCE(SUM((total_tickets - available_tickets) * ticket_price), 0) as total_sales
                  FROM events 
                  WHERE organizer_id = ?";
    $stmt = $conn->prepare($sales_sql);
    $stmt->bind_param("i", $organizer_id);
    $stmt->execute();
    $sales_result = $stmt->get_result()->fetch_assoc();
    $total_tickets_sold = $sales_result['total_tickets_sold'] ?? 0;
    $total_sales = $sales_result['total_sales'] ?? 0;

    // Get pending requests (events waiting for admin approval)
    $pending_sql = "SELECT COUNT(*) as total FROM events WHERE organizer_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($pending_sql);
    $stmt->bind_param("i", $organizer_id);
    $stmt->execute();
    $pending_requests = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Get upcoming events
    $upcoming_sql = "SELECT COUNT(*) as total FROM events 
                    WHERE organizer_id = ? 
                    AND event_date > NOW() 
                    AND status = 'approved'";
    $stmt = $conn->prepare($upcoming_sql);
    $stmt->bind_param("i", $organizer_id);
    $stmt->execute();
    $upcoming_events = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Check if messages table exists before querying it
    $unread_messages = 0;
    $table_check_sql = "SHOW TABLES LIKE 'messages'";
    $table_result = $conn->query($table_check_sql);
    
    if ($table_result->num_rows > 0) {
        // Messages table exists, get unread messages
        $messages_sql = "SELECT COUNT(*) as total FROM messages WHERE receiver_id = ? AND is_read = 0";
        $stmt = $conn->prepare($messages_sql);
        $stmt->bind_param("i", $organizer_id);
        $stmt->execute();
        $unread_messages = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    echo json_encode([
        "success" => true,
        "stats" => [
            "total_events" => (int)$total_events,
            "total_tickets_sold" => (int)$total_tickets_sold,
            "total_sales" => number_format($total_sales, 2),
            "pending_requests" => (int)$pending_requests,
            "upcoming_events" => (int)$upcoming_events,
            "unread_messages" => (int)$unread_messages
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_dashboard_stats.php: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Error loading dashboard stats: " . $e->getMessage()
    ]);
}
?> 