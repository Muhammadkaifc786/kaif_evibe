<?php
require_once 'config.php';

// Check if contact_number column exists
$check_contact = $conn->query("SHOW COLUMNS FROM users LIKE 'contact_number'");
if ($check_contact->num_rows == 0) {
    // Add contact_number column
    $sql = "ALTER TABLE users ADD COLUMN contact_number VARCHAR(20) AFTER email";
    if ($conn->query($sql)) {
        echo "Added contact_number column successfully<br>";
    } else {
        echo "Error adding contact_number column: " . $conn->error . "<br>";
    }
}

// Check if address column exists
$check_address = $conn->query("SHOW COLUMNS FROM users LIKE 'address'");
if ($check_address->num_rows == 0) {
    // Add address column
    $sql = "ALTER TABLE users ADD COLUMN address TEXT AFTER contact_number";
    if ($conn->query($sql)) {
        echo "Added address column successfully<br>";
    } else {
        echo "Error adding address column: " . $conn->error . "<br>";
    }
}

$conn->close();
?> 