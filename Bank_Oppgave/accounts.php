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
    // Get available account types with interest rates
    $stmt = $pdo->prepare("SELECT * FROM account_types ORDER BY type_name");
    $stmt->execute();
    $account_types = $stmt->fetchAll();

    // Get user's existing accounts
    $stmt = $pdo->prepare("
        SELECT a.*, at.type_name, at.interest_rate 
        FROM accounts a 
        JOIN account_types at ON a.account_type_id = at.id
        WHERE a.user_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_accounts = $stmt->fetchAll();

    // Handle account creation
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $account_type_id = $_POST['account_type'];
            
            // Validate account type
            $stmt = $pdo->prepare("SELECT id FROM account_types WHERE id = ?");
            $stmt->execute([$account_type_id]);
            if (!$stmt->fetch()) {
                throw new Exception("Invalid account type selected");
            }

            // Generate account number
            $stmt = $pdo->prepare("SELECT type_name FROM account_types WHERE id = ?");
            $stmt->execute([$account_type_id]);
            $type = $stmt->fetch()['type_name'];
            
            $bankCode = '1234';
            $typeCode = ($type === 'savings') ? '01' : 
                       (($type === 'checking') ? '02' : '03');
            $accountNum = str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $account_number = $bankCode . '.' . $typeCode . '.' . $accountNum;

            // Create account
            $stmt = $pdo->prepare("
                INSERT INTO accounts (user_id, account_type_id, account_number, status) 
                VALUES (?, ?, ?, 'active')
            ");
            $stmt->execute([$_SESSION['user_id'], $account_type_id, $account_number]);

            $success = "Account created successfully!";
            
            // Refresh account list
            $stmt = $pdo->prepare("
                SELECT a.*, at.type_name, at.interest_rate 
                FROM accounts a 
                JOIN account_types at ON a.account_type_id = at.id
                WHERE a.user_id = ?
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $user_accounts = $stmt->fetchAll();

        } catch (Exception $e) {
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
    <title>Manage Accounts - Online Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3>Create New Account</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="account_type" class="form-label">Account Type</label>
                                <select class="form-select" id="account_type" name="account_type" required>
                                    <option value="">Select account type</option>
                                    <?php foreach ($account_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>">
                                            <?php echo ucfirst($type['type_name']) . 
                                                      ' (' . number_format($type['interest_rate'], 2) . '% APR)'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Create Account</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Your Accounts</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_accounts)): ?>
                            <p class="text-muted">You don't have any accounts yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Account Number</th>
                                            <th>Type</th>
                                            <th>Balance</th>
                                            <th>Interest Rate</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($user_accounts as $account): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($account['account_number']); ?></td>
                                                <td><?php echo ucfirst($account['type_name']); ?></td>
                                                <td>$<?php echo number_format($account['balance'], 2); ?></td>
                                                <td><?php echo number_format($account['interest_rate'], 2); ?>%</td>
                                                <td>
                                                    <span class="badge bg-<?php echo $account['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst($account['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="statements.php?account=<?php echo $account['account_number']; ?>" 
                                                       class="btn btn-sm btn-info">View Statements</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 