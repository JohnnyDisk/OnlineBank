<?php
session_start();

include('connection.php');
include('functions.php');

$user_data = check_login($con);

$account_types = array(
    'checking' => 'Checking Account',
    'savings' => 'Savings Account',
    'high_yield' => 'High-Yield Account'
);
?>

<html>
<head>
    <title>Index</title>
</head>
<body>
    <a href="logout.php">Logout</a>
    <a href="index.php">Main</a>
    <a href="account.php">Account</a>
    <a href="transfer.php">Transfer</a>

    <?php
    if ($user_data['is_admin']) {
        echo '<a href="admin_panel.php">Admin Panel</a>';
    }
    ?><br><br>
    <h1>Welcome to The Bank of Johnny</h1>

    <br>
    Hello, <?php echo $user_data['name']; ?>
    <!-- display the bank account linked with this account -->
    <h2>Accounts</h2>
    <table>
        <tr>
            <th>Account Number</th>
            <th>Account Type</th>
            <th>Balance</th>
        </tr>
        <?php
        $query = "SELECT * FROM accounts WHERE user_id = '$user_data[user_id]'";
        $result = mysqli_query($con, $query);
        while($row = mysqli_fetch_assoc($result)){
            echo "<tr>";
            echo "<td>" . format_account_number($row['account_number']) . "</td>";
            echo "<td>" . $account_types[$row['account_type']] . "</td>";
            echo "<td>" . $row['balance'] . "</td>";
            if ($row['is_frozen']) {
                echo "<td>Account is frozen</td>";
            }
            echo "</tr>";
        }
        ?>
    </table>
</body>
</html>