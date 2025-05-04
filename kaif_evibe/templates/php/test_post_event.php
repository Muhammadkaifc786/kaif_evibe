<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Start output buffering
ob_start();

try {
    session_start();
    require_once 'config.php';

    // Log all incoming data
    error_log("POST Data: " . print_r($_POST, true));
    error_log("FILES Data: " . print_r($_FILES, true));

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "You must be logged in to post an event"]);
        exit();
    }

    // Get user ID from session
    $organizer_id = $_SESSION['user_id'];
    error_log("Organizer ID: " . $organizer_id);

    // Get form data
    $title = isset($_POST['title']) ? $_POST['title'] : '';
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $category_id = isset($_POST['category_id']) ? $_POST['category_id'] : '';
    $event_date = isset($_POST['event_date']) ? $_POST['event_date'] : '';
    $venue = isset($_POST['venue']) ? $_POST['venue'] : '';
    $ticket_price = isset($_POST['ticket_price']) ? $_POST['ticket_price'] : '';
    $total_tickets = isset($_POST['total_tickets']) ? $_POST['total_tickets'] : '';
    $google_maps_link = isset($_POST['google_maps_link']) ? $_POST['google_maps_link'] : null;
    $payment_methods = isset($_POST['payment_methods']) ? $_POST['payment_methods'] : [];

    // Log form data
    error_log("Title: " . $title);
    error_log("Description: " . $description);
    error_log("Category ID: " . $category_id);
    error_log("Event Date: " . $event_date);
    error_log("Venue: " . $venue);
    error_log("Ticket Price: " . $ticket_price);
    error_log("Total Tickets: " . $total_tickets);
    error_log("Google Maps Link: " . $google_maps_link);
    error_log("Payment Methods: " . print_r($payment_methods, true));

    // Validate required fields
    if (empty($title) || empty($description) || empty($category_id) || empty($event_date) || 
        empty($venue) || empty($ticket_price) || empty($total_tickets)) {
        echo json_encode(["success" => false, "message" => "All required fields must be filled"]);
        exit();
    }

    // Handle image upload
    $image_url = null;
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                error_log("Failed to create upload directory: " . $upload_dir);
                echo json_encode(["success" => false, "message" => "Failed to create upload directory"]);
                exit();
            }
        }
        
        $file_extension = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $target_file = $upload_dir . $file_name;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['event_image']['tmp_name'], $target_file)) {
            $image_url = 'uploads/' . $file_name;
            error_log("File successfully uploaded to: " . $target_file);
        } else {
            error_log("Failed to move uploaded file to: " . $target_file);
            echo json_encode(["success" => false, "message" => "Failed to upload image"]);
            exit();
        }
    } else {
        $error_message = isset($_FILES['event_image']) ? $_FILES['event_image']['error'] : 'No file uploaded';
        error_log("Image upload error: " . $error_message);
        echo json_encode(["success" => false, "message" => "Event image is required"]);
        exit();
    }

    // Set initial status to pending
    $status = 'pending';

    // Set available_tickets equal to total_tickets initially
    $available_tickets = $total_tickets;

    // Insert event into database
    $sql = "INSERT INTO events (organizer_id, title, description, category_id, event_date, venue, 
            ticket_price, total_tickets, available_tickets, image_url, status, google_maps_link) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    error_log("SQL Query: " . $sql);
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        exit();
    }

    $stmt->bind_param("isssssddiss", 
        $organizer_id, 
        $title, 
        $description, 
        $category_id, 
        $event_date, 
        $venue, 
        $ticket_price, 
        $total_tickets, 
        $available_tickets, 
        $image_url, 
        $status, 
        $google_maps_link
    );

    if (!$stmt->execute()) {
        echo json_encode(["success" => false, "message" => "Error inserting event: " . $stmt->error]);
        exit();
    }

    // Get the ID of the inserted event
    $event_id = $conn->insert_id;
    error_log("Event inserted successfully with ID: " . $event_id);

    echo json_encode(["success" => true, "message" => "Event posted successfully", "event_id" => $event_id]);

    $conn->close();
} catch (Exception $e) {
    // Catch any unhandled exceptions
    error_log("Unhandled exception in test_post_event.php: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "An unexpected error occurred: " . $e->getMessage()]);
} catch (Error $e) {
    // Catch any PHP errors
    error_log("PHP error in test_post_event.php: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "A PHP error occurred: " . $e->getMessage()]);
} finally {
    // Clear any output buffers
    ob_end_clean();
}
?> 