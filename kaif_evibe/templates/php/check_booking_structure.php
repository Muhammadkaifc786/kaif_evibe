<?php
// Include the database connection
require_once 'db_connect.php';

try {
    // Create a database connection
    $conn = connectDB();
    
    // Query to get column information for the bookings table
    $sql = "SHOW COLUMNS FROM bookings";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Output column information
    echo "<h2>Bookings Table Structure</h2>";
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
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 