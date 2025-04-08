<?php
require_once 'config.php';
require_once 'notification_function.php';
requireLogin();

$user_id = getUserId();

// Handle marking a single notification as read
if (isset($_POST['credential_id']) && isset($_POST['notification_type'])) {
    $credential_id = $_POST['credential_id'];
    $notification_type = $_POST['notification_type'];
    
    markNotificationAsRead($pdo, $user_id, $credential_id, $notification_type);
    
    // Redirect back to the referring page
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'notifications.php';
    header('Location: ' . $redirect);
    exit;
}

// If no parameters provided, redirect to notifications page
header('Location: notifications.php');
exit;
?>

