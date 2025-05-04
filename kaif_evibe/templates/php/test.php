<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Test database connection
require_once 'config.php';

if ($conn) {
    echo json_encode(["success" => true, "message" => "Database connection successful"]);
} else {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
}

$conn->close();

echo "PHP is working!";
?> 