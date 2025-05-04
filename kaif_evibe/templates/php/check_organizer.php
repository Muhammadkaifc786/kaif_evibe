<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

// Check if user is an organizer
if ($_SESSION['role'] !== 'organizer') {
    header('Location: login.html');
    exit;
}

// Get organizer details
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ? AND role = 'organizer'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User is not an organizer
    header('Location: login.html');
    exit;
}

$conn->close();
?> 