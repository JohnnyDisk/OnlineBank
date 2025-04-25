<?php

$dbhost = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "login_sample_db";

# $dbhost = "10.2.3.39";
# $dbuser = "Admin";
# $dbpass = "IMKuben1337!";
# $dbname = "Bank_DB";

if(!$con = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname))
{
    die("failed to connect!");
}

