<?php
require_once 'db_connection.php';

// Read the SQL file
$sql = file_get_contents(__DIR__ . '/../sql/create_event_reviews_table.sql');

// Execute the SQL
if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    
    echo "Table 'event_reviews' created successfully or already exists.";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?> 