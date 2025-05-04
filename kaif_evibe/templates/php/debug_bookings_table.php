<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

try {
    // Check if bookings table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'bookings'");
    $tableExists = ($tableCheck && $tableCheck->rowCount() > 0);
    
    if (!$tableExists) {
        echo json_encode([
            'success' => false, 
            'message' => 'Bookings table does not exist',
            'tables' => getTablesList($conn)
        ]);
        exit;
    }
    
    // Get table structure
    $tableStructure = $conn->query("DESCRIBE bookings");
    $columns = $tableStructure->fetchAll(PDO::FETCH_ASSOC);
    
    // Get a sample of data
    $sampleData = $conn->query("SELECT * FROM bookings LIMIT 3");
    $data = $sampleData->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Bookings table exists',
        'columns' => $columns,
        'sample_data' => $data,
        'record_count' => getRecordCount($conn, 'bookings')
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

function getTablesList($conn) {
    $tables = $conn->query("SHOW TABLES");
    $tableList = [];
    while ($row = $tables->fetch(PDO::FETCH_NUM)) {
        $tableList[] = $row[0];
    }
    return $tableList;
}

function getRecordCount($conn, $table) {
    $countQuery = $conn->query("SELECT COUNT(*) FROM $table");
    return $countQuery->fetchColumn();
}

$conn = null;
?> 