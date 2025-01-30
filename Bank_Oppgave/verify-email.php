<?php
session_start();
require_once 'config/database.php';

$db = new Database();
$pdo = $db->getConnection();

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("Location: login.php");
    exit();
}

try {
    // Check if token exists and is valid
    $stmt = $pdo->prepare("
        SELECT v.*, u.email 
        FROM email_verifications v
        JOIN users u ON v.user_id = u.id
        WHERE v.token = ? AND v.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $verification = $stmt->fetch();

    if ($verification) {
        // Begin transaction
        $pdo->beginTransaction();

        try {
            // Mark email as verified
            $stmt = $pdo->prepare("
                UPDATE users 
                SET email_verified = true 
                WHERE id = ?
            ");
            $stmt->execute([$verification['user_id']]);

            // Delete verification token
            $stmt = $pdo->prepare("
                DELETE FROM email_verifications 
                WHERE user_id = ?
            ");
            $stmt->execute([$verification['user_id']]);

            $pdo->commit();
            $success = "Your email has been verified successfully. You can now <a href='login.php'>login</a>.";

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } else {
        $error = "Invalid or expired verification link. Please request a new one.";
    }
} catch (Exception $e) {
    $error = "Verification error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Online Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Email Verification</h3>
                    </div>
                    <div class="card-body text-center">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 