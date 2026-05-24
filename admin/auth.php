<?php
session_start();
require_once 'db_config.php';

// Set JSON header for API responses
if (isset($_GET['api']) || isset($_POST['api'])) {
    header('Content-Type: application/json');
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

// Function to enforce login (redirects if not logged in)
function requireLogin() {
    if (!isLoggedIn()) {
        if (isset($_GET['api']) || isset($_POST['api']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        } else {
            header('Location: index.html#login');
            exit;
        }
    }
}

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    header('Content-Type: application/json');
    $conn = getDBConnection();
    
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            
            echo json_encode(['success' => true, 'message' => 'Login successful']);
        } else {
            // Invalid password
            echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        }
    } else {
        // User not found
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}

// Handle logout request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    header('Content-Type: application/json');
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    exit;
}

// Handle auth check
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'check') {
    header('Content-Type: application/json');
    if (isLoggedIn()) {
        echo json_encode([
            'success' => true, 
            'logged_in' => true,
            'user' => ['username' => $_SESSION['admin_username']]
        ]);
    } else {
        echo json_encode(['success' => true, 'logged_in' => false]);
    }
    exit;
}
?>
