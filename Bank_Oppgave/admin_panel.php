<?php
session_start();

include('connection.php');
include('functions.php');

$user_data = check_login($con);

if (!$user_data['is_admin']) {
    header("Location: index.php");
    die;
}

$account_types = array(
    'checking' => 'Checking Account',
    'savings' => 'Savings Account',
    'high_yield' => 'High-Yield Account'
);

if (isset($_POST['freeze'])) {
    echo "Account frozen!";
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
</head>
<body>
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
                    echo "<tr>";
                    echo "<td>" . format_account_number($account['account_number']) . "</td>";
                    echo "<td>" . $account_types[$account['account_type']] . "</td>";
                    echo "<td>" . $account['balance'] . "</td>";
                    echo '<form method="POST"><td> <input type="submit" name="freeze" value="freeze" /> </td></form>';
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