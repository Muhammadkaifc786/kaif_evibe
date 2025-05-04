<?php
require_once 'config.php';

// Default categories
$default_categories = [
    ['name' => 'Concerts', 'description' => 'Live music performances and concerts', 'icon' => 'fa-music'],
    ['name' => 'Sports', 'description' => 'Sports events and matches', 'icon' => 'fa-futbol'],
    ['name' => 'Education', 'description' => 'Educational events and workshops', 'icon' => 'fa-graduation-cap'],
    ['name' => 'Technology', 'description' => 'Tech conferences and meetups', 'icon' => 'fa-laptop-code'],
    ['name' => 'Food & Dining', 'description' => 'Food festivals and culinary events', 'icon' => 'fa-utensils'],
    ['name' => 'Arts & Theatre', 'description' => 'Art exhibitions and theatrical performances', 'icon' => 'fa-theater-masks']
];

// Check if categories table exists
$table_exists = $conn->query("SHOW TABLES LIKE 'categories'")->num_rows > 0;

if (!$table_exists) {
    // Create categories table
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
        echo "Error creating categories table: " . $conn->error . "<br>";
        exit();
    }
}

// Check if categories exist
$result = $conn->query("SELECT COUNT(*) as count FROM categories");
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // Insert default categories
    $sql = "INSERT INTO categories (name, description, icon) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    foreach ($default_categories as $category) {
        $stmt->bind_param("sss", $category['name'], $category['description'], $category['icon']);
        if ($stmt->execute()) {
            echo "Category '{$category['name']}' inserted successfully<br>";
        } else {
            echo "Error inserting category '{$category['name']}': " . $stmt->error . "<br>";
        }
    }
    $stmt->close();
    echo "All default categories inserted successfully<br>";
} else {
    echo "Categories already exist<br>";
}

$conn->close();
?> 