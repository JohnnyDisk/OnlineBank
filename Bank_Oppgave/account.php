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

// Create account form
if(isset($_POST['create_account'])) {
    $account_type = $_POST['account_type'];
    $user_id = $user_data['user_id'];
    
    $query = "INSERT INTO accounts (user_id, account_type, balance) VALUES ('$user_id', '$account_type', 0)";
    if(mysqli_query($con, $query)){
        echo "Account created successfully!";
    } else {
        echo "Error: " . mysqli_error($con);
    }
}
?>

<form method="post">
    <select name="account_type">
        <?php foreach($account_types as $key => $value): ?>
            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
        <?php endforeach; ?>
    </select>
    <input type="submit" name="create_account" value="Create Account">
</form>