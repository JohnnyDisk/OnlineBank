-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS online_bank;
USE online_bank;

-- Drop existing tables if they exist
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS accounts;
DROP TABLE IF EXISTS account_types;
DROP TABLE IF EXISTS login_logs;
DROP TABLE IF EXISTS bank_bills;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS remember_tokens;
DROP TABLE IF EXISTS two_factor_auth;
SET FOREIGN_KEY_CHECKS = 1;

-- Create users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    user_type ENUM('personal', 'business', 'admin') NOT NULL DEFAULT 'personal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create account types table with interest rates
CREATE TABLE account_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type_name ENUM('savings', 'checking', 'business') NOT NULL,
    interest_rate DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create accounts table
CREATE TABLE accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    account_number VARCHAR(20) NOT NULL UNIQUE,
    account_type_id INT NOT NULL,
    balance DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    last_interest_calc_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (account_type_id) REFERENCES account_types(id)
);

-- Create transactions table
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    from_account VARCHAR(20),
    to_account VARCHAR(20),
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('deposit', 'withdrawal', 'transfer', 'payment', 'interest') NOT NULL,
    description VARCHAR(255),
    kid_number VARCHAR(25),
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create login logs table
CREATE TABLE login_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    activity_type VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create bank bills table
CREATE TABLE bank_bills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(20) NOT NULL UNIQUE,
    kid_prefix VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add password reset table
CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add remember me tokens table
CREATE TABLE remember_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Add two factor auth table
CREATE TABLE two_factor_auth (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    secret_key VARCHAR(32) NOT NULL,
    is_enabled BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert default account types with interest rates
INSERT INTO account_types (type_name, interest_rate, description) VALUES
('savings', 2.50, 'Standard savings account with competitive interest rate'),
('checking', 0.25, 'Everyday checking account for regular transactions'),
('business', 1.75, 'Business account with moderate interest rate');

-- Create default admin user (password: admin123)
INSERT INTO users (name, email, password, user_type) 
VALUES ('Admin', 'admin@bank.com', '$2y$10$8tPbzqmqmtvUgZWJjAB3wODGZDhZvnBkn1XgxUkCA8TqHEUaUYXK.', 'admin');

-- Insert some sample companies for bill payments
INSERT INTO bank_bills (company_name, account_number, kid_prefix) VALUES
('Electric Company', '1234.01.00001', 'EC'),
('Water Services', '1234.01.00002', 'WS'),
('Internet Provider', '1234.01.00003', 'IP'); 