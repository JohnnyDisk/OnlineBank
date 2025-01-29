<?php
class Database {
    private $host = "localhost";
    private $db_name = "online_bank";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            // Try to create database if it doesn't exist
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host,
                    $this->username,
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Create database
                $this->conn->exec("CREATE DATABASE IF NOT EXISTS " . $this->db_name);
                $this->conn->exec("USE " . $this->db_name);
                
                // Initialize tables
                $this->initializeTables();
                
            } catch(PDOException $e2) {
                throw new PDOException("Connection failed: " . $e2->getMessage());
            }
        }
        return $this->conn;
    }

    private function initializeTables() {
        // Read and execute SQL file
        $sql = file_get_contents(__DIR__ . '/../database.sql');
        $this->conn->exec($sql);
    }
}
?> 