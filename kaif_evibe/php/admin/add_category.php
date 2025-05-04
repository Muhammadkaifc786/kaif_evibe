<?php
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the form data
$name = $_POST['name'] ?? '';
$icon = $_POST['icon'] ?? '';

// Validate the input
if (empty($name) || empty($icon)) {
    http_response_code(400);
    echo json_encode(['error' => 'Category name and icon are required']);
    exit;
}

try {
    // Connect to the database
    $pdo = new PDO(
        'mysql:host=localhost;dbname=evibe_db',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Prepare and execute the query
    $stmt = $pdo->prepare('INSERT INTO categories (name, icon, created_at) VALUES (?, ?, NOW())');
    $stmt->execute([$name, $icon]);

    // Return success message
    echo json_encode(['message' => 'Category added successfully']);

} catch (PDOException $e) {
    // Log the error
    error_log('Database error in add_category.php: ' . $e->getMessage());
    
    // Return error message
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 