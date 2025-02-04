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
    $from_account = str_replace(' ', '', $_POST['from_account']); // Remove spaces
    $to_account_number = str_replace(' ', '', $_POST['to_account_number']); // Remove spaces
    $amount = $_POST['amount'];

    // Check if from account exists and belongs to the user
    $query = "SELECT * FROM accounts WHERE account_number = '$from_account' AND user_id = '{$user_data['user_id']}'";
    $result = mysqli_query($con, $query);
    if(mysqli_num_rows($result) > 0){
        $from_account_data = mysqli_fetch_assoc($result);

        // Check if the recipient's account exists
        $query = "SELECT * FROM accounts WHERE account_number = '$to_account_number' LIMIT 1";
        $result = mysqli_query($con, $query);
        if(mysqli_num_rows($result) > 0){
            $to_account_data = mysqli_fetch_assoc($result);

            // Perform the transfer
            if($from_account_data['balance'] >= $amount){
                $new_from_balance = $from_account_data['balance'] - $amount;
                $new_to_balance = $to_account_data['balance'] + $amount;

                $query = "UPDATE accounts SET balance = '$new_from_balance' WHERE account_number = '$from_account'";
                mysqli_query($con, $query);

                $query = "UPDATE accounts SET balance = '$new_to_balance' WHERE account_number = '$to_account_number'";
                mysqli_query($con, $query);

                echo "Transfer successful!";
            } else {
                echo "Insufficient balance!";
            }
        } else {
            echo "Recipient account not found!";
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
    <style>
        input[name="to_account_number"] {
            letter-spacing: 1px;
        }
    </style>
    <script>
        function formatAccountNumber(input) {
            let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || '';
            input.value = formattedValue;
        }
    </script>
</head>
<body>
    <form method="post">
        <select name="from_account">
            <?php
            $query = "SELECT * FROM accounts WHERE user_id = '{$user_data['user_id']}'";
            $result = mysqli_query($con, $query);
            while($row = mysqli_fetch_assoc($result)){
                echo "<option value='{$row['account_number']}'>" . $account_types[$row['account_type']] . " - " . format_account_number($row['account_number']) . "</option>";
            }
            ?>
        </select>
        <input type="text" name="to_account_number" placeholder="Recipient's Account Number" maxlength="19" oninput="formatAccountNumber(this)" required>
        <input type="number" name="amount" placeholder="Amount" required>
        <input type="submit" value="Transfer">
    </form>
</body>
</html>