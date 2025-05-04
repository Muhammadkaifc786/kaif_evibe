<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../html/login.html');
    exit;
}

// Get event ID from URL
$event_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$event_id) {
    header('Location: ../html/organizer.html');
    exit;
}

// Get event details
$query = "SELECT e.*, u.fullname as organizer_name, 
          DATE_FORMAT(e.event_date, '%M %d, %Y') as formatted_date,
          c.name as category_name
          FROM events e 
          JOIN users u ON e.organizer_id = u.id 
          LEFT JOIN categories c ON e.category_id = c.category_id
          WHERE e.event_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$event = mysqli_fetch_assoc($result);

if (!$event) {
    header('Location: ../html/organizer.html');
    exit;
}

// Check if user is an organizer
$is_organizer = isset($_SESSION['role']) && $_SESSION['role'] === 'organizer';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> - EVibe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Import Google Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: url('../images/bg.jpg') no-repeat center center/cover;
            min-height: 100vh;
            color: #fff;
            position: relative;
        }

        .background-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: inherit;
            filter: blur(5px) brightness(0.7);
            z-index: -1;
        }

        .Nav-1 {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: rgba(0, 0, 0, 0.7);
            width: 100%;
            margin: 0;
        }

        .Nav-1 h1 {
            color: #ffcc00;
            font-weight: 600;
        }

        .Nav-Element {
            margin-left: 100px;
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .Nav-Element ul {
            list-style: none;
            display: flex;
            gap: 50px;
            padding: 0;
            margin: 0;
        }

        .Nav-Element ul a {
            text-decoration: none;
            color: #fff;
            transition: 0.3s;
        }

        .Nav-Element ul a:hover {
            color: #ffcc00;
        }

        .event-details-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }

        .event-header {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .event-image {
            width: 400px;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
        }

        .event-info {
            flex: 1;
        }

        .event-title {
            font-size: 2.5rem;
            color: #ffcc00;
            margin-bottom: 1rem;
        }

        .event-meta {
            display: grid;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .meta-item i {
            color: #ffcc00;
            font-size: 1.2rem;
        }

        .event-description {
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .ticket-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .ticket-price {
            font-size: 1.5rem;
            color: #ffcc00;
            margin-bottom: 1rem;
        }

        .ticket-availability {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .progress-bar {
            flex: 1;
            height: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            background: #ffcc00;
            transition: width 0.3s ease;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .book-btn {
            background-color: #ffcc00;
            color: #000;
        }

        .edit-btn {
            background-color: #ffcc00;
            color: white;
        }

        .back-btn {
            background-color: #6c757d;
            color: white;
        }

        .btn-logout {
            background-color: #ffcc00;
            color: black;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-left: 20px;
        }

        .btn-logout:hover {
            background-color: #ffd633;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 71, 87, 0.3);
        }
    </style>
</head>
<body>
    <div class="background-container"></div>
    
    <!-- Navigation -->
    <div class="Nav-1">
        <h1>EVibe</h1>
        
        <div class="Nav-Element">
            <ul>
                <a href="organizer.html" class="active"><li id="home">Home</li></a>
                <a href="post-event.html"><li id="post">Post Event</li></a>
                <a href="manage-event.html"><li id="manage">Manage Events</li></a>
                <a href="my_events.html"><li id="events">My Events</li></a>
                <a href="tickets.html"><li id="tickets">Tickets</li></a>
            </ul>
        </div>
        <button class="btn-logout" onclick="window.location.href='logout.php'">Logout</button>
    </div>

    <!-- Event Details -->
    <div class="event-details-container">
        <div class="event-header">
            <?php
            // Process image URL
            $imageUrl = $event['image_url'];
            if ($imageUrl && !str_starts_with($imageUrl, '/kaif_evibe/')) {
                if (!str_starts_with($imageUrl, '/')) {
                    $imageUrl = '/kaif_evibe/templates/' . $imageUrl;
                } else {
                    $imageUrl = '/kaif_evibe' . $imageUrl;
                }
            }
            ?>
            <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
                 alt="<?php echo htmlspecialchars($event['title']); ?>" 
                 class="event-image"
                 onerror="this.onerror=null; this.src='/kaif_evibe/templates/images/default-event.jpg';">
            <div class="event-info">
                <h1 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h1>
                <div class="event-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo htmlspecialchars($event['formatted_date']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($event['venue']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-tag"></i>
                        <span><?php echo htmlspecialchars($event['category_name']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span>Organizer: <?php echo htmlspecialchars($event['organizer_name']); ?></span>
                    </div>
                </div>
                <div class="event-description">
                    <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                </div>
                <div class="ticket-info">
                    <div class="ticket-price">
                        Price: $<?php echo number_format($event['ticket_price'], 2); ?>
                    </div>
                    <div class="ticket-availability">
                        <span>
                            <?php 
                            $ticketsSold = $event['total_tickets'] - $event['available_tickets'];
                            echo $ticketsSold . ' of ' . $event['total_tickets'] . ' tickets sold';
                            ?>
                        </span>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo ($ticketsSold / $event['total_tickets']) * 100; ?>%"></div>
                        </div>
                    </div>
                </div>
                <div class="action-buttons">
                    <?php if ($is_organizer): ?>
                        <button class="action-btn edit-btn" onclick="window.location.href='../html/edit-event.html?id=<?php echo $event_id; ?>'">Edit Event</button>
                        <button class="action-btn back-btn" onclick="window.location.href='../html/organizer.html'">Back to Events</button>
                    <?php else: ?>
                        <button class="action-btn book-btn" onclick="window.location.href='../html/book_event.html?id=<?php echo $event_id; ?>'">Book Now</button>
                        <button class="action-btn back-btn" onclick="window.location.href='../html/user_panel.html'">Back to Events</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 