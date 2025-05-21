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

$message = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $transfer_type = $_POST['transfer_type'];
    $from_account = $_POST['from_account'];
    $to_account = ($transfer_type == "own") ? $_POST['to_account_select'] : $_POST['to_account_input'];
    $amount = floatval($_POST['amount']);

    $query = "SELECT * FROM accounts WHERE account_number = '$from_account' AND user_id = '{$user_data['user_id']}'";
    $result = mysqli_query($con, $query);
    if (mysqli_num_rows($result) > 0) {
        $from_account_data = mysqli_fetch_assoc($result);

        // Check if either account is frozen
        $frozen_query = "SELECT * FROM accounts WHERE is_frozen = '1' AND account_number IN ('$from_account', '$to_account')";
        $frozen_result = mysqli_query($con, $frozen_query);
        if (mysqli_num_rows($frozen_result) > 0) {
            $message = "One of the accounts is frozen!";
        } else {
            if ($transfer_type == "own") {
                $query = "SELECT * FROM accounts WHERE account_number = '$to_account' AND user_id = '{$user_data['user_id']}'";
            } else {
                $query = "SELECT * FROM accounts WHERE account_number = '$to_account'";
            }

            $result = mysqli_query($con, $query);
            if (mysqli_num_rows($result) > 0) {
                $to_account_data = mysqli_fetch_assoc($result);

                if ($from_account_data['balance'] >= $amount) {
                    $new_from_balance = $from_account_data['balance'] - $amount;
                    $new_to_balance = $to_account_data['balance'] + $amount;

                    mysqli_query($con, "UPDATE accounts SET balance = '$new_from_balance' WHERE account_number = '$from_account'");
                    mysqli_query($con, "UPDATE accounts SET balance = '$new_to_balance' WHERE account_number = '$to_account'");

                    $message = "Transfer successful!";
                } else {
                    $message = "Insufficient balance!";
                }
            } else {
                $message = "Recipient account not found!";
            }
        }
    } else {
        $message = "From account not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.9.0/css/foundation.min.css" integrity="sha512-HU1oKdPcZ02o+Wxs7Mm07gVjKbPAn3i0pyud1gi3nAFTVYAVLqe+de607xHer+p9B2I9069l3nCsWFOdID/cUw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="style.css">    
<title>Transfer & Send</title>
<script>
    function toggleFields() {
        const type = document.getElementById("transfer_type").value;
        document.getElementById("to_select").style.display = (type === "own") ? "block" : "none";
        document.getElementById("to_input").style.display = (type === "send") ? "block" : "none";
    }
</script>
</head>
<body>

<div class="navbar">
    <a href="logout.php">Logout</a>
    <a href="index.php">Main</a>
    <a href="account.php">Account</a>
    <a href="transfer.php">Transfer</a>
    <a href="faq.php">FAQ</a>
    <?php
    if ($user_data['is_admin']) {
        echo '<a href="admin_panel.php">Admin Panel</a></div>';
    } else {
        echo '</div>';
    }
    ?>
</div>

<br><br>

<?php if ($message) echo "<strong>$message</strong><br><br>"; ?>

<form method="post">
    <label for="transfer_type">Transfer Type:</label>
    <select name="transfer_type" id="transfer_type" onchange="toggleFields()">
        <option value="own">Transfer between my accounts</option>
        <option value="send">Send to someone else</option>
    </select>
    <br><br>

    <label for="from_account">From Account:</label>
    <select name="from_account" id="from_account">
        <?php
        $query = "SELECT * FROM accounts WHERE user_id = '{$user_data['user_id']}'";
        $result = mysqli_query($con, $query);
        $account_options = []; // store rows for reuse
        while ($row = mysqli_fetch_assoc($result)) {
            $account_options[] = $row;
            echo "<option value='{$row['account_number']}'>" . $account_types[$row['account_type']] . " - " . format_account_number($row['account_number']) . "</option>";
        }
        ?>
    </select>
    <br><br>

    <!-- Dropdown for own account transfer -->
    <div id="to_select">
        <label for="to_account_select">To Account:</label>
        <select name="to_account_select" id="to_account_select">
            <?php
            foreach ($account_options as $row) {
                echo "<option value='{$row['account_number']}'>" . $account_types[$row['account_type']] . " - " . format_account_number($row['account_number']) . "</option>";
            }
            ?>
        </select>
    </div>

    <!-- Input for sending to someone else -->
    <div id="to_input" style="display:none;">
        <label for="to_account_input">Recipient Account Number:</label>
        <input type="text" name="to_account_input" id="to_account_input">
    </div>
    <br>

    <label for="amount">Amount:</label>
    <input type="number" name="amount" placeholder="Amount" min="0.01" step="0.01" required>
    <input type="submit" value="Submit">
</form>

<script>
    toggleFields(); // Ensure correct display on reload
</script>
</body>
</html>
