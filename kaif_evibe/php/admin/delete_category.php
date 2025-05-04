<?php
header('Content-Type: application/json');

// Check if the request method is DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate the category ID
if (!isset($data['category_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Category ID is required']);
    exit;
}

$categoryId = $data['category_id'];

try {
    // Connect to the database
    $pdo = new PDO(
        'mysql:host=localhost;dbname=evibe_db',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Delete the category
    $stmt = $pdo->prepare('DELETE FROM categories WHERE category_id = ?');
    $stmt->execute([$categoryId]);

    // Check if any rows were affected
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Category not found']);
        exit;
    }

    // Return success message
    echo json_encode(['message' => 'Category deleted successfully']);

} catch (PDOException $e) {
    // Log the error
    error_log('Database error in delete_category.php: ' . $e->getMessage());
    
    // Return error message
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>