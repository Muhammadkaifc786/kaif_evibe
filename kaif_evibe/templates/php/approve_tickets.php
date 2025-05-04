<?php
require_once "config.php";

// Function to get all pending ticket requests for an event
function getPendingTicketRequests($conn, $event_id) {
    $sql = "SELECT tr.*, u.name as user_name, u.email as user_email 
            FROM ticket_requests tr 
            JOIN users u ON tr.user_id = u.id 
            WHERE tr.event_id = ? AND tr.status = 'pending'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result;
}

// Function to approve a ticket request
function approveTicketRequest($conn, $request_id, $event_id) {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get ticket request details
        $sql = "SELECT * FROM ticket_requests WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $request_id);
        mysqli_stmt_execute($stmt);
        $request = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        
        // Check if there are enough tickets available
        $sql = "SELECT total_tickets, sold_tickets FROM events WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $event_id);
        mysqli_stmt_execute($stmt);
        $event = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        
        if ($event['total_tickets'] - $event['sold_tickets'] >= $request['quantity']) {
            // Update ticket request status
            $sql = "UPDATE ticket_requests SET status = 'approved' WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $request_id);
            mysqli_stmt_execute($stmt);
            
            // Update event sold tickets
            $sql = "UPDATE events SET sold_tickets = sold_tickets + ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $request['quantity'], $event_id);
            mysqli_stmt_execute($stmt);
            
            mysqli_commit($conn);
            return true;
        } else {
            mysqli_rollback($conn);
            return false;
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return false;
    }
}

// Function to reject a ticket request
function rejectTicketRequest($conn, $request_id) {
    $sql = "UPDATE ticket_requests SET status = 'rejected' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    return mysqli_stmt_execute($stmt);
}

// Handle API requests
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET['action']) && $_GET['action'] == 'get_requests') {
        $event_id = $_GET['event_id'];
        $requests = getPendingTicketRequests($conn, $event_id);
        $requests_array = [];
        while ($row = mysqli_fetch_assoc($requests)) {
            $requests_array[] = $row;
        }
        echo json_encode($requests_array);
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve':
                $request_id = $_POST['request_id'];
                $event_id = $_POST['event_id'];
                if (approveTicketRequest($conn, $request_id, $event_id)) {
                    echo json_encode(["success" => true, "message" => "Ticket request approved successfully"]);
                } else {
                    echo json_encode(["success" => false, "message" => "Error approving ticket request"]);
                }
                break;
                
            case 'reject':
                $request_id = $_POST['request_id'];
                if (rejectTicketRequest($conn, $request_id)) {
                    echo json_encode(["success" => true, "message" => "Ticket request rejected successfully"]);
                } else {
                    echo json_encode(["success" => false, "message" => "Error rejecting ticket request"]);
                }
                break;
        }
    }
}
?> 