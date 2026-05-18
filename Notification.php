<?php
require_once __DIR__ . '/app_init.php';
$notifications = [];
if ($mysqli instanceof mysqli) {
    $res = $mysqli->query("SHOW TABLES LIKE 'requests'");
    if ($res && $res->num_rows > 0) {
        $res->free();
        // Determine whether the requests table has an `updated_at` column.
        $useUpdated = false;
        $colRes = $mysqli->query("SHOW COLUMNS FROM requests LIKE 'updated_at'");
        if ($colRes) {
          if ($colRes->num_rows > 0) $useUpdated = true;
          $colRes->free();
        }

        // Build time expressions depending on availability of updated_at
        if ($useUpdated) {
          $timeSelect = "DATE_FORMAT(COALESCE(updated_at, created_at), '%Y-%m-%d') AS date, DATE_FORMAT(COALESCE(updated_at, created_at), '%Y-%m-%d %h:%i %p') AS timestamp";
          $orderBy = "COALESCE(updated_at, created_at) DESC";
        } else {
          $timeSelect = "DATE_FORMAT(created_at, '%Y-%m-%d') AS date, DATE_FORMAT(created_at, '%Y-%m-%d %h:%i %p') AS timestamp";
          $orderBy = "created_at DESC";
        }

        $q = "SELECT tracking_id, COALESCE(fullname, full_name) AS fullname, COALESCE(document_type, doc_type) AS document_type, status, COALESCE(progress,0) AS progress, $timeSelect FROM requests ORDER BY $orderBy LIMIT 20";
        if ($r = $mysqli->query($q)) {
            while ($row = $r->fetch_assoc()) {
                $status = trim($row['status'] ?? '');
                $type = 'system';
                $title = 'Request update';
                $icon = 'ℹ️';
                $accent = '#3b82f6';
                $unread = true;
                switch ($status) {
                    case 'Completed':
                        $type = 'approved';
                        $title = 'Document Approved';
                        $icon = '✅';
                        $accent = '#10b981';
                        $unread = false;
                        break;
                    case 'Processing':
                        $type = 'processing';
                        $title = 'Processing Update';
                        $icon = 'ℹ️';
                        $accent = '#3b82f6';
                        break;
                    case 'Pending':
                        $type = 'system';
                        $title = 'Pending Approval';
                        $icon = '⏳';
                        $accent = '#f59e0b';
                        break;
                    case 'Rejected':
                        $type = 'rejected';
                        $title = 'Request Rejected';
                        $icon = '❌';
                        $accent = '#ef4444';
                        break;
                }
                $notifications[] = [
                    'id' => count($notifications) + 1,
                    'type' => $type,
                    'icon' => $icon,
                    'accentColor' => $accent,
                    'title' => $title,
                    'time' => date('g:i a', strtotime($row['timestamp'] ?? 'now')),
                    'timestamp' => $row['timestamp'] ?? '',
                    'message' => sprintf(
                        'Your <strong>%s</strong> <span class="tracking-chip">%s</span> is currently <strong>%s</strong>.',
                        htmlspecialchars($row['document_type'] ?? '', ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($row['tracking_id'] ?? '', ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($status ?: 'Pending', ENT_QUOTES, 'UTF-8')
                    ),
                    'unread' => $unread,
                    'details' => [
                        ['label' => 'Tracking ID', 'value' => $row['tracking_id'] ?? ''],
                        ['label' => 'Document Type', 'value' => $row['document_type'] ?? ''],
                        ['label' => 'Status', 'value' => $status ?: 'Pending'],
                        ['label' => 'Progress', 'value' => ($row['progress'] ?? 0) . '%']
                    ]
                ];
            }
            $r->free();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DocuTrack — Notifications</title>
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
    transition: opacity 0.3s;
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
    background: rgba(15,27,45,0.88);
    backdrop-filter: blur(20px);
    border-right: 1px solid rgba(255,255,255,0.08);
    padding: 22px 16px;
    display: flex; flex-direction: column; gap: 10px;
    animation: slideRight 0.5s 0.1s ease both;
    overflow-y: auto;
  }
  @keyframes slideRight { from { transform: translateX(-30px); opacity: 0; } to { transform: none; opacity: 1; } }

  .nav-item {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 16px; border-radius: 16px;
    cursor: pointer; color: var(--text-muted);
    font-size: 14px; font-weight: 500;
    transition: transform 0.2s, background 0.2s, color 0.2s, border-color 0.2s;
    text-decoration: none; border: 1px solid transparent;
    position: relative;
    background: rgba(255,255,255,0.02);
  }
  .nav-item:hover { background: rgba(37,99,235,0.14); color: var(--text); transform: translateX(2px); }
  .nav-item.active {
    background: rgba(37,99,235,0.22);
    color: var(--blue-light);
    border-color: rgba(37,99,235,0.24);
    font-weight: 600;
    box-shadow: inset 4px 0 0 0 var(--blue);
  }
  .nav-icon {
    width: 34px; height: 34px;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 12px;
    background: rgba(255,255,255,0.05);
    color: var(--blue-light);
    font-size: 18px;
  }
  .nav-item.active .nav-icon { background: rgba(37,99,235,0.22); color: #fff; }
  .nav-badge {
    margin-left: auto; background: var(--red); color: #fff;
    font-size: 10px; font-weight: 700; min-width: 18px; height: 18px;
    border-radius: 9px; display: flex; align-items: center; justify-content: center;
    padding: 0 5px; font-family: 'Syne', sans-serif;
  }

  /* ── Main ── */
  .main {
    margin-left: var(--sidebar-w);
    flex: 1; padding: 32px 36px 60px;
    display: flex; flex-direction: column; gap: 24px;
    max-width: 960px;
  }

  @keyframes fadeUp { from { transform: translateY(14px); opacity: 0; } to { transform: none; opacity: 1; } }

  /* ── Page header ── */
  .page-header-row {
    display: flex; align-items: flex-end; justify-content: space-between;
    animation: fadeUp 0.5s 0.15s ease both;
  }
  .page-header h2 { font-family: 'Syne', sans-serif; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
  .page-header p { font-size: 13.5px; color: var(--text-muted); margin-top: 4px; }

  .mark-all-btn {
    background: transparent; border: none; cursor: pointer;
    color: var(--blue-light); font-family: 'DM Sans', sans-serif; font-size: 13.5px; font-weight: 500;
    padding: 6px 0; transition: color 0.2s; white-space: nowrap;
  }
  .mark-all-btn:hover { color: #93c5fd; text-decoration: underline; }

  /* ── Filter tabs ── */
  .filter-tabs {
    display: flex; gap: 8px; flex-wrap: wrap;
    animation: fadeUp 0.5s 0.25s ease both;
  }
  .tab-btn {
    padding: 8px 18px; border-radius: 40px;
    background: var(--card-bg); border: 1px solid var(--border);
    color: var(--text-muted); font-family: 'DM Sans', sans-serif;
    font-size: 13px; font-weight: 500; cursor: pointer;
    transition: border-color 0.2s, color 0.2s, background 0.2s;
    display: flex; align-items: center; gap: 6px;
  }
  .tab-btn:hover { border-color: rgba(99,140,200,0.3); color: var(--text); }
  .tab-btn.active {
    background: linear-gradient(135deg, rgba(37,99,235,0.2), rgba(37,99,235,0.08));
    border-color: rgba(37,99,235,0.4); color: var(--blue-light);
  }
  .tab-count {
    background: rgba(37,99,235,0.2); color: var(--blue-light);
    font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 10px;
    font-family: 'Syne', sans-serif;
  }
  .tab-btn.active .tab-count { background: var(--blue); color: #fff; }

  /* ── Notification list ── */
  .notif-list {
    display: flex; flex-direction: column; gap: 12px;
    animation: fadeUp 0.5s 0.35s ease both;
  }

  /* ── Notification card ── */
  .notif-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-left: 4px solid transparent;
    border-radius: 16px; padding: 20px 22px;
    display: flex; gap: 18px; align-items: flex-start;
    transition: transform 0.2s, border-color 0.2s, opacity 0.3s, max-height 0.4s;
    position: relative; overflow: hidden;
  }
  .notif-card:hover { transform: translateY(-2px); }
  .notif-card.unread { border-left-color: var(--accent-color); }
  .notif-card.unread::before {
    content: ''; position: absolute; top: 20px; right: 20px;
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--accent-color);
  }
  .notif-card.dismissing {
    opacity: 0; transform: translateX(30px) scale(0.98);
    max-height: 0; padding-top: 0; padding-bottom: 0; margin: 0;
    border-width: 0; transition: all 0.35s ease;
  }

  /* Icon */
  .notif-icon-wrap {
    width: 46px; height: 46px; border-radius: 14px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    background: color-mix(in srgb, var(--accent-color) 14%, transparent);
    border: 1px solid color-mix(in srgb, var(--accent-color) 25%, transparent);
  }

  /* Content */
  .notif-content { flex: 1; min-width: 0; }
  .notif-top {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    margin-bottom: 6px;
  }
  .notif-title {
    font-family: 'Syne', sans-serif; font-size: 14.5px; font-weight: 700;
    color: var(--text);
  }
  .new-badge {
    background: var(--blue); color: #fff;
    font-size: 9.5px; font-weight: 700; padding: 2px 8px; border-radius: 8px;
    letter-spacing: 0.4px; font-family: 'Syne', sans-serif;
    text-transform: uppercase;
  }
  .notif-time {
    margin-left: auto; font-size: 12px; color: var(--text-muted);
    white-space: nowrap; font-weight: 300;
  }
  .notif-body { font-size: 13.5px; color: var(--text-muted); line-height: 1.55; margin-bottom: 14px; }
  .notif-body strong { color: var(--text); font-weight: 500; }

  .notif-actions { display: flex; align-items: center; gap: 14px; }
  .view-details-btn {
    background: transparent; border: none; cursor: pointer;
    color: var(--blue-light); font-family: 'DM Sans', sans-serif;
    font-size: 13px; font-weight: 500; padding: 0;
    transition: color 0.2s;
  }
  .view-details-btn:hover { color: #93c5fd; text-decoration: underline; }
  .dismiss-btn {
    background: transparent; border: none; cursor: pointer;
    color: var(--text-muted); font-family: 'DM Sans', sans-serif;
    font-size: 13px; padding: 0;
    transition: color 0.2s;
  }
  .dismiss-btn:hover { color: var(--red); }

  /* Tracking chip */
  .tracking-chip {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(37,99,235,0.1); border: 1px solid rgba(37,99,235,0.2);
    border-radius: 6px; padding: 2px 8px;
    font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700;
    color: var(--blue-light); margin-left: 4px;
  }

  /* ── Empty state ── */
  .empty-state {
    display: none; flex-direction: column; align-items: center;
    gap: 14px; padding: 72px 24px; text-align: center;
  }
  .empty-state.show { display: flex; }
  .empty-icon { font-size: 48px; opacity: 0.35; }
  .empty-state h3 { font-family: 'Syne', sans-serif; font-size: 17px; }
  .empty-state p { font-size: 13.5px; color: var(--text-muted); }

  /* ── Detail modal ── */
  .modal-overlay {
    position: fixed; inset: 0; z-index: 200;
    background: rgba(10,18,32,0.78); backdrop-filter: blur(10px);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; pointer-events: none; transition: opacity 0.25s;
  }
  .modal-overlay.open { opacity: 1; pointer-events: all; }

  .modal {
    background: var(--navy-mid); border: 1px solid var(--border);
    border-radius: 20px; padding: 30px 32px; width: 500px; max-width: 94vw;
    transform: translateY(20px); transition: transform 0.25s;
    box-shadow: 0 28px 80px rgba(0,0,0,0.5);
  }
  .modal-overlay.open .modal { transform: none; }

  .modal-top { display: flex; align-items: flex-start; gap: 16px; margin-bottom: 22px; }
  .modal-icon {
    width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 24px;
  }
  .modal-header-text { flex: 1; }
  .modal-title { font-family: 'Syne', sans-serif; font-size: 17px; font-weight: 800; margin-bottom: 4px; }
  .modal-meta { font-size: 12px; color: var(--text-muted); }
  .modal-close {
    background: transparent; border: 1px solid var(--border);
    border-radius: 8px; width: 32px; height: 32px;
    color: var(--text-muted); cursor: pointer; font-size: 15px;
    display: flex; align-items: center; justify-content: center;
    transition: border-color 0.2s, color 0.2s; flex-shrink: 0;
  }
  .modal-close:hover { border-color: var(--red); color: var(--red); }

  .modal-body { font-size: 14px; color: var(--text-muted); line-height: 1.7; margin-bottom: 24px; }
  .modal-body strong { color: var(--text); }

  .modal-detail-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 13px;
  }
  .modal-detail-row:last-of-type { border: none; }
  .modal-detail-label { color: var(--text-muted); }
  .modal-detail-value { color: var(--text); font-weight: 500; }

  .modal-footer { display: flex; gap: 10px; margin-top: 22px; justify-content: flex-end; }
  .btn-ghost {
    padding: 10px 20px; border-radius: 10px;
    background: transparent; border: 1px solid var(--border);
    color: var(--text-muted); font-family: 'DM Sans', sans-serif; font-size: 14px;
    cursor: pointer; transition: border-color 0.2s;
  }
  .btn-ghost:hover { border-color: var(--text-muted); color: var(--text); }
  .btn-solid {
    padding: 10px 22px; border-radius: 10px;
    background: linear-gradient(135deg, var(--blue), #1d4ed8); border: none;
    color: #fff; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500;
    cursor: pointer; transition: opacity 0.2s;
  }
  .btn-solid:hover { opacity: 0.88; }

  /* ── Toast ── */
  .toast {
    position: fixed; bottom: 28px; right: 28px; z-index: 400;
    background: var(--navy-mid); border: 1px solid var(--green);
    border-radius: 12px; padding: 13px 20px;
    display: flex; align-items: center; gap: 10px;
    font-size: 13.5px; color: var(--green);
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

<!-- ── TOPBAR ── -->
<?php
  $dt_name = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
  $dt_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Administrator';
  $p = preg_split('/\s+/', trim($dt_name));
  $dt_initials = strtoupper(substr($p[0],0,1) . (isset($p[1])?substr($p[1],0,1):''));
?>
<header class="topbar">
  <div class="logo-mark"><img src="docutrack-logo.svg" alt="DocuTrack logo" style="width:100%;height:100%;object-fit:contain;border-radius:8px;"></div>
  <div class="logo-text">
    <h1>DocuTrack</h1>
    <span>Document Management</span>
  </div>
  <div class="topbar-right">
    <button type="button" class="bell-btn" title="Notifications" onclick="window.location.href='Notification.php'">🔔<span class="bell-dot" id="bellDot"></span></button>
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

    <!-- Page Header -->
    <div class="page-header-row">
      <div class="page-header">
        <h2>Notifications</h2>
        <p>Stay updated with your document requests</p>
      </div>
      <button class="mark-all-btn" onclick="markAllRead()">✓ Mark all as read</button>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button class="tab-btn active" onclick="filterTab('all', this)">All <span class="tab-count" id="count-all">5</span></button>
      <button class="tab-btn" onclick="filterTab('unread', this)">Unread <span class="tab-count" id="count-unread">3</span></button>
      <button class="tab-btn" onclick="filterTab('approved', this)">Approved <span class="tab-count" id="count-approved">1</span></button>
      <button class="tab-btn" onclick="filterTab('processing', this)">Processing <span class="tab-count" id="count-processing">1</span></button>
      <button class="tab-btn" onclick="filterTab('rejected', this)">Rejected <span class="tab-count" id="count-rejected">1</span></button>
      <button class="tab-btn" onclick="filterTab('system', this)">System <span class="tab-count" id="count-system">1</span></button>
    </div>

    <!-- Notification List -->
    <div class="notif-list" id="notifList"></div>

    <!-- Empty State -->
    <div class="empty-state" id="emptyState">
      <div class="empty-icon">🔔</div>
      <h3>All caught up!</h3>
      <p>No notifications in this category.</p>
    </div>

  </main>
</div>

<!-- ── DETAIL MODAL ── -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
  <div class="modal">
    <div class="modal-top">
      <div class="modal-icon" id="modalIcon"></div>
      <div class="modal-header-text">
        <div class="modal-title" id="modalTitle"></div>
        <div class="modal-meta" id="modalMeta"></div>
      </div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body" id="modalBody"></div>
    <div id="modalDetails"></div>
    <div class="modal-footer">
      <button class="btn-ghost" onclick="closeModal()">Close</button>
      <button class="btn-solid" onclick="closeModal()">Track Document →</button>
    </div>
  </div>
</div>

<!-- ── TOAST ── -->
<div class="toast" id="toast"></div>

<script>
  // ── Data ──
  const notifications = <?php echo json_encode($notifications, JSON_UNESCAPED_UNICODE); ?>;

  let activeFilter = 'all';
  let dismissedIds = new Set();

  function getVisible() {
    return notifications.filter(n => {
      if (dismissedIds.has(n.id)) return false;
      if (activeFilter === 'all') return true;
      if (activeFilter === 'unread') return n.unread;
      return n.type === activeFilter;
    });
  }

  function renderNotifications() {
    const list = document.getElementById('notifList');
    const empty = document.getElementById('emptyState');
    const visible = getVisible();

    list.innerHTML = '';
    empty.classList.toggle('show', visible.length === 0);

    visible.forEach((n, i) => {
      const card = document.createElement('div');
      card.className = `notif-card${n.unread ? ' unread' : ''}`;
      card.id = `notif-${n.id}`;
      card.style.cssText = `--accent-color:${n.accentColor}; animation: fadeUp 0.4s ${i * 0.06}s ease both;`;

      card.innerHTML = `
        <div class="notif-icon-wrap" style="--accent-color:${n.accentColor}">${n.icon}</div>
        <div class="notif-content">
          <div class="notif-top">
            <span class="notif-title">${n.title}</span>
            ${n.unread ? '<span class="new-badge">New</span>' : ''}
            <span class="notif-time">${n.time}</span>
          </div>
          <div class="notif-body">${n.message}</div>
          <div class="notif-actions">
            <button class="view-details-btn" onclick="openModal(${n.id})">View Details</button>
            <button class="dismiss-btn" onclick="dismissNotif(${n.id})">Dismiss</button>
          </div>
        </div>
      `;
      list.appendChild(card);
    });

    updateCounts();
  }

  function updateCounts() {
    const active = notifications.filter(n => !dismissedIds.has(n.id));
    const unread = active.filter(n => n.unread).length;

    document.getElementById('count-all').textContent = active.length;
    document.getElementById('count-unread').textContent = unread;
    document.getElementById('count-approved').textContent = active.filter(n => n.type === 'approved').length;
    document.getElementById('count-processing').textContent = active.filter(n => n.type === 'processing').length;
    document.getElementById('count-rejected').textContent = active.filter(n => n.type === 'rejected').length;
    document.getElementById('count-system').textContent = active.filter(n => n.type === 'system').length;

    const badge = document.getElementById('sidebarBadge');
    if (badge) {
      badge.textContent = unread;
      badge.style.display = unread > 0 ? '' : 'none';
    }
    const bellDot = document.getElementById('bellDot');
    if (bellDot) {
      bellDot.style.opacity = unread > 0 ? '1' : '0';
    }
  }

  function dismissNotif(id) {
    const card = document.getElementById(`notif-${id}`);
    card.classList.add('dismissing');
    setTimeout(() => {
      dismissedIds.add(id);
      renderNotifications();
      showToast('Notification dismissed');
    }, 360);
  }

  function markAllRead() {
    notifications.forEach(n => n.unread = false);
    renderNotifications();
    showToast('All notifications marked as read');
  }

  function filterTab(type, btn) {
    activeFilter = type;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderNotifications();
  }

  // ── Modal ──
  function openModal(id) {
    const n = notifications.find(x => x.id === id);
    if (!n) return;

    // Mark as read
    n.unread = false;
    const card = document.getElementById(`notif-${id}`);
    if (card) {
      card.classList.remove('unread');
      card.querySelector('.new-badge')?.remove();
    }
    updateCounts();

    document.getElementById('modalIcon').textContent = n.icon;
    document.getElementById('modalIcon').style.cssText = `background:color-mix(in srgb,${n.accentColor} 14%,transparent);border:1px solid color-mix(in srgb,${n.accentColor} 25%,transparent)`;
    document.getElementById('modalTitle').textContent = n.title;
    document.getElementById('modalMeta').textContent = n.timestamp;
    document.getElementById('modalBody').innerHTML = n.message.replace(/<span class="tracking-chip">([^<]+)<\/span>/g, '<strong>$1</strong>');

    const details = document.getElementById('modalDetails');
    details.innerHTML = n.details.map(d => `
      <div class="modal-detail-row">
        <span class="modal-detail-label">${d.label}</span>
        <span class="modal-detail-value">${d.value}</span>
      </div>
    `).join('');

    document.getElementById('modalOverlay').classList.add('open');
  }

  function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
  function closeModalOutside(e) { if (e.target === document.getElementById('modalOverlay')) closeModal(); }

  // ── Toast ──
  function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = '✓ ' + msg;
    t.classList.add('show');
    clearTimeout(t._t);
    t._t = setTimeout(() => t.classList.remove('show'), 3000);
  }


  // ── Init ──
  renderNotifications();
</script>
</body>
</html>