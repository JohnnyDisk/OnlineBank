<?php
session_start();

require_once 'config/database.php';

// Log the logout if user was logged in
if (isset($_SESSION['user_id'])) {
    try {
        $db = new Database();
        $pdo = $db->getConnection();
        
        // Log the logout activity
        $stmt = $pdo->prepare("
            INSERT INTO login_logs (user_id, ip_address, user_agent, activity_type) 
            VALUES (?, ?, ?, 'logout')
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);

        // Delete remember-me token if exists
        if (isset($_COOKIE['remember_token'])) {
            $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token = ?");
            $stmt->execute([hash('sha256', $_COOKIE['remember_token'])]);
            setcookie('remember_token', '', time() - 3600, '/'); // Delete cookie
        }
    } catch(PDOException $e) {
        // Silently fail - logout should continue
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?> 