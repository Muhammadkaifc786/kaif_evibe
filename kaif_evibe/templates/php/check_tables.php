<?php
require_once 'db_connection.php';

try {
    // Check users table structure
    $stmt = $conn->query("DESCRIBE users");
    echo "Structure of users table:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "\n";

    // Check events table structure
    $stmt = $conn->query("DESCRIBE events");
    echo "\nStructure of events table:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn = null;
?> 