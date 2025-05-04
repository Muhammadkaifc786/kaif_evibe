<?php
require_once "config.php";

// Function to get all events for an organizer
function getOrganizerEvents($conn, $organizer_id) {
    $sql = "SELECT * FROM events WHERE organizer_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $organizer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result;
}

// Function to get a single event by ID
function getEventById($conn, $event_id) {
    $sql = "SELECT * FROM events WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Function to update an event
function updateEvent($conn, $event_id, $event_name, $event_date, $event_time, $venue, $description, $ticket_price, $total_tickets) {
    $sql = "UPDATE events SET event_name = ?, event_date = ?, event_time = ?, venue = ?, description = ?, ticket_price = ?, total_tickets = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssddii", $event_name, $event_date, $event_time, $venue, $description, $ticket_price, $total_tickets, $event_id);
    return mysqli_stmt_execute($stmt);
}

// Function to delete an event
function deleteEvent($conn, $event_id) {
    $sql = "DELETE FROM events WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    return mysqli_stmt_execute($stmt);
}

// Handle API requests
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'get_events':
                $organizer_id = $_GET['organizer_id'];
                $events = getOrganizerEvents($conn, $organizer_id);
                $events_array = [];
                while ($row = mysqli_fetch_assoc($events)) {
                    $events_array[] = $row;
                }
                echo json_encode($events_array);
                break;
                
            case 'get_event':
                $event_id = $_GET['event_id'];
                $event = getEventById($conn, $event_id);
                echo json_encode($event);
                break;
        }
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                $event_id = $_POST['event_id'];
                $event_name = $_POST['event_name'];
                $event_date = $_POST['event_date'];
                $event_time = $_POST['event_time'];
                $venue = $_POST['venue'];
                $description = $_POST['description'];
                $ticket_price = $_POST['ticket_price'];
                $total_tickets = $_POST['total_tickets'];
                
                if (updateEvent($conn, $event_id, $event_name, $event_date, $event_time, $venue, $description, $ticket_price, $total_tickets)) {
                    echo json_encode(["success" => true, "message" => "Event updated successfully"]);
                } else {
                    echo json_encode(["success" => false, "message" => "Error updating event"]);
                }
                break;
                
            case 'delete':
                $event_id = $_POST['event_id'];
                if (deleteEvent($conn, $event_id)) {
                    echo json_encode(["success" => true, "message" => "Event deleted successfully"]);
                } else {
                    echo json_encode(["success" => false, "message" => "Error deleting event"]);
                }
                break;
        }
    }
}
?> 