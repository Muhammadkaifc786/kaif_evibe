<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Fetch all users from database
$sql = "SELECT * FROM users ORDER BY role, fullname";
$result = $conn->query($sql);

$users = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Remove sensitive information
        unset($row['password']);
        $users[] = $row;
    }
}

echo json_encode(['users' => $users]);
?> 