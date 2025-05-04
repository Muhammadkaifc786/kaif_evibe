<?php
session_start();
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Event Posting Debug Information</h1>";

// Check database connection
echo "<h2>Database Connection</h2>";
if ($conn) {
    echo "Database connection successful<br>";
    echo "Database name: " . DB_NAME . "<br>";
    echo "Server: " . DB_SERVER . "<br>";
    echo "Username: " . DB_USERNAME . "<br>";
} else {
    echo "Database connection failed: " . mysqli_connect_error() . "<br>";
    exit();
}

// Check if tables exist
echo "<h2>Database Tables</h2>";
$tables = ['users', 'categories', 'events', 'payment_methods', 'event_payment_methods'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "Table '$table' exists<br>";
        
        // Count records
        $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
        echo "Number of records in '$table': $count<br>";
    } else {
        echo "Table '$table' does NOT exist<br>";
    }
}

// Check session
echo "<h2>Session Information</h2>";
if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    
    // Check if user exists
    $user_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT * FROM users WHERE id = $user_id");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "User found: " . $user['fullname'] . " (Role: " . $user['role'] . ")<br>";
    } else {
        echo "User not found in database<br>";
    }
} else {
    echo "No user session found<br>";
}

// Check uploads directory
echo "<h2>Uploads Directory</h2>";
$uploads_dir = "../uploads/";
if (file_exists($uploads_dir)) {
    echo "Uploads directory exists<br>";
    if (is_writable($uploads_dir)) {
        echo "Uploads directory is writable<br>";
    } else {
        echo "Uploads directory is NOT writable<br>";
    }
} else {
    echo "Uploads directory does NOT exist<br>";
    if (mkdir($uploads_dir, 0777, true)) {
        echo "Created uploads directory<br>";
    } else {
        echo "Failed to create uploads directory<br>";
    }
}

// Check PHP configuration
echo "<h2>PHP Configuration</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Max Upload Size: " . ini_get('upload_max_filesize') . "<br>";
echo "Post Max Size: " . ini_get('post_max_size') . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";

// Test database insert
echo "<h2>Database Insert Test</h2>";
try {
    // Check if test table exists
    $result = $conn->query("SHOW TABLES LIKE 'test_table'");
    if ($result->num_rows == 0) {
        // Create test table
        $conn->query("CREATE TABLE test_table (id INT AUTO_INCREMENT PRIMARY KEY, test_field VARCHAR(255))");
        echo "Created test table<br>";
    }
    
    // Insert test record
    $test_value = "Test " . date('Y-m-d H:i:s');
    $sql = "INSERT INTO test_table (test_field) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $test_value);
    
    if ($stmt->execute()) {
        echo "Successfully inserted test record<br>";
    } else {
        echo "Failed to insert test record: " . $stmt->error . "<br>";
    }
    $stmt->close();
} catch (Exception $e) {
    echo "Error testing database insert: " . $e->getMessage() . "<br>";
}

$conn->close();
?> 