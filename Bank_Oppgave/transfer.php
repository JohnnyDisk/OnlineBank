<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'bank_db';

$error = '';
$success = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get sender's account info
    $stmt = $pdo->prepare("SELECT a.*, u.name FROM accounts a JOIN users u ON a.user_id = u.id WHERE a.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $sender_account = $stmt->fetch();

    // Get list of other users for transfer
    $stmt = $pdo->prepare("SELECT u.id, u.name, a.account_number FROM users u JOIN accounts a ON u.id = a.user_id WHERE u.id != ?");
    $stmt->execute([$_SESSION['user_id']]);
    $recipients = $stmt->fetchAll();

    // Process transfer
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $recipient_account = $_POST['recipient'];
            $amount = floatval($_POST['amount']);
            $description = trim($_POST['description']);

            // Validate amount
            if ($amount <= 0) {
                throw new Exception("Amount must be greater than zero");
            }

            // Check if sender has enough balance
            if ($sender_account['balance'] < $amount) {
                throw new Exception("Insufficient funds");
            }

            // Begin transaction
            $pdo->beginTransaction();

            // Get recipient account
            $stmt = $pdo->prepare("SELECT * FROM accounts WHERE account_number = ?");
            $stmt->execute([$recipient_account]);
            $recipient_data = $stmt->fetch();

            if (!$recipient_data) {
                throw new Exception("Recipient account not found");
            }

            // Update sender's balance
            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE user_id = ?");
            $stmt->execute([$amount, $_SESSION['user_id']]);

            // Update recipient's balance
            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_number = ?");
            $stmt->execute([$amount, $recipient_account]);

            // Record transaction for sender (debit)
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'debit', ?)");
            $stmt->execute([$_SESSION['user_id'], $amount, $description]);

            // Record transaction for recipient (credit)
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'credit', ?)");
            $stmt->execute([$recipient_data['user_id'], $amount, $description]);

            $pdo->commit();
            $success = "Transfer successful!";

            // Refresh sender's account info
            $stmt = $pdo->prepare("SELECT a.*, u.name FROM accounts a JOIN users u ON a.user_id = u.id WHERE a.user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $sender_account = $stmt->fetch();

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
    <title>Transfer Money - Banking System</title>
    
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Transfer Money</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            Your current balance: $<?php echo number_format($sender_account['balance'], 2); ?>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="recipient" class="form-label">Recipient</label>
                                <select class="form-select" id="recipient" name="recipient" required>
                                    <option value="">Select recipient</option>
                                    <?php foreach ($recipients as $recipient): ?>
                                        <option value="<?php echo htmlspecialchars($recipient['account_number']); ?>">
                                            <?php echo htmlspecialchars($recipient['name'] . ' (' . $recipient['account_number'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount ($)</label>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <input type="text" class="form-control" id="description" name="description" required>
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
</body>
</html> 