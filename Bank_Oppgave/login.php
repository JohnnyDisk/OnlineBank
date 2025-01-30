<?php
session_start();

// Check for remember-me token
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    require_once 'config/database.php';
    $db = new Database();
    $pdo = $db->getConnection();

    try {
        $stmt = $pdo->prepare("
            SELECT u.* 
            FROM users u
            JOIN remember_tokens rt ON u.id = rt.user_id
            WHERE rt.token = ? AND rt.expires_at > NOW()
        ");
        $stmt->execute([hash('sha256', $_COOKIE['remember_token'])]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_type'] = $user['user_type'];

            // Log the auto-login
            $stmt = $pdo->prepare("
                INSERT INTO login_logs (user_id, ip_address, user_agent, activity_type) 
                VALUES (?, ?, ?, 'auto_login')
            ");
            $stmt->execute([
                $user['id'],
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);

            // Redirect based on user type
            header("Location: " . ($user['user_type'] === 'admin' ? 'admin/index.php' : 'dashboard.php'));
            exit();
        }
    } catch(PDOException $e) {
        // Silently fail - user will need to login manually
    }
}

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'config/database.php';
$db = new Database();
$pdo = $db->getConnection();

$error = '';

// Add this function at the top of login.php
function isAccountLocked($pdo, $email, $user_id = null) {
    // Check if account is manually locked
    if ($user_id) {
        $stmt = $pdo->prepare("
            SELECT locked_until FROM locked_accounts 
            WHERE user_id = ? AND locked_until > NOW() 
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) {
            return true;
        }
    }

    // Check failed attempts in last 15 minutes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM login_attempts 
        WHERE email = ? AND is_successful = false 
        AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $stmt->execute([$email]);
    $attempts = $stmt->fetchColumn();

    return $attempts >= 5; // Lock after 5 failed attempts
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    try {
        // Check if account is locked
        if (isAccountLocked($pdo, $email)) {
            throw new Exception("Account is temporarily locked. Please try again later or contact support.");
        }

        // Get user by email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Log this attempt
        $stmt = $pdo->prepare("
            INSERT INTO login_attempts (email, ip_address, is_successful) 
            VALUES (?, ?, ?)
        ");

        if ($user && password_verify($password, $user['password'])) {
            // Successful login
            $stmt->execute([$email, $_SERVER['REMOTE_ADDR'], true]);

            // Clear failed attempts
            $stmt = $pdo->prepare("
                DELETE FROM login_attempts 
                WHERE email = ? AND is_successful = false
            ");
            $stmt->execute([$email]);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_type'] = $user['user_type'];

            // Handle remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $hashed_token = hash('sha256', $token);
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

                $stmt = $pdo->prepare("
                    INSERT INTO remember_tokens (user_id, token, expires_at)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$user['id'], $hashed_token, $expires]);

                setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
            }

            // Log the login
            $stmt = $pdo->prepare("
                INSERT INTO login_logs (user_id, ip_address, user_agent, activity_type) 
                VALUES (?, ?, ?, 'login')
            ");
            $stmt->execute([
                $user['id'],
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);

            // Redirect based on user type
            if ($user['user_type'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            // Failed login attempt
            $stmt->execute([$email, $_SERVER['REMOTE_ADDR'], false]);

            // Check if we should lock the account
            if (isAccountLocked($pdo, $email)) {
                // Lock account for 15 minutes
                if ($user) {
                    $stmt = $pdo->prepare("
                        INSERT INTO locked_accounts (user_id, locked_until, reason) 
                        VALUES (?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), 'failed_attempts')
                    ");
                    $stmt->execute([$user['id']]);
                }
                throw new Exception("Too many failed attempts. Account is temporarily locked.");
            }

            $error = "Invalid email or password";
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Online Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Login</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <p>Don't have an account? <a href="register.php">Register here</a></p>
                            <p><a href="forgot-password.php">Forgot Password?</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 