<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get message from request body
$data = json_decode(file_get_contents('php://input'), true);
$message = isset($data['message']) ? trim($data['message']) : '';

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Message is required']);
    exit;
}

// Convert message to lowercase for easier matching
$message = strtolower($message);

// Define responses based on keywords
$responses = [
    'hello' => 'Hi! How can I help you today?',
    'hi' => 'Hello! How can I assist you?',
    'help' => 'I can help you with:\n- Finding events\n- Booking tickets\n- Managing your profile\n- Payment issues\n- General inquiries\nWhat would you like to know?',
    'event' => 'I can help you find events. Would you like to:\n- Browse all events\n- Search by category\n- Find events near you\n- View trending events',
    'book' => 'To book an event:\n1. Find the event you want to attend\n2. Click "View Details"\n3. Select your ticket type and quantity\n4. Proceed to payment\nWould you like me to help you find an event?',
    'price' => 'Event prices vary based on the event type and ticket category. You can find the price for each event on its details page. Would you like me to help you find events within a specific price range?',
    'location' => 'I can help you find events in your area. Would you like to:\n- View events in your city\n- Search by venue\n- Find events near your location',
    'date' => 'I can help you find events on specific dates. Would you like to:\n- View today\'s events\n- See events this week\n- Browse events this month\n- Search for a specific date',
    'payment' => 'We accept various payment methods including credit cards and digital wallets. If you\'re having trouble with payment, please contact our support team at support@evibe.com',
    'profile' => 'To manage your profile:\n1. Click on your profile picture\n2. Select "Profile"\n3. Edit your information\n4. Save changes\nWould you like me to help you with anything specific in your profile?',
    'favorite' => 'To add an event to your favorites:\n1. Find the event you like\n2. Click the heart icon\n3. The event will be saved to your favorites\nYou can view your favorite events in the "Favorites" section.',
    'contact' => 'You can contact us through:\n- Email: support@evibe.com\n- Phone: +92 300 1234567\n- Live chat: Available 24/7\nHow can we help you?',
    'organizer' => 'To become an organizer:\n1. Create an account\n2. Click "Become an Organizer"\n3. Fill out the application form\n4. Submit for review\nWould you like me to help you with the application process?',
    'refund' => 'Our refund policy varies by event. Generally:\n- Refunds are available up to 24 hours before the event\n- Processing time is 5-7 business days\n- Some events may have non-refundable tickets\nWould you like to know more about our refund policy?'
];

// Find matching response
$response = '';
foreach ($responses as $keyword => $reply) {
    if (strpos($message, $keyword) !== false) {
        $response = $reply;
        break;
    }
}

// If no keyword match found, provide a default response
if (empty($response)) {
    $response = "I'm not sure I understand. Could you please rephrase your question? You can also try asking about:\n- Events and bookings\n- Profile management\n- Payment issues\n- General help";
}

// Log the conversation
$logQuery = "INSERT INTO chatbot_logs (user_id, message, response) VALUES (?, ?, ?)";
$stmt = $conn->prepare($logQuery);
$stmt->bind_param('iss', $_SESSION['user_id'], $message, $response);
$stmt->execute();

// Return response
header('Content-Type: application/json');
echo json_encode([
    'response' => $response
]); 