<?php
session_start();

include('connection.php');
include('functions.php');

$user_data = check_login($con);

if($_SERVER['REQUEST_METHOD'] == "post"){
    echo "test";
    $new_name = $_POST['name'];
    $user_id = $user_data['user_id'];

    $safe_name = mysqli_escape_string($con, $new_name);
    if(!empty($new_name) && !is_numeric($new_name)){
        $query = "UPDATE users SET name = '$safe_name' WHERE user_id = '$user_id";
        if(mysqli_query($con, $query)){
            echo "name change successfully";
        } else {
            echo "error in name change";
        }
    }


}

?>

<html>
<body>
<form method="post">
<label for="name">Name:</label>
<input type="text" name="name" placeholder="name: ">
<input type="submit" value="Submit">
</form>
</body>
</html>