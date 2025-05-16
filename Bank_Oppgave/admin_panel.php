<?php
session_start();

include('connection.php');
include('functions.php');

$user_data = check_login($con);

if (!isset($_SESSION['freeze_tokens'])) {
    $_SESSION['freeze_tokens'] = array();
}

if (!$user_data['is_admin']) {
    header("Location: index.php");
    die;
}

$account_types = array(
    'checking' => 'Checking Account',
    'savings' => 'Savings Account',
    'high_yield' => 'High-Yield Account'
);

if (isset($_POST['freeze_token'])) {
    $account_number = $_SESSION['freeze_tokens'][$_POST['freeze_token']];
    // Get the is_frozen value from the database
    $query = "SELECT is_frozen FROM accounts WHERE account_number = '$account_number'";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);

    if ($row['is_frozen'] == 1) {
        // Unfreeze the account
        $query = "UPDATE accounts SET is_frozen = 0 WHERE account_number = '$account_number'";
        if (mysqli_query($con, $query)) {
            echo "Account unfrozen successfully!";
        } else {
            echo "Error: " . mysqli_error($con);
        }
    } elseif ($row['is_frozen'] == 0) {
        // Freeze the account
        $query = "UPDATE accounts SET is_frozen = 1 WHERE account_number = '$account_number'";
        if (mysqli_query($con, $query)) {
            echo "Account frozen successfully!";
        } else {
            echo "Error: " . mysqli_error($con);
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.9.0/css/foundation.min.css" integrity="sha512-HU1oKdPcZ02o+Wxs7Mm07gVjKbPAn3i0pyud1gi3nAFTVYAVLqe+de607xHer+p9B2I9069l3nCsWFOdID/cUw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="navbar">
    <a href="logout.php">Logout</a>
    <a href="index.php">Main</a>
    <a href="account.php">Account</a>
    <a href="transfer.php">Transfer</a>
    

    <?php
    if ($user_data['is_admin']) {
        echo '<a href="admin_panel.php">Admin Panel</a></div>';
    } else {
        echo '</div>';
    }
    ?>

    <br><br>
    
    <h1>Admin Panel</h1>
    <h2>All Users</h2>
    <table border="1">
        <tr>
            <th>User ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Telephone</th>
            <th>Is Company Account</th>
            <th>Is Admin</th>
            <th>Accounts</th>
        </tr>
        <?php
        $query = "SELECT * FROM users";
        $result = mysqli_query($con, $query);
        while($user = mysqli_fetch_assoc($result)){
            echo "<tr>";
            echo "<td>" . $user['user_id'] . "</td>";
            echo "<td>" . $user['name'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['telephone'] . "</td>";
            echo "<td>" . ($user['is_company_account'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . ($user['is_admin'] ? 'Yes' : 'No') . "</td>";
            echo "<td>";
            $account_query = "SELECT * FROM accounts WHERE user_id = '{$user['user_id']}'";
            $account_result = mysqli_query($con, $account_query);
            if(mysqli_num_rows($account_result) > 0){
                echo "<table border='1'>";
                echo "<tr><th>Account Number</th><th>Account Type</th><th>Balance</th><th>Freeze</th></tr>";
                while($account = mysqli_fetch_assoc($account_result)){
                    $freeze_token = bin2hex(random_bytes(16));
                    $_SESSION['freeze_tokens'][$freeze_token] = $account['account_number'];
                    
                    echo "<tr>";
                    echo "<td>" . format_account_number($account['account_number']) . "</td>";
                    echo "<td>" . $account_types[$account['account_type']] . "</td>";
                    echo "<td>" . $account['balance'] . "</td>";
                    echo '<td>
                    <form method="POST">
                                <input type="hidden" name="freeze_token" value="' . $freeze_token . '" />
                                <input type="submit" name="freeze" value="' . ($account['is_frozen'] ? 'unfreeze' : 'freeze') . '" />
                            </form>
                            </td>';
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "No accounts";
            }
            echo "</td>";
            echo "</tr>";
        }
        ?>
    </table>
</body>
</html>