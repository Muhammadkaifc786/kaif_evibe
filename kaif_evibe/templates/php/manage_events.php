<?php
// Enable error reporting but suppress display
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/Applications/XAMPP/xamppfiles/logs/php_error.log');

// Set content type to JSON
header('Content-Type: application/json');

// Start output buffering
ob_start();

// Log the start of the script
error_log("manage_events.php started");

try {
    session_start();
    require_once __DIR__ . '/config.php';
    
    // Log session and connection status
    error_log("Session started, user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
    error_log("Database connection: " . ($conn ? "successful" : "failed"));
    error_log("POST data received: " . print_r($_POST, true));

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("You must be logged in to view events");
    }

    // Get organizer ID from session
    $organizer_id = $_SESSION['user_id'];
    error_log("Organizer ID: " . $organizer_id);

    // Test database connection
    $test_query = "SELECT 1";
    $test_result = $conn->query($test_query);
    if (!$test_result) {
        throw new Exception("Database connection test failed: " . $conn->error);
    }
    error_log("Database connection test successful");

    // Handle POST requests (for delete action)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        error_log("POST request received");
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));
        
        if (!isset($_POST['action'])) {
            throw new Exception("No action specified");
        }

        if ($_POST['action'] === 'delete') {
            // Check if event_id is provided
            if (!isset($_POST['event_id']) || !is_numeric($_POST['event_id'])) {
                throw new Exception("Invalid event ID");
            }

            $event_id = intval($_POST['event_id']);

            // Verify that the event belongs to this organizer
            $verify_sql = "SELECT event_id FROM events WHERE event_id = ? AND organizer_id = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->bind_param("ii", $event_id, $organizer_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();

            if ($verify_result->num_rows === 0) {
                throw new Exception("You don't have permission to delete this event");
            }

            // Begin transaction
            $conn->begin_transaction();

            try {
                // Delete payment methods associated with this event
                $delete_payments_sql = "DELETE FROM event_payment_methods WHERE event_id = ?";
                $delete_payments_stmt = $conn->prepare($delete_payments_sql);
                $delete_payments_stmt->bind_param("i", $event_id);
                $delete_payments_stmt->execute();

                // Delete the event
                $delete_event_sql = "DELETE FROM events WHERE event_id = ? AND organizer_id = ?";
                $delete_event_stmt = $conn->prepare($delete_event_sql);
                $delete_event_stmt->bind_param("ii", $event_id, $organizer_id);
                $delete_event_stmt->execute();

                // Commit transaction
                $conn->commit();

                echo json_encode(["success" => true, "message" => "Event deleted successfully"]);
                exit();
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                throw $e;
            }
        } elseif ($_POST['action'] === 'update') {
            if (!isset($_POST['event_id']) || !is_numeric($_POST['event_id'])) {
                throw new Exception("Invalid event ID: " . (isset($_POST['event_id']) ? $_POST['event_id'] : 'not set'));
            }

            // Validate required fields
            $required_fields = ['title', 'description', 'category_id', 'event_date', 'venue', 'ticket_price', 'total_tickets', 'available_tickets', 'current_image'];
            foreach ($required_fields as $field) {
                if (!isset($_POST[$field]) || ($_POST[$field] === '' && $field !== 'description')) {
                    throw new Exception("Missing required field: " . $field);
                }
            }

            $event_id = intval($_POST['event_id']);
            error_log("Updating event ID: " . $event_id);

            // Begin transaction
            $conn->begin_transaction();

            try {
                // Handle image upload if a new image is provided
                $image_url = $_POST['current_image']; // Default to current image
                error_log("Current image: " . $image_url);
                
                if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
                    error_log("Processing image upload...");
                    $upload_dir = '../uploads/events/';
                    
                    // Check if directory exists and is writable
                    if (!file_exists($upload_dir)) {
                        error_log("Upload directory does not exist, creating: " . $upload_dir);
                        if (!mkdir($upload_dir, 0777, true)) {
                            error_log("Failed to create upload directory: " . $upload_dir);
                            throw new Exception("Failed to create upload directory");
                        }
                    }
                    
                    if (!is_writable($upload_dir)) {
                        error_log("Upload directory is not writable: " . $upload_dir);
                        throw new Exception("Upload directory is not writable");
                    }

                    $file_extension = strtolower(pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                    if (!in_array($file_extension, $allowed_extensions)) {
                        error_log("Invalid file extension: " . $file_extension);
                        throw new Exception("Invalid file type. Allowed types: JPG, JPEG, PNG, GIF");
                    }

                    $new_filename = uniqid() . '.' . $file_extension;
                    $target_file = $upload_dir . $new_filename;
                    error_log("Attempting to move uploaded file to: " . $target_file);

                    if (!move_uploaded_file($_FILES['event_image']['tmp_name'], $target_file)) {
                        error_log("Failed to move uploaded file. Upload error: " . $_FILES['event_image']['error']);
                        throw new Exception("Failed to upload image");
                    }

                    // Delete old image if it exists and is not the default image
                    if (!empty($_POST['current_image']) && $_POST['current_image'] !== 'default.jpg') {
                        $old_file = '../' . $_POST['current_image'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                    $image_url = 'uploads/events/' . $new_filename;
                    error_log("Image successfully uploaded: " . $image_url);
                }

                // Convert and validate numeric values
                $category_id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
                $ticket_price = filter_var($_POST['ticket_price'], FILTER_VALIDATE_FLOAT);
                $total_tickets = filter_var($_POST['total_tickets'], FILTER_VALIDATE_INT);
                $available_tickets = filter_var($_POST['available_tickets'], FILTER_VALIDATE_INT);
                
                if ($category_id === false || $ticket_price === false || $total_tickets === false || $available_tickets === false) {
                    throw new Exception("Invalid numeric values provided");
                }

                // Format the event date
                $event_date = date('Y-m-d H:i:s', strtotime($_POST['event_date']));
                if ($event_date === false) {
                    throw new Exception("Invalid date format");
                }

                // Update event details
                $update_sql = "UPDATE events SET 
                    title = ?, 
                    description = ?, 
                    category_id = ?, 
                    event_date = ?, 
                    venue = ?, 
                    ticket_price = ?, 
                    total_tickets = ?, 
                    available_tickets = ?,
                    image_url = ?
                    WHERE event_id = ? AND organizer_id = ?";

                $update_stmt = $conn->prepare($update_sql);
                if (!$update_stmt) {
                    throw new Exception("Database prepare error: " . $conn->error);
                }

                error_log("Binding parameters for update...");
                $update_stmt->bind_param(
                    "ssisddiisii",
                    $_POST['title'],
                    $_POST['description'],
                    $category_id,
                    $event_date,
                    $_POST['venue'],
                    $ticket_price,
                    $total_tickets,
                    $available_tickets,
                    $image_url,
                    $event_id,
                    $organizer_id
                );

                if (!$update_stmt->execute()) {
                    throw new Exception("Error executing update: " . $update_stmt->error);
                }

                if ($update_stmt->affected_rows === 0) {
                    throw new Exception("No changes were made to the event");
                }

                // Commit transaction
                $conn->commit();
                error_log("Event updated successfully");

                echo json_encode([
                    "success" => true,
                    "message" => "Event updated successfully"
                ]);
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                throw new Exception("Error updating event: " . $e->getMessage());
            }
        }
    }

    // Handle GET requests (for fetching events)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Check if we're getting a single event
        if (isset($_GET['event_id'])) {
            // Get single event
            $event_id = intval($_GET['event_id']);
            
            $sql = "SELECT e.*, c.name as category_name 
                    FROM events e 
                    LEFT JOIN categories c ON e.category_id = c.category_id 
                    WHERE e.event_id = ? AND e.organizer_id = ?";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $stmt->bind_param("ii", $event_id, $organizer_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode([
                    "success" => false,
                    "message" => "Event not found"
                ]);
                exit();
            }

            $event = $result->fetch_assoc();
            
            // Format the event date
            $event_date = new DateTime($event['event_date']);
            $event['formatted_date'] = $event_date->format('F j, Y g:i A');
            
            // Format the ticket price
            $event['formatted_price'] = number_format($event['ticket_price'], 2);
            
            // Calculate available tickets percentage
            $event['available_percentage'] = ($event['total_tickets'] > 0) 
                ? round(($event['available_tickets'] / $event['total_tickets']) * 100) 
                : 0;

            // Get payment methods for this event
            $payment_sql = "SELECT pm.name 
                           FROM event_payment_methods epm 
                           JOIN payment_methods pm ON epm.method_id = pm.method_id 
                           WHERE epm.event_id = ?";
            
            $payment_stmt = $conn->prepare($payment_sql);
            $payment_stmt->bind_param("i", $event_id);
            $payment_stmt->execute();
            $payment_result = $payment_stmt->get_result();
            
            $payment_methods = [];
            while ($payment_row = $payment_result->fetch_assoc()) {
                $payment_methods[] = $payment_row['name'];
            }
            $event['payment_methods'] = $payment_methods;
            
            echo json_encode([
                "success" => true,
                "event" => $event
            ]);
            exit();
        }
        
        // Get status filter if provided
        $status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
        
        // Get search query if provided
        $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        // Base query
        $query = "SELECT e.*, c.name as category_name 
                  FROM events e 
                  JOIN categories c ON e.category_id = c.category_id 
                  WHERE e.organizer_id = ?";
        
        // Add status filter if provided
        if (!empty($status_filter)) {
            $query .= " AND e.status = ?";
        }
        
        // Add search condition if search term is provided
        if (!empty($search_query)) {
            $query .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.venue LIKE ?)";
        }
        
        // Add ordering
        $query .= " ORDER BY e.event_date DESC";
        
        // Prepare the statement
        $stmt = $conn->prepare($query);
        
        // Bind parameters
        $param_types = "i";
        $params = [$organizer_id];
        
        if (!empty($status_filter)) {
            $param_types .= "s";
            $params[] = $status_filter;
        }
        
        if (!empty($search_query)) {
            $param_types .= "sss";
            $search_param = "%$search_query%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        $stmt->bind_param($param_types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            // Format date
            $event_date = new DateTime($row['event_date']);
            $row['formatted_date'] = $event_date->format('F j, Y g:i A');
            
            // Format price
            $row['formatted_price'] = number_format($row['ticket_price'], 2);
            
            $events[] = $row;
        }
        
        echo json_encode([
            "success" => true,
            "events" => $events
        ]);
        exit();
    } else {
        // Get all events for this organizer
        $sql = "SELECT e.*, c.name as category_name 
                FROM events e 
                LEFT JOIN categories c ON e.category_id = c.category_id 
                WHERE e.organizer_id = ? 
                ORDER BY e.created_at DESC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("i", $organizer_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $events = [];

        while ($row = $result->fetch_assoc()) {
            // Format the event date
            $event_date = new DateTime($row['event_date']);
            $row['formatted_date'] = $event_date->format('F j, Y g:i A');
            
            // Format the ticket price
            $row['formatted_price'] = number_format($row['ticket_price'], 2);
            
            // Calculate available tickets percentage
            $row['available_percentage'] = ($row['total_tickets'] > 0) 
                ? round(($row['available_tickets'] / $row['total_tickets']) * 100) 
                : 0;

            // Get payment methods for this event
            $payment_sql = "SELECT pm.name 
                           FROM event_payment_methods epm 
                           JOIN payment_methods pm ON epm.method_id = pm.method_id 
                           WHERE epm.event_id = ?";
            
            $payment_stmt = $conn->prepare($payment_sql);
            $payment_stmt->bind_param("i", $row['event_id']);
            $payment_stmt->execute();
            $payment_result = $payment_stmt->get_result();
            
            $payment_methods = [];
            while ($payment_row = $payment_result->fetch_assoc()) {
                $payment_methods[] = $payment_row['name'];
            }
            $row['payment_methods'] = $payment_methods;

            $events[] = $row;
        }

        echo json_encode([
            "success" => true,
            "events" => $events
        ]);
    }

} catch (Exception $e) {
    error_log("Error in manage_events.php: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
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