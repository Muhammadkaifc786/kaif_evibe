<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an organizer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organizer') {
    header('Location: ../templates/html/login.html');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $organizer_id = $_SESSION['user_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $event_date = $_POST['event_date'];
    $venue = $_POST['venue'];
    $ticket_price = $_POST['ticket_price'];
    $total_tickets = $_POST['total_tickets'];
    $available_tickets = $total_tickets; // Initially all tickets are available

    // Handle image upload
    $image_url = '';
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/events/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['event_image']['tmp_name'], $target_path)) {
                $image_url = 'uploads/events/' . $file_name;
            }
        }
    }

    try {
        $sql = "INSERT INTO events (organizer_id, title, description, category_id, event_date, 
                venue, ticket_price, total_tickets, available_tickets, image_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issisddiis", $organizer_id, $title, $description, $category_id, 
                         $event_date, $venue, $ticket_price, $total_tickets, 
                         $available_tickets, $image_url);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Event posted successfully and pending admin approval'
            ]);
        } else {
            throw new Exception("Error posting event");
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?> 