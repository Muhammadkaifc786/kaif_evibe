<?php
require_once 'config.php';

// Check if events table exists
$table_check = "SHOW TABLES LIKE 'events'";
$table_result = mysqli_query($conn, $table_check);

if (mysqli_num_rows($table_result) == 0) {
    echo "Events table does not exist!";
    exit;
}

// Get table structure
$structure = "DESCRIBE events";
$structure_result = mysqli_query($conn, $structure);

echo "Events Table Structure:\n";
while ($row = mysqli_fetch_assoc($structure_result)) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . " | " . $row['Default'] . "\n";
}

// Count total events
$count_sql = "SELECT COUNT(*) as total FROM events WHERE status = 'active'";
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);

echo "\nTotal active events: " . $count_row['total'] . "\n";

// List all events
$list_sql = "SELECT event_id, title, event_date, status FROM events ORDER BY event_date DESC";
$list_result = mysqli_query($conn, $list_sql);

echo "\nEvent List:\n";
while ($row = mysqli_fetch_assoc($list_result)) {
    echo "ID: " . $row['event_id'] . " | Title: " . $row['title'] . " | Date: " . $row['event_date'] . " | Status: " . $row['status'] . "\n";
}

$conn->close();
?> 