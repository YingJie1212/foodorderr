<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'food_order_system';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}
?>
