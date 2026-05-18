
<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

/* helper */
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/* default live data */
$counts = ['total'=>0,'pending'=>0,'processing'=>0,'completed'=>0];
$recent = [];

require_once __DIR__ . '/app_init.php';

$userStats = ['total'=>0,'admins'=>0,'active'=>0];
$weeklyData = [];
if ($mysqli instanceof mysqli) {
    $res = $mysqli->query("SHOW TABLES LIKE 'requests'");
    if ($res && $res->num_rows > 0) {
        $hasRequestStatus = false;
        if ($cols = $mysqli->query("SHOW COLUMNS FROM requests LIKE 'status'")) {
            $hasRequestStatus = $cols->num_rows > 0;
            $cols->free();
        }

        $q = $hasRequestStatus
            ? "SELECT COUNT(*) AS total, SUM(status = 'Pending') AS pending, SUM(status = 'Processing') AS processing, SUM(status = 'Completed') AS completed FROM requests"
            : "SELECT COUNT(*) AS total, 0 AS pending, 0 AS processing, 0 AS completed FROM requests";

        if ($r = $mysqli->query($q)) {
            $row = $r->fetch_assoc();
            if ($row) {
                $counts['total'] = (int)$row['total'];
                $counts['pending'] = (int)$row['pending'];
                $counts['processing'] = (int)$row['processing'];
                $counts['completed'] = (int)$row['completed'];
            }
            $r->free();
        }

        // determine which name column(s) exist and build a safe select expression
        $nameExpr = "'' AS fullname";
        $cols = $mysqli->query("SHOW COLUMNS FROM requests LIKE 'fullname'");
        if ($cols && $cols->num_rows > 0) { $nameExpr = "fullname AS fullname"; $cols->free(); }
        else {
          if ($cols) $cols->free();
          $cols = $mysqli->query("SHOW COLUMNS FROM requests LIKE 'full_name'");
          if ($cols && $cols->num_rows > 0) { $nameExpr = "full_name AS fullname"; $cols->free(); }
          else {
            if ($cols) $cols->free();
            $cols = $mysqli->query("SHOW COLUMNS FROM requests LIKE 'name'");
            if ($cols && $cols->num_rows > 0) { $nameExpr = "name AS fullname"; $cols->free(); }
            else {
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

        $docExpr = "COALESCE(document_type, doc_type) AS document_type";

        $rq = "SELECT tracking_id, {$nameExpr}, {$docExpr}, " . ($hasRequestStatus ? 'status' : "'' AS status") . ", DATE_FORMAT(created_at, '%Y-%m-%d') AS created_at
             FROM requests ORDER BY created_at DESC LIMIT 12";
        if ($r2 = $mysqli->query($rq)) {
            $recent = [];
            while ($row = $r2->fetch_assoc()) {
                $recent[] = $row;
            }
            $r2->free();
        }

        $weekQuery = "SELECT DATE(created_at) AS day, COUNT(*) AS total
                      FROM requests
                      WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                      GROUP BY DATE(created_at)
                      ORDER BY DATE(created_at)";
        if ($wr = $mysqli->query($weekQuery)) {
            $dates = [];
            while ($row = $wr->fetch_assoc()) {
                $weeklyData[$row['day']] = (int)$row['total'];
            }
            $wr->free();
        }
    }

    $res2 = $mysqli->query("SHOW TABLES LIKE 'users'");
    if ($res2 && $res2->num_rows > 0) {
        $hasRole = false;
        $hasStatus = false;
        if ($cols = $mysqli->query("SHOW COLUMNS FROM users LIKE 'role'")) {
            $hasRole = $cols->num_rows > 0;
            $cols->free();
        }
        if ($cols = $mysqli->query("SHOW COLUMNS FROM users LIKE 'status'")) {
            $hasStatus = $cols->num_rows > 0;
            $cols->free();
        }

        if ($hasRole || $hasStatus) {
            $fields = ["COUNT(*) AS total"];
            $fields[] = $hasRole ? "SUM(role = 'Administrator') AS admins" : "0 AS admins";
            $fields[] = $hasStatus ? "SUM(status = 'Active') AS active" : "0 AS active";
            $uquery = "SELECT " . implode(",\n", $fields) . " FROM users";
        } else {
            $uquery = "SELECT COUNT(*) AS total, 0 AS admins, 0 AS active FROM users";
        }

        if ($ur = $mysqli->query($uquery)) {
            $row = $ur->fetch_assoc();
            if ($row) {
                $userStats['total'] = (int)$row['total'];
                $userStats['admins'] = (int)$row['admins'];
                $userStats['active'] = (int)$row['active'];
            }
            $ur->free();
        }
        $res2->free();
    }
}

// Fill missing days for the last 7 days
$weeklyLabels = [];
$weeklyDisplayLabels = [];
$weeklyValues = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $weeklyLabels[] = $day;
    $weeklyDisplayLabels[] = date('D', strtotime($day));
    $weeklyValues[] = (int)($weeklyData[$day] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DocuTrack — Document Management</title>
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
    display: flex;
    flex-direction: column;
    overflow-x: hidden;
  }

  /* ── Animated background mesh ── */
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
    padding: 0 28px;
    z-index: 100;
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
  .logo-text { line-height: 1; }
  .logo-text h1 { font-family: 'Syne', sans-serif; font-size: 17px; font-weight: 800; letter-spacing: -0.3px; color: #fff; }
  .logo-text span { font-size: 10px; color: var(--text-muted); font-weight: 300; letter-spacing: 0.5px; }

  .topbar-right {
    margin-left: auto;
    display: flex; align-items: center; gap: 16px;
  }

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
  .user-info { line-height: 1.2; }
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
  .layout {
    display: flex; padding-top: 64px; min-height: 100vh;
    position: relative; z-index: 1;
  }

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
    text-decoration: none;
    border: 1px solid transparent;
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

  /* ── Main ── */
  .main {
    margin-left: var(--sidebar-w);
    flex: 1; padding: 32px 32px 48px;
    display: flex; flex-direction: column; gap: 28px;
  }

  .page-header { animation: fadeUp 0.5s 0.2s ease both; }
  .page-header h2 { font-family: 'Syne', sans-serif; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
  .page-header p { font-size: 13.5px; color: var(--text-muted); margin-top: 4px; }

  @keyframes fadeUp { from { transform: translateY(16px); opacity: 0; } to { transform: none; opacity: 1; } }

  /* ── Stat cards ── */
  .stats-row {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    animation: fadeUp 0.5s 0.3s ease both;
  }

  .stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 14px; padding: 18px;
    position: relative; overflow: hidden;
    transition: transform 0.18s, border-color 0.18s, box-shadow 0.18s;
    cursor: default;
  }
  .stat-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: var(--accent); opacity: 0.12;
  }
  .stat-card:hover { transform: translateY(-3px); border-color: rgba(99,140,200,0.18); box-shadow: 0 8px 24px rgba(0,0,0,0.18); }

  .stat-top { display: flex; align-items: center; justify-content: flex-start; gap: 12px; margin-bottom: 12px; }
  .stat-icon {
    width: 44px; height: 44px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
    background: color-mix(in srgb, var(--accent) 14%, transparent);
  }
  .stat-num {
    font-family: 'Syne', sans-serif; font-size: 30px; font-weight: 800;
    line-height: 1; letter-spacing: -0.6px; color: #fff;
  }
  .stat-label { font-size: 13px; color: var(--text-muted); margin-top: 6px; font-weight: 400; letter-spacing: 0.2px; }

  /* ── Charts row ── */
  .charts-row {
    display: grid; grid-template-columns: 3.5fr 1fr;
    gap: 16px;
    animation: fadeUp 0.5s 0.4s ease both;
  }

  .bar-chart-container {
    display: grid;
    grid-template-columns: 42px 1fr;
    gap: 18px;
    width: 100%;
    align-items: flex-end;
    min-height: 220px;
  }

  .y-axis {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    width: 100%;
    height: 190px;
  }

  .y-axis-label {
    font-size: 11px;
    color: var(--text-muted);
    text-align: right;
    width: 100%;
    padding-right: 4px;
  }

  .bar-chart {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 18px;
    height: 190px;
    width: 100%;
    border-left: 1px solid rgba(255,255,255,0.12);
    border-bottom: 1px solid rgba(255,255,255,0.12);
    padding-left: 10px;
    padding-right: 10px;
  }

  .bar-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    min-width: 56px;
    max-width: 92px;
  }

  .bar-wrap {
    flex: 1;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    width: 100%;
    min-height: 190px;
    position: relative;
  }

  .bar-wrap::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: linear-gradient(to top,
      transparent 0%, transparent 32%, rgba(255,255,255,0.08) 32%, rgba(255,255,255,0.08) 34%,
      transparent 34%, transparent 52%, rgba(255,255,255,0.08) 52%, rgba(255,255,255,0.08) 54%,
      transparent 54%, transparent 72%, rgba(255,255,255,0.08) 72%, rgba(255,255,255,0.08) 74%,
      transparent 74%, transparent 100%);
    pointer-events: none;
  }

  .bar {
    width: 80%;
    max-width: 78px;
    border-radius: 12px 12px 0 0;
    transition: transform 0.2s, opacity 0.2s;
    box-shadow: 0 14px 30px rgba(0,0,0,0.16);
    animation: growUp 0.8s 0.3s ease both;
    transform-origin: bottom;
    min-height: 8px;
  }

  .card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 24px;
  }

  .card-title {
    font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700;
    margin-bottom: 20px; color: var(--text);
    display: flex; align-items: center; gap: 8px;
  }

  .card-title::before {
    content: ''; display: block; width: 3px; height: 16px;
    background: var(--blue); border-radius: 2px;
  }

  @keyframes growUp { from { transform: scaleY(0); opacity: 0; } to { transform: scaleY(1); opacity: 1; } }
  .bar:hover { opacity: 0.75; }
  .bar-value { font-size: 11px; color: #fff; margin-bottom: 6px; font-weight: 600; }
  .bar-label { font-size: 11px; color: var(--text-muted); letter-spacing: 0.02em; }
  .card-note { font-size: 12px; color: var(--text-muted); margin-bottom: 12px; }

  @media (max-width: 900px) {
    .stats-row { grid-template-columns: repeat(2, 1fr); }
  }
  @media (max-width: 520px) {
    .stats-row { grid-template-columns: 1fr; }
    .stat-top { gap: 10px; }
    .stat-num { font-size: 26px; }
  }

  /* Donut chart */
  .donut-wrap { display: flex; flex-direction: column; align-items: center; gap: 20px; }
  .donut-svg { filter: drop-shadow(0 0 20px rgba(37,99,235,0.2)); }
  .donut-legend { width: 100%; display: flex; flex-direction: column; gap: 10px; }
  .legend-item { display: flex; align-items: center; justify-content: space-between; font-size: 13px; }
  .legend-dot { width: 9px; height: 9px; border-radius: 50%; margin-right: 8px; flex-shrink: 0; }
  .legend-left { display: flex; align-items: center; color: var(--text-muted); }
  .legend-val { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 14px; }

  /* ── Table ── */
  .table-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 18px; overflow: hidden;
    animation: fadeUp 0.5s 0.5s ease both;
  }
  .table-header {
    padding: 20px 24px 16px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--border);
  }
  .table-header .card-title { margin: 0; }

  .search-box {
    display: flex; align-items: center; gap: 8px;
    background: rgba(15,27,45,0.6); border: 1px solid var(--border);
    border-radius: 10px; padding: 7px 12px;
    color: var(--text-muted); font-size: 13px;
    transition: border-color 0.2s;
  }
  .search-box:hover { border-color: var(--blue); }
  .search-box input {
    background: transparent; border: none; outline: none;
    color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 13px;
    width: 160px;
  }
  .search-box input::placeholder { color: var(--text-muted); }

  table { width: 100%; border-collapse: collapse; }
  thead tr { border-bottom: 1px solid var(--border); }
  th {
    padding: 12px 24px; text-align: left;
    font-size: 10.5px; font-weight: 500; letter-spacing: 0.8px;
    color: var(--text-muted); text-transform: uppercase;
  }
  tbody tr {
    border-bottom: 1px solid rgba(99,140,200,0.06);
    transition: background 0.15s;
  }
  tbody tr:last-child { border: none; }
  tbody tr:hover { background: rgba(37,99,235,0.05); }
  td { padding: 16px 24px; font-size: 13.5px; }

  .tracking-id {
    font-family: 'Syne', sans-serif; font-weight: 700; font-size: 13px;
    color: var(--blue-light); cursor: pointer;
    transition: color 0.15s;
  }
  .tracking-id:hover { color: #93c5fd; text-decoration: underline; }

  .status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 500;
  }
  .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
  .status-processing { background: rgba(37,99,235,0.15); color: var(--blue-light); }
  .status-completed  { background: rgba(16,185,129,0.15); color: var(--green); }
  .status-pending    { background: rgba(245,158,11,0.15); color: var(--amber); }

  .date-cell { color: var(--text-muted); font-size: 12.5px; font-weight: 300; }

  /* scrollbar */
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
  $dt_initials = '';
  if ($dt_name) {
    $p = preg_split('/\s+/', trim($dt_name));
    $dt_initials = strtoupper(substr($p[0],0,1) . (isset($p[1])?substr($p[1],0,1):''));
  }
