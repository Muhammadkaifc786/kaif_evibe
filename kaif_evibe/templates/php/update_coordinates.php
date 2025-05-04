<?php
require_once 'db_connection.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Show all events
    $query = "SELECT event_id, title, venue, latitude, longitude, status FROM events ORDER BY event_id DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    echo "<h2>All Events in Database:</h2>";
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
    // Update coordinates for both events with venue 'rustam'
    $update_query = "UPDATE events SET latitude = :latitude, longitude = :longitude WHERE venue = :venue";
    $stmt = $conn->prepare($update_query);
    
    // Using the same coordinates as other events in Peshawar
    $latitude = 34.012385;
    $longitude = 71.578746;
    $venue = "rustam";
    
    $stmt->bindParam(':latitude', $latitude);
    $stmt->bindParam(':longitude', $longitude);
    $stmt->bindParam(':venue', $venue);
    
    if ($stmt->execute()) {
        echo "<h2>Update Result:</h2>";
        echo "Coordinates updated successfully!<br>";
        echo "Rows affected: " . $stmt->rowCount();
        
        // Show the updated events
        $query = "SELECT event_id, title, venue, latitude, longitude, status FROM events WHERE venue = :venue";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':venue', $venue);
        $stmt->execute();
        
        echo "<h2>Updated Events:</h2>";
        echo "<pre>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            print_r($row);
        }
        echo "</pre>";
    } else {
        echo "Error updating coordinates: " . implode(" ", $stmt->errorInfo());
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 