<?php
require_once 'config.php';

// Create payment_methods table
$sql = "CREATE TABLE IF NOT EXISTS payment_methods (
    method_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "Payment methods table created successfully<br>";
} else {
    echo "Error creating payment methods table: " . $conn->error . "<br>";
    exit();
}

// Create event_payment_methods junction table
$sql = "CREATE TABLE IF NOT EXISTS event_payment_methods (
    event_id INT NOT NULL,
    method_id INT NOT NULL,
    PRIMARY KEY (event_id, method_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (method_id) REFERENCES payment_methods(method_id) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "Event payment methods junction table created successfully<br>";
} else {
    echo "Error creating event payment methods table: " . $conn->error . "<br>";
    exit();
}

// Insert default payment methods if they don't exist
$default_methods = [
    ['name' => 'Cash', 'description' => 'Pay in cash at the venue', 'icon' => 'fa-money-bill-wave'],
    ['name' => 'Bank Transfer', 'description' => 'Transfer money to bank account', 'icon' => 'fa-university'],
    ['name' => 'Easypaisa', 'description' => 'Pay through Easypaisa', 'icon' => 'fa-mobile-alt'],
    ['name' => 'JazzCash', 'description' => 'Pay through JazzCash', 'icon' => 'fa-mobile-alt'],
    ['name' => 'UPaisa', 'description' => 'Pay through UPaisa', 'icon' => 'fa-mobile-alt'],
    ['name' => 'Credit Card', 'description' => 'Pay with credit card', 'icon' => 'fa-credit-card'],
    ['name' => 'Debit Card', 'description' => 'Pay with debit card', 'icon' => 'fa-credit-card']
];

foreach ($default_methods as $method) {
    $name = $method['name'];
    $description = $method['description'];
    $icon = $method['icon'];
    
    // Check if method already exists
    $result = $conn->query("SELECT method_id FROM payment_methods WHERE name = '$name'");
    if ($result->num_rows == 0) {
        $sql = "INSERT INTO payment_methods (name, description, icon) VALUES ('$name', '$description', '$icon')";
        if ($conn->query($sql)) {
            echo "Added payment method: $name<br>";
        } else {
            echo "Error adding payment method $name: " . $conn->error . "<br>";
        }
    }
}

$conn->close();
?> 