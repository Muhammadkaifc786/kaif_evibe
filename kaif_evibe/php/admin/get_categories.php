<?php
header('Content-Type: application/json');

try {
    // Connect to the database
    $pdo = new PDO(
        'mysql:host=localhost;dbname=evibe_db',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Get all categories ordered by name
    $stmt = $pdo->prepare('SELECT category_id, name, icon, created_at FROM categories ORDER BY name');
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the categories as JSON
    echo json_encode($categories);

} catch (PDOException $e) {
    // Log the error
    error_log('Database error in get_categories.php: ' . $e->getMessage());
    
    // Return error message
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>