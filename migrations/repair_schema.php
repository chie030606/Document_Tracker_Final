<?php
// Repair schema runner for DocuTracker
// Usage: php repair_schema.php         -> dry-run (shows planned changes)
//        php repair_schema.php --apply -> applies changes

$apply = in_array('--apply', $argv ?? []);

// include existing DB helpers if available
if (file_exists(__DIR__ . '/../db.php')) include_once __DIR__ . '/../db.php';
if (!isset($mysqli) && file_exists(__DIR__ . '/../database.php/db.php')) include_once __DIR__ . '/../database.php/db.php';

if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    // try default local XAMPP credentials
    $mysqli = @new mysqli('127.0.0.1','root','','docu_tracker');
}

if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
    echo "Could not connect to MySQL: ";
    if ($mysqli instanceof mysqli) echo $mysqli->connect_error . "\n";
    else echo "no mysqli instance\n";
    exit(1);
}

$planned = [];
$executed = [];

function table_exists($m, $name) {
    $res = $m->query("SHOW TABLES LIKE '" . $m->real_escape_string($name) . "'");
    if (!$res) return false; $e = $res->num_rows > 0; $res->free(); return $e;
}

function column_exists($m, $table, $column) {
    $res = $m->query("SHOW COLUMNS FROM `" . $m->real_escape_string($table) . "` LIKE '" . $m->real_escape_string($column) . "'");
    if (!$res) return false; $e = $res->num_rows > 0; $res->free(); return $e;
}

// Desired schema (based on app code)
$schemas = [];
$schemas['users'] = "CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL DEFAULT '',
    last_name VARCHAR(100) NOT NULL DEFAULT '',
    role VARCHAR(16) NOT NULL DEFAULT 'Viewer',
    status VARCHAR(16) NOT NULL DEFAULT 'Active',
    age SMALLINT UNSIGNED DEFAULT NULL,
    address VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$schemas['requests'] = "CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_id VARCHAR(64) UNIQUE,
    fullname VARCHAR(255),
    full_name VARCHAR(255) DEFAULT NULL,
    document_type VARCHAR(128),
    doc_type VARCHAR(128) DEFAULT NULL,
    submission_date DATE,
    phone VARCHAR(32),
    email VARCHAR(255),
    purpose VARCHAR(128),
    priority VARCHAR(64),
    status VARCHAR(32) NOT NULL DEFAULT 'Pending',
    progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
    remarks TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$schemas['documents'] = "CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Plan: ensure tables exist, then ensure specific columns exist with safe ALTERs
