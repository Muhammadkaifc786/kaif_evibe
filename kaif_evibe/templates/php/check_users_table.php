<?php
require_once 'db_connection.php';

try {
    // Get table structure
    $result = $conn->query("DESCRIBE users");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Users table structure:\n";
    echo "-------------------\n";
    foreach ($columns as $column) {
        echo "Field: " . $column['Field'] . "\n";
        echo "Type: " . $column['Type'] . "\n";
        echo "Null: " . $column['Null'] . "\n";
        echo "Key: " . $column['Key'] . "\n";
        echo "Default: " . $column['Default'] . "\n";
        echo "Extra: " . $column['Extra'] . "\n";
        echo "-------------------\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 