<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Log admin activity
try {
    $stmt = $pdo->prepare("
        INSERT INTO login_logs (user_id, ip_address, user_agent, activity_type) 
        VALUES (?, ?, ?, 'admin_access')
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
} catch (PDOException $e) {
    // Log error silently
}
?> 