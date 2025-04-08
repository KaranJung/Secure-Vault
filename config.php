<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'api_vault';
$db_user = 'root';
$db_pass = '';

// Create connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Encryption functions
function encrypt($text, $key) {
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($text, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decrypt($text, $key) {
    $text = base64_decode($text);
    $iv = substr($text, 0, 16);
    $encrypted = substr($text, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Encryption key (should be stored securely, e.g., in environment variables)
$encryption_key = getenv('ENCRYPTION_KEY') ?: 'your-32-character-encryption-key-here';

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatDate($date) {
    return date('M j, Y', strtotime($date));
}
?>