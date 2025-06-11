<?php
session_start();

include('connection.php');
include('functions.php');

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!empty($email) && !empty($password) && !is_numeric($email)) {

        $query = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
        $result = mysqli_query($con, $query);

        if($result){
            if(mysqli_num_rows($result) > 0){
                $user_data = mysqli_fetch_assoc($result);
                
                if(password_verify($password, $user_data['password'])){
                    $_SESSION['user_id'] = $user_data['user_id'];
                    header("Location: index.php");
                    die;
                } else {
                    echo "Wrong password!";
                }
            } else {
                echo "Email not found!";
            }
        } else {
            echo "Query failed: " . mysqli_error($con);
        }
    } else {
        echo "Please enter valid email and password!";
    }
}
?>

<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.9.0/css/foundation.min.css" integrity="sha512-HU1oKdPcZ02o+Wxs7Mm07gVjKbPAn3i0pyud1gi3nAFTVYAVLqe+de607xHer+p9B2I9069l3nCsWFOdID/cUw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="navbar">
    <a href="signup.php">Signup</a>
    

    </div>
    <br><br>

    <div id="box" style="margin-left: 40%;">
        <form method="post">
            <div class="form">Login
            <input type="text" name="email" placeholder="E-mail" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <input type="submit" value="Login"><br><br>
            </div>

            <a href="signup.php">Sign up</a>
        </form>
    </div>
</body>
</html>