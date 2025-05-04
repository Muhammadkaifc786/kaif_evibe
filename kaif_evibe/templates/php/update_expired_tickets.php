<?php
// This script should be run periodically via a cron job or similar scheduler
// to automatically update ticket statuses based on event dates

// Include database connection file
require_once 'db_connect.php';

// Log file for debugging
$logFile = __DIR__ . '/ticket_status_updates.log';

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

try {
    // Create a database connection
    $conn = connectDB();
    
    // Update tickets to 'expired' status for events that have passed
    // and where tickets are still marked as 'valid'
    $updateSql = "UPDATE bookings b
                  JOIN events e ON b.event_id = e.event_id
                  SET b.status = 'expired'
                  WHERE b.status = 'valid'
                  AND e.event_date < NOW()";
    
    $stmt = $conn->prepare($updateSql);
    $stmt->execute();
    
    $count = $stmt->rowCount();
    
    // Log the result
    logMessage("Updated $count ticket(s) to 'expired' status.");
    
    echo "Success: Updated $count ticket(s) to 'expired' status.";
    
} catch (PDOException $e) {
    logMessage("Error: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
}
?> 