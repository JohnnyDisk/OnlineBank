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
$selected_account = $_GET['account'] ?? null;
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t');      // Last day of current month

try {
    // Get user's accounts
    $stmt = $pdo->prepare("
        SELECT a.*, at.type_name, at.interest_rate 
        FROM accounts a 
        JOIN account_types at ON a.account_type_id = at.id
        WHERE a.user_id = ? AND a.status = 'active'
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $accounts = $stmt->fetchAll();

    if (empty($accounts)) {
        header("Location: accounts.php?error=no_accounts");
        exit();
    }

    // If no account selected, use the first one
    if (!$selected_account) {
        $selected_account = $accounts[0]['account_number'];
    }

    // Get account details
    $stmt = $pdo->prepare("
        SELECT a.*, at.type_name, at.interest_rate 
        FROM accounts a 
        JOIN account_types at ON a.account_type_id = at.id
        WHERE a.account_number = ? AND a.user_id = ?
    ");
    $stmt->execute([$selected_account, $_SESSION['user_id']]);
    $account = $stmt->fetch();

    if (!$account) {
        throw new Exception("Invalid account selected");
    }

    // Get transactions for the selected period
    $stmt = $pdo->prepare("
        SELECT t.*,
            CASE 
                WHEN t.from_account = ? THEN 'debit'
                WHEN t.to_account = ? THEN 'credit'
                ELSE t.type 
            END as transaction_type
        FROM transactions t
        WHERE (t.from_account = ? OR t.to_account = ?)
            AND DATE(t.transaction_date) BETWEEN ? AND ?
        ORDER BY t.transaction_date DESC
    ");
    $stmt->execute([
        $selected_account, $selected_account,
        $selected_account, $selected_account,
        $start_date, $end_date
    ]);
    $transactions = $stmt->fetchAll();

    // Calculate statement summary
    $opening_balance = 0;
    $closing_balance = $account['balance'];
    $total_credits = 0;
    $total_debits = 0;

    foreach ($transactions as $transaction) {
        if ($transaction['transaction_type'] === 'credit') {
            $total_credits += $transaction['amount'];
        } else {
            $total_debits += $transaction['amount'];
        }
    }

} catch(Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Statements - Online Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3>Account Statement</h3>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label for="account" class="form-label">Select Account</label>
                                    <select class="form-select" id="account" name="account" onchange="this.form.submit()">
                                        <?php foreach ($accounts as $acc): ?>
                                            <option value="<?php echo $acc['account_number']; ?>" 
                                                    <?php echo $acc['account_number'] === $selected_account ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($acc['type_name']) . ' - ' . $acc['account_number']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo $start_date; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="<?php echo $end_date; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary d-block">View Statement</button>
                                </div>
                            </form>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5>Account Information</h5>
                                    <p>Account Number: <?php echo htmlspecialchars($account['account_number']); ?></p>
                                    <p>Account Type: <?php echo ucfirst($account['type_name']); ?></p>
                                    <p>Interest Rate: <?php echo number_format($account['interest_rate'], 2); ?>%</p>
                                </div>
                                <div class="col-md-6">
                                    <h5>Statement Summary</h5>
                                    <p>Period: <?php echo date('M d, Y', strtotime($start_date)); ?> - 
                                              <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                                    <p>Total Credits: $<?php echo number_format($total_credits, 2); ?></p>
                                    <p>Total Debits: $<?php echo number_format($total_debits, 2); ?></p>
                                    <p>Closing Balance: $<?php echo number_format($closing_balance, 2); ?></p>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th>Reference</th>
                                            <th>Type</th>
                                            <th>Debit</th>
                                            <th>Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['kid_number'] ?? '-'); ?></td>
                                                <td><?php echo ucfirst($transaction['type']); ?></td>
                                                <td><?php echo $transaction['transaction_type'] === 'debit' ? 
                                                              '$' . number_format($transaction['amount'], 2) : ''; ?></td>
                                                <td><?php echo $transaction['transaction_type'] === 'credit' ? 
                                                              '$' . number_format($transaction['amount'], 2) : ''; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                <button onclick="window.print()" class="btn btn-secondary">Print Statement</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 