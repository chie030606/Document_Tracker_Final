<?php
// Safe migration runner: adds `full_name` column to `requests` if missing.

// Attempt to include existing DB connection helpers
if (file_exists(__DIR__ . '/../db.php')) {
    include_once __DIR__ . '/../db.php';
}
if (!isset($mysqli) && file_exists(__DIR__ . '/../database.php/db.php')) {
    include_once __DIR__ . '/../database.php/db.php';
}

if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    // try default local XAMPP credentials
    $mysqli = @new mysqli('127.0.0.1','root','','docu_tracker');
}

if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
    echo "Could not connect to MySQL.\n";
    exit(1);
}

$colCheck = $mysqli->query("SHOW COLUMNS FROM `requests` LIKE 'full_name'");
if ($colCheck && $colCheck->num_rows > 0) {
    echo "Column `full_name` already exists. No action taken.\n";
    exit(0);
}

// run alter
$sql = "ALTER TABLE `requests` ADD COLUMN `full_name` VARCHAR(255) NULL AFTER `tracking_id`";
if ($mysqli->query($sql)) {
    echo "Migration applied: column `full_name` added to `requests`.\n";
} else {
    echo "Failed to apply migration: (" . $mysqli->errno . ") " . $mysqli->error . "\n";
}

$mysqli->close();
