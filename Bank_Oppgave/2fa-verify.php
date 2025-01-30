<?php
session_start();
require_once 'config/database.php';
require_once 'vendor/autoload.php';

// Check if user needs to verify 2FA
if (!isset($_SESSION['2fa_needed']) || !isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$pdo = $db->getConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $code = trim($_POST['code']);
        
        // Get user's 2FA secret
        $stmt = $pdo->prepare("
            SELECT u.*, tfa.secret_key 
            FROM users u 
            JOIN two_factor_auth tfa ON u.id = tfa.user_id 
            WHERE u.id = ? AND tfa.is_enabled = true
        ");
        $stmt->execute([$_SESSION['temp_user_id']]);
        $user = $stmt->fetch();

        if ($user) {
            $tfa = new RobThree\Auth\TwoFactorAuth('Online Bank');
            
            if ($tfa->verifyCode($user['secret_key'], $code)) {
                // Code is valid, complete login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Clear 2FA session variables
                unset($_SESSION['2fa_needed']);
                unset($_SESSION['temp_user_id']);

                // Log successful 2FA verification
                $stmt = $pdo->prepare("
                    INSERT INTO login_logs (user_id, ip_address, user_agent, activity_type) 
                    VALUES (?, ?, ?, '2fa_success')
                ");
                $stmt->execute([
                    $user['id'],
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT']
                ]);

                // Redirect based on user type
                header("Location: " . ($user['user_type'] === 'admin' ? 'admin/index.php' : 'dashboard.php'));
                exit();
            } else {
                // Log failed attempt
                $stmt = $pdo->prepare("
                    INSERT INTO login_logs (user_id, ip_address, user_agent, activity_type) 
                    VALUES (?, ?, ?, '2fa_failed')
                ");
                $stmt->execute([
                    $_SESSION['temp_user_id'],
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT']
                ]);
                
                $error = "Invalid verification code. Please try again.";
            }
        } else {
            $error = "Authentication error. Please try logging in again.";
        }
    } catch(Exception $e) {
        $error = "Verification error. Please try again.";
    }
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
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Two-Factor Authentication</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <p class="text-center mb-4">
                            Please enter the verification code from your authenticator app.
                        </p>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="code" class="form-label">Verification Code</label>
                                <input type="text" class="form-control form-control-lg text-center" 
                                       id="code" name="code" required pattern="[0-9]{6}" maxlength="6"
                                       autocomplete="off" autofocus>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Verify</button>
                                <a href="logout.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 