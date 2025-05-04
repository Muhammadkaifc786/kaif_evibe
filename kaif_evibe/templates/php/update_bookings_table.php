<?php
// Include database connection file
require_once 'db_connect.php';

// Set content type to plain text for debugging
header('Content-Type: text/plain');

try {
    // Create database connection
    $conn = connectDB();
    
    // Check if the status column already exists in the table
    $checkStatusColumn = $conn->query("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'evibe_db' 
        AND TABLE_NAME = 'bookings' 
        AND COLUMN_NAME = 'status'
    ");
    
    if ($checkStatusColumn->rowCount() > 0) {
        echo "Status column already exists in the bookings table.\n";
    } else {
        // Add the status column to the bookings table
        $conn->exec("
            ALTER TABLE bookings 
            ADD COLUMN status ENUM('valid', 'used', 'expired') NOT NULL DEFAULT 'valid'
        ");
        
        echo "Successfully added status column to bookings table with default value 'valid'.\n";
    }
    
    // Check if the attendance_date column exists
    $checkAttendanceColumn = $conn->query("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'evibe_db' 
        AND TABLE_NAME = 'bookings' 
        AND COLUMN_NAME = 'attendance_date'
    ");
    
    if ($checkAttendanceColumn->rowCount() > 0) {
        echo "Attendance_date column already exists in the bookings table.\n";
    } else {
        // Add the attendance_date column
        $conn->exec("
            ALTER TABLE bookings 
            ADD COLUMN attendance_date DATETIME NULL
        ");
        
        echo "Successfully added attendance_date column to bookings table.\n";
    }
    
    // Update the status to 'expired' for past events
    $updateExpired = $conn->prepare("
        UPDATE bookings b
        JOIN events e ON b.event_id = e.id
        SET b.status = 'expired'
        WHERE e.date < CURDATE() AND b.status = 'valid'
    ");
    
    $updateExpired->execute();
    $expiredCount = $updateExpired->rowCount();
    
    if ($expiredCount > 0) {
        echo "Updated $expiredCount bookings to 'expired' status for past events.\n";
    } else {
        echo "No bookings needed to be updated to 'expired' status.\n";
    }
    
    echo "Table update completed successfully.";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit;
}
?> 