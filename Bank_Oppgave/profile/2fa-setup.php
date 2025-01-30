<?php
session_start();
require_once '../config/database.php';
require_once '../vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$db = new Database();
$pdo = $db->getConnection();

$error = '';
$success = '';

try {
    // Get user's 2FA status
    $stmt = $pdo->prepare("
        SELECT * FROM two_factor_auth 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $tfa = $stmt->fetch();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['enable_2fa'])) {
            // Initialize 2FA
            $tfa = new RobThree\Auth\TwoFactorAuth('Online Bank');
            $secret = $tfa->createSecret();
            
            // Store secret temporarily in session
            $_SESSION['2fa_temp_secret'] = $secret;
            
            // Generate QR code
            $qrCodeUrl = $tfa->getQRCodeImageAsDataUri(
                $_SESSION['user_name'], 
                $secret
            );
            
        } elseif (isset($_POST['verify_2fa'])) {
            $code = trim($_POST['code']);
            $secret = $_SESSION['2fa_temp_secret'];
            
            $tfa = new RobThree\Auth\TwoFactorAuth('Online Bank');
            
            if ($tfa->verifyCode($secret, $code)) {
                // Store secret permanently
                $stmt = $pdo->prepare("
                    INSERT INTO two_factor_auth (user_id, secret_key, is_enabled) 
                    VALUES (?, ?, true)
                    ON DUPLICATE KEY UPDATE 
                        secret_key = VALUES(secret_key),
                        is_enabled = VALUES(is_enabled)
                ");
                $stmt->execute([$_SESSION['user_id'], $secret]);
                
                unset($_SESSION['2fa_temp_secret']);
                $success = "Two-factor authentication has been enabled.";
                
            } else {
                $error = "Invalid verification code. Please try again.";
            }
            
        } elseif (isset($_POST['disable_2fa'])) {
            $stmt = $pdo->prepare("
                UPDATE two_factor_auth 
                SET is_enabled = false 
                WHERE user_id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $success = "Two-factor authentication has been disabled.";
        }
    }

} catch(Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - Online Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Two-Factor Authentication</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <?php if (!$tfa || !$tfa['is_enabled']): ?>
                            <?php if (!isset($_SESSION['2fa_temp_secret'])): ?>
                                <p>Two-factor authentication adds an extra layer of security to your account.</p>
                                <form method="POST">
                                    <button type="submit" name="enable_2fa" class="btn btn-primary">
                                        Enable Two-Factor Authentication
                                    </button>
                                </form>
                            <?php else: ?>
                                <h4>Setup Instructions:</h4>
                                <ol>
                                    <li>Install Google Authenticator on your phone</li>
                                    <li>Scan this QR code with the app:</li>
                                </ol>
                                <div class="text-center mb-4">
                                    <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code">
                                </div>
                                <form method="POST" class="mb-3">
                                    <div class="mb-3">
                                        <label for="code" class="form-label">Verification Code</label>
                                        <input type="text" class="form-control" id="code" name="code" 
                                               required pattern="[0-9]{6}" maxlength="6">
                                        <small class="text-muted">Enter the 6-digit code from your authenticator app</small>
                                    </div>
                                    <button type="submit" name="verify_2fa" class="btn btn-primary">
                                        Verify and Enable
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-success">
                                Two-factor authentication is currently enabled.
                            </div>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to disable 2FA?');">
                                <button type="submit" name="disable_2fa" class="btn btn-danger">
                                    Disable Two-Factor Authentication
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 