<?php
session_start();
require_once '../config/database.php';
require_once 'admin_auth.php';

$db = new Database();
$pdo = $db->getConnection();

$error = '';
$success = '';

try {
    // Handle user status updates
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['action']) && isset($_POST['user_id'])) {
            $user_id = $_POST['user_id'];
            
            switch ($_POST['action']) {
                case 'block_accounts':
                    $stmt = $pdo->prepare("UPDATE accounts SET status = 'blocked' WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $success = "User accounts have been blocked";
                    break;
                    
                case 'activate_accounts':
                    $stmt = $pdo->prepare("UPDATE accounts SET status = 'active' WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $success = "User accounts have been activated";
                    break;
                    
                case 'delete_user':
                    // First check if user has any active accounts or transactions
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM accounts 
                        WHERE user_id = ? AND balance > 0
                    ");
                    $stmt->execute([$user_id]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception("Cannot delete user with active accounts and balance");
                    }
                    
                    $pdo->beginTransaction();
                    try {
                        // Delete related records
                        $stmt = $pdo->prepare("DELETE FROM login_logs WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        
                        $stmt = $pdo->prepare("DELETE FROM accounts WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type != 'admin'");
                        $stmt->execute([$user_id]);
                        
                        $pdo->commit();
                        $success = "User has been deleted";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                    break;
            }
        }
    }

    // Get all users except admin
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(DISTINCT a.id) as account_count,
               SUM(a.balance) as total_balance,
               GROUP_CONCAT(DISTINCT a.status) as account_statuses
        FROM users u
        LEFT JOIN accounts a ON u.id = a.user_id
        WHERE u.user_type != 'admin'
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();

} catch(Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Users</h1>
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
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Type</th>
                                        <th>Phone</th>
                                        <th>Accounts</th>
                                        <th>Total Balance</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo ucfirst($user['user_type']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                            <td><?php echo $user['account_count']; ?></td>
                                            <td>$<?php echo number_format($user['total_balance'] ?? 0, 2); ?></td>
                                            <td>
                                                <?php
                                                $statuses = explode(',', $user['account_statuses']);
                                                $status_class = in_array('blocked', $statuses) ? 'danger' : 
                                                              (in_array('inactive', $statuses) ? 'warning' : 'success');
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo in_array('blocked', $statuses) ? 'Blocked' : 
                                                          (in_array('inactive', $statuses) ? 'Inactive' : 'Active'); ?>
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
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <input type="hidden" name="action" value="block_accounts">
                                                                <button type="submit" class="dropdown-item text-danger"
                                                                        onclick="return confirm('Are you sure you want to block this user\'s accounts?')">
                                                                    Block Accounts
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <input type="hidden" name="action" value="activate_accounts">
                                                                <button type="submit" class="dropdown-item text-success">
                                                                    Activate Accounts
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <input type="hidden" name="action" value="delete_user">
                                                                <button type="submit" class="dropdown-item text-danger"
                                                                        onclick="return confirm('Are you sure you want to delete this user? This cannot be undone.')">
                                                                    Delete User
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 