<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an organizer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organizer') {
    header('Location: ../templates/html/login.html');
    exit();
}

$organizer_id = $_SESSION['user_id'];

// Get all events for the organizer
function getOrganizerEvents($conn, $organizer_id) {
    $sql = "SELECT e.*, c.name as category_name 
            FROM events e 
            JOIN categories c ON e.category_id = c.category_id 
            WHERE e.organizer_id = ? 
            ORDER BY e.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $organizer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        // Process image URL
        if (!empty($row['image_url'])) {
            // Log the original image URL
            error_log("Original image URL: " . $row['image_url']);
            
            // If the image URL doesn't start with a slash or http, make it absolute
            if (!preg_match('/^(http:\/\/|\/)/', $row['image_url'])) {
                // Remove any leading 'uploads/' or 'events/' from the path
                $image_path = preg_replace('/^(uploads\/|events\/)/', '', $row['image_url']);
                // Set the absolute path
                $row['image_url'] = '/kaif_evibe/templates/uploads/events/' . $image_path;
            } else {
                // If it's an absolute path, fix any incorrect paths
                $row['image_url'] = str_replace(
                    ['/evibe_database-update_with_php/', '/uploads/', '/events/'],
                    ['/kaif_evibe/', '/kaif_evibe/templates/uploads/events/', '/kaif_evibe/templates/uploads/events/'],
                    $row['image_url']
                );
            }
            
            // Log the processed image URL
            error_log("Processed image URL: " . $row['image_url']);
            
            // Check if the file exists, if not use default image
            $full_path = $_SERVER['DOCUMENT_ROOT'] . $row['image_url'];
            if (!file_exists($full_path)) {
                error_log("Image not found: " . $full_path);
                $row['image_url'] = '/kaif_evibe/templates/images/default-event.jpg';
            }
        } else {
            // Set default image if no image URL is provided
            $row['image_url'] = '/kaif_evibe/templates/images/default-event.jpg';
        }
        
        // Format date and price
        $row['formatted_date'] = date('F j, Y', strtotime($row['event_date']));
        $row['formatted_price'] = number_format($row['ticket_price'], 2);
        
        $events[] = $row;
    }
    
    return $events;
}

// Update event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update') {
        $event_id = $_POST['event_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $category_id = $_POST['category_id'];
        $event_date = $_POST['event_date'];
        $venue = $_POST['venue'];
        $ticket_price = $_POST['ticket_price'];
        $total_tickets = $_POST['total_tickets'];
        $available_tickets = $_POST['available_tickets'];

        // Handle image upload if new image is provided
        $image_url = $_POST['current_image'];
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/kaif_evibe/templates/uploads/events/';
            
            // Check if directory exists and is writable
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception("Failed to create upload directory: " . $upload_dir);
                }
            }
            
            if (!is_writable($upload_dir)) {
                // Try to change permissions
                if (!chmod($upload_dir, 0777)) {
                    throw new Exception("Upload directory is not writable. Current permissions: " . decoct(fileperms($upload_dir) & 0777));
                }
            }

            $file_extension = strtolower(pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_extension, $allowed_extensions)) {
                $file_name = uniqid() . '.' . $file_extension;
                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['event_image']['tmp_name'], $target_path)) {
                    // Store absolute path in database
                    $image_url = '/kaif_evibe/templates/uploads/events/' . $file_name;
                    
                    // Verify the file was moved successfully
                    if (!file_exists($target_path)) {
                        throw new Exception("Failed to verify uploaded file exists");
                    }
                } else {
                    throw new Exception("Failed to move uploaded file. Check PHP error log for details.");
                }
            } else {
                throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowed_extensions));
            }
        }

        try {
            $sql = "UPDATE events SET 
                    title = ?, description = ?, category_id = ?, 
                    event_date = ?, venue = ?, ticket_price = ?, 
                    total_tickets = ?, available_tickets = ?, image_url = ?
                    WHERE event_id = ? AND organizer_id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssisddiisi", $title, $description, $category_id, 
                            $event_date, $venue, $ticket_price, $total_tickets, 
                            $available_tickets, $image_url, $event_id, $organizer_id);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Event updated successfully'
                ]);
            } else {
                throw new Exception("Error updating event");
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    // Delete event
    else if ($_POST['action'] === 'delete') {
        $event_id = $_POST['event_id'];

        try {
            // First get the image URL to delete the file
            $sql = "SELECT image_url FROM events WHERE event_id = ? AND organizer_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $event_id, $organizer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $event = $result->fetch_assoc();

            // Delete the event
            $sql = "DELETE FROM events WHERE event_id = ? AND organizer_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $event_id, $organizer_id);
            
            if ($stmt->execute()) {
                // Delete the image file if it exists
                if ($event && $event['image_url']) {
                    // Convert absolute path to filesystem path
                    $image_path = $_SERVER['DOCUMENT_ROOT'] . $event['image_url'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Event deleted successfully'
                ]);
            } else {
                throw new Exception("Error deleting event");
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
// Get events for display
else {
    $events = getOrganizerEvents($conn, $organizer_id);
    echo json_encode([
        'success' => true,
        'events' => $events,
        'debug' => [
            'base_path' => '/kaif_evibe/templates/uploads/events/',
            'image_count' => count($events),
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'server_name' => $_SERVER['SERVER_NAME'],
            'request_uri' => $_SERVER['REQUEST_URI']
        ]
    ]);
}
?> 