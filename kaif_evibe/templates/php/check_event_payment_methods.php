<?php
require_once 'config.php';

// Check if event_payment_methods table exists
$table_exists = $conn->query("SHOW TABLES LIKE 'event_payment_methods'")->num_rows > 0;

if (!$table_exists) {
    // Create event_payment_methods junction table
    $sql = "CREATE TABLE IF NOT EXISTS event_payment_methods (
        event_id INT NOT NULL,
        method_id INT NOT NULL,
        PRIMARY KEY (event_id, method_id),
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
        FOREIGN KEY (method_id) REFERENCES payment_methods(method_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "Event payment methods junction table created successfully<br>";
    } else {
        echo "Error creating event payment methods table: " . $conn->error . "<br>";
        exit();
    }
} else {
    echo "Event payment methods table already exists<br>";
}

$conn->close();
?> 