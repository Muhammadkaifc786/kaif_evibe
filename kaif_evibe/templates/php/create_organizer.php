<?php
require_once 'config.php';

// Demo organizer credentials
$fullname = "Demo Organizer";
$email = "organizer@evibe.com";
$password = "admin123";
$role = "organizer";

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if organizer already exists
$check_sql = "SELECT * FROM users WHERE email = '$email'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    // Insert the organizer
    $sql = "INSERT INTO users (fullname, email, password, role) 
            VALUES ('$fullname', '$email', '$hashed_password', '$role')";
    
    if ($conn->query($sql) === TRUE) {
        echo "Demo organizer account created successfully!<br>";
        echo "Email: organizer@evibe.com<br>";
        echo "Password: admin123";
    } else {
        echo "Error creating organizer account: " . $conn->error;
    }
} else {
    echo "Organizer account already exists!<br>";
    echo "Email: organizer@evibe.com<br>";
    echo "Password: admin123";
}

$conn->close();
?> 