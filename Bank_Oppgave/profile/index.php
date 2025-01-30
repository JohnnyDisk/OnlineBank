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
    $stmt = $pdo->prepare("
        SELECT u.*, tfa.is_enabled as has_2fa 
        FROM users u 
        LEFT JOIN two_factor_auth tfa ON u.id = tfa.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['update_profile'])) {
            $name = trim($_POST['name']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);

            // Validate input
            if (empty($name)) {
                throw new Exception("Name is required");
            }

            // Update user info
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, phone = ?, address = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $phone, $address, $_SESSION['user_id']]);
            
            // Update session name
            $_SESSION['user_name'] = $name;
            
            $success = "Profile updated successfully";
            
        } elseif (isset($_POST['change_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }

            // Validate new password
            if (strlen($new_password) < 8) {
                throw new Exception("New password must be at least 8 characters long");
            }

            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match");
            }

            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);

            $success = "Password changed successfully";
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Online Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex flex-column align-items-center text-center">
                            <i class="bi bi-person-circle" style="font-size: 4rem;"></i>
                            <div class="mt-3">
                                <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                                <p class="text-muted"><?php echo ucfirst($user['user_type']); ?> Account</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="list-group">
                    <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                        Profile Information
                    </a>
                    <a href="#security" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        Security Settings
                    </a>
                    <a href="2fa-setup.php" class="list-group-item list-group-item-action">
                        Two-Factor Authentication
                        <?php if ($user['has_2fa']): ?>
                            <span class="badge bg-success float-end">Enabled</span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <div class="col-md-9">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="profile">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="update_profile" value="1">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                        <?php if (!$user['email_verified']): ?>
                                            <div class="form-text text-danger">
                                                Email not verified. <a href="resend-verification.php">Resend verification email</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" class="form-control" name="name" 
                                               value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="address" rows="3"><?php 
                                            echo htmlspecialchars($user['address'] ?? ''); 
                                        ?></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="security">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="passwordForm" onsubmit="return validatePasswordForm()">
                                    <input type="hidden" name="change_password" value="1">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" name="new_password" 
                                               id="newPassword" required>
                                        <div class="mt-2">
                                            <div class="progress">
                                                <div id="passwordStrength" class="progress-bar" role="progressbar" 
                                                     style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <ul id="passwordRequirements" class="mt-2 small"></ul>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_password" 
                                               id="confirmPassword" required>
                                        <div class="invalid-feedback">Passwords do not match</div>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Change Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/password-validator.js"></script>
    <script>
    let validator;

    document.addEventListener('DOMContentLoaded', function() {
        validator = new PasswordValidator(
            document.getElementById('newPassword'),
            document.getElementById('confirmPassword'),
            document.getElementById('passwordStrength'),
            document.getElementById('passwordRequirements')
        );
    });

    function validatePasswordForm() {
        if (!validator.isValid()) {
            alert('Please ensure your password meets all requirements and matches the confirmation.');
            return false;
        }
        return true;
    }
    </script>
</body>
</html> 