<?php
// db.php
// Connects to MySQL for user authentication only.
// Starts a session safely (no “already active” errors).
// No table creation.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'darkness_list';  // Change if your DB name differs
$username = 'root';         // Default XAMPP MySQL user
$password = '';             // Default XAMPP MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>