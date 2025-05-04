<?php
require_once 'db_connection.php';

try {
    // Create view_events table if not exists
    $createViewEvents = "CREATE TABLE IF NOT EXISTS view_events (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        event_id INT NOT NULL,
        viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
    )";
    
    // Execute the create table command
    $conn->exec($createViewEvents);
    
    // Try to add a view date column for the unique constraint
    try {
        $addViewDateColumn = "ALTER TABLE view_events ADD COLUMN view_date DATE GENERATED ALWAYS AS (DATE(viewed_at)) STORED";
        $conn->exec($addViewDateColumn);
    } catch (PDOException $e) {
        // Ignore if column already exists
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            // Only throw if it's not a duplicate column error
            throw $e;
        }
    }
    
    // Add the unique constraint using the view_date column
    try {
        $addUniqueConstraint = "ALTER TABLE view_events ADD UNIQUE INDEX unique_view (user_id, event_id, view_date)";
        $conn->exec($addUniqueConstraint);
    } catch (PDOException $e) {
        // Ignore if constraint already exists
        if (strpos($e->getMessage(), 'Duplicate key name') === false && 
            strpos($e->getMessage(), 'Duplicate entry') === false) {
            // Only throw if it's not a duplicate key error
            throw $e;
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'view_events table created successfully!']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

// PDO connection doesn't need explicit close
$conn = null;
?> 