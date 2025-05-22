<?php
session_start();

include('connection.php');
include('functions.php');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $verify_password = $_POST['verify_password'];
    $is_company_account = isset($_POST['firma']) ? 1 : 0;

    if (!empty($email) && !empty($password) && !empty($telephone)) {

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "Invalid email address!";
        }
        // Check if the email is actually a phone number
        elseif (preg_match('/^\+?[0-9]+$/', $email)) {
            echo "Email cannot be a phone number!";
        }
        // Validate telephone number
        elseif (!preg_match('/^\+?[0-9]{8,15}$/', $telephone)) {
            echo "Invalid telephone number!";
        } else {
            // Check if the email already exists
            $safe_email = mysqli_real_escape_string($con, $email);
            $check_query = "SELECT * FROM users WHERE email = '$safe_email' LIMIT 1";
            $check_result = mysqli_query($con, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                echo "Email already taken!";
            } else {
                if (strlen($password) < 6) {
                    echo "Password must be at least 6 characters!";
                } elseif ($password !== $verify_password) {
                    echo "Passwords do not match!";
                } else {
                    $user_id = random_num(20);
                    $safe_name = mysqli_real_escape_string($con, $name);
                    $safe_telephone = mysqli_real_escape_string($con, $telephone);
                    $query = "INSERT INTO users (user_id, name, email, telephone, password, is_company_account) 
                              VALUES ($user_id, '$safe_name', '$safe_email', '$safe_telephone', '$hashed_password', $is_company_account)";
                    if (mysqli_query($con, $query)) {
                        header("Location: login.php");
                        die;
                    } else {
                        echo "Error: " . mysqli_error($con);
                    }
                }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.9.0/css/foundation.min.css" integrity="sha512-HU1oKdPcZ02o+Wxs7Mm07gVjKbPAn3i0pyud1gi3nAFTVYAVLqe+de607xHer+p9B2I9069l3nCsWFOdID/cUw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="navbar">
        <a href="login.php">Login</a>
        <a href="faq.php">FAQ</a>
    </div>
    
    <br><br>

    <div id="box">
        <form method="post">
            <div class="form">Signup
                <input type="text" name="name" placeholder="Name" required><br>
                <input type="text" name="email" placeholder="E-mail" required><br>
                <input type="text" name="telephone" placeholder="Telephone" required><br>
                <input type="password" name="password" placeholder="Password" required><br>
                <input type="password" name="verify_password" placeholder="Verify Password" required><br>
                Er dette en firma konto? <input type="checkbox" name="firma" value="Ja"><br>
                <input type="submit" value="Signup"><br><br>
            </div>

            <a href="login.php">Login</a>
        </form>
    </div>
</body>
</html>
