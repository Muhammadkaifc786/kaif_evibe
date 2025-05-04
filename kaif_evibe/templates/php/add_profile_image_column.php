<?php
require_once '../../config/database.php';

try {
    // Check if column exists
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
    if ($stmt->rowCount() == 0) {
        // Add profile_image column
        $conn->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL");
        echo "Profile image column added successfully";
    } else {
        echo "Profile image column already exists";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 