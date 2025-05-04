<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT * FROM payment_methods WHERE is_active = TRUE ORDER BY name";
    $result = $conn->query($sql);
    
    $methods = [];
    while ($row = $result->fetch_assoc()) {
        $methods[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'methods' => $methods
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching payment methods: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 