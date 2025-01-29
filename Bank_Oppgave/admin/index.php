<?php
session_start();
require_once '../config/database.php';
require_once 'admin_auth.php';

$db = new Database();
$pdo = $db->getConnection();

// Get statistics
try {
    $stats = [
        'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type != 'admin'")->fetchColumn(),
        'total_accounts' => $pdo->query("SELECT COUNT(*) FROM accounts")->fetchColumn(),
        'total_transactions' => $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn(),
        'total_balance' => $pdo->query("SELECT SUM(balance) FROM accounts")->fetchColumn() ?? 0
    ];

    // Get recent transactions
    $transactions = $pdo->query("
        SELECT t.*, 
               u1.name as sender_name, 
               u2.name as receiver_name,
               a1.account_type_id as sender_type,
               a2.account_type_id as receiver_type
        FROM transactions t
        LEFT JOIN accounts a1 ON t.from_account = a1.account_number
        LEFT JOIN accounts a2 ON t.to_account = a2.account_number
        LEFT JOIN users u1 ON a1.user_id = u1.id
        LEFT JOIN users u2 ON a2.user_id = u2.id
        ORDER BY t.transaction_date DESC
        LIMIT 10
    ")->fetchAll();

    // Get recent logins
    $logins = $pdo->query("
        SELECT l.*, u.name, u.email 
        FROM login_logs l
        JOIN users u ON l.user_id = u.id
        ORDER BY l.login_time DESC
        LIMIT 10
    ")->fetchAll();

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Online Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Admin Dashboard</h1>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title">Total Users</h5>
                                <h2><?php echo number_format($stats['total_users']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title">Active Accounts</h5>
                                <h2><?php echo number_format($stats['total_accounts']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title">Transactions</h5>
                                <h2><?php echo number_format($stats['total_transactions']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card bg-warning text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title">Total Balance</h5>
                                <h2>$<?php echo number_format($stats['total_balance'], 2); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Recent Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Amount</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i', strtotime($transaction['transaction_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['sender_name'] ?? 'System'); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['receiver_name'] ?? 'System'); ?></td>
                                        <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                                        <td><?php echo ucfirst($transaction['type']); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Logins -->
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Login Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>User</th>
                                        <th>IP Address</th>
                                        <th>Activity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logins as $login): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i', strtotime($login['login_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($login['name']); ?></td>
                                        <td><?php echo htmlspecialchars($login['ip_address']); ?></td>
                                        <td><?php echo ucfirst($login['activity_type']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 