<?php
/**
 * Database connection function
 * Returns a PDO database connection
 */
function connectDB() {
    // Database configuration
    $host = 'localhost';
    $dbname = 'evibe_db';
    $username = 'root';
    $password = '';
    $charset = 'utf8mb4';
    
    // DSN (Data Source Name)
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    
    // PDO options
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    try {
        // Create new PDO instance
        $pdo = new PDO($dsn, $username, $password, $options);
        return $pdo;
    } catch (PDOException $e) {
        // If connection fails, throw exception
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    }
}
?> 