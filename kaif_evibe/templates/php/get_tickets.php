<?php
// Include database connection file
require_once 'db_connect.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$organizerId = $userId; // Assuming organizer_id is the same as user_id for organizers

try {
    // Create a database connection
    $conn = connectDB();
    
    // Get search parameters
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    
    // Debug log
    error_log("Search term: " . $search);
    
    // Base query
    $query = "SELECT b.*, e.title as event_title,e.event_id, e.venue as location, e.event_date, 
                     u.fullname as user_name, u.email as user_email, u.contact_number as user_phone,
                     c.name as category_name, e.category_id, e.image_url, e.description as event_description
              FROM bookings b
              JOIN events e ON b.event_id = e.event_id
              JOIN users u ON b.user_id = u.id
              JOIN categories c ON e.category_id = c.category_id
              WHERE e.organizer_id = ?";
    
    $params = array($organizerId);
    
    // Add search condition
    if (!empty($search)) {
        $query .= " AND e.title LIKE ?";
        $searchParam = $search . "%";
        $params[] = $searchParam;
    }
    
    // Add category filter
    if (!empty($category)) {
        $query .= " AND e.category_id = ?";
        $params[] = $category;
    }
    
    // Add date filter
    if (!empty($date_filter)) {
        switch ($date_filter) {
            case 'today':
                $query .= " AND DATE(e.event_date) = CURDATE()";
                break;
            case 'week':
                $query .= " AND YEARWEEK(e.event_date) = YEARWEEK(CURDATE())";
                break;
            case 'month':
                $query .= " AND MONTH(e.event_date) = MONTH(CURDATE()) AND YEAR(e.event_date) = YEAR(CURDATE())";
                break;
            case 'upcoming':
                $query .= " AND e.event_date >= CURDATE()";
                break;
            case 'past':
                $query .= " AND e.event_date < CURDATE()";
                break;
        }
    }
    
    // Add status filter
    if (!empty($status)) {
        $query .= " AND b.status = ?";
        $params[] = $status;
    }
    
    // Order by event date
    $query .= " ORDER BY e.event_date DESC";
    
    // Debug log
    error_log("Final query: " . $query);
    error_log("Final params: " . print_r($params, true));
    
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    
    // Fetch all tickets
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug log
    error_log("Number of tickets found: " . count($tickets));
    
    // Format the data for display
    $formattedTickets = [];
    foreach ($tickets as $ticket) {
        // Fix image path - check if it already has a full path
        $imageUrl = $ticket['image_url'];
        if ($imageUrl && !preg_match('/^(http|https):\/\/|^\//i', $imageUrl)) {
            // Check if image path already contains 'uploads/'
            if (strpos($imageUrl, 'uploads/') === 0) {
                // If it starts with 'uploads/', just add the prefix
                $imageUrl = '/kaif_evibe/templates/' . $imageUrl;
            } else {
                // Otherwise, add the full path
                $imageUrl = '/kaif_evibe/templates/uploads/' . $imageUrl;
            }
        } else if (!$imageUrl) {
            // Use default image if no image
            $imageUrl = '/kaif_evibe/templates/images/default-event.png';
        }
        
        $formattedTickets[] = [
            'booking_id' => $ticket['booking_id'],
            'ticket_id' => $ticket['ticket_id'],
            'event_id' => $ticket['event_id'],
            'status' => $ticket['status'],
            'event_title' => $ticket['event_title'],
            'event_description' => $ticket['event_description'],
            'event_date' => date('F d, Y', strtotime($ticket['event_date'])),
            'event_time' => date('h:i A', strtotime($ticket['event_date'])),
            'location' => $ticket['location'],
            'category' => $ticket['category_name'],
            'category_id' => $ticket['category_id'],
            'image_url' => $imageUrl,
            'user_name' => $ticket['user_name'],
            'user_email' => $ticket['user_email'],
            'user_phone' => $ticket['user_phone'] ?? 'Not provided',
            'ticket_count' => $ticket['ticket_count'],
            'seat_info' => 'General Admission',
            'total_price' => '$' . number_format($ticket['total_price'], 2),
            'booking_date' => date('F d, Y', strtotime($ticket['booking_date'])),
            'attendance_date' => $ticket['attendance_date'] ? date('F d, Y', strtotime($ticket['attendance_date'])) : null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'tickets' => $formattedTickets
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 