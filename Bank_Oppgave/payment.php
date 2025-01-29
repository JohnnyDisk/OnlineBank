<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
$db = new Database();
$pdo = $db->getConnection();

$error = '';
$success = '';

try {
    // Get user's accounts
    $stmt = $pdo->prepare("
        SELECT a.*, at.type_name 
        FROM accounts a 
        JOIN account_types at ON a.account_type_id = at.id
        WHERE a.user_id = ? AND a.status = 'active'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_accounts = $stmt->fetchAll();

    // Get list of companies that accept bill payments
    $stmt = $pdo->prepare("SELECT * FROM bank_bills ORDER BY company_name");
    $stmt->execute();
    $companies = $stmt->fetchAll();

    // Process payment
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $from_account = $_POST['from_account'];
            $company_id = $_POST['company'];
            $amount = floatval($_POST['amount']);
            $kid_number = trim($_POST['kid_number']);
            $description = trim($_POST['description']);

            // Validate amount
            if ($amount <= 0) {
                throw new Exception("Amount must be greater than zero");
            }

            // Begin transaction
            $pdo->beginTransaction();

            // Get sender's account
            $stmt = $pdo->prepare("
                SELECT a.*, at.type_name 
                FROM accounts a 
                JOIN account_types at ON a.account_type_id = at.id 
                WHERE a.account_number = ? AND a.status = 'active'
                FOR UPDATE
            ");
            $stmt->execute([$from_account]);
            $sender_account = $stmt->fetch();

            if (!$sender_account) {
                throw new Exception("Account not found or inactive");
            }

            // Check if sender has enough balance
            if ($sender_account['balance'] < $amount) {
                throw new Exception("Insufficient funds");
            }

            // Get company account
            $stmt = $pdo->prepare("SELECT * FROM bank_bills WHERE id = ?");
            $stmt->execute([$company_id]);
            $company = $stmt->fetch();

            if (!$company) {
                throw new Exception("Invalid company selected");
            }

            // Validate KID number format
            if (!empty($company['kid_prefix']) && !str_starts_with($kid_number, $company['kid_prefix'])) {
                throw new Exception("Invalid KID number format for this company");
            }

            // Update sender's balance
            $stmt = $pdo->prepare("
                UPDATE accounts 
                SET balance = balance - ? 
                WHERE account_number = ? AND status = 'active'
            ");
            $stmt->execute([$amount, $from_account]);

            // Record transaction
            $stmt = $pdo->prepare("
                INSERT INTO transactions (from_account, to_account, amount, type, description, kid_number) 
                VALUES (?, ?, ?, 'payment', ?, ?)
            ");
            $stmt->execute([
                $from_account, 
                $company['account_number'], 
                $amount, 
                $description ?: 'Bill payment to ' . $company['company_name'],
                $kid_number
            ]);

            $pdo->commit();
            $success = "Payment successful!";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }

} catch(PDOException $e) {
    $error = "Connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Bills - Online Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Pay Bills</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <?php if (empty($user_accounts)): ?>
                            <div class="alert alert-warning">
                                You need an active account to make payments. 
                                <a href="accounts.php" class="alert-link">Create an account</a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="" id="paymentForm">
                                <div class="mb-3">
                                    <label for="from_account" class="form-label">From Account</label>
                                    <select class="form-select" id="from_account" name="from_account" required>
                                        <option value="">Select account</option>
                                        <?php foreach ($user_accounts as $account): ?>
                                            <option value="<?php echo htmlspecialchars($account['account_number']); ?>" 
                                                    data-balance="<?php echo $account['balance']; ?>">
                                                <?php echo ucfirst($account['type_name']) . ' - ' . 
                                                          $account['account_number'] . ' ($' . 
                                                          number_format($account['balance'], 2) . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="company" class="form-label">Company</label>
                                    <select class="form-select" id="company" name="company" required>
                                        <option value="">Select company</option>
                                        <?php foreach ($companies as $company): ?>
                                            <option value="<?php echo $company['id']; ?>" 
                                                    data-kid-prefix="<?php echo $company['kid_prefix']; ?>">
                                                <?php echo htmlspecialchars($company['company_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="kid_number" class="form-label">KID Number</label>
                                    <input type="text" class="form-control" id="kid_number" name="kid_number" required>
                                    <div id="kidHelp" class="form-text"></div>
                                </div>

                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount ($)</label>
                                    <input type="number" step="0.01" min="0.01" class="form-control" 
                                           id="amount" name="amount" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description (Optional)</label>
                                    <input type="text" class="form-control" id="description" name="description">
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Pay Bill</button>
                                    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('company')?.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const kidPrefix = selectedOption.dataset.kidPrefix;
            const kidHelp = document.getElementById('kidHelp');
            
            if (kidPrefix) {
                kidHelp.textContent = `KID number must start with: ${kidPrefix}`;
            } else {
                kidHelp.textContent = '';
            }
        });

        document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
            const fromAccount = document.getElementById('from_account');
            const amount = parseFloat(document.getElementById('amount').value);
            const balance = parseFloat(fromAccount.options[fromAccount.selectedIndex].dataset.balance);
            
            if (amount > balance) {
                e.preventDefault();
                alert('Payment amount cannot exceed your account balance.');
            }
        });
    </script>
</body>
</html> 