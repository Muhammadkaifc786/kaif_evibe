<?php
require_once 'config.php';

try {
    // Create organizers table
    $sql = "CREATE TABLE IF NOT EXISTS organizers (
        organizer_id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "Organizers table created successfully<br>";
    } else {
        throw new Exception("Error creating organizers table: " . $conn->error);
    }

    // Create categories table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        category_id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        icon VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "Categories table created successfully<br>";
    } else {
        throw new Exception("Error creating categories table: " . $conn->error);
    }

    // Create events table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS events (
        event_id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        event_date DATETIME NOT NULL,
        venue VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        image_url VARCHAR(255),
        category_id INT,
        organizer_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(category_id),
        FOREIGN KEY (organizer_id) REFERENCES organizers(organizer_id)
    )";
    
    if ($conn->query($sql)) {
        echo "Events table created successfully<br>";
    } else {
        throw new Exception("Error creating events table: " . $conn->error);
    }

    // Create ratings table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS ratings (
        rating_id INT PRIMARY KEY AUTO_INCREMENT,
        event_id INT,
        user_id INT,
        rating DECIMAL(2,1) NOT NULL,
        review TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(event_id)
    )";
    
    if ($conn->query($sql)) {
        echo "Ratings table created successfully<br>";
    } else {
        throw new Exception("Error creating ratings table: " . $conn->error);
    }

    // Create messages table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS messages (
        message_id INT PRIMARY KEY AUTO_INCREMENT,
        event_id INT NOT NULL,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(event_id)
    )";
    
    if ($conn->query($sql)) {
        echo "Messages table created successfully<br>";
    } else {
        throw new Exception("Error creating messages table: " . $conn->error);
    }

    // Insert default categories if none exist
    $result = $conn->query("SELECT COUNT(*) as count FROM categories");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $default_categories = [
            ['name' => 'Concerts', 'description' => 'Live music performances and concerts', 'icon' => 'fa-music'],
            ['name' => 'Sports', 'description' => 'Sports events and matches', 'icon' => 'fa-futbol'],
            ['name' => 'Education', 'description' => 'Educational events and workshops', 'icon' => 'fa-graduation-cap'],
            ['name' => 'Technology', 'description' => 'Tech conferences and meetups', 'icon' => 'fa-laptop-code'],
            ['name' => 'Food & Dining', 'description' => 'Food festivals and culinary events', 'icon' => 'fa-utensils'],
            ['name' => 'Arts & Theatre', 'description' => 'Art exhibitions and theatrical performances', 'icon' => 'fa-theater-masks']
        ];
        
        $stmt = $conn->prepare("INSERT INTO categories (name, description, icon) VALUES (?, ?, ?)");
        
        foreach ($default_categories as $category) {
            $stmt->bind_param("sss", $category['name'], $category['description'], $category['icon']);
            if (!$stmt->execute()) {
                throw new Exception("Error inserting category: " . $stmt->error);
            }
        }
        
        echo "Default categories inserted successfully<br>";
    }

    echo "Database setup completed successfully!";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?> 