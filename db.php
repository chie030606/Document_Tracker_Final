<?php
/**
 * Shared database bootstrap for Docu Tracker.
 * Supports mysqli via $mysqli/$conn and keeps legacy compatibility.
 */
$mysqli = null;
$conn = null;
$pdo = null;

$dbHost = '127.0.0.1';
$dbUser = 'root';
$dbPass = '';
$dbName = 'docu_tracker';

$mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli instanceof mysqli && !$mysqli->connect_errno) {
    $mysqli->set_charset('utf8mb4');
    $conn = $mysqli;
} else {
    $mysqli = null;
    $conn = null;
}
