<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Get user data
    $sql = "SELECT u.id, u.email, u.role, u.fullname 
            FROM users u 
            WHERE u.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Update session with latest user data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['fullname'] = $user['fullname'];
        
        echo json_encode([
            'logged_in' => true,
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'name' => $user['fullname']
        ]);
    } else {
        // User not found in database, destroy session
        session_destroy();
        echo json_encode([
            'logged_in' => false,
            'message' => 'User not found'
        ]);
    }
} catch (Exception $e) {
    session_destroy();
    echo json_encode([
        'logged_in' => false,
        'message' => 'Error checking session: ' . $e->getMessage()
    ]);
}
?> 