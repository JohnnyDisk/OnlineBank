<?php

# Azure

$dbhost = "onlinebank-server.mysql.database.azure.com";
$dbuser = "JohnnyDisk";
$dbpass = "IMKuben1337!";
$dbname = "onlinebank-database";

# Localhost

# $dbhost = "localhost";
# $dbuser = "root";
# $dbpass = "";
# $dbname = "login_sample_db";

# Raspberry Pi

# $dbhost = "10.2.3.39";
# $dbuser = "Admin";
# $dbpass = "IMKuben1337!";
# $dbname = "Bank_DB";

if(!$con = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname))
{
    die("failed to connect!");
}

