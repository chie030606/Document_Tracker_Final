<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
// Try to load project's DB connector(s) and fall back to a direct mysqli if missing.
// This version supports either mysqli ($mysqli or $conn) or PDO ($pdo) connectors.

$rows = [];
$selectedId = isset($_GET['id']) ? (string)$_GET['id'] : '';

$mysqli = null;
$pdo = null;
$conn = null;

// Prefer project root db.php, then legacy database.php/db.php
@include_once __DIR__ . '/db.php';
@include_once __DIR__ . '/database.php/db.php';

// After includes, detect available connectors
if (isset($mysqli) && $mysqli instanceof mysqli) {
    // already provided by included file
} elseif (isset($conn) && $conn instanceof mysqli) {
    $mysqli = $conn;
} elseif (isset($pdo) && $pdo instanceof PDO) {
    // PDO is available; use it below
} else {
    // try to create a mysqli connection as last resort
    @$tmp = new mysqli('127.0.0.1', 'root', '', 'docu_tracker');
    if ($tmp && !$tmp->connect_errno) {
        $mysqli = $tmp;
    } else {
        $mysqli = null;
    }
}

// Handle status updates from the update modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update-status') {
    $trackingId = trim((string)($_POST['tracking_id'] ?? ''));
    $status = trim((string)($_POST['status'] ?? '')) ?: 'Pending';
    $progress = isset($_POST['progress']) && $_POST['progress'] !== '' ? (int)$_POST['progress'] : 0;
    $remarks = trim((string)($_POST['remarks'] ?? ''));

    header('Content-Type: application/json; charset=utf-8');
    if (!$trackingId || !$mysqli instanceof mysqli) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
    }

    if ($stmt = $mysqli->prepare("UPDATE requests SET status = ?, progress = ?, remarks = ? WHERE tracking_id = ?")) {
        $stmt->bind_param('siss', $status, $progress, $remarks, $trackingId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unable to update request.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// Fetch rows from `requests` table if it exists (support both mysqli and PDO)
if ($pdo instanceof PDO) {
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'requests'")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($check)) {
            $stmt = $pdo->query("SELECT tracking_id, COALESCE(fullname, full_name) AS name, COALESCE(document_type, doc_type) AS type, status, COALESCE(progress,0) AS progress, DATE_FORMAT(created_at, '%Y-%m-%d') AS date, COALESCE(remarks,'') AS remarks FROM requests ORDER BY created_at DESC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // silent fail — keep demo data below
    }
} elseif ($mysqli instanceof mysqli) {
    if ($res = $mysqli->query("SHOW TABLES LIKE 'requests'")) {
        if ($res->num_rows > 0) {
            $res->free();
            $q = "SELECT tracking_id, COALESCE(fullname, full_name) AS name, COALESCE(document_type, doc_type) AS type, status, COALESCE(progress,0) AS progress, DATE_FORMAT(created_at, '%Y-%m-%d') AS date, COALESCE(remarks,'') AS remarks FROM requests ORDER BY created_at DESC";
            if ($r = $mysqli->query($q)) {
                while ($row = $r->fetch_assoc()) {
                    // ensure types are consistent for the front-end
                    $row['progress'] = isset($row['progress']) ? (int)$row['progress'] : 0;
                    $rows[] = $row;
                }
                $r->free();
            }
        } else {
            $res->free();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DocuTrack — Track Documents</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
  :root {
    --navy: #0f1b2d;
    --navy-mid: #162236;
    --navy-light: #1e3050;
    --blue: #2563eb;
    --blue-light: #3b82f6;
    --blue-glow: rgba(37,99,235,0.18);
    --amber: #f59e0b;
    --green: #10b981;
    --red: #ef4444;
    --purple: #8b5cf6;
    --text: #e2eaf5;
    --text-muted: #7a92b0;
    --border: rgba(99,140,200,0.12);
    --card-bg: rgba(22,34,54,0.85);
    --sidebar-w: 240px;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--navy);
    color: var(--text);
    min-height: 100vh;
    display: flex; flex-direction: column;
    overflow-x: hidden;
  }

  body::before {
    content: '';
    position: fixed; inset: 0;
    background:
      radial-gradient(ellipse 60% 40% at 20% 10%, rgba(37,99,235,0.13) 0%, transparent 70%),
      radial-gradient(ellipse 40% 50% at 80% 80%, rgba(139,92,246,0.09) 0%, transparent 70%),
      radial-gradient(ellipse 50% 30% at 60% 20%, rgba(16,185,129,0.06) 0%, transparent 70%);
    pointer-events: none; z-index: 0;
  }

  /* ── Topbar ── */
  .topbar {
    position: fixed; top: 0; left: 0; right: 0; height: 64px;
    background: rgba(15,27,45,0.92);
    backdrop-filter: blur(18px);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 12px;
    padding: 0 28px; z-index: 100;
    animation: slideDown 0.5s ease both;
  }
  @keyframes slideDown { from { transform: translateY(-100%); opacity: 0; } to { transform: none; opacity: 1; } }

  .logo-mark {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, var(--blue), #1d4ed8);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
    box-shadow: 0 0 18px var(--blue-glow);
  }
  .brand-logo {
    width: 42px; height: 42px; border-radius: 14px;
    background: rgba(255,255,255,0.06);
    padding: 8px; object-fit: contain;
  }
  .brand-title {
    display: flex; flex-direction: column; gap: 3px;
  }
  .brand-title h1 { font-family: 'Syne', sans-serif; font-size: 17px; font-weight: 800; color: #fff; margin: 0; line-height: 1; }
  .brand-title span { font-size: 10px; color: var(--text-muted); }
  .logo-text h1 { font-family: 'Syne', sans-serif; font-size: 17px; font-weight: 800; letter-spacing: -0.3px; color: #fff; }
  .logo-text span { font-size: 10px; color: var(--text-muted); font-weight: 300; letter-spacing: 0.5px; }

  .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 16px; }

  .bell-btn {
    position: relative; width: 38px; height: 38px;
    border: 1px solid var(--border); border-radius: 10px;
    background: var(--card-bg); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: var(--text-muted); font-size: 16px;
    transition: border-color 0.2s, color 0.2s;
  }
  .bell-btn:hover { border-color: var(--blue); color: var(--blue-light); }
  .bell-dot {
    position: absolute; top: 6px; right: 7px;
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--red); border: 2px solid var(--navy);
  }

  .user-chip {
    display: flex; align-items: center; gap: 10px;
    padding: 6px 14px 6px 6px;
    background: var(--card-bg); border: 1px solid var(--border);
    border-radius: 40px; cursor: pointer;
    transition: border-color 0.2s;
  }
  .user-chip:hover { border-color: var(--blue); }
  .avatar {
    width: 30px; height: 30px; border-radius: 50%;
    background: linear-gradient(135deg, var(--blue), var(--purple));
    display: flex; align-items: center; justify-content: center;
    font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700;
    color: #fff; flex-shrink: 0;
  }
  .user-info strong { display: block; font-size: 12px; font-weight: 500; color: var(--text); }
  .user-info small { font-size: 10px; color: var(--text-muted); }

  .logout-btn {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 10px;
    background: linear-gradient(135deg, var(--blue), #1d4ed8);
    border: none; cursor: pointer; color: #fff;
    font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 500;
    transition: opacity 0.2s, transform 0.15s;
    box-shadow: 0 4px 16px rgba(37,99,235,0.3);
  }
  .logout-btn:hover { opacity: 0.88; transform: translateY(-1px); }

  /* ── Layout ── */
  .layout { display: flex; padding-top: 64px; min-height: 100vh; position: relative; z-index: 1; }

  /* ── Sidebar ── */
  .sidebar {
    width: var(--sidebar-w); flex-shrink: 0;
    position: fixed; top: 64px; left: 0; bottom: 0;
    background: rgba(15,27,45,0.75);
    backdrop-filter: blur(18px);
    border-right: 1px solid var(--border);
    padding: 20px 12px;
    display: flex; flex-direction: column; gap: 4px;
    animation: slideRight 0.5s 0.1s ease both;
    overflow-y: auto;
  }
  @keyframes slideRight { from { transform: translateX(-30px); opacity: 0; } to { transform: none; opacity: 1; } }

  .nav-item {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; border-radius: 12px;
    cursor: pointer; color: var(--text-muted);
    font-size: 14px; font-weight: 400;
    transition: background 0.2s, color 0.2s;
    text-decoration: none;
    border: 1px solid transparent;
  }
  .nav-item:hover { background: rgba(37,99,235,0.08); color: var(--text); }
  .nav-item.active {
    background: linear-gradient(135deg, rgba(37,99,235,0.22), rgba(37,99,235,0.08));
    color: var(--blue-light); border-color: rgba(37,99,235,0.25); font-weight: 500;
  }
  .nav-icon { font-size: 16px; width: 20px; text-align: center; }

  /* ── Main ── */
  .main {
    margin-left: var(--sidebar-w);
    flex: 1; padding: 32px 32px 48px;
    display: flex; flex-direction: column; gap: 24px;
  }

  @keyframes fadeUp { from { transform: translateY(16px); opacity: 0; } to { transform: none; opacity: 1; } }

  .page-header { animation: fadeUp 0.5s 0.2s ease both; }
  .page-header h2 { font-family: 'Syne', sans-serif; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
  .page-header p { font-size: 13.5px; color: var(--text-muted); margin-top: 4px; }

  /* ── Search bar ── */
  .search-row {
    display: flex; gap: 12px; align-items: center;
    animation: fadeUp 0.5s 0.3s ease both;
  }

  .search-main {
    flex: 1; display: flex; align-items: center; gap: 10px;
    background: var(--card-bg); border: 1px solid var(--border);
    border-radius: 14px; padding: 13px 18px;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .search-main:focus-within {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
  }
  .search-main span { font-size: 16px; color: var(--text-muted); }
  .search-main input {
    flex: 1; background: transparent; border: none; outline: none;
    color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 14px;
  }
  .search-main input::placeholder { color: var(--text-muted); }

  .filter-btn {
    display: flex; align-items: center; gap: 8px;
    padding: 13px 20px; border-radius: 14px;
    background: var(--card-bg); border: 1px solid var(--border);
    color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 14px;
    cursor: pointer; transition: border-color 0.2s, background 0.2s;
    white-space: nowrap;
  }
  .filter-btn:hover { border-color: var(--blue); background: rgba(37,99,235,0.08); }
  .filter-btn.active { border-color: var(--blue); color: var(--blue-light); background: rgba(37,99,235,0.12); }

  .search-btn {
    padding: 13px 28px; border-radius: 14px;
    background: linear-gradient(135deg, var(--blue), #1d4ed8);
    border: none; color: #fff; font-family: 'DM Sans', sans-serif;
    font-size: 14px; font-weight: 500; cursor: pointer;
    box-shadow: 0 4px 20px rgba(37,99,235,0.3);
    transition: opacity 0.2s, transform 0.15s;
    white-space: nowrap;
  }
  .search-btn:hover { opacity: 0.88; transform: translateY(-1px); }

  /* ── Filter panel ── */
  .filter-panel {
    background: var(--card-bg); border: 1px solid var(--border);
    border-radius: 14px; padding: 20px 24px;
    display: none; gap: 20px; flex-wrap: wrap;
    animation: fadeUp 0.3s ease both;
  }
  .filter-panel.open { display: flex; }

  .filter-group { display: flex; flex-direction: column; gap: 8px; min-width: 160px; }
  .filter-group label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.6px; font-weight: 500; }
  .filter-group select {
    background: var(--navy-mid); border: 1px solid var(--border);
    border-radius: 8px; padding: 8px 12px;
    color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 13px;
    cursor: pointer; outline: none;
    transition: border-color 0.2s;
  }
  .filter-group select:focus { border-color: var(--blue); }
  .filter-group select option { background: var(--navy-mid); }

  .filter-actions { display: flex; gap: 10px; align-items: flex-end; margin-left: auto; }
  .btn-clear {
    padding: 8px 16px; border-radius: 8px;
    background: transparent; border: 1px solid var(--border);
    color: var(--text-muted); font-family: 'DM Sans', sans-serif; font-size: 13px;
    cursor: pointer; transition: border-color 0.2s, color 0.2s;
  }
  .btn-clear:hover { border-color: var(--red); color: var(--red); }
  .btn-apply {
    padding: 8px 16px; border-radius: 8px;
    background: var(--blue); border: none;
    color: #fff; font-family: 'DM Sans', sans-serif; font-size: 13px;
    cursor: pointer; transition: opacity 0.2s;
  }
  .btn-apply:hover { opacity: 0.85; }

  /* ── Table card ── */
  .table-card {
    background: var(--card-bg); border: 1px solid var(--border);
    border-radius: 18px; overflow: hidden;
    animation: fadeUp 0.5s 0.4s ease both;
  }

  .table-header {
    padding: 20px 24px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--border);
  }
  .table-header-left { display: flex; align-items: center; gap: 14px; }
  .table-title {
    font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700;
    display: flex; align-items: center; gap: 8px;
  }
  .table-title::before {
    content: ''; display: block; width: 3px; height: 16px;
    background: var(--blue); border-radius: 2px;
  }
  .record-count {
    background: rgba(37,99,235,0.15); color: var(--blue-light);
    font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 20px;
  }

  .update-btn {
    display: flex; align-items: center; gap: 7px;
    padding: 9px 18px; border-radius: 10px;
    background: linear-gradient(135deg, var(--blue), #1d4ed8);
    border: none; color: #fff; font-family: 'DM Sans', sans-serif;
    font-size: 13px; font-weight: 500; cursor: pointer;
    box-shadow: 0 4px 16px rgba(37,99,235,0.3);
    transition: opacity 0.2s, transform 0.15s;
  }
  .update-btn:hover { opacity: 0.88; transform: translateY(-1px); }

  /* ── Table ── */
  table { width: 100%; border-collapse: collapse; }
  thead tr { border-bottom: 1px solid var(--border); }
  th {
    padding: 12px 20px; text-align: left;
    font-size: 10.5px; font-weight: 500; letter-spacing: 0.8px;
    color: var(--text-muted); text-transform: uppercase;
    cursor: pointer; user-select: none;
    white-space: nowrap;
  }
  th:hover { color: var(--text); }
  th .sort-icon { margin-left: 4px; opacity: 0.4; font-size: 10px; }
  th.sorted .sort-icon { opacity: 1; color: var(--blue-light); }

  tbody tr {
    border-bottom: 1px solid rgba(99,140,200,0.06);
    transition: background 0.15s;
    animation: fadeUp 0.4s ease both;
  }
  tbody tr:last-child { border: none; }
  tbody tr:hover { background: rgba(37,99,235,0.05); }

  td { padding: 18px 20px; font-size: 13.5px; vertical-align: middle; }

  .tracking-id {
    font-family: 'Syne', sans-serif; font-weight: 700; font-size: 13px;
    color: var(--blue-light); cursor: pointer;
    transition: color 0.15s;
  }
  .tracking-id:hover { color: #93c5fd; text-decoration: underline; }

  .doc-type-chip {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 13px; color: var(--text);
  }
  .doc-type-icon { font-size: 14px; }

  .status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 11px; border-radius: 20px; font-size: 11.5px; font-weight: 500;
    white-space: nowrap;
  }
  .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
  .status-processing { background: rgba(37,99,235,0.15); color: var(--blue-light); }
  .status-completed  { background: rgba(16,185,129,0.15); color: var(--green); }
  .status-pending    { background: rgba(245,158,11,0.15); color: var(--amber); }
  .status-rejected   { background: rgba(239,68,68,0.15);  color: var(--red); }

  /* ── Progress bar ── */
  .progress-cell { min-width: 140px; }
  .progress-wrap { display: flex; flex-direction: column; gap: 5px; }
  .progress-bar-bg {
    height: 6px; border-radius: 10px;
    background: rgba(99,140,200,0.12); overflow: hidden;
  }
  .progress-bar-fill {
    height: 100%; border-radius: 10px;
    transition: width 1.2s cubic-bezier(0.4,0,0.2,1);
    animation: fillBar 1.2s ease both;
  }
  @keyframes fillBar { from { width: 0 !important; } }
  .progress-pct { font-size: 11px; color: var(--text-muted); font-weight: 500; }

  .date-cell { color: var(--text-muted); font-size: 12.5px; font-weight: 300; white-space: nowrap; }

  .action-btn {
    background: transparent; border: 1px solid var(--border);
    border-radius: 8px; padding: 6px 10px;
    color: var(--text-muted); font-size: 13px; cursor: pointer;
    transition: border-color 0.2s, color 0.2s, background 0.2s;
    display: inline-flex; align-items: center; gap: 5px;
  }
  .action-btn:hover { border-color: var(--blue); color: var(--blue-light); background: rgba(37,99,235,0.08); }

  /* ── Pagination ── */
  .pagination {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 24px; border-top: 1px solid var(--border);
    font-size: 13px; color: var(--text-muted);
  }
  .page-controls { display: flex; gap: 6px; }
  .page-btn {
    width: 34px; height: 34px; border-radius: 8px;
    background: transparent; border: 1px solid var(--border);
    color: var(--text-muted); cursor: pointer; font-size: 13px;
    display: flex; align-items: center; justify-content: center;
    transition: border-color 0.2s, color 0.2s, background 0.2s;
    font-family: 'Syne', sans-serif; font-weight: 700;
  }
  .page-btn:hover { border-color: var(--blue); color: var(--blue-light); }
  .page-btn.active { background: var(--blue); border-color: var(--blue); color: #fff; }
  .page-btn:disabled { opacity: 0.3; cursor: not-allowed; }

  /* ── Empty state ── */
  .empty-state {
    text-align: center; padding: 56px 24px;
    color: var(--text-muted); display: none; flex-direction: column;
    align-items: center; gap: 12px;
  }
  .empty-state.show { display: flex; }
  .empty-icon { font-size: 40px; opacity: 0.4; }
  .empty-state h3 { font-family: 'Syne', sans-serif; font-size: 16px; color: var(--text); }
  .empty-state p { font-size: 13px; }

  /* ── Modal ── */
  .modal-overlay {
    position: fixed; inset: 0; z-index: 200;
    background: rgba(10,18,32,0.75); backdrop-filter: blur(8px);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; pointer-events: none; transition: opacity 0.25s;
  }
  .modal-overlay.open { opacity: 1; pointer-events: all; }

  .modal {
    background: var(--navy-mid); border: 1px solid var(--border);
    border-radius: 20px; padding: 28px 32px; width: 480px; max-width: 95vw;
    transform: translateY(20px); transition: transform 0.25s;
    box-shadow: 0 24px 80px rgba(0,0,0,0.5);
  }
  .modal-overlay.open .modal { transform: none; }

  .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
  .modal-title { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 800; }
  .modal-close {
    background: transparent; border: 1px solid var(--border);
    border-radius: 8px; width: 32px; height: 32px;
    color: var(--text-muted); cursor: pointer; font-size: 16px;
    display: flex; align-items: center; justify-content: center;
    transition: border-color 0.2s, color 0.2s;
  }
  .modal-close:hover { border-color: var(--red); color: var(--red); }

  .modal-field { margin-bottom: 16px; }
  .modal-field label { display: block; font-size: 11.5px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
  .modal-field select, .modal-field input, .modal-field textarea {
    width: 100%; background: rgba(15,27,45,0.8); border: 1px solid var(--border);
    border-radius: 10px; padding: 10px 14px;
    color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 14px;
    outline: none; transition: border-color 0.2s;
  }
  .modal-field select:focus, .modal-field input:focus, .modal-field textarea:focus { border-color: var(--blue); }
  .modal-field select option { background: var(--navy-mid); }
  .modal-field textarea { resize: vertical; min-height: 80px; }

  .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
  .btn-secondary {
    padding: 10px 20px; border-radius: 10px;
    background: transparent; border: 1px solid var(--border);
    color: var(--text-muted); font-family: 'DM Sans', sans-serif; font-size: 14px;
    cursor: pointer; transition: border-color 0.2s;
  }
  .btn-secondary:hover { border-color: var(--text-muted); color: var(--text); }
  .btn-primary {
    padding: 10px 24px; border-radius: 10px;
    background: linear-gradient(135deg, var(--blue), #1d4ed8); border: none;
    color: #fff; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500;
    cursor: pointer; transition: opacity 0.2s;
  }
  .btn-primary:hover { opacity: 0.88; }

  /* ── Toast ── */
  .toast {
    position: fixed; bottom: 28px; right: 28px; z-index: 300;
    background: var(--navy-mid); border: 1px solid var(--green);
    border-radius: 12px; padding: 14px 20px;
    display: flex; align-items: center; gap: 10px;
    font-size: 14px; color: var(--green);
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    transform: translateY(80px); opacity: 0;
    transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s;
    pointer-events: none;
  }
  .toast.show { transform: none; opacity: 1; }

  ::-webkit-scrollbar { width: 5px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: var(--navy-light); border-radius: 4px; }
</style>
</head>
<body>
<?php
  $dt_name = isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'Guest User';
  $dt_role = isset($_SESSION['role']) && $_SESSION['role'] !== '' ? $_SESSION['role'] : 'Administrator';
  $p = preg_split('/\s+/', trim($dt_name));
  $dt_initials = strtoupper(substr($p[0] ?? 'G', 0, 1) . (isset($p[1]) ? substr($p[1], 0, 1) : ''));
?>

<!-- ── TOPBAR ── -->
<header class="topbar">
  <div class="logo-mark"><img src="docutrack-logo.svg" alt="DocuTrack logo" style="width:100%;height:100%;object-fit:contain;border-radius:8px;"></div>
  <div class="logo-text">
    <h1>DocuTrack</h1>
    <span>Document Management</span>
  </div>
  <div class="topbar-right">
    <button type="button" class="bell-btn" title="Notifications" onclick="window.location.href='Notification.php'">🔔<span class="bell-dot"></span></button>
    <div class="user-chip" onclick="window.location.href='User Management.php'">
      <div class="avatar"><?php echo htmlspecialchars($dt_initials, ENT_QUOTES, 'UTF-8'); ?></div>
      <div class="user-info">
        <strong><?php echo htmlspecialchars($dt_name, ENT_QUOTES, 'UTF-8'); ?></strong>
        <small><?php echo htmlspecialchars($dt_role, ENT_QUOTES, 'UTF-8'); ?></small>
      </div>
    </div>
    <form method="post" action="logout.php" style="display:inline" onsubmit="return confirm('Are you sure you want to log out?');">
      <button type="submit" class="logout-btn">↪ Logout</button>
    </form>
  </div>
</header>

<div class="layout">

  <!-- ── SIDEBAR ── -->
  <?php include 'sidebar.php'; ?>

  <!-- ── MAIN ── -->
  <main class="main">

    <div class="page-header">
      <h2>Track Documents</h2>
      <p>Search and monitor document processing status</p>
    </div>

    <!-- Search bar -->
    <div class="search-row">
      <div class="search-main">
        <span>🔍</span>
        <input type="text" id="searchInput" placeholder="Search by tracking ID, name, or document type..." oninput="applyFilters()">
      </div>
      <button class="filter-btn" id="filterToggle" onclick="toggleFilters()">⚙ Filters</button>
      <button class="search-btn" onclick="applyFilters()">Search</button>
    </div>

    <!-- Filter panel -->
    <div class="filter-panel" id="filterPanel">
      <div class="filter-group">
        <label>Status</label>
        <select id="fStatus" onchange="applyFilters()">
          <option value="">All Statuses</option>
          <option value="Processing">Processing</option>
          <option value="Completed">Completed</option>
          <option value="Pending">Pending</option>
          <option value="Rejected">Rejected</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Document Type</label>
        <select id="fType" onchange="applyFilters()">
          <option value="">All Types</option>
          <option value="Birth Certificate">Birth Certificate</option>
          <option value="Marriage License">Marriage License</option>
          <option value="Business Permit">Business Permit</option>
          <option value="Tax Clearance">Tax Clearance</option>
          <option value="ID Card">ID Card</option>
          <option value="Land Title">Land Title</option>
          <option value="Driver's License">Driver's License</option>
          <option value="Passport">Passport</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Sort By</label>
        <select id="fSort" onchange="applyFilters()">
          <option value="date-desc">Date (Newest)</option>
          <option value="date-asc">Date (Oldest)</option>
          <option value="id-asc">Tracking ID (A–Z)</option>
          <option value="progress-desc">Progress (High–Low)</option>
        </select>
      </div>
      <div class="filter-actions">
        <button class="btn-clear" onclick="clearFilters()">Clear</button>
        <button class="btn-apply" onclick="toggleFilters()">Apply</button>
      </div>
    </div>

    <!-- Table -->
    <div class="table-card">
      <div class="table-header">
        <div class="table-header-left">
          <div class="table-title">All Documents</div>
          <span class="record-count" id="recordCount">0 records</span>
        </div>
        <button class="update-btn" onclick="openUpdateModal()">✏ Update Status</button>
      </div>

      <table>
        <thead>
          <tr>
            <th onclick="sortBy('id')">Tracking ID <span class="sort-icon">↕</span></th>
            <th>Name</th>
            <th>Document Type</th>
            <th onclick="sortBy('status')">Status <span class="sort-icon">↕</span></th>
            <th>Progress</th>
            <th onclick="sortBy('date')">Date <span class="sort-icon">↕</span></th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="tableBody"></tbody>
      </table>

      <div class="empty-state" id="emptyState">
        <div class="empty-icon">🔍</div>
        <h3>No results found</h3>
        <p>Try adjusting your search or filters.</p>
      </div>

      <div class="pagination">
        <span id="paginationInfo">Showing 0</span>
        <div class="page-controls">
          <button class="page-btn" id="prevBtn" onclick="changePage(-1)" disabled>‹</button>
          <button class="page-btn active" id="p1">1</button>
          <button class="page-btn" id="p2" onclick="changePage(1)">2</button>
          <button class="page-btn" onclick="changePage(1)">›</button>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- ── UPDATE STATUS MODAL ── -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Update Document Status</div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-field">
      <label>Tracking ID</label>
      <select id="modalTrackingId"></select>
    </div>
    <div class="modal-field">
      <label>New Status</label>
      <select id="modalStatus">
        <option value="Pending">Pending</option>
        <option value="Processing">Processing</option>
        <option value="Completed">Completed</option>
        <option value="Rejected">Rejected</option>
      </select>
    </div>
    <div class="modal-field">
      <label>Progress (%)</label>
      <input type="number" id="modalProgress" min="0" max="100" placeholder="e.g. 75">
    </div>
    <div class="modal-field">
      <label>Remarks (optional)</label>
      <textarea id="modalRemarks" placeholder="Add notes about the status update..."></textarea>
    </div>
    <div class="modal-actions">
      <button class="btn-secondary" onclick="closeModal()">Cancel</button>
      <button class="btn-primary" onclick="applyStatusUpdate()">Save Changes</button>
    </div>
  </div>
</div>

<!-- ── TOAST ── -->
<div class="toast" id="toast">✅ Status updated successfully!</div>

<script>
  const docTypeIcons = {
    'Birth Certificate': '🏥',
    'Marriage License': '💍',
    'Business Permit': '🏢',
    'Tax Clearance': '📊',
    'ID Card': '🪪',
    'Land Title': '🏡',
    "Driver's License": '🚗',
    'Passport': '✈️'
  };

  // server-provided rows -> ensure consistent fields for frontend
  let documents = <?php echo json_encode($rows, JSON_UNESCAPED_UNICODE); ?>;
  documents = documents.map(d => ({
    id: d.tracking_id ?? d.tracking_id ?? d.id,
    name: d.name ?? d.fullname ?? '',
    type: d.type ?? d.document_type ?? '',
    status: d.status ?? 'Pending',
    progress: parseInt(d.progress ?? 0),
    date: d.date ?? d.created_at ?? ''
  }));

  const PRESELECT = <?php echo json_encode($selectedId, JSON_UNESCAPED_UNICODE); ?>;

  function openForId(id) {
    const sel = document.getElementById('modalTrackingId');
    if (!sel) return;
    sel.innerHTML = documents.map(d => `<option value="${d.id}" ${d.id===id ? 'selected' : ''}>${d.id}</option>`).join('');
    const doc = documents.find(x => x.id === id);
    if (doc) {
      document.getElementById('modalStatus').value = doc.status || 'Pending';
      document.getElementById('modalProgress').value = doc.progress ?? '';
    }
    document.getElementById('modalOverlay').classList.add('open');
  }

  let currentPage = 1;
  const PAGE_SIZE = 7;
  let filtered = [...documents];

  function progressColor(pct, status) {
    if (status === 'Completed') return 'var(--green)';
    if (status === 'Pending') return 'var(--amber)';
    if (status === 'Rejected') return 'var(--red)';
    return 'var(--blue-light)';
  }

  function statusClass(s) {
    return { Processing:'status-processing', Completed:'status-completed', Pending:'status-pending', Rejected:'status-rejected' }[s] || '';
  }

  function renderTable() {
    const tbody = document.getElementById('tableBody');
    const start = (currentPage - 1) * PAGE_SIZE;
    const page  = filtered.slice(start, start + PAGE_SIZE);

    tbody.innerHTML = '';
    document.getElementById('emptyState').classList.toggle('show', filtered.length === 0);
    document.getElementById('recordCount').textContent = `${filtered.length} record${filtered.length !== 1 ? 's' : ''}`;
    document.getElementById('paginationInfo').textContent = filtered.length
      ? `Showing ${start + 1}–${Math.min(start + PAGE_SIZE, filtered.length)} of ${filtered.length}`
      : 'No records';

    const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
    document.getElementById('prevBtn').disabled = currentPage <= 1;
    document.getElementById('p1').classList.toggle('active', currentPage === 1);
    document.getElementById('p2').classList.toggle('active', currentPage === 2);
    document.getElementById('p2').style.display = totalPages > 1 ? '' : 'none';

    page.forEach((doc, i) => {
      const tr = document.createElement('tr');
      tr.style.animationDelay = `${i * 0.04}s`;
      const color = progressColor(doc.progress, doc.status);
      tr.innerHTML = `
        <td><span class="tracking-id">${doc.id}</span></td>
        <td>${doc.name}</td>
        <td><span class="doc-type-chip"><span class="doc-type-icon">${docTypeIcons[doc.type] || '📄'}</span>${doc.type}</span></td>
        <td><span class="status-badge ${statusClass(doc.status)}">${doc.status}</span></td>
        <td class="progress-cell">
          <div class="progress-wrap">
            <div class="progress-bar-bg">
              <div class="progress-bar-fill" style="width:${doc.progress}%; background:${color};"></div>
            </div>
            <span class="progress-pct">${doc.progress}%</span>
          </div>
        </td>
        <td class="date-cell">${doc.date}</td>
        <td><button class="action-btn" onclick="viewDoc('${doc.id}')">👁 View</button></td>
      `;
      tbody.appendChild(tr);
    });
  }

  function applyFilters() {
    const q       = document.getElementById('searchInput').value.toLowerCase();
    const status  = document.getElementById('fStatus').value;
    const type    = document.getElementById('fType').value;
    const sortVal = document.getElementById('fSort').value;

    filtered = documents.filter(d => {
      const matchQ = !q || d.id.toLowerCase().includes(q) || d.name.toLowerCase().includes(q) || d.type.toLowerCase().includes(q);
      const matchS = !status || d.status === status;
      const matchT = !type   || d.type === type;
      return matchQ && matchS && matchT;
    });

    filtered.sort((a, b) => {
      if (sortVal === 'date-asc')       return a.date.localeCompare(b.date);
      if (sortVal === 'date-desc')      return b.date.localeCompare(a.date);
      if (sortVal === 'id-asc')         return a.id.localeCompare(b.id);
      if (sortVal === 'progress-desc')  return b.progress - a.progress;
      return 0;
    });

    currentPage = 1;
    renderTable();
  }

  function sortBy(key) {
    filtered.sort((a, b) => {
      const av = key === 'date' ? a.date : key === 'id' ? a.id : a.status;
      const bv = key === 'date' ? b.date : key === 'id' ? b.id : b.status;
      return av.localeCompare(bv);
    });
    renderTable();
  }

  function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('fStatus').value = '';
    document.getElementById('fType').value = '';
    document.getElementById('fSort').value = 'date-desc';
    applyFilters();
  }

  function changePage(dir) {
    const total = Math.ceil(filtered.length / PAGE_SIZE);
    currentPage = Math.max(1, Math.min(total, currentPage + dir));
    renderTable();
  }

  function toggleFilters() {
    const panel = document.getElementById('filterPanel');
    const btn   = document.getElementById('filterToggle');
    panel.classList.toggle('open');
    btn.classList.toggle('active');
  }

  function openUpdateModal() {
    const sel = document.getElementById('modalTrackingId');
    if (!sel) return;
    sel.innerHTML = documents.map(d => `<option value="${d.id}">${d.id}</option>`).join('');
    document.getElementById('modalOverlay').classList.add('open');
  }
  function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
  function closeModalOutside(e) { if (e.target === document.getElementById('modalOverlay')) closeModal(); }

  async function applyStatusUpdate() {
    const id = document.getElementById('modalTrackingId').value;
    const newStatus = document.getElementById('modalStatus').value;
    const progress = parseInt(document.getElementById('modalProgress').value);
    const remarks = document.getElementById('modalRemarks').value.trim();

    const formData = new FormData();
    formData.append('action', 'update-status');
    formData.append('tracking_id', id);
    formData.append('status', newStatus);
    formData.append('progress', isNaN(progress) ? '' : progress);
    formData.append('remarks', remarks);

    try {
      const response = await fetch(window.location.href, { method: 'POST', body: formData });
      const result = await response.json();
      if (!result.success) {
        showToast(result.message || 'Unable to update request.', 'error');
        return;
      }
    } catch (err) {
      console.error(err);
      showToast('Network error while updating status.', 'error');
      return;
    }

    const doc = documents.find(d => d.id === id);
    if (doc) {
      doc.status = newStatus;
      if (!isNaN(progress)) {
        doc.progress = Math.min(100, Math.max(0, progress));
      }
      doc.remarks = remarks;
    }

    applyFilters();
    closeModal();
    showToast(`Status updated to ${newStatus}`);

    document.getElementById('modalProgress').value = '';
    document.getElementById('modalRemarks').value = '';
  }

  function viewDoc(id) {
    const doc = documents.find(d => d.id === id);
    if (doc) showToast(`Viewing ${id} — ${doc.name} (${doc.type})`);
  }

  function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = '✅ ' + msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3200);
  }


  // Init
  applyFilters();

  // Auto-open modal if ?id=TRK-... provided
  if (PRESELECT) setTimeout(() => openForId(PRESELECT), 220);
</script>
</body>
</html>
