<?php
$host = "localhost";
$user = "root";
$pass = ""; // Default XAMPP password is empty
$dbname = "login";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>