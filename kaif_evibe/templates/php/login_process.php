<?php
// Prevent any output before headers
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

session_start();
require_once 'config.php';

// Set headers to prevent caching and ensure JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get POST data
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        // Log the attempt
        error_log("Login attempt for email: " . $email);

        // Validate input
        if (empty($email) || empty($password)) {
            throw new Exception('Email and password are required');
        }

        // Check for default admin credentials
        if ($email === "admin@evibe.com" && $password === "admin123") {
            // Destroy any existing session
            session_destroy();
            session_start();
            
            // Set session variables
            $_SESSION['user_id'] = 0;
            $_SESSION['fullname'] = "Admin";
            $_SESSION['email'] = $email;
            $_SESSION['role'] = "admin";
            
            // Set session cookie parameters
            session_set_cookie_params([
                'lifetime' => 86400, // 24 hours
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Set session cookie
            setcookie(session_name(), session_id(), time() + 86400, '/');
            
            echo json_encode([
                'success' => true,
                'role' => 'admin',
                'message' => 'Login successful',
                'redirect' => '../html/admin_panel.html'
            ]);
            exit();
        }

        // Get user data for regular users
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        
        if (!$stmt->execute()) {
            throw new Exception('Database execute error: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Destroy any existing session
                session_destroy();
                session_start();
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Set session cookie parameters
                session_set_cookie_params([
                    'lifetime' => 86400, // 24 hours
                    'path' => '/',
                    'domain' => '',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Set session cookie
                setcookie(session_name(), session_id(), time() + 86400, '/');

                $redirect = '../html/user_panel.html';
                if ($user['role'] === 'organizer') {
                    $redirect = '../html/organizer.html';
                }

                echo json_encode([
                    'success' => true,
                    'role' => $user['role'],
                    'message' => 'Login successful',
                    'redirect' => $redirect
                ]);
                exit();
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid password!'
                ]);
                exit();
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'User not found!'
            ]);
            exit();
        }
    } catch (Exception $e) {
        error_log("Login error details: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred during login. Please try again.',
            'debug' => $e->getMessage() // Only for development, remove in production
        ]);
        exit();
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

$conn->close();
?> 