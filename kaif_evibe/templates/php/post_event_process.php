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

    // Check if user is logged in and is an organizer
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organizer') {
        echo json_encode(["success" => false, "message" => "You must be logged in as an organizer to post events"]);
        exit();
    }

    // Get organizer ID from session
    $organizer_id = $_SESSION['user_id'];

    // Log incoming data for debugging
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));

    // Validate required fields
    $required_fields = ['title', 'description', 'category_id', 'event_date', 'venue', 
                       'ticket_price', 'total_tickets', 'latitude', 'longitude'];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(["success" => false, "message" => "Missing required field: " . $field]);
            exit();
        }
    }
    
    // Validate event date (must be at least 1 hour in the future)
    $event_date = new DateTime($_POST['event_date']);
    $now = new DateTime();
    $now->modify('+1 hour'); // Add 1 hour buffer
    
    if ($event_date <= $now) {
        echo json_encode(["success" => false, "message" => "Event date must be at least 1 hour in the future"]);
        exit();
    }

    // Validate image upload
    if (!isset($_FILES['event_image']) || $_FILES['event_image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["success" => false, "message" => "Please upload an event image"]);
        exit();
    }

    // Validate payment methods
    if (!isset($_POST['payment_methods']) || empty($_POST['payment_methods'])) {
        echo json_encode(["success" => false, "message" => "Please select at least one payment method"]);
        exit();
    }

    // Handle image upload
    $upload_dir = '../uploads/';
    
    // Check if upload directory exists, if not create it
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            error_log("Failed to create upload directory: " . $upload_dir);
            echo json_encode(["success" => false, "message" => "Failed to create upload directory"]);
            exit();
        }
        // Set proper permissions after creation
        chmod($upload_dir, 0777);
    }

    // Check if directory is writable
    if (!is_writable($upload_dir)) {
        error_log("Upload directory is not writable: " . $upload_dir);
        echo json_encode(["success" => false, "message" => "Upload directory is not writable"]);
        exit();
    }

    $file_extension = strtolower(pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($file_extension, $allowed_extensions)) {
        echo json_encode(["success" => false, "message" => "Invalid file type. Allowed types: JPG, JPEG, PNG, GIF"]);
        exit();
    }

    $filename = uniqid() . '.' . $file_extension;
    $target_path = $upload_dir . $filename;

    // Log upload attempt
    error_log("Attempting to upload file to: " . $target_path);

    if (!move_uploaded_file($_FILES['event_image']['tmp_name'], $target_path)) {
        error_log("Failed to move uploaded file. Upload error: " . $_FILES['event_image']['error']);
        echo json_encode(["success" => false, "message" => "Error uploading image. Please try again."]);
        exit();
    }

    // Set proper permissions for the uploaded file
    chmod($target_path, 0644);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert event into database
        $sql = "INSERT INTO events (organizer_id, title, description, category_id, event_date, 
                venue, ticket_price, total_tickets, available_tickets, image_url, status, latitude, longitude) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $image_url = 'uploads/' . $filename;
        $available_tickets = $_POST['total_tickets'];
        
        $stmt->bind_param("ississsiissd", 
            $organizer_id,
            $_POST['title'],
            $_POST['description'],
            $_POST['category_id'],
            $_POST['event_date'],
            $_POST['venue'],
            $_POST['ticket_price'],
            $_POST['total_tickets'],
            $available_tickets,
            $image_url,
            $_POST['latitude'],
            $_POST['longitude']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting event: " . $stmt->error);
        }

        $event_id = $conn->insert_id;

        // Insert payment methods
        $payment_methods = $_POST['payment_methods'];
        $sql = "INSERT INTO event_payment_methods (event_id, method_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);

        foreach ($payment_methods as $method_id) {
            $stmt->bind_param("ii", $event_id, $method_id);
            if (!$stmt->execute()) {
                throw new Exception("Error adding payment method");
            }
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            "success" => true,
            "message" => "Event posted successfully and pending admin approval",
            "event_id" => $event_id
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in post_event_process.php: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Error posting event: " . $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
    ob_end_flush();
}
?> 