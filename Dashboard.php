
<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

/* helper */
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/* default demo data */
$counts = ['total'=>120,'pending'=>45,'processing'=>50,'completed'=>25];
$recent = [
  ['tracking_id'=>'TRK-001','fullname'=>'John Doe','document_type'=>'Birth Certificate','status'=>'Processing','created_at'=>'2026-05-01'],
  ['tracking_id'=>'TRK-002','fullname'=>'Jane Smith','document_type'=>'Marriage License','status'=>'Completed','created_at'=>'2026-05-02'],
  ['tracking_id'=>'TRK-003','fullname'=>'Bob Johnson','document_type'=>'Business Permit','status'=>'Pending','created_at'=>'2026-05-03'],
  ['tracking_id'=>'TRK-004','fullname'=>'Alice Brown','document_type'=>'Tax Clearance','status'=>'Processing','created_at'=>'2026-05-04'],
  ['tracking_id'=>'TRK-005','fullname'=>'Charlie Davis','document_type'=>'ID Card','status'=>'Pending','created_at'=>'2026-05-05'],
];

/* try to obtain a mysqli connection (prefer existing connector) */
$mysqli = null;
if (file_exists(__DIR__ . '/db.php')) {
    include_once __DIR__ . '/db.php'; // may set $mysqli or $conn
}
if (!$mysqli && file_exists(__DIR__ . '/database.php/db.php')) {
    include_once __DIR__ . '/database.php/db.php';
}
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    // common fallbacks
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        // ok
    } elseif (isset($conn) && $conn instanceof mysqli) {
        $mysqli = $conn;
    } else {
        // try direct connect
        @$tmp = new mysqli('127.0.0.1','root','','docu_tracker');
        if (!$tmp->connect_errno) $mysqli = $tmp;
        unset($tmp);
    }
}

