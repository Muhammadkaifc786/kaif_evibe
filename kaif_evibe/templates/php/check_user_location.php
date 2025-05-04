<?php
require_once 'db_connection.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        echo "No user logged in";
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Get user's location
    $query = "SELECT id, latitude, longitude FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Your User Information:</h2>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    // If no coordinates, update them to match Peshawar
    if (!$user['latitude'] || !$user['longitude']) {
        $update_query = "UPDATE users SET latitude = :latitude, longitude = :longitude WHERE id = :user_id";
        $stmt = $conn->prepare($update_query);
        
        $latitude = 34.012385;
        $longitude = 71.578746;
        
        $stmt->bindParam(':latitude', $latitude);
        $stmt->bindParam(':longitude', $longitude);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            echo "<h2>Location Updated:</h2>";
            echo "Your location has been set to Peshawar coordinates.<br>";
            echo "New coordinates: $latitude, $longitude";
        } else {
            echo "Error updating location: " . implode(" ", $stmt->errorInfo());
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 