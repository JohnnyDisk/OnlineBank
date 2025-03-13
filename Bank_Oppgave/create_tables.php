<?php

// Include database connection
include 'connection.php';

// Check if the users table exists
$tableExists = mysqli_query($con, "SHOW TABLES LIKE 'users'");

if (mysqli_num_rows($tableExists) == 0) {
    // SQL query to create the 'users' table
    $sql = "CREATE TABLE users (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT NOT NULL,
        name VARCHAR(100) COLLATE utf8mb4_0900_ai_ci NOT NULL,
        email VARCHAR(100) COLLATE utf8mb4_0900_ai_ci NOT NULL UNIQUE,
        telephone VARCHAR(20) COLLATE utf8mb4_0900_ai_ci NOT NULL,
        password VARCHAR(100) COLLATE utf8mb4_0900_ai_ci NOT NULL,
        is_company_account BOOLEAN NOT NULL DEFAULT FALSE,
        is_admin BOOLEAN NOT NULL DEFAULT FALSE,
        date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    if (mysqli_query($con, $sql)) {
        echo "Table 'users' created successfully!<br>";
    } else {
        echo "Error creating table: " . mysqli_error($con);
    }
} else {
    echo "Table 'users' already exists.<br>";
}

// Check if the accounts table exists
$tableExists = mysqli_query($con, "SHOW TABLES LIKE 'accounts'");

if (mysqli_num_rows($tableExists) == 0) {
    // SQL query to create the 'accounts' table
    $sql = "CREATE TABLE accounts (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        account_number BIGINT NOT NULL UNIQUE,
        account_type ENUM('checking', 'savings', 'high_yield') NOT NULL,
        balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        user_id BIGINT NOT NULL,
        is_frozen BOOLEAN NOT NULL DEFAULT FALSE,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )";

    if (mysqli_query($con, $sql)) {
        echo "Table 'accounts' created successfully!<br>";
    } else {
        echo "Error creating table: " . mysqli_error($con);
    }
} else {
    echo "Table 'accounts' already exists.<br>";
}

// Close the connection
mysqli_close($con);

?>