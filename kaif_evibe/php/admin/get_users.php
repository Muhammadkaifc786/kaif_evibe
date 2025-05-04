<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Simple query to get all users
$sql = "SELECT * FROM users";
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
    echo json_encode([
        'success' => false,
        'error' => $conn->error
    ]);
}

$conn->close();
?> 