?>
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

<!-- ── LAYOUT ── -->
<div class="layout">

  <!-- ── SIDEBAR ── -->
  <?php include 'sidebar.php'; ?>

  <!-- ── MAIN ── -->
  <main class="main">

    <!-- Page Header -->
    <div class="page-header">
      <h2>Dashboard Overview</h2>
      <p>Welcome back <?php echo e($_SESSION['username'] ?? 'Maria'); ?>! Here's what's happening today.</p>
    </div>

    <!-- Stat Cards -->
    <div class="stats-row">
      <div class="stat-card" style="--accent: #3b82f6;">
        <div class="stat-top">
          <div class="stat-icon" style="--accent:#3b82f6;">📄</div>
        </div>
        <div class="stat-num"><?php echo e($counts['total']); ?></div>
        <div class="stat-label">Total Documents</div>
      </div>
      <div class="stat-card" style="--accent: #f59e0b;">
        <div class="stat-top">
          <div class="stat-icon" style="--accent:#f59e0b;">⏱</div>
        </div>
        <div class="stat-num"><?php echo e($counts['pending']); ?></div>
        <div class="stat-label">Pending Documents</div>
      </div>
      <div class="stat-card" style="--accent: #8b5cf6;">
        <div class="stat-top">
          <div class="stat-icon" style="--accent:#8b5cf6;">📊</div>
        </div>
        <div class="stat-num"><?php echo e($counts['processing']); ?></div>
        <div class="stat-label">Processing Documents</div>
      </div>
      <div class="stat-card" style="--accent: #10b981;">
        <div class="stat-top">
          <div class="stat-icon" style="--accent:#10b981;">✅</div>
        </div>
        <div class="stat-num"><?php echo e($counts['completed']); ?></div>
        <div class="stat-label">Completed Documents</div>
      </div>
      <div class="stat-card" style="--accent: #6d28d9;">
        <div class="stat-top">
          <div class="stat-icon" style="--accent:#6d28d9;">👥</div>
        </div>
        <div class="stat-num"><?php echo e($userStats['total']); ?></div>
        <div class="stat-label">Total Users</div>
      </div>
      <div class="stat-card" style="--accent: #2563eb;">
        <div class="stat-top">
          <div class="stat-icon" style="--accent:#2563eb;">🔑</div>
        </div>
        <div class="stat-num"><?php echo e($userStats['admins']); ?></div>
        <div class="stat-label">Administrators</div>
      </div>
      <div class="stat-card" style="--accent: #10b981;">
        <div class="stat-top">
          <div class="stat-icon" style="--accent:#10b981;">🟢</div>
        </div>
        <div class="stat-num"><?php echo e($userStats['active']); ?></div>
        <div class="stat-label">Active Users</div>
      </div>
    </div>

    <!-- Charts -->
    <div class="charts-row">
      <!-- Bar Chart -->
      <div class="card">
        <div class="card-title">Weekly Request Activity</div>
      <div class="card-note">Shows requests created each day for the past week.</div>
      <div class="bar-chart-container">
        <div class="y-axis">
          <div class="y-axis-label y-25">25</div>
          <div class="y-axis-label y-15">15</div>
          <div class="y-axis-label y-10">10</div>
          <div class="y-axis-label y-5">5</div>
          <div class="y-axis-label y-0">0</div>
        </div>
        <div class="bar-chart" id="barChart"></div>
      </div>
      </div>
      <!-- Donut Chart -->
      <div class="card">
        <div class="card-title">Status Distribution</div>
        <div class="donut-wrap">
          <svg class="donut-svg" width="150" height="150" viewBox="0 0 150 150">
            <circle cx="75" cy="75" r="55" fill="none" stroke="rgba(99,140,200,0.08)" stroke-width="22"/>
            <circle id="pendingSegment" cx="75" cy="75" r="55" fill="none" stroke="#f59e0b" stroke-width="22" stroke-linecap="round" transform="rotate(-90 75 75)"/>
            <circle id="processingSegment" cx="75" cy="75" r="55" fill="none" stroke="#8b5cf6" stroke-width="22" stroke-linecap="round" transform="rotate(-90 75 75)"/>
            <circle id="completedSegment" cx="75" cy="75" r="55" fill="none" stroke="#10b981" stroke-width="22" stroke-linecap="round" transform="rotate(-90 75 75)"/>
            <text x="75" y="70" text-anchor="middle" fill="#e2eaf5" font-family="Syne,sans-serif" font-size="22" font-weight="800"><?php echo e($counts['total']); ?></text>
            <text x="75" y="88" text-anchor="middle" fill="#7a92b0" font-family="DM Sans,sans-serif" font-size="10">Total</text>
          </svg>
          <div class="donut-legend">
            <div class="legend-item">
              <div class="legend-left"><span class="legend-dot" style="background:#f59e0b"></span>Pending</div>
              <span class="legend-val" style="color:#f59e0b"><?php echo e($counts['pending']); ?></span>
            </div>
            <div class="legend-item">
              <div class="legend-left"><span class="legend-dot" style="background:#8b5cf6"></span>Processing</div>
              <span class="legend-val" style="color:#8b5cf6"><?php echo e($counts['processing']); ?></span>
            </div>
            <div class="legend-item">
              <div class="legend-left"><span class="legend-dot" style="background:#10b981"></span>Completed</div>
              <span class="legend-val" style="color:#10b981"><?php echo e($counts['completed']); ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Documents Table -->
    <div class="table-card">
      <div class="table-header">
        <div class="card-title">Recent Documents</div>
        <div class="search-box">
          <span>🔍</span>
          <input type="text" placeholder="Search documents..." oninput="filterTable(this.value)">
        </div>
      </div>
      <table>
        <thead>
          <tr>
            <th>Tracking ID</th>
            <th>Name</th>
            <th>Document Type</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody id="tableBody">
          <?php if (count($recent) === 0): ?>
            <tr>
              <td colspan="5" class="empty-row">No document requests found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($recent as $r): ?>
              <tr>
                <td><span class="tracking-id"><?php echo e($r['tracking_id']); ?></span></td>
                <td><?php echo e($r['fullname']); ?></td>
                <td><?php echo e($r['document_type']); ?></td>
                <td>
                  <?php
                    $st = strtolower($r['status']);
                    $cls = $st === 'processing' ? 'status-processing' : ($st === 'completed' ? 'status-completed' : 'status-pending');
                  ?>
                  <span class="status-badge <?php echo e($cls); ?>"><?php echo e($r['status']); ?></span>
                </td>
                <td class="date-cell"><?php echo e($r['created_at']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>
</div>

<script>
  const weeklyLabels = <?php echo json_encode($weeklyDisplayLabels); ?>;
  const weeklyVals = <?php echo json_encode($weeklyValues); ?>;
  const weeklyDates = <?php echo json_encode($weeklyLabels); ?>;
  const statusCounts = {
    total: <?php echo (int)$counts['total']; ?>,
    pending: <?php echo (int)$counts['pending']; ?>,
    processing: <?php echo (int)$counts['processing']; ?>,
    completed: <?php echo (int)$counts['completed']; ?>
  };

  const chart = document.getElementById('barChart');
  const colors = ['#2563eb', '#10b981', '#8b5cf6', '#f59e0b', '#ef4444', '#22c55e', '#0ea5e9'];
  const maxScale = 25;
  const maxHeightPx = 170;
  weeklyLabels.forEach((label, index) => {
    const col = document.createElement('div');
    col.className = 'bar-col';
    const value = Number(weeklyVals[index] || 0);
    const scaleValue = Math.min(value, maxScale);
    const barHeight = value > 0 ? Math.max(18, Math.round((scaleValue / maxScale) * maxHeightPx)) : 8;
    const color = colors[index % colors.length];
    const light = `${color}44`;
    const overflowLabel = value > maxScale ? ' +' : '';
    col.innerHTML = `
      <div class="bar-wrap">
        <div class="bar" style="height:${barHeight}px; background: linear-gradient(180deg, ${color}, ${light}); animation-delay:${0.3 + index*0.06}s;" title="${value} requests"></div>
      </div>
      <div class="bar-value">${value}${overflowLabel}</div>
      <div class="bar-label" title="${weeklyDates[index]}">${label}</div>
    `;
    chart.appendChild(col);
  });

  function drawDonut() {
    const total = statusCounts.total || 1;
    const circumference = 2 * Math.PI * 55;
    const segments = [
      { id: 'pendingSegment', value: statusCounts.pending },
      { id: 'processingSegment', value: statusCounts.processing },
      { id: 'completedSegment', value: statusCounts.completed }
    ];

    let offset = 0;
    segments.forEach(segment => {
      const el = document.getElementById(segment.id);
      if (!el) return;
      const part = Math.max(0, segment.value);
      const length = (part / total) * circumference;
      el.style.strokeDasharray = `${length} ${circumference - length}`;
      el.style.strokeDashoffset = -offset;
      offset += length;
    });
  }

  drawDonut();

  // Table filter
  function filterTable(q) {
    const rows = document.querySelectorAll('#tableBody tr');
    rows.forEach(r => {
      r.style.display = r.textContent.toLowerCase().includes(q.toLowerCase()) ? '' : 'none';
    });
  }

</script>
</body>
</html>
