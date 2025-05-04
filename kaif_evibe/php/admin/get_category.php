<?php
header('Content-Type: application/json');

// Check if category ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Category ID is required']);
    exit;
}

$categoryId = $_GET['id'];

try {
    // Connect to the database
    $pdo = new PDO(
        'mysql:host=localhost;dbname=evibe_db',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Get the category
    $stmt = $pdo->prepare('SELECT category_id, name, icon, created_at FROM categories WHERE category_id = ?');
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        http_response_code(404);
        echo json_encode(['error' => 'Category not found']);
        exit;
    }

    // Return the category as JSON
    echo json_encode($category);

} catch (PDOException $e) {
    // Log the error
    error_log('Database error in get_category.php: ' . $e->getMessage());
    
    // Return error message
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 