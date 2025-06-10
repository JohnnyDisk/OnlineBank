<?php
session_start();

include('connection.php');
include('functions.php');

$user_data = check_login($con);

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $new_name = $_POST['name'];
    $user_id = $user_data['user_id'];

    $safe_name = mysqli_escape_string($con, $new_name);
    if (!empty($safe_name) && !is_numeric($safe_name)) {
        $query = "UPDATE users SET name = '$safe_name' WHERE user_id = '$user_id'";
        if (mysqli_query($con, $query)) {
            echo "Name changed successfully";
        } else {
            echo "Error changing name";
        }
        
    }

?>

<html>
<body>
<form method="post">
<label for="name">Name:</label>
<input type="type" name="name" placeholder="name">
<input type="submit" value="submit">
</form>
</body>
</html>
