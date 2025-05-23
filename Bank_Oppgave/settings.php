<?php
session_start();

include('connection.php');
include('functions.php');

$user_data = check_login($con);

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $new_Name = $_POST['name'];
    $user_id = $user_data['user_id'];
    
    $safe_name = mysqli_real_escape_string($con, $new_Name);

    if (!empty($safe_name) && !is_numeric($safe_name)) {
        $query = "UPDATE users SET name = '$safe_name' WHERE user_id = '$user_id'";
        if (mysqli_query($con, $query)) {
            header("Location: index.php");
            die;
        } else {
            echo "Error: " . mysqli_error($con);
        }
    }
        
    }

?>

<html>
<body>
<form method="post">
<label for="name">Name:</label>
<input type="text" name="name" placeholder="name">
<br>
<input type="submit" value="Submit">
</form>
</body>
</html>

