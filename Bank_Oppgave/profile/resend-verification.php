<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$db = new Database();
$pdo = $db->getConnection();

$error = '';
$success = '';

try {
    // Get user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user['email_verified']) {
        header("Location: index.php");
        exit();
    }

    // Delete any existing verification tokens
    $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);

    // Generate new verification token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    $stmt = $pdo->prepare("
        INSERT INTO email_verifications (user_id, token, expires_at)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $token, $expires]);

    // Send verification email
    $verify_link = "http://{$_SERVER['HTTP_HOST']}/verify-email.php?token=" . $token;
    $to = $user['email'];
    $subject = "Verify your Online Bank account";
    $message = "Hello " . htmlspecialchars($user['name']) . ",\n\n";
    $message .= "Please click the following link to verify your email address:\n";
    $message .= $verify_link . "\n\n";
    $message .= "This link will expire in 24 hours.\n";
    $message .= "If you didn't request this verification, please ignore this email.";
    
    // mail($to, $subject, $message); // Enable in production
    
    // Log the verification email send
    $stmt = $pdo->prepare("
        INSERT INTO login_logs (user_id, ip_address, user_agent, activity_type) 
        VALUES (?, ?, ?, 'verification_sent')
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);

    $success = "Verification email has been sent to " . htmlspecialchars($user['email']);

} catch (Exception $e) {
    $error = "Failed to send verification email. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification - Online Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Email Verification</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success); ?>
                                <hr>
                                <p class="mb-0">
                                    Please check your email and click the verification link. 
                                    The link will expire in 24 hours.
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="text-center mt-3">
                            <a href="index.php" class="btn btn-secondary">Back to Profile</a>
                        </div>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5>Didn't receive the email?</h5>
                            <ul>
                                <li>Check your spam folder</li>
                                <li>Verify your email address is correct in your profile</li>
                                <li>Wait a few minutes before trying again</li>
                            </ul>
                            <form method="POST">
                                <button type="submit" class="btn btn-primary">
                                    Send Another Verification Email
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 