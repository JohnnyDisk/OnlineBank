<?php
session_start();
require_once '../config/database.php';
require_once 'admin_auth.php';

$db = new Database();
$pdo = $db->getConnection();

$error = '';
$success = '';

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['calculate_interest'])) {
        $pdo->beginTransaction();
        
        // Get all active accounts that haven't had interest calculated today
        $stmt = $pdo->prepare("
            SELECT a.*, at.interest_rate, at.type_name
            FROM accounts a
            JOIN account_types at ON a.account_type_id = at.id
            WHERE a.status = 'active'
            AND (a.last_interest_calc_date IS NULL 
                OR DATE(a.last_interest_calc_date) < CURDATE())
        ");
        $stmt->execute();
        $accounts = $stmt->fetchAll();

        $total_interest = 0;
        $accounts_updated = 0;

        foreach ($accounts as $account) {
            // Calculate daily interest (annual rate / 365)
            $daily_rate = $account['interest_rate'] / 100 / 365;
            $interest_amount = $account['balance'] * $daily_rate;

            if ($interest_amount > 0) {
                // Update account balance and last calculation date
                $stmt = $pdo->prepare("
                    UPDATE accounts 
                    SET balance = balance + ?,
                        last_interest_calc_date = CURDATE()
                    WHERE id = ?
                ");
                $stmt->execute([$interest_amount, $account['id']]);

                // Record interest transaction
                $stmt = $pdo->prepare("
                    INSERT INTO transactions (
                        to_account, amount, type, description
                    ) VALUES (?, ?, 'interest', ?)
                ");
                $stmt->execute([
                    $account['account_number'],
                    $interest_amount,
                    'Daily interest at ' . $account['interest_rate'] . '% APR'
                ]);

                $total_interest += $interest_amount;
                $accounts_updated++;
            }
        }

        $pdo->commit();
        $success = "Interest calculated and applied to $accounts_updated accounts. Total interest: $" . 
                  number_format($total_interest, 2);
    }

    // Get interest calculation statistics
    $stats = [
        'total_interest_paid' => $pdo->query("
            SELECT SUM(amount) FROM transactions WHERE type = 'interest'
        ")->fetchColumn() ?? 0,
        'last_calculation' => $pdo->query("
            SELECT MAX(transaction_date) FROM transactions WHERE type = 'interest'
        ")->fetchColumn(),
        'accounts_earning_interest' => $pdo->query("
            SELECT COUNT(*) FROM accounts a 
            JOIN account_types at ON a.account_type_id = at.id
            WHERE a.status = 'active' AND at.interest_rate > 0
        ")->fetchColumn()
    ];

    // Get interest rates by account type
    $interest_rates = $pdo->query("
        SELECT type_name, interest_rate, 
               (SELECT COUNT(*) FROM accounts WHERE account_type_id = account_types.id) as account_count
        FROM account_types
        ORDER BY interest_rate DESC
    ")->fetchAll();

} catch(Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculate Interest - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Interest Calculation</h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Interest Paid</h5>
                                <h2>$<?php echo number_format($stats['total_interest_paid'], 2); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Accounts Earning Interest</h5>
                                <h2><?php echo number_format($stats['accounts_earning_interest']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Last Calculation</h5>
                                <h2><?php echo $stats['last_calculation'] ? date('Y-m-d', strtotime($stats['last_calculation'])) : 'Never'; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Interest Rates Table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Current Interest Rates</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Account Type</th>
                                        <th>Interest Rate (APR)</th>
                                        <th>Active Accounts</th>
                                        <th>Daily Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($interest_rates as $rate): ?>
                                        <tr>
                                            <td><?php echo ucfirst($rate['type_name']); ?></td>
                                            <td><?php echo number_format($rate['interest_rate'], 2); ?>%</td>
                                            <td><?php echo number_format($rate['account_count']); ?></td>
                                            <td><?php echo number_format($rate['interest_rate'] / 365, 4); ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Calculate Interest Button -->
                <div class="card">
                    <div class="card-body">
                        <form method="POST" onsubmit="return confirm('Are you sure you want to calculate and apply interest to all eligible accounts?');">
                            <input type="hidden" name="calculate_interest" value="1">
                            <button type="submit" class="btn btn-primary">Calculate and Apply Daily Interest</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 