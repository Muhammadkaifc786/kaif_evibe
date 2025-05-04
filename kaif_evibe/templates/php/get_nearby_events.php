<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Prevent any output before headers
ob_start();

// Start session and include database connection
session_start();
require_once 'db_connection.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($success, $message = '', $data = null) {
    $response = ['success' => $success];
    if ($message) $response['message'] = $message;
    if ($data !== null) $response['data'] = $data;
    error_log("Sending response: " . json_encode($response));
    echo json_encode($response);
    exit();
}

// Function to calculate distance between two points using Haversine formula
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Radius of the earth in km

    $latDelta = deg2rad($lat2 - $lat1);
    $lonDelta = deg2rad($lon2 - $lon1);

    $a = sin($latDelta/2) * sin($latDelta/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lonDelta/2) * sin($lonDelta/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c;

    return $distance;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(false, 'User not logged in');
}

$user_id = $_SESSION['user_id'];
error_log("User ID: " . $user_id);

// Get user's location from POST data or database
if (isset($_POST['lat']) && isset($_POST['lng'])) {
    $user_lat = floatval($_POST['lat']);
    $user_lng = floatval($_POST['lng']);
    error_log("Using POST coordinates - User location: $user_lat, $user_lng");
} else {
    // Get user's location from database
    $user_query = "SELECT latitude, longitude FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$user['latitude'] || !$user['longitude']) {
        sendJsonResponse(false, 'User location not available');
    }

    $user_lat = floatval($user['latitude']);
    $user_lng = floatval($user['longitude']);
    error_log("Using database coordinates - User location: $user_lat, $user_lng");
}

// Get search radius from POST or use default
$radius = isset($_POST['radius']) ? floatval($_POST['radius']) : 10; // default 10km
error_log("Search radius: $radius km");

// Get all active or approved events
$events_query = "SELECT event_id, title, description, venue, event_date, image_url, ticket_price, latitude, longitude 
                FROM events 
                WHERE status IN ('active', 'approved')
                AND latitude IS NOT NULL 
                AND longitude IS NOT NULL";
$events_stmt = $conn->prepare($events_query);
$events_stmt->execute();
$all_events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);

error_log("Total events found: " . count($all_events));
error_log("All events: " . print_r($all_events, true));

$nearby_events = [];
foreach ($all_events as $event) {
    $event_lat = floatval($event['latitude']);
    $event_lng = floatval($event['longitude']);
    
    // Calculate distance
    $distance = calculateDistance($user_lat, $user_lng, $event_lat, $event_lng);
    
    error_log("Event {$event['event_id']} ({$event['title']}) - Distance: " . round($distance, 2) . " km");
    error_log("Event coordinates: $event_lat, $event_lng");
    error_log("User coordinates: $user_lat, $user_lng");

    // If coordinates are exactly the same or very close (within 0.0001 degrees)
    $is_exact_match = (abs($user_lat - $event_lat) < 0.0001 && abs($user_lng - $event_lng) < 0.0001);
    if ($is_exact_match) {
        error_log("Exact coordinate match found for event {$event['event_id']}");
    }

    // If distance is within radius (with a small buffer for floating point precision)
    if ($distance <= ($radius + 0.1) || $is_exact_match) {
        $event['distance'] = round($distance, 2);
        $event['formatted_date'] = date('F j, Y', strtotime($event['event_date']));
        $event['price'] = 'Rs. ' . number_format($event['ticket_price'], 2);
        $nearby_events[] = $event;
        error_log("Event {$event['event_id']} added to nearby events");
    }
}

error_log("Nearby events found: " . count($nearby_events));
error_log("Nearby events: " . print_r($nearby_events, true));

// Sort events by distance
usort($nearby_events, function($a, $b) {
    return $a['distance'] <=> $b['distance'];
});

// Send response with events in the data field
sendJsonResponse(true, '', ['events' => $nearby_events]);
?> 