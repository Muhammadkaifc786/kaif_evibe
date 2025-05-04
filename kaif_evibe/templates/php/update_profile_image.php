<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!isset($_FILES['profile_image'])) {
    echo json_encode(['success' => false, 'message' => 'No image uploaded']);
    exit;
}

$user_id = $_SESSION['user_id'];
$file = $_FILES['profile_image'];

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.']);
    exit;
}

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
    exit;
}

try {
    // Use existing uploads directory in templates
    $upload_dir = __DIR__ . '/../uploads/profile_images/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }

    // Check if directory is writable
    if (!is_writable($upload_dir)) {
        chmod($upload_dir, 0777);
    }

    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Update database with new image path
        $image_url = '/kaif_evibe/templates/uploads/profile_images/' . $filename;
        
        $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
        $stmt->execute([$image_url, $user_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Profile image updated successfully',
            'image_url' => $image_url
        ]);
    } else {
        throw new Exception('Failed to move uploaded file');
    }
} catch (Exception $e) {
    error_log('Profile image upload error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error uploading image: ' . $e->getMessage()
    ]);
}
?> 