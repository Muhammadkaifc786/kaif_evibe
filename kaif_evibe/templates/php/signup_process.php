<?php
session_start();
require_once 'config.php';

// Set proper content type
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $response = array('success' => false, 'message' => '');
    
    try {
        $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate password match
        if ($password !== $confirm_password) {
            $response['message'] = 'Passwords do not match!';
            echo json_encode($response);
            exit();
        }

        // Check if email already exists
        $check_email = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_email);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response['message'] = 'Email already exists!';
            echo json_encode($response);
            exit();
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Start transaction
        $conn->begin_transaction();

        // Insert user data
        $sql = "INSERT INTO users (fullname, email, contact_number, address, role, password) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $fullname, $email, $contact_number, $address, $role, $hashed_password);

        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            
            // Commit transaction
            $conn->commit();
            
            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['fullname'] = $fullname;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;
            
            $response['success'] = true;
            $response['message'] = 'Account created successfully!';
            $response['redirect'] = $role === 'organizer' ? '../html/organizer.html' : '../html/home2.html';
            
            echo json_encode($response);
            exit();
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $response['message'] = 'Error: ' . $e->getMessage();
        echo json_encode($response);
        exit();
    }
}

$conn->close();
?> 