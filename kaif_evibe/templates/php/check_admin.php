<?php
session_start();

// Check if user is logged in as admin
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

echo json_encode(['isAdmin' => $isAdmin]);
?> 