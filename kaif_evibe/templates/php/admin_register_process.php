<?php
session_start();
require_once 'config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('Unauthorized access!'); window.location.href='login.html';</script>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'admin'; // Explicitly set role as admin

    // Validate password match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.location.href='admin_register.html';</script>";
        exit();
    }

    // Check if email already exists
    $check_email = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($check_email);

    if ($result->num_rows > 0) {
        echo "<script>alert('Email already exists!'); window.location.href='admin_register.html';</script>";
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert admin data with role 'admin'
    $sql = "INSERT INTO users (fullname, email, contact_number, address, role, password) 
            VALUES ('$fullname', '$email', '$contact_number', '$address', '$role', '$hashed_password')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Admin registered successfully!'); window.location.href='admin_panel.html';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "'); window.location.href='admin_register.html';</script>";
    }
}

$conn->close();
?> 