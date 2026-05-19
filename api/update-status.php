<?php
/**
 * API Endpoint: Update Request Status
 * 
 * Method: POST
 * Content-Type: application/json or form-data
 * 
 * Inputs:
 *   - tracking_id: string (required)
 *   - status: string (required) - must be one of: Pending, Processing, Completed, Rejected
 *   - progress: int (optional) - 0-100
 *   - remarks: string (optional)
 * 
 * Response:
 *   {
 *     "success": true/false,
 *     "message": "string",
 *     "data": {},
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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Method not allowed. Use POST.', [], 405);
}

// Include database connection from app_init.php
require_once __DIR__ . '/../app_init.php';

// Check database connection
if (!($mysqli instanceof mysqli) && !($pdo instanceof PDO)) {
    sendJsonResponse(false, 'Database connection error.', [], 500);
}

// Parse input (support both JSON and form-data)
$input = $_POST;
if (empty($input) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
}

// Validate required fields
$trackingId = trim((string)($input['tracking_id'] ?? ''));
$status = trim((string)($input['status'] ?? ''));
$progress = isset($input['progress']) && $input['progress'] !== '' ? (int)$input['progress'] : 0;
$remarks = trim((string)($input['remarks'] ?? ''));

if (!$trackingId) {
    sendJsonResponse(false, 'Missing required field: tracking_id', [], 400);
}

if (!$status) {
    sendJsonResponse(false, 'Missing required field: status', [], 400);
}

// Validate status is allowed value
$allowedStatuses = ['Pending', 'Processing', 'Completed', 'Rejected'];
if (!in_array($status, $allowedStatuses, true)) {
    sendJsonResponse(false, 'Invalid status. Allowed: ' . implode(', ', $allowedStatuses), [], 400);
}

// Validate progress range
if ($progress < 0 || $progress > 100) {
    sendJsonResponse(false, 'Progress must be between 0 and 100.', [], 400);
}

// Fetch current status before update
$oldStatus = '';
$userEmail = '';
$userName = '';

if ($mysqli instanceof mysqli) {
    $checkStmt = $mysqli->prepare("SELECT status, email, COALESCE(fullname, full_name) AS fullname FROM requests WHERE tracking_id = ? LIMIT 1");
    if ($checkStmt) {
        $checkStmt->bind_param('s', $trackingId);
        if ($checkStmt->execute()) {
            $checkResult = $checkStmt->get_result();
            if ($checkRow = $checkResult->fetch_assoc()) {
                $oldStatus = trim((string)($checkRow['status'] ?? ''));
                $userEmail = trim((string)($checkRow['email'] ?? ''));
                $userName = trim((string)($checkRow['fullname'] ?? ''));
            } else {
                sendJsonResponse(false, 'Request not found.', [], 404);
            }
            $checkResult->free();
        }
        $checkStmt->close();
    }
} elseif ($pdo instanceof PDO) {
    $checkStmt = $pdo->prepare("SELECT status, email, COALESCE(fullname, full_name) AS fullname FROM requests WHERE tracking_id = ? LIMIT 1");
    $checkStmt->execute([$trackingId]);
    if ($checkRow = $checkStmt->fetch(PDO::FETCH_ASSOC)) {
        $oldStatus = trim((string)($checkRow['status'] ?? ''));
        $userEmail = trim((string)($checkRow['email'] ?? ''));
        $userName = trim((string)($checkRow['fullname'] ?? ''));
    } else {
        sendJsonResponse(false, 'Request not found.', [], 404);
    }
}

// Check if updated_at column exists
$hasUpdatedAt = false;
if ($mysqli instanceof mysqli) {
    $colRes = $mysqli->query("SHOW COLUMNS FROM requests LIKE 'updated_at'");
    if ($colRes && $colRes->num_rows > 0) {
        $hasUpdatedAt = true;
        $colRes->free();
    }
}

// Prepare update query
if ($hasUpdatedAt) {
    $updateSql = "UPDATE requests SET status = ?, progress = ?, remarks = ?, updated_at = NOW() WHERE tracking_id = ?";
} else {
    $updateSql = "UPDATE requests SET status = ?, progress = ?, remarks = ? WHERE tracking_id = ?";
}

// Execute update
$success = false;
if ($mysqli instanceof mysqli) {
    $updateStmt = $mysqli->prepare($updateSql);
    if ($updateStmt) {
        $updateStmt->bind_param('siss', $status, $progress, $remarks, $trackingId);
        if ($updateStmt->execute()) {
            $success = true;
        }
        $updateStmt->close();
    }
} elseif ($pdo instanceof PDO) {
    try {
        if ($hasUpdatedAt) {
            $updateStmt = $pdo->prepare($updateSql);
            $success = $updateStmt->execute([$status, $progress, $remarks, $trackingId]);
        } else {
            $updateStmt = $pdo->prepare($updateSql);
            $success = $updateStmt->execute([$status, $progress, $remarks, $trackingId]);
        }
    } catch (Exception $e) {
        error_log("Update status error: " . $e->getMessage());
    }
}

if (!$success) {
    sendJsonResponse(false, 'Failed to update request status.', [], 500);
}

// Initialize email tracking variable
$emailSent = false;
$emailAttempted = false;

// Send completion email if status changed to "Completed"
if ($status === 'Completed' && $oldStatus !== 'Completed') {
    error_log("======= EMAIL NOTIFICATION SYSTEM =======");
    error_log("Document Completed: tracking_id = {$trackingId}");
    error_log("Email condition met: Status changing from '{$oldStatus}' to 'Completed'");
    error_log("Recipient Email: '{$userEmail}'");
    error_log("Recipient Name: '{$userName}'");
    
    // Only attempt email if both email and name are available
    if ($userEmail && $userName) {
        $emailAttempted = true;
        error_log("Email prerequisites met - attempting to send...");
        
        if (file_exists(__DIR__ . '/../email_helper.php')) {
            require_once __DIR__ . '/../email_helper.php';
            error_log("email_helper.php loaded successfully");
            
            $emailSent = sendDocumentCompleteEmail($userEmail, $userName);
            
            if ($emailSent) {
                error_log("✅ EMAIL SENT SUCCESSFULLY to {$userEmail}");
            } else {
                error_log("❌ EMAIL SENDING FAILED for {$userEmail}");
                error_log("Check: Gmail credentials, SMTP connection, recipient email validity");
            }
        } else {
            error_log("❌ ERROR: email_helper.php not found at " . __DIR__ . '/../email_helper.php');
        }
    } else {
        error_log("❌ EMAIL PREREQUISITES NOT MET");
        if (!$userEmail) error_log("   - Missing email address");
        if (!$userName) error_log("   - Missing recipient name");
    }
    error_log("========================================");
} else {
    error_log("Email NOT triggered for {$trackingId}: Status condition = false (New: '{$status}', Old: '{$oldStatus}')");
}

// Return success response
sendJsonResponse(true, "Status updated to {$status}", [
    'old_status' => $oldStatus,
    'new_status' => $status,
    'tracking_id' => $trackingId,
    'progress' => $progress,
    'email_attempted' => $emailAttempted,
    'email_sent' => $emailSent
], 200);
