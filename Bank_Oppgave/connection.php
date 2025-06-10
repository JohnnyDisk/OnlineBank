<?php

# Azure

# $dbhost = "onlinebank-server.mysql.database.azure.com";
# $dbuser = "JohnnyDisk";
# $dbpass = "IMKuben1337!";
# $dbname = "onlinebank-database";
# $ssl_cert = __DIR__ . "/certs/azure.pem";

// Create a connection using SSL
# $con = mysqli_init();
# mysqli_ssl_set($con, NULL, NULL, $ssl_cert, NULL, NULL);
# mysqli_real_connect($con, $dbhost, $dbuser, $dbpass, $dbname, 3306, NULL, MYSQLI_CLIENT_SSL);

# if (mysqli_connect_errno()) {
#     die("Failed to connect: " . mysqli_connect_error());
# }

# Localhost

# $dbhost = "localhost";
# $dbuser = "root";
# $dbpass = "";
# $dbname = "login_sample_db";

# Raspberry Pi

$dbhost = "localhost";
$dbuser = "johnny";
$dbpass = "disk123";
$dbname = "online_bank";

if(!$con = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname))
{
    die("failed to connect!");
}
?>
