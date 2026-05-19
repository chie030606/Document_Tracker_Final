<?php
/**
 * API Endpoint: Delete Document Request
 * 
 * POST /api/delete-request.php
 * Body: { "tracking_id": "TRK-..." }
 * 
 * Returns JSON response with success status
 */

require_once __DIR__ . '/../app_init.php';

header('Content-Type: application/json; charset=utf-8');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Get JSON body
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$trackingId = trim((string)($input['tracking_id'] ?? ''));

// Validate input
if (!$trackingId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tracking ID is required.']);
    exit;
}

// Check database connection
if (!$mysqli instanceof mysqli) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection error.']);
    exit;
}

// Delete the request
$deleteStmt = $mysqli->prepare("DELETE FROM requests WHERE tracking_id = ? LIMIT 1");
if ($deleteStmt) {
    $deleteStmt->bind_param('s', $trackingId);
    if ($deleteStmt->execute()) {
        if ($deleteStmt->affected_rows > 0) {
            // Successfully deleted
            error_log("Document deleted: tracking_id = {$trackingId}");
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Document deleted successfully.',
                'tracking_id' => $trackingId
            ]);
        } else {
            // No record found
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Document not found.']);
        }
    } else {
        error_log("Delete failed for tracking_id: {$trackingId}. Error: " . $deleteStmt->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete document.']);
    }
    $deleteStmt->close();
} else {
    error_log("Delete prepare failed: " . $mysqli->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}

exit;
?>
