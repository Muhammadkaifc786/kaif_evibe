<?php
require_once 'config.php';

// Check if events table exists
$table_check = "SHOW TABLES LIKE 'events'";
$table_result = mysqli_query($conn, $table_check);

if (mysqli_num_rows($table_result) == 0) {
    echo "Events table does not exist!";
    exit;
}

// Count total events
$count_sql = "SELECT COUNT(*) as total FROM events WHERE status = 'active'";
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);

echo "Total active events: " . $count_row['total'] . "\n";

// List all events
$list_sql = "SELECT event_id, title, date, status FROM events ORDER BY date DESC";
$list_result = mysqli_query($conn, $list_sql);

echo "\nEvent List:\n";
while ($row = mysqli_fetch_assoc($list_result)) {
    echo "ID: " . $row['event_id'] . " | Title: " . $row['title'] . " | Date: " . $row['date'] . " | Status: " . $row['status'] . "\n";
}
?> 