foreach ($schemas as $table => $createSql) {
    if (!table_exists($mysqli, $table)) {
        $planned[] = "CREATE TABLE $table";
        if ($apply) {
            if ($mysqli->query($createSql)) $executed[] = "Created table $table";
            else $executed[] = "Failed to create $table: (" . $mysqli->errno . ") " . $mysqli->error;
        }
    } else {
        // table exists, check required columns for requests and users
        if ($table === 'requests') {
            $cols = ['fullname','full_name','document_type','doc_type','tracking_id','submission_date','phone','email','purpose','priority','status','progress','remarks','notes','created_at'];
            foreach ($cols as $col) {
                if (!column_exists($mysqli, 'requests', $col)) {
                    $planned[] = "ALTER TABLE requests ADD COLUMN $col";
                    if ($apply) {
                        // Add with basic type heuristics
                        switch ($col) {
                            case 'fullname': $sql = "ALTER TABLE requests ADD COLUMN fullname VARCHAR(255)"; break;
                            case 'full_name': $sql = "ALTER TABLE requests ADD COLUMN full_name VARCHAR(255) DEFAULT NULL"; break;
                            case 'document_type': $sql = "ALTER TABLE requests ADD COLUMN document_type VARCHAR(128)"; break;
                            case 'doc_type': $sql = "ALTER TABLE requests ADD COLUMN doc_type VARCHAR(128) DEFAULT NULL"; break;
                            case 'tracking_id': $sql = "ALTER TABLE requests ADD COLUMN tracking_id VARCHAR(64) UNIQUE"; break;
                            case 'submission_date': $sql = "ALTER TABLE requests ADD COLUMN submission_date DATE"; break;
                            case 'phone': $sql = "ALTER TABLE requests ADD COLUMN phone VARCHAR(32)"; break;
                            case 'email': $sql = "ALTER TABLE requests ADD COLUMN email VARCHAR(255)"; break;
                            case 'purpose': $sql = "ALTER TABLE requests ADD COLUMN purpose VARCHAR(128)"; break;
                            case 'priority': $sql = "ALTER TABLE requests ADD COLUMN priority VARCHAR(64)"; break;
                            case 'status': $sql = "ALTER TABLE requests ADD COLUMN status VARCHAR(32) NOT NULL DEFAULT 'Pending'"; break;
                            case 'progress': $sql = "ALTER TABLE requests ADD COLUMN progress TINYINT UNSIGNED NOT NULL DEFAULT 0"; break;
                            case 'remarks': $sql = "ALTER TABLE requests ADD COLUMN remarks TEXT"; break;
                            case 'notes': $sql = "ALTER TABLE requests ADD COLUMN notes TEXT"; break;
                            case 'created_at': $sql = "ALTER TABLE requests ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"; break;
                            default: $sql = null; break;
                        }
                        if ($sql) {
                            if ($mysqli->query($sql)) $executed[] = "Added column $col to requests";
                            else $executed[] = "Failed to add $col to requests: (" . $mysqli->errno . ") " . $mysqli->error;
                        }
                    }
                }
            }
        } elseif ($table === 'users') {
            $cols = ['username','email','password','first_name','last_name','role','status','age','address','created_at'];
            foreach ($cols as $col) {
                if (!column_exists($mysqli, 'users', $col)) {
                    $planned[] = "ALTER TABLE users ADD COLUMN $col";
                    if ($apply) {
                        switch ($col) {
                            case 'username': $sql = "ALTER TABLE users ADD COLUMN username VARCHAR(100) NOT NULL UNIQUE"; break;
                            case 'email': $sql = "ALTER TABLE users ADD COLUMN email VARCHAR(255) NOT NULL UNIQUE"; break;
                            case 'password': $sql = "ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL"; break;
                            case 'first_name': $sql = "ALTER TABLE users ADD COLUMN first_name VARCHAR(100) NOT NULL DEFAULT ''"; break;
                            case 'last_name': $sql = "ALTER TABLE users ADD COLUMN last_name VARCHAR(100) NOT NULL DEFAULT ''"; break;
                            case 'role': $sql = "ALTER TABLE users ADD COLUMN role VARCHAR(16) NOT NULL DEFAULT 'Viewer'"; break;
                            case 'status': $sql = "ALTER TABLE users ADD COLUMN status VARCHAR(16) NOT NULL DEFAULT 'Active'"; break;
                            case 'age': $sql = "ALTER TABLE users ADD COLUMN age SMALLINT UNSIGNED DEFAULT NULL"; break;
                            case 'address': $sql = "ALTER TABLE users ADD COLUMN address VARCHAR(255) NOT NULL DEFAULT ''"; break;
                            case 'created_at': $sql = "ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"; break;
                            default: $sql = null; break;
                        }
                        if ($sql) {
                            if ($mysqli->query($sql)) $executed[] = "Added column $col to users";
                            else $executed[] = "Failed to add $col to users: (" . $mysqli->errno . ") " . $mysqli->error;
                        }
                    }
                }
            }
        } elseif ($table === 'documents') {
            $cols = ['request_id','file_name','file_path','file_size','uploaded_at'];
            foreach ($cols as $col) {
                if (!column_exists($mysqli, 'documents', $col)) {
                    $planned[] = "ALTER TABLE documents ADD COLUMN $col";
                    if ($apply) {
                        switch ($col) {
                            case 'request_id': $sql = "ALTER TABLE documents ADD COLUMN request_id INT"; break;
                            case 'file_name': $sql = "ALTER TABLE documents ADD COLUMN file_name VARCHAR(255)"; break;
                            case 'file_path': $sql = "ALTER TABLE documents ADD COLUMN file_path VARCHAR(255)"; break;
                            case 'file_size': $sql = "ALTER TABLE documents ADD COLUMN file_size INT"; break;
                            case 'uploaded_at': $sql = "ALTER TABLE documents ADD COLUMN uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"; break;
                            default: $sql = null; break;
                        }
                        if ($sql) {
                            if ($mysqli->query($sql)) $executed[] = "Added column $col to documents";
                            else $executed[] = "Failed to add $col to documents: (" . $mysqli->errno . ") " . $mysqli->error;
                        }
                    }
                }
            }
        }
    }
}

// Output results
if (!$apply) {
    echo "Planned changes (dry-run). Run with --apply to apply.\n\n";
    if (empty($planned)) echo "No changes planned. Schema appears to match expectations.\n";
    else foreach ($planned as $p) echo "- $p\n";
} else {
    echo "Applied changes summary:\n\n";
    if (empty($executed)) echo "No changes were necessary or operations failed.\n";
    else foreach ($executed as $e) echo "- $e\n";
}

$mysqli->close();

?>
