<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

$host = 'localhost';
$dbname = 'gamerental';
$username = 'root';
$password = '';
$port = 3307;

$conn = mysqli_connect($host, $username, $password, $dbname, $port);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error() . ". Please ensure you have created a database named '$dbname' and imported schema.sql");
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

// Track user activity
if (isLoggedIn()) {
    $uid = (int)$_SESSION['user_id'];
    mysqli_query($conn, "UPDATE users SET last_activity = CURRENT_TIMESTAMP WHERE id = $uid");
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}
?>
