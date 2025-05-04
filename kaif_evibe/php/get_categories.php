<?php
session_start();
require_once 'config.php';

// Get all categories
function getCategories($conn) {
    $sql = "SELECT * FROM categories ORDER BY name ASC";
    $result = $conn->query($sql);
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    return $categories;
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $categories = getCategories($conn);
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?> 