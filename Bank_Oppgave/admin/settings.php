<?php
session_start();
require_once '../config/database.php';
require_once 'admin_auth.php';

$db = new Database();
$pdo = $db->getConnection();

$error = '';
$success = '';

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['update_interest'])) {
            // Update interest rates
            $stmt = $pdo->prepare("
                UPDATE account_types 
                SET interest_rate = ? 
                WHERE id = ?
            ");
            
            foreach ($_POST['interest_rates'] as $type_id => $rate) {
                if (!is_numeric($rate) || $rate < 0 || $rate > 100) {
                    throw new Exception("Invalid interest rate specified");
                }
                $stmt->execute([floatval($rate), $type_id]);
            }
            $success = "Interest rates updated successfully";
        }
    }

    // Get current settings
    $account_types = $pdo->query("
        SELECT * FROM account_types 
        ORDER BY type_name
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
    <title>System Settings - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">System Settings</h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <!-- Interest Rate Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Interest Rate Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="update_interest" value="1">
                            <?php foreach ($account_types as $type): ?>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <?php echo ucfirst($type['type_name']); ?> Account Interest Rate (APR)
                                    </label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0" max="100" 
                                               class="form-control" 
                                               name="interest_rates[<?php echo $type['id']; ?>]" 
                                               value="<?php echo $type['interest_rate']; ?>" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <button type="submit" class="btn btn-primary">Update Interest Rates</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 