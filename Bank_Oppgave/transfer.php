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
        SELECT a.*, at.type_name as account_type 
        FROM accounts a 
        JOIN account_types at ON a.account_type_id = at.id
        WHERE a.user_id = ? AND a.status = 'active'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_accounts = $stmt->fetchAll();

    if (empty($user_accounts)) {
        header("Location: accounts.php?error=no_active_account");
        exit();
    }

    // Get list of other users' accounts for transfer
    $stmt = $pdo->prepare("
        SELECT a.account_number, a.account_type_id, at.type_name, u.name 
        FROM accounts a 
        JOIN users u ON a.user_id = u.id 
        JOIN account_types at ON a.account_type_id = at.id
        WHERE a.user_id != ? AND a.status = 'active'
        ORDER BY u.name ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recipient_accounts = $stmt->fetchAll();

    // Process transfer
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $from_account = $_POST['from_account'];
            $to_account = $_POST['to_account'];
            $amount = floatval($_POST['amount']);
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
                throw new Exception("Sender account not found or inactive");
            }

            // Check if sender has enough balance
            if ($sender_account['balance'] < $amount) {
                throw new Exception("Insufficient funds");
            }

            // Get recipient's account
            $stmt = $pdo->prepare("
                SELECT * FROM accounts WHERE account_number = ? AND status = 'active'
                FOR UPDATE
            ");
            $stmt->execute([$to_account]);
            $recipient_account = $stmt->fetch();

            if (!$recipient_account) {
                throw new Exception("Recipient account not found or inactive");
            }

            // Update sender's balance
            $stmt = $pdo->prepare("
                UPDATE accounts 
                SET balance = balance - ? 
                WHERE account_number = ? AND status = 'active'
            ");
            $stmt->execute([$amount, $from_account]);

            // Update recipient's balance
            $stmt = $pdo->prepare("
                UPDATE accounts 
                SET balance = balance + ? 
                WHERE account_number = ? AND status = 'active'
            ");
            $stmt->execute([$amount, $to_account]);

            // Record transaction
            $stmt = $pdo->prepare("
                INSERT INTO transactions (from_account, to_account, amount, type, description) 
                VALUES (?, ?, ?, 'transfer', ?)
            ");
            $stmt->execute([$from_account, $to_account, $amount, $description]);

            $pdo->commit();
            $success = "Transfer successful!";

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
    <title>Transfer Money - Online Bank</title>
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
                        <h3 class="text-center">Transfer Money</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="" id="transferForm">
                            <div class="mb-3">
                                <label for="from_account" class="form-label">From Account</label>
                                <select class="form-select" id="from_account" name="from_account" required>
                                    <option value="">Select account</option>
                                    <?php foreach ($user_accounts as $account): ?>
                                        <option value="<?php echo htmlspecialchars($account['account_number']); ?>" 
                                                data-balance="<?php echo $account['balance']; ?>">
                                            <?php echo ucfirst($account['account_type']) . ' - ' . 
                                                      $account['account_number'] . ' ($' . 
                                                      number_format($account['balance'], 2) . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="to_account" class="form-label">To Account</label>
                                <select class="form-select" id="to_account" name="to_account" required>
                                    <option value="">Select recipient</option>
                                    <?php foreach ($recipient_accounts as $account): ?>
                                        <option value="<?php echo htmlspecialchars($account['account_number']); ?>">
                                            <?php echo htmlspecialchars($account['name'] . ' - ' . 
                                                      ucfirst($account['type_name']) . ' (' . 
                                                      $account['account_number'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount ($)</label>
                                <input type="number" step="0.01" min="0.01" class="form-control" 
                                       id="amount" name="amount" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <input type="text" class="form-control" id="description" 
                                       name="description" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Transfer</button>
                                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('transferForm').addEventListener('submit', function(e) {
            const fromAccount = document.getElementById('from_account');
            const amount = parseFloat(document.getElementById('amount').value);
            const balance = parseFloat(fromAccount.options[fromAccount.selectedIndex].dataset.balance);
            
            if (amount > balance) {
                e.preventDefault();
                alert('Transfer amount cannot exceed your account balance.');
            }
        });
    </script>
</body>
</html> 