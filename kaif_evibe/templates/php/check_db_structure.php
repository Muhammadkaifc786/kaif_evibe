<?php
// Include the database connection
require_once 'db_connect.php';

try {
    // Create a database connection
    $conn = connectDB();
    
    // Get all tables in the database
    $sql = "SHOW TABLES";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Output the list of tables
    echo "<h1>Database Structure</h1>";
    
    foreach ($tables as $table) {
        echo "<h2>Table: {$table}</h2>";
        
        // Get the table structure
        $sql = "DESCRIBE {$table}";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Output the table structure
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "<hr>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 