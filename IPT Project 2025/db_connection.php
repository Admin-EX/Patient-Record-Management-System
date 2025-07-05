<?php
$host = "sql306.byethost16.com";
$dbname = "b16_38839040_user_auth"; // Replace with your actual DB name
$username = "b16_38839040";
$password = "@Free247"; // Your ByetHost MySQL password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>