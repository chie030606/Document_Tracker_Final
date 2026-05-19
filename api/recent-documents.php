<?php
/**
 * API Endpoint: Get Recent Documents
 * 
 * GET /api/recent-documents.php
 * Query params: limit (default 12, max 50)
 * 
 * Returns JSON array of recent document requests
 */

require_once __DIR__ . '/../app_init.php';

header('Content-Type: application/json; charset=utf-8');

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Get limit parameter
$limit = (int)($_GET['limit'] ?? 12);
$limit = max(1, min($limit, 50)); // Between 1 and 50

// Check database connection
if (!$mysqli instanceof mysqli) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection error.']);
    exit;
}

// Check if requests table exists
$tableCheck = $mysqli->query("SHOW TABLES LIKE 'requests'");
if (!$tableCheck || $tableCheck->num_rows === 0) {
    if ($tableCheck) $tableCheck->free();
    http_response_code(200);
    echo json_encode(['success' => true, 'documents' => []]);
    exit;
}
$tableCheck->free();

// Determine which name column exists
$nameExpr = "'' AS fullname";
$cols = $mysqli->query("SHOW COLUMNS FROM requests LIKE 'fullname'");
if ($cols && $cols->num_rows > 0) {
    $nameExpr = "fullname AS fullname";
    $cols->free();
} else {
    if ($cols) $cols->free();
    $cols = $mysqli->query("SHOW COLUMNS FROM requests LIKE 'full_name'");
    if ($cols && $cols->num_rows > 0) {
        $nameExpr = "full_name AS fullname";
        $cols->free();
    } else {
        if ($cols) $cols->free();
        $cols = $mysqli->query("SHOW COLUMNS FROM requests LIKE 'name'");
        if ($cols && $cols->num_rows > 0) {
            $nameExpr = "name AS fullname";
            $cols->free();
        } else {
            if ($cols) $cols->free();
            $c1 = $mysqli->query("SHOW COLUMNS FROM requests LIKE 'first_name'");
            $c2 = $mysqli->query("SHOW COLUMNS FROM requests LIKE 'last_name'");
            if ($c1 && $c2 && $c1->num_rows > 0 && $c2->num_rows > 0) {
                $nameExpr = "CONCAT(first_name, ' ', last_name) AS fullname";
            }
            if ($c1) $c1->free();
            if ($c2) $c2->free();
        }
    }
}

// Check if status column exists
$statusCol = $mysqli->query("SHOW COLUMNS FROM requests LIKE 'status'");
$hasStatus = $statusCol && $statusCol->num_rows > 0;
if ($statusCol) $statusCol->free();

$statusExpr = $hasStatus ? 'status' : "'' AS status";
$docExpr = "COALESCE(document_type, doc_type) AS document_type";

// Fetch recent documents
$query = "SELECT tracking_id, {$nameExpr}, {$docExpr}, {$statusExpr}, 
          DATE_FORMAT(created_at, '%Y-%m-%d') AS created_at
          FROM requests ORDER BY created_at DESC LIMIT {$limit}";

$documents = [];
if ($result = $mysqli->query($query)) {
    while ($row = $result->fetch_assoc()) {
        $documents[] = [
            'tracking_id' => $row['tracking_id'],
            'fullname' => $row['fullname'],
            'document_type' => $row['document_type'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
    $result->free();
}

http_response_code(200);
echo json_encode(['success' => true, 'documents' => $documents]);
exit;
?>
