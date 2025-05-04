<?php
require_once 'db_connection.php';

try {
    // Create only the favorites table with correct foreign key references
    $sql = "CREATE TABLE IF NOT EXISTS favorites (
        favorite_id INT NOT NULL AUTO_INCREMENT,
        user_id INT NOT NULL,
        event_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (favorite_id),
        CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_event FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
        UNIQUE KEY unique_favorite (user_id, event_id)
    ) ENGINE=InnoDB";
    
    $conn->exec($sql);
    echo "Favorites table created successfully";
} catch(PDOException $e) {
    echo "Error creating favorites table: " . $e->getMessage();
}

$conn = null;
?> 