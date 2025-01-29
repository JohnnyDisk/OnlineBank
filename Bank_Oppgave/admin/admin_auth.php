<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Verify admin status
$stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user['user_type'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}
?> 