<?php
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the form data
$categoryId = $_POST['category_id'] ?? '';
$name = $_POST['name'] ?? '';
$icon = $_POST['icon'] ?? '';

// Validate the input
if (empty($categoryId) || empty($name) || empty($icon)) {
    http_response_code(400);
    echo json_encode(['error' => 'Category ID, name, and icon are required']);
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

    // Update the category
    $stmt = $pdo->prepare('UPDATE categories SET name = ?, icon = ? WHERE category_id = ?');
    $stmt->execute([$name, $icon, $categoryId]);

    // Check if any rows were affected
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Category not found']);
        exit;
    }

    // Return success message
    echo json_encode(['message' => 'Category updated successfully']);

} catch (PDOException $e) {
    // Log the error
    error_log('Database error in update_category.php: ' . $e->getMessage());
    
    // Return error message
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 