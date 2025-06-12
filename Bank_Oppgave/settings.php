<?php
session_start();

include('connection.php');
include('functions.php');

$user_data = check_login($con);

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $new_name = $_POST['name'];
    $new_password = $_POST['password'];
    $password_verify = $_POST['password_verify'];
    $user_id = $user_data['user_id'];

    $safe_name = mysqli_escape_string($con, $new_name);
    if(!empty($new_name) && !is_numeric($new_name)){
        $query = "UPDATE users SET name = '$safe_name' WHERE user_id = '$user_id'";
        if(mysqli_query($con, $query)){
            echo "name change successfully";
        } else {
            echo "error in name change";
        }
    }

    $safe_password = mysqli_escape_string($con, $new_password);
    if(!empty($new_password) && $new_password == $password_verify && password_verify($new_password, $user_data['password'])){
        $hashed_password = password_hash($safe_password, PASSWORD_DEFAULT);
        $query2 = "UPDATE users SET password = '$safe_password' WHERE user_id = '$user_id'";
        if(mysqli_query($con, $query2)){
            echo "password change successfully";
        } else {
            echo "error in password change";
        }
    }


}

?>

<html>
<body>
<form method="post">
<label for="name">Name:</label>
<input type="text" name="name" placeholder="name: "> <br>
<label for="name">Password:</label>
<input type="password" name="password" placeholder="password: ">
<input type="password_verify" name="password" placeholder="verify password: "> <br>
<input type="submit" value="Submit">
</form>
</body>
</html>