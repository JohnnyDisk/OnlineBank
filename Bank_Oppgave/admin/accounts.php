<?php
session_start();
require_once '../config/database.php';
require_once 'admin_auth.php';

$db = new Database();
$pdo = $db->getConnection();

$error = '';
$success = '';

try {
    // Handle account actions
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['action']) && isset($_POST['account_id'])) {
            $account_id = $_POST['account_id'];
            
            switch ($_POST['action']) {
                case 'block_account':
                    $stmt = $pdo->prepare("UPDATE accounts SET status = 'blocked' WHERE id = ?");
                    $stmt->execute([$account_id]);
                    $success = "Account has been blocked";
                    break;
                    
                case 'activate_account':
                    $stmt = $pdo->prepare("UPDATE accounts SET status = 'active' WHERE id = ?");
                    $stmt->execute([$account_id]);
                    $success = "Account has been activated";
                    break;
                    
                case 'adjust_balance':
                    if (!isset($_POST['amount']) || !is_numeric($_POST['amount'])) {
                        throw new Exception("Invalid amount specified");
                    }
                    
                    $amount = floatval($_POST['amount']);
                    $description = trim($_POST['description'] ?? 'Balance adjustment by admin');
                    
                    $pdo->beginTransaction();
                    try {
                        // Get account details
                        $stmt = $pdo->prepare("SELECT account_number, balance FROM accounts WHERE id = ?");
                        $stmt->execute([$account_id]);
                        $account = $stmt->fetch();
                        
                        if (!$account) {
                            throw new Exception("Account not found");
                        }
                        
                        // Update balance
                        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
                        $stmt->execute([$amount, $account_id]);
                        
                        // Record transaction
                        $type = $amount > 0 ? 'deposit' : 'withdrawal';
                        $stmt = $pdo->prepare("
                            INSERT INTO transactions (from_account, to_account, amount, type, description) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $amount < 0 ? $account['account_number'] : null,
                            $amount > 0 ? $account['account_number'] : null,
                            abs($amount),
                            $type,
                            $description
                        ]);
                        
                        $pdo->commit();
                        $success = "Balance has been adjusted";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                    break;
            }
        }
    }

    // Get all accounts with user information
    $stmt = $pdo->prepare("
        SELECT a.*, 
               u.name as user_name, 
               u.email,
               at.type_name,
               at.interest_rate,
               (SELECT COUNT(*) FROM transactions t 
                WHERE t.from_account = a.account_number 
                   OR t.to_account = a.account_number) as transaction_count
        FROM accounts a
        JOIN users u ON a.user_id = u.id
        JOIN account_types at ON a.account_type_id = at.id
        ORDER BY a.created_at DESC
    ");
    $stmt->execute();
    $accounts = $stmt->fetchAll();

} catch(Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Accounts</h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Account Number</th>
                                        <th>Owner</th>
                                        <th>Type</th>
                                        <th>Balance</th>
                                        <th>Interest Rate</th>
                                        <th>Transactions</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($accounts as $account): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($account['account_number']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($account['user_name']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($account['email']); ?></small>
                                            </td>
                                            <td><?php echo ucfirst($account['type_name']); ?></td>
                                            <td>$<?php echo number_format($account['balance'], 2); ?></td>
                                            <td><?php echo number_format($account['interest_rate'], 2); ?>%</td>
                                            <td><?php echo $account['transaction_count']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $account['status'] === 'active' ? 'success' : 
                                                                           ($account['status'] === 'blocked' ? 'danger' : 'warning'); ?>">
                                                    <?php echo ucfirst($account['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" 
                                                            data-bs-toggle="dropdown">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <button type="button" class="dropdown-item"
                                                                    onclick="showAdjustBalanceModal(<?php echo $account['id']; ?>)">
                                                                Adjust Balance
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <form method="POST">
                                                                <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                                                <input type="hidden" name="action" value="block_account">
                                                                <button type="submit" class="dropdown-item text-danger"
                                                                        onclick="return confirm('Are you sure you want to block this account?')">
                                                                    Block Account
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form method="POST">
                                                                <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                                                <input type="hidden" name="action" value="activate_account">
                                                                <button type="submit" class="dropdown-item text-success">
                                                                    Activate Account
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
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

    <!-- Adjust Balance Modal -->
    <div class="modal fade" id="adjustBalanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Account Balance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="adjust_balance">
                        <input type="hidden" name="account_id" id="adjustAccountId">
                        
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (use negative for withdrawal)</label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description" 
                                   placeholder="Reason for adjustment">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Adjust Balance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showAdjustBalanceModal(accountId) {
            document.getElementById('adjustAccountId').value = accountId;
            new bootstrap.Modal(document.getElementById('adjustBalanceModal')).show();
        }
    </script>
</body>
</html> 