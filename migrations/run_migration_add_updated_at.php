<?php
// Safe migration runner: adds `updated_at` column to `requests` if missing, then backfills from `created_at`.

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

$colCheck = $mysqli->query("SHOW COLUMNS FROM `requests` LIKE 'updated_at'");
if ($colCheck && $colCheck->num_rows > 0) {
    echo "Column `updated_at` already exists. Backfilling any NULL values...\n";
    if ($mysqli->query("UPDATE `requests` SET `updated_at` = `created_at` WHERE `updated_at` IS NULL")) {
        echo "Backfill complete.\n";
    } else {
        echo "Backfill failed: (" . $mysqli->errno . ") " . $mysqli->error . "\n";
    }
    $mysqli->close();
    exit(0);
}

// run alter
$sql = "ALTER TABLE `requests` ADD COLUMN `updated_at` DATETIME NULL AFTER `created_at'";
// Note: some MySQL versions may not allow single quotes around column names; using SQL without quoting `created_at` position.
$sql = "ALTER TABLE `requests` ADD COLUMN `updated_at` DATETIME NULL AFTER `created_at`";

if ($mysqli->query($sql)) {
    echo "Migration applied: column `updated_at` added to `requests`.\n";
    // backfill
    if ($mysqli->query("UPDATE `requests` SET `updated_at` = `created_at` WHERE `updated_at` IS NULL")) {
        echo "Backfill complete: existing rows set updated_at = created_at.\n";
    } else {
        echo "Backfill failed: (" . $mysqli->errno . ") " . $mysqli->error . "\n";
    }
} else {
    echo "Failed to apply migration: (" . $mysqli->errno . ") " . $mysqli->error . "\n";
}

$mysqli->close();
