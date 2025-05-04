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

    // Remove the description column
    $stmt = $pdo->prepare('ALTER TABLE categories DROP COLUMN description');
    $stmt->execute();

    echo json_encode(['message' => 'Description column removed successfully']);

} catch (PDOException $e) {
    // Log the error
    error_log('Database error in remove_description_column.php: ' . $e->getMessage());
    
    // Return error message
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 