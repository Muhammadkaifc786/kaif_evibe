<?php
// Include database connection
require_once __DIR__ . '/../templates/php/db_connect.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/login_errors.log');

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log the request
error_log("Login attempt received");
error_log("Session ID before login: " . session_id());
error_log("Session data before login: " . print_r($_SESSION, true));

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and sanitize input
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    error_log("Missing email or password");
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

try {
    // Check database connection
    if (!isset($conn) || $conn->connect_error) {
        error_log("Database connection error: " . ($conn->connect_error ?? 'Connection not established'));
        throw new Exception("Database connection error");
    }

    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    if (!$stmt) {
        error_log("Prepare statement error: " . $conn->error);
        throw new Exception("Database error");
    }

    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        error_log("Execute error: " . $stmt->error);
        throw new Exception("Database error");
    }

    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("User not found: " . $email);
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        error_log("Invalid password for user: " . $email);
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }
    
    // Set session data using the setSessionData function
    setSessionData($user['id'], $user['name'], $user['email'], $user['role']);
    
    // Log successful login
    error_log("User logged in successfully: " . $email);
    error_log("Session ID after login: " . session_id());
    error_log("Session data after login: " . print_r($_SESSION, true));
    
    // Return success response with user data and redirect URL
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ],
        'redirect' => $user['role'] === 'organizer' ? '/kaif_evibe/organizer.php' : 
                     ($user['role'] === 'admin' ? '/kaif_evibe/templates/html/admin_panel.html' : 
                     '/kaif_evibe/templates/html/user_panel.html')
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'An error occurred during login']);
} 