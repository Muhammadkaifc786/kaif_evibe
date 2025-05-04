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
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $password = $_POST['password'];

    // Check if email already exists for other users
    $check_email = "SELECT * FROM users WHERE email = '$email' AND id != '$user_id'";
    $result = $conn->query($check_email);

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }

    // Update user data
    if (!empty($password)) {
        // If password is provided, update with new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET 
                fullname = '$fullname',
                email = '$email',
                contact_number = '$contact_number',
                address = '$address',
                role = '$role',
                password = '$hashed_password'
                WHERE id = '$user_id'";
    } else {
        // If no password provided, update without changing password
        $sql = "UPDATE users SET 
                fullname = '$fullname',
                email = '$email',
                contact_number = '$contact_number',
                address = '$address',
                role = '$role'
                WHERE id = '$user_id'";
    }

    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating user: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?> 