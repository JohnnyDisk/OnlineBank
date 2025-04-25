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

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $from_account = $_POST['from_account'];
    $to_account = $_POST['to_account'];
    $amount = $_POST['amount'];

    // Check if from account exists and belongs to the user
    $query = "SELECT * FROM accounts WHERE account_number = '$from_account' AND user_id = '{$user_data['user_id']}'";
    $result = mysqli_query($con, $query);
    if(mysqli_num_rows($result) > 0){
        $from_account_data = mysqli_fetch_assoc($result);

        $query = "SELECT * FROM accounts WHERE is_frozen = '1' AND account_number = '$from_account'";
        $result = mysqli_query($con, $query);
        if(mysqli_num_rows($result) > 0){
            echo "Account is frozen!";
            die;

        } else {
            $query = "SELECT * FROM accounts WHERE is_frozen = '1' AND account_number = '$to_account'";
            $result = mysqli_query($con, $query);
            if(mysqli_num_rows($result) > 0){
                echo "Recipient account is frozen!";
                die;
            } else {

        // Check if to account exists and belongs to the user
        $query = "SELECT * FROM accounts WHERE account_number = '$to_account'";
        $result = mysqli_query($con, $query);
        if(mysqli_num_rows($result) > 0){
            $to_account_data = mysqli_fetch_assoc($result);

            // Perform the transfer
            if($from_account_data['balance'] >= $amount){
                $new_from_balance = $from_account_data['balance'] - $amount;
                $new_to_balance = $to_account_data['balance'] + $amount;

                $query = "UPDATE accounts SET balance = '$new_from_balance' WHERE account_number = '$from_account'";
                mysqli_query($con, $query);

                $query = "UPDATE accounts SET balance = '$new_to_balance' WHERE account_number = '$to_account'";
                mysqli_query($con, $query);

                echo "Transfer successful!";
            } else {
                echo "Insufficient balance!";
            }
        } else {
            echo "Recipient account not found!";
        }
    }
}
    } else {
        echo "From account not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Transfer</title>
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

    <form method="post">
        <label for="from_account">From Account:</label>
        <select name="from_account" id="from_account">
            <?php
            $query = "SELECT * FROM accounts WHERE user_id = '{$user_data['user_id']}'";
            $result = mysqli_query($con, $query);
            while($row = mysqli_fetch_assoc($result)){
                echo "<option value='{$row['account_number']}'>" . $account_types[$row['account_type']] . " - " . format_account_number($row['account_number']) . "</option>";
            }
            ?>
        </select>
        <br><br>
        <label for="to_account">To Account:</label>
        <input name="to_account" id="to_account">

        </input>
        <br><br>
        <input type="number" name="amount" placeholder="Amount" required>
        <input type="submit" value="Transfer">
    </form>
</body>
</html>