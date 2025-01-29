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

// At the top of the file, after session_start():
if (!isset($_SESSION['user_name'])) {
    // Get user name if not in session
    try {
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $_SESSION['user_name'] = $user['name'] ?? 'User';
    } catch(PDOException $e) {
        $_SESSION['user_name'] = 'User';
    }
}

try {
    // Get user's accounts with their types
    $stmt = $pdo->prepare("
        SELECT a.*, at.type_name, at.interest_rate 
        FROM accounts a 
        JOIN account_types at ON a.account_type_id = at.id
        WHERE a.user_id = ? AND a.status = 'active'
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $accounts = $stmt->fetchAll();

    // Get recent transactions
    $stmt = $pdo->prepare("
        SELECT t.*, 
               a1.account_number as from_account_number,
               a2.account_number as to_account_number
        FROM transactions t
        LEFT JOIN accounts a1 ON t.from_account = a1.account_number
        LEFT JOIN accounts a2 ON t.to_account = a2.account_number
        WHERE a1.user_id = ? OR a2.user_id = ?
        ORDER BY t.transaction_date DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $transactions = $stmt->fetchAll();

    // Calculate total balance
    $total_balance = array_sum(array_column($accounts, 'balance'));

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Online Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h2>
        
        <!-- Account Summary -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Your Accounts</h5>
                        <a href="accounts.php" class="btn btn-sm btn-primary">Manage Accounts</a>
                    </div>
                    <div class="card-body">
                        <?php foreach ($accounts as $account): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-0"><?php echo ucfirst($account['type_name']); ?></h6>
                                    <small class="text-muted"><?php echo $account['account_number']; ?></small>
                                </div>
                                <div class="text-end">
                                    <h6 class="mb-0">$<?php echo number_format($account['balance'], 2); ?></h6>
                                    <small class="text-muted"><?php echo $account['interest_rate']; ?>% APR</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="border-top pt-3 mt-3">
                            <div class="d-flex justify-content-between">
                                <h6>Total Balance</h6>
                                <h6>$<?php echo number_format($total_balance, 2); ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="transfer.php" class="btn btn-primary">New Transfer</a>
                            <a href="payment.php" class="btn btn-primary">Pay Bills</a>
                            <a href="statements.php" class="btn btn-primary">View Statements</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Transactions</h5>
                <a href="statements.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $transaction['type'] === 'deposit' ? 'success' : 
                                                 ($transaction['type'] === 'withdrawal' ? 'danger' : 
                                                 ($transaction['type'] === 'transfer' ? 'primary' : 'secondary')); 
                                        ?>">
                                            <?php echo ucfirst($transaction['type']); ?>
                                        </span>
                                    </td>
                                    <td class="<?php echo $transaction['amount'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $transaction['amount'] > 0 ? '+' : '-'; ?>
                                        $<?php echo number_format(abs($transaction['amount']), 2); ?>
                                    </td>
                                    <td>$<?php echo number_format($transaction['balance_after'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 