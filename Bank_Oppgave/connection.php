<?php

# Azure

$dbhost = "onlinebank-server.mysql.database.azure.com";
$dbuser = "JohnnyDisk";
$dbpass = "IMKuben1337!";
$dbname = "onlinebank-database";
$ssl_cert = __DIR__ . "/certs/azure.pem";

// Create a connection using SSL
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, $ssl_cert, NULL, NULL);
mysqli_real_connect($conn, $dbhost, $dbuser, $dbpass, $dbname, 3306, NULL, MYSQLI_CLIENT_SSL);

if (mysqli_connect_errno()) {
    die("Failed to connect: " . mysqli_connect_error());
} else {
    echo "Connected with SSL!";
}

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

# if(!$con = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname))
# {
#     die("failed to connect!");
# }
?>
