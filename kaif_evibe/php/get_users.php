<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

try {
    // Get all users from the database
    $sql = "SELECT id, fullname, email, role, status FROM users ORDER BY id DESC";
    $result = $conn->query($sql);
    
    if ($result) {
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
    } else {
        throw new Exception('Error fetching users: ' . $conn->error);
    }
} catch (Exception $e) {
    error_log("Error in get_users.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching users'
    ]);
}

$conn->close();
?> 