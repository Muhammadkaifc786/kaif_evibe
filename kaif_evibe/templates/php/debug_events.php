<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php';

// Get all events
$query = "SELECT event_id, title, latitude, longitude, status FROM events";
$stmt = $conn->prepare($query);
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>All Events in Database:</h2>";
echo "<pre>";
print_r($events);
echo "</pre>";

// Check if the events table has the correct structure
$query = "DESCRIBE events";
$stmt = $conn->prepare($query);
$stmt->execute();
$structure = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Events Table Structure:</h2>";
echo "<pre>";
print_r($structure);
echo "</pre>";
?> 