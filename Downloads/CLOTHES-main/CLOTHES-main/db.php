<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "fashion_store";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $_SESSION['user_id'] ?? null;
$currentUserName = $_SESSION['full_name'] ?? null;
?>