/* If we have mysqli, try to read real data from `requests` table */
if ($mysqli instanceof mysqli) {
    $res = $mysqli->query("SHOW TABLES LIKE 'requests'");
    if ($res && $res->num_rows > 0) {
        $q = "SELECT 
                COUNT(*) AS total,
                SUM(status = 'Pending') AS pending,
                SUM(status = 'Processing') AS processing,
                SUM(status = 'Completed') AS completed
              FROM requests";
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

        $rq = "SELECT tracking_id, fullname, document_type, status, DATE_FORMAT(created_at, '%Y-%m-%d') AS created_at
               FROM requests ORDER BY created_at DESC LIMIT 12";
        if ($r2 = $mysqli->query($rq)) {
            $recent = [];
            while ($row = $r2->fetch_assoc()) {
                $recent[] = $row;
            }
            $r2->free();
        }
    }
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
    color: var(--blue-light);
    border-color: rgba(37,99,235,0.25);
    font-weight: 500;
  }
  .nav-icon { font-size: 16px; width: 20px; text-align: center; }

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
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;
    animation: fadeUp 0.5s 0.3s ease both;
  }

  .stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 18px; padding: 22px 20px;
    position: relative; overflow: hidden;
    transition: transform 0.2s, border-color 0.2s;
    cursor: default;
  }
  .stat-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
    background: var(--accent);
    opacity: 0.7;
  }
  .stat-card:hover { transform: translateY(-3px); border-color: rgba(99,140,200,0.24); }

  .stat-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px; }
  .stat-icon {
    width: 42px; height: 42px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 19px;
    background: color-mix(in srgb, var(--accent) 16%, transparent);
  }
  .stat-badge {
    font-size: 11.5px; font-weight: 500; color: var(--green);
    background: rgba(16,185,129,0.1); padding: 3px 8px; border-radius: 20px;
  }
  .stat-num {
    font-family: 'Syne', sans-serif; font-size: 36px; font-weight: 800;
    line-height: 1; letter-spacing: -1px;
    background: linear-gradient(135deg, #fff, var(--text-muted));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  }
  .stat-label { font-size: 12px; color: var(--text-muted); margin-top: 6px; font-weight: 300; letter-spacing: 0.3px; }

  /* ── Charts row ── */
  .charts-row {
    display: grid; grid-template-columns: 1.6fr 1fr; gap: 16px;
    animation: fadeUp 0.5s 0.4s ease both;
  }

  .card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 18px; padding: 24px;
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

  /* Bar chart */
  .bar-chart { display: flex; align-items: flex-end; gap: 10px; height: 140px; }
  .bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; }
  .bar-wrap { flex: 1; display: flex; align-items: flex-end; width: 100%; }
  .bar {
    width: 100%; border-radius: 6px 6px 0 0;
    background: linear-gradient(to top, var(--blue), var(--blue-light));
    transition: opacity 0.2s;
    box-shadow: 0 0 14px rgba(37,99,235,0.25);
    animation: growUp 0.8s 0.5s ease both;
    transform-origin: bottom;
  }
  @keyframes growUp { from { transform: scaleY(0); opacity: 0; } to { transform: scaleY(1); opacity: 1; } }
  .bar:hover { opacity: 0.75; }
  .bar-label { font-size: 10px; color: var(--text-muted); }

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
<header class="topbar">
  <div class="logo-mark">📄</div>
  <div class="logo-text">
    <h1>DocuTrack</h1>
    <span>Document Management</span>
  </div>
  <div class="topbar-right">
    <button class="bell-btn" title="Notifications">
      🔔
      <span class="bell-dot"></span>
    </button>
    <div class="user-chip">
      <div class="avatar">MS</div>
      <div class="user-info">
        <strong><?php echo e($_SESSION['username'] ?? 'Maria Santisima'); ?></strong>
        <small>Administrator</small>
      </div>
    </div>
    <form method="post" action="logout.php" style="display:inline">
      <button type="submit" class="logout-btn">↪ Logout</button>
    </form>
  </div>
</header>

<!-- ── LAYOUT ── -->
<div class="layout">

  <!-- ── SIDEBAR ── -->
  <nav class="sidebar">
    <a class="nav-item active" href=>
      <span class="nav-icon">🏠</span> Dashboard
    </a>
    <a class="nav-item" href=>
      <span class="nav-icon">📋</span> Track Documents
    </a>
    <a class="nav-item" href=>
      <span class="nav-icon">➕</span> Add Request
    </a>
    <a class="nav-item" href=>
      <span class="nav-icon">🔔</span> Notifications
    </a>
    <a class="nav-item" href=>
      <span class="nav-icon">👥</span> User Management
    </a>
    <a class="nav-item" href=>
      <span class="nav-icon">🕐</span> Document History
    </a>
  </nav>

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
          <span class="stat-badge">+12%</span>
        </div>
        <div class="stat-num"><?php echo e($counts['total']); ?></div>
        <div class="stat-label">Total Requests</div>
      </div>
      <div class="stat-card" style="--accent: #f59e0b;">
        <div class="stat-top">
          <div class="stat-icon" style="--accent:#f59e0b;">⏱</div>
          <span class="stat-badge">+5%</span>
        </div>
        <div class="stat-num"><?php echo e($counts['pending']); ?></div>
        <div class="stat-label">Pending</div>
      </div>
      <div class="stat-card" style="--accent: #8b5cf6;">
        <div class="stat-top">
          <div class="stat-icon" style="--accent:#8b5cf6;">📊</div>
          <span class="stat-badge">+8%</span>
        </div>
        <div class="stat-num"><?php echo e($counts['processing']); ?></div>
        <div class="stat-label">Processing</div>
      </div>
      <div class="stat-card" style="--accent: #10b981;">
        <div class="stat-top">
          <div class="stat-icon" style="--accent:#10b981;">✅</div>
          <span class="stat-badge">+15%</span>
        </div>
        <div class="stat-num"><?php echo e($counts['completed']); ?></div>
        <div class="stat-label">Completed</div>
      </div>
    </div>

    <!-- Charts -->
    <div class="charts-row">
      <!-- Bar Chart -->
      <div class="card">
        <div class="card-title">Weekly Request Activity</div>
        <div class="bar-chart" id="barChart"></div>
      </div>
      <!-- Donut Chart -->
      <div class="card">
        <div class="card-title">Status Distribution</div>
        <div class="donut-wrap">
          <svg class="donut-svg" width="150" height="150" viewBox="0 0 150 150">
            <circle cx="75" cy="75" r="55" fill="none" stroke="rgba(99,140,200,0.08)" stroke-width="22"/>
            <!-- segments are static visuals; numbers below reflect DB counts -->
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

    <!-- Recent Requests Table -->
    <div class="table-card">
      <div class="table-header">
        <div class="card-title">Recent Requests</div>
        <div class="search-box">
          <span>🔍</span>
          <input type="text" placeholder="Search requests..." oninput="filterTable(this.value)">
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
        </tbody>
      </table>
    </div>

  </main>
</div>

<script>
  // Bar chart data (static demo, replace with dynamic fetch if needed)
  const days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
  const vals = [18, 12, 26, 21, 28, 22, 15];
  const max = Math.max(...vals);
  const chart = document.getElementById('barChart');
  days.forEach((d,i) => {
    const col = document.createElement('div');
    col.className = 'bar-col';
    const pct = (vals[i] / max * 100).toFixed(1);
    col.innerHTML = `
      <div class="bar-wrap">
        <div class="bar" style="height:${pct}%; animation-delay:${0.5 + i*0.07}s;" title="${vals[i]} requests"></div>
      </div>
      <div class="bar-label">${d}</div>
    `;
    chart.appendChild(col);
  });

  // Table filter
  function filterTable(q) {
    const rows = document.querySelectorAll('#tableBody tr');
    rows.forEach(r => {
      r.style.display = r.textContent.toLowerCase().includes(q.toLowerCase()) ? '' : 'none';
    });
  }

  // Sidebar active state
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', e => {
      e.preventDefault();
      document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
      item.classList.add('active');
    });
  });
</script>
</body>
</html>
