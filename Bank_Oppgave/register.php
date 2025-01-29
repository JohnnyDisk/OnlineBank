<?php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'config/database.php';
$db = new Database();
$pdo = $db->getConnection();

$error = '';
$success = '';

try {
    // Get available account types
    $stmt = $pdo->prepare("SELECT * FROM account_types ORDER BY type_name");
    $stmt->execute();
    $account_types = $stmt->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $user_type = $_POST['user_type'];
        $account_type_id = $_POST['account_type'];

        // Validate input
        if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
            throw new Exception('All fields are required');
        }

        if ($password !== $confirm_password) {
            throw new Exception('Passwords do not match');
        }

        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email already registered');
        }

        // Validate account type
        $stmt = $pdo->prepare("SELECT id FROM account_types WHERE id = ?");
        $stmt->execute([$account_type_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Invalid account type selected');
        }

        // Begin transaction
        $pdo->beginTransaction();

        try {
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, phone, address, user_type) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $email, $hashed_password, $phone, $address, $user_type]);
            $user_id = $pdo->lastInsertId();

            // Generate account number
            $stmt = $pdo->prepare("SELECT type_name FROM account_types WHERE id = ?");
            $stmt->execute([$account_type_id]);
            $type = $stmt->fetch()['type_name'];
            
            $bankCode = '1234';
            $typeCode = ($type === 'savings') ? '01' : 
                       (($type === 'checking') ? '02' : '03');
            $accountNum = str_pad($user_id, 5, '0', STR_PAD_LEFT);
            $account_number = $bankCode . '.' . $typeCode . '.' . $accountNum;

            // Create account
            $stmt = $pdo->prepare("
                INSERT INTO accounts (user_id, account_type_id, account_number, status) 
                VALUES (?, ?, ?, 'active')
            ");
            $stmt->execute([$user_id, $account_type_id, $account_number]);

            $pdo->commit();
            $success = 'Registration successful! You can now <a href="login.php">login</a>.';

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Online Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Create Account</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="" id="registerForm">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="user_type" class="form-label">Account Type</label>
                                <select class="form-select" id="user_type" name="user_type" required>
                                    <option value="personal">Personal Account</option>
                                    <option value="business">Business Account</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="account_type" class="form-label">Initial Account Type</label>
                                <select class="form-select" id="account_type" name="account_type" required>
                                    <?php foreach ($account_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>">
                                            <?php echo ucfirst($type['type_name']) . 
                                                      ' (' . number_format($type['interest_rate'], 2) . '% APR)'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       minlength="8" required>
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });

        // Update available account types based on user type
        document.getElementById('user_type').addEventListener('change', function() {
            const accountTypeSelect = document.getElementById('account_type');
            const userType = this.value;
            
            Array.from(accountTypeSelect.options).forEach(option => {
                const accountType = option.text.toLowerCase();
                if (userType === 'business') {
                    option.disabled = !accountType.includes('business');
                } else {
                    option.disabled = accountType.includes('business');
                }
            });

            // Select first non-disabled option
            const firstEnabled = Array.from(accountTypeSelect.options)
                                    .find(option => !option.disabled);
            if (firstEnabled) {
                accountTypeSelect.value = firstEnabled.value;
            }
        });
    </script>
</body>
</html> 