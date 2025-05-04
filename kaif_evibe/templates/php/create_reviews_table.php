<?php
require_once 'config.php';

try {
    // Create reviews table
    $sql = "CREATE TABLE IF NOT EXISTS reviews (
        review_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        user_id INT NOT NULL,
        rating DECIMAL(2,1) NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";

    if (mysqli_query($conn, $sql)) {
        echo "Reviews table created successfully";
    } else {
        throw new Exception("Error creating reviews table: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 