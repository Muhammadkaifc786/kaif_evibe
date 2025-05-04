<?php
session_start();
require_once 'config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    
    // Prevent deleting the default admin
    if ($user_id == 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete the default admin']);
        exit();
    }

    $sql = "DELETE FROM users WHERE id = '$user_id'";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?> 