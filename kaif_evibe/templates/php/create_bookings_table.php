<?php
require_once 'db_connection.php';

try {
    // Create bookings table
    $sql = "CREATE TABLE IF NOT EXISTS bookings (
        booking_id INT NOT NULL AUTO_INCREMENT,
        ticket_id VARCHAR(20) NOT NULL,
        user_id INT NOT NULL,
        event_id INT NOT NULL,
        ticket_count INT NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (booking_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    
    $conn->exec($sql);
    echo json_encode(['success' => true, 'message' => 'Bookings table created successfully']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error creating bookings table: ' . $e->getMessage()]);
}

$conn = null;
?> 