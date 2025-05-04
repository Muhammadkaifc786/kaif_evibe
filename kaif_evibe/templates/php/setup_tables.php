<?php
require_once '../config/database.php';

try {
    // Create users table if not exists
    $createUsers = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        fullname VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        contact_number VARCHAR(20),
        address TEXT,
        password VARCHAR(255) NOT NULL,
        role ENUM('user', 'organizer', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($createUsers)) {
        throw new Exception("Error creating users table: " . $conn->error);
    }

    // Create events table if not exists
    $createEvents = "CREATE TABLE IF NOT EXISTS events (
        event_id INT PRIMARY KEY AUTO_INCREMENT,
        organizer_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        category_id INT NOT NULL,
        event_date DATETIME NOT NULL,
        venue VARCHAR(255) NOT NULL,
        ticket_price DECIMAL(10,2) NOT NULL,
        total_tickets INT NOT NULL,
        available_tickets INT NOT NULL,
        image_url VARCHAR(255),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if (!$conn->query($createEvents)) {
        throw new Exception("Error creating events table: " . $conn->error);
    }

    // Create reviews table if not exists
    $createReviews = "CREATE TABLE IF NOT EXISTS reviews (
        review_id INT PRIMARY KEY AUTO_INCREMENT,
        event_id INT NOT NULL,
        user_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if (!$conn->query($createReviews)) {
        throw new Exception("Error creating reviews table: " . $conn->error);
    }

    echo "Tables created successfully!";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?> 