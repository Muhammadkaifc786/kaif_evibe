<?php
// Include database connection file
require_once 'db_connect.php';

// Set header to JSON
header('Content-Type: application/json');

try {
    // Create a new PDO connection
    $conn = connectDB();
    
    // Prepare and execute query to get categories
    $stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    
    // Fetch results
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response with categories
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (PDOException $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 