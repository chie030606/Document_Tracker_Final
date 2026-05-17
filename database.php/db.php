<?php
$conn = mysqli_connect("localhost", "root", "", "docu_tracker");

if (!$conn) {
    die("Connection failed");
}

mysqli_set_charset($conn, 'utf8mb4');
?>