<?php
/**
 * API Endpoint: Get All Requests
 * 
 * Method: GET
 * 
 * Query Parameters (optional):
 *   - limit: int (default: 100, max: 1000)
 *   - offset: int (default: 0)
 *   - status: string (filter by status)
 *   - search: string (search by tracking_id or fullname)
 * 
 * Response:
 *   {
 *     "success": true/false,
 *     "message": "string",
 *     "data": [],
 *     "timestamp": "ISO 8601 datetime"
 *   }
 */

header('Content-Type: application/json; charset=utf-8');

/**
 * Helper function to send standardized JSON response
 */
function sendJsonResponse($success, $message, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(false, 'Method not allowed. Use GET.', [], 405);
}

// Include database connection from app_init.php
require_once __DIR__ . '/../app_init.php';

// Check database connection
if (!($mysqli instanceof mysqli) && !($pdo instanceof PDO)) {
    sendJsonResponse(false, 'Database connection error.', [], 500);
}

// Parse query parameters
$limit = min((int)($_GET['limit'] ?? 100), 1000);
$offset = max((int)($_GET['offset'] ?? 0), 0);
$statusFilter = trim((string)($_GET['status'] ?? ''));
$search = trim((string)($_GET['search'] ?? ''));

if ($limit < 1) $limit = 100;

// Validate status filter if provided
$allowedStatuses = ['Pending', 'Processing', 'Completed', 'Rejected'];
if ($statusFilter && !in_array($statusFilter, $allowedStatuses, true)) {
    sendJsonResponse(false, 'Invalid status filter. Allowed: ' . implode(', ', $allowedStatuses), [], 400);
}

$requests = [];
$total = 0;

if ($mysqli instanceof mysqli) {
    // Build WHERE clause
    $whereConditions = ["1=1"];
    $types = '';
    $params = [];

    if ($statusFilter) {
        $whereConditions[] = "status = ?";
        $types .= 's';
        $params[] = $statusFilter;
    }

    if ($search) {
        $whereConditions[] = "(tracking_id LIKE ? OR COALESCE(fullname, full_name) LIKE ?)";
        $searchTerm = "%{$search}%";
        $types .= 'ss';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $where = implode(' AND ', $whereConditions);

    // Get total count
    $countSql = "SELECT COUNT(*) AS total FROM requests WHERE {$where}";
    $countStmt = $mysqli->prepare($countSql);
    if ($countStmt && !empty($types)) {
        $countStmt->bind_param($types, ...$params);
    }
    if ($countStmt && $countStmt->execute()) {
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();
        $total = (int)$countRow['total'];
        $countResult->free();
        $countStmt->close();
    }

    // Fetch paginated results
    $sql = "SELECT 
                tracking_id, 
                COALESCE(fullname, full_name) AS fullname, 
                COALESCE(document_type, doc_type) AS document_type, 
                status, 
                COALESCE(progress, 0) AS progress, 
                created_at 
            FROM requests 
            WHERE {$where}
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $requests[] = [
                    'tracking_id' => $row['tracking_id'],
                    'fullname' => $row['fullname'],
                    'document_type' => $row['document_type'],
                    'status' => $row['status'],
                    'progress' => (int)$row['progress'],
                    'created_at' => $row['created_at']
                ];
            }
            $result->free();
        }
        $stmt->close();
    }

} elseif ($pdo instanceof PDO) {
    try {
        // Build WHERE clause
        $whereConditions = ["1=1"];
        $params = [];

        if ($statusFilter) {
            $whereConditions[] = "status = ?";
            $params[] = $statusFilter;
        }

        if ($search) {
            $whereConditions[] = "(tracking_id LIKE ? OR COALESCE(fullname, full_name) LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $where = implode(' AND ', $whereConditions);

        // Get total count
        $countSql = "SELECT COUNT(*) AS total FROM requests WHERE {$where}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
        $total = (int)$countRow['total'];

        // Fetch paginated results
        $sql = "SELECT 
                    tracking_id, 
                    COALESCE(fullname, full_name) AS fullname, 
                    COALESCE(document_type, doc_type) AS document_type, 
                    status, 
                    COALESCE(progress, 0) AS progress, 
                    created_at 
                FROM requests 
                WHERE {$where}
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";

        $stmt = $pdo->prepare($sql);
        $queryParams = array_merge($params, [$limit, $offset]);
        $stmt->execute($queryParams);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $requests[] = [
                'tracking_id' => $row['tracking_id'],
                'fullname' => $row['fullname'],
                'document_type' => $row['document_type'],
                'status' => $row['status'],
                'progress' => (int)$row['progress'],
                'created_at' => $row['created_at']
            ];
        }

    } catch (Exception $e) {
        error_log("Get requests error: " . $e->getMessage());
        sendJsonResponse(false, 'Error fetching requests.', [], 500);
    }
}

// Build response data with pagination info
$responseData = [
    'requests' => $requests,
    'pagination' => [
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'returned' => count($requests)
    ]
];

// Return success response
sendJsonResponse(true, 'Requests retrieved successfully.', $responseData, 200);

?>
