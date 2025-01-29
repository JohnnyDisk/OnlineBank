<?php
session_start();
require_once '../config/database.php';
require_once 'admin_auth.php';

$db = new Database();
$pdo = $db->getConnection();

$error = '';
$success = '';

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filter settings
$type_filter = $_GET['type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$account = $_GET['account'] ?? '';

try {
    // Build the base query
    $query = "
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
        WHERE 1=1
    ";

    $params = [];

    // Add filters
    if ($type_filter) {
        $query .= " AND t.type = ?";
        $params[] = $type_filter;
    }
    if ($date_from) {
        $query .= " AND DATE(t.transaction_date) >= ?";
        $params[] = $date_from;
    }
    if ($date_to) {
        $query .= " AND DATE(t.transaction_date) <= ?";
        $params[] = $date_to;
    }
    if ($account) {
        $query .= " AND (t.from_account = ? OR t.to_account = ?)";
        $params[] = $account;
        $params[] = $account;
    }

    // Get total count for pagination
    $count_stmt = $pdo->prepare(str_replace("t.*, u1.name", "COUNT(*)", $query));
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);

    // Add sorting and pagination
    $query .= " ORDER BY t.transaction_date DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;

    // Get transactions
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

    // Get all account numbers for filter dropdown
    $accounts = $pdo->query("
        SELECT DISTINCT account_number, u.name as owner_name
        FROM accounts a
        JOIN users u ON a.user_id = u.id
        ORDER BY account_number
    ")->fetchAll();

} catch(Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Transaction History</h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">Transaction Type</label>
                                <select name="type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="deposit" <?php echo $type_filter === 'deposit' ? 'selected' : ''; ?>>Deposit</option>
                                    <option value="withdrawal" <?php echo $type_filter === 'withdrawal' ? 'selected' : ''; ?>>Withdrawal</option>
                                    <option value="transfer" <?php echo $type_filter === 'transfer' ? 'selected' : ''; ?>>Transfer</option>
                                    <option value="payment" <?php echo $type_filter === 'payment' ? 'selected' : ''; ?>>Payment</option>
                                    <option value="interest" <?php echo $type_filter === 'interest' ? 'selected' : ''; ?>>Interest</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Account</label>
                                <select name="account" class="form-select">
                                    <option value="">All Accounts</option>
                                    <?php foreach ($accounts as $acc): ?>
                                        <option value="<?php echo $acc['account_number']; ?>" 
                                                <?php echo $account === $acc['account_number'] ? 'selected' : ''; ?>>
                                            <?php echo $acc['account_number'] . ' (' . $acc['owner_name'] . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">From Date</label>
                                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">To Date</label>
                                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                                    <a href="transactions.php" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Amount</th>
                                        <th>Description</th>
                                        <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d H:i', strtotime($transaction['transaction_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $transaction['type'] === 'deposit' ? 'success' : 
                                                         ($transaction['type'] === 'withdrawal' ? 'danger' : 
                                                         ($transaction['type'] === 'transfer' ? 'primary' : 
                                                         ($transaction['type'] === 'interest' ? 'info' : 'secondary'))); 
                                                ?>">
                                                    <?php echo ucfirst($transaction['type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($transaction['from_account']): ?>
                                                    <?php echo htmlspecialchars($transaction['from_account']); ?><br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($transaction['sender_name'] ?? 'System'); ?>
                                                    </small>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($transaction['to_account']): ?>
                                                    <?php echo htmlspecialchars($transaction['to_account']); ?><br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($transaction['receiver_name'] ?? 'System'); ?>
                                                    </small>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['kid_number'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&type=<?php echo $type_filter; ?>&account=<?php echo $account; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 