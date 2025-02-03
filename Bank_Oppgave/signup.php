<?php
session_start();

include('connection.php');
include('functions.php');

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $verify_password = $_POST['verify_password'];
    $is_company_account = isset($_POST['firma']) ? 1 : 0;

    if (!empty($email) && !empty($password) && !is_numeric($email)) {
        // Check if the email already exists
        $check_query = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
        $check_result = mysqli_query($con, $check_query);

        if(mysqli_num_rows($check_result) > 0){
            echo "Email already taken!";
        } else {
            if(strlen($password) < 6){
                echo "Password must be at least 6 characters!";
            } else
            if($password === $verify_password){
                $user_id = random_num(20);
                $query = "INSERT INTO users (user_id, name, email, telephone, password, is_company_account) VALUES ('$user_id', '$name', '$email', '$telephone', '$hashed_password', '$is_company_account')";
                if(mysqli_query($con, $query)){
                    header("Location: login.php");
                    die;
                } else {
                    echo "Error: " . mysqli_error($con);
                }
            } else {
                echo "Passwords do not match!";
            }
        }
    } else {
        echo "Please enter some valid information!";
    }
}
?>

<html>
<head>
    <title>Signup</title>
</head>
<body>
    <div id="box">
        <form method="post">
            <div>Signup</div>
            <input type="text" name="name" placeholder="Name" required><br>
            <input type="text" name="email" placeholder="E-mail" required><br>
            <input type="text" name="telephone" placeholder="Telephone" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <input type="password" name="verify_password" placeholder="Verify Password" required><br>
            Er dette en firma konto? <input type="checkbox" name="firma" value="Ja"><br>
            <input type="submit" value="Signup"><br><br>

            <a href="login.php">Login</a>
        </form>
    </div>
</body>
</html>