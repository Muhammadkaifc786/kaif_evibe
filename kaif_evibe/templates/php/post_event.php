<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $event_name = $_POST['event_name'];
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $venue = $_POST['venue'];
    $description = $_POST['description'];
    $ticket_price = $_POST['ticket_price'];
    $total_tickets = $_POST['total_tickets'];
    $organizer_id = $_SESSION['user_id'];
    
    // Handle image upload
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["event_image"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    
    // Check if image file is actual image or fake image
    $check = getimagesize($_FILES["event_image"]["tmp_name"]);
    if($check === false) {
        die("File is not an image.");
    }
    
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        die("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
    }
    
    // Upload image
    if (move_uploaded_file($_FILES["event_image"]["tmp_name"], $target_file)) {
        // Prepare SQL statement
        $sql = "INSERT INTO events (event_name, event_date, event_time, venue, description, ticket_price, total_tickets, organizer_id, image_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ssssddiis", $event_name, $event_date, $event_time, $venue, $description, $ticket_price, $total_tickets, $organizer_id, $target_file);
            
            if(mysqli_stmt_execute($stmt)){
                echo json_encode(["success" => true, "message" => "Event posted successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Error posting event"]);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Error uploading image"]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Event - EVibe</title>
    <link rel="stylesheet" href="organizer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="background-container"></div>
    <div class="main-1">
        <div class="Nav-1">
            <h1>EVibe</h1>
            <div class="Nav-Element">
                <ul>
                    <a href="organizer.php"><li id="home">Home</li></a>
                    <a href="post_event.php"><li id="post">Post Event</li></a>
                    <a href="manage_event.php"><li id="manage">Manage Events</li></a>
                    <a href="my_events.php"><li id="events">My Events</li></a>
                </ul>
            </div>
            <button class="btn-logout">Logout</button>
        </div>

        <div class="post-event-form">
            <h2>Post New Event</h2>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>
            
            <form action="post_event.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="event_name">Event Name</label>
                    <input type="text" id="event_name" name="event_name" required>
                </div>

                <div class="form-group">
                    <label for="event_date">Date</label>
                    <input type="date" id="event_date" name="event_date" required>
                </div>

                <div class="form-group">
                    <label for="event_time">Time</label>
                    <input type="time" id="event_time" name="event_time" required>
                </div>

                <div class="form-group">
                    <label for="venue">Venue</label>
                    <input type="text" id="venue" name="venue" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="ticket_price">Ticket Price ($)</label>
                    <input type="number" id="ticket_price" name="ticket_price" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="total_tickets">Total Tickets</label>
                    <input type="number" id="total_tickets" name="total_tickets" required>
                </div>

                <div class="form-group">
                    <label for="event_image">Event Image</label>
                    <input type="file" id="event_image" name="event_image" accept="image/*" required>
                </div>

                <button type="submit" class="submit-btn">Post Event</button>
            </form>
        </div>
    </div>
</body>
</html> 