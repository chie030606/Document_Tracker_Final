<?php
/**
 * Common bootstrap for session and database initialization.
 * Included by page scripts to unify connection behavior.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$mysqli = null;
$pdo = null;
$conn = null;

@include_once __DIR__ . '/db.php';
if (isset($conn) && $conn instanceof mysqli) {
    $mysqli = $conn;
}

if (!$mysqli && file_exists(__DIR__ . '/database.php/db.php')) {
    @include_once __DIR__ . '/database.php/db.php';
    if (isset($conn) && $conn instanceof mysqli) {
        $mysqli = $conn;
    }
}

if (!$mysqli) {
    @$tmp = new mysqli('127.0.0.1', 'root', '', 'docu_tracker');
    if ($tmp instanceof mysqli && !$tmp->connect_errno) {
        $tmp->set_charset('utf8mb4');
        $mysqli = $tmp;
        $conn = $tmp;
    }
    unset($tmp);
}
