<?php include 'sidebar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DocuTrack — Document History</title>
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
    background: var(--navy); color: var(--text);
    min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden;
  }
  body::before {
    content: ''; position: fixed; inset: 0;
    background:
      radial-gradient(ellipse 60% 40% at 20% 10%, rgba(37,99,235,0.13) 0%, transparent 70%),
      radial-gradient(ellipse 40% 50% at 80% 80%, rgba(139,92,246,0.09) 0%, transparent 70%),
      radial-gradient(ellipse 50% 30% at 60% 20%, rgba(16,185,129,0.06) 0%, transparent 70%);
    pointer-events: none; z-index: 0;
  }

  /* ── Topbar ── */
  .topbar {
    position: fixed; top: 0; left: 0; right: 0; height: 64px;
    background: rgba(15,27,45,0.92); backdrop-filter: blur(18px);
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
    font-size: 18px; flex-shrink: 0; box-shadow: 0 0 18px var(--blue-glow);
  }
  .logo-text h1 { font-family: 'Syne', sans-serif; font-size: 17px; font-weight: 800; letter-spacing: -0.3px; color: #fff; }
  .logo-text span { font-size: 10px; color: var(--text-muted); font-weight: 300; letter-spacing: 0.5px; }
  .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 16px; }
  .bell-btn {
    position: relative; width: 38px; height: 38px;
    border: 1px solid var(--border); border-radius: 10px;
    background: var(--card-bg); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: var(--text-muted); font-size: 16px; transition: border-color 0.2s;
  }
  .bell-btn:hover { border-color: var(--blue); color: var(--blue-light); }
  .bell-dot { position: absolute; top: 6px; right: 7px; width: 8px; height: 8px; border-radius: 50%; background: var(--red); border: 2px solid var(--navy); }
  .user-chip {
    display: flex; align-items: center; gap: 10px;
    padding: 6px 14px 6px 6px; background: var(--card-bg);
    border: 1px solid var(--border); border-radius: 40px; cursor: pointer; transition: border-color 0.2s;
  }
  .user-chip:hover { border-color: var(--blue); }
  .avatar { width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, var(--blue), var(--purple)); display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700; color: #fff; flex-shrink: 0; }
  .user-info strong { display: block; font-size: 12px; font-weight: 500; color: var(--text); }
  .user-info small { font-size: 10px; color: var(--text-muted); }
  .logout-btn { display: flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 10px; background: linear-gradient(135deg, var(--blue), #1d4ed8); border: none; cursor: pointer; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 500; transition: opacity 0.2s, transform 0.15s; box-shadow: 0 4px 16px rgba(37,99,235,0.3); }
  .logout-btn:hover { opacity: 0.88; transform: translateY(-1px); }

  /* ── Layout ── */
  .layout { display: flex; padding-top: 64px; min-height: 100vh; position: relative; z-index: 1; }
  .sidebar {
    width: var(--sidebar-w); flex-shrink: 0;
    position: fixed; top: 64px; left: 0; bottom: 0;
    background: rgba(15,27,45,0.75); backdrop-filter: blur(18px);
    border-right: 1px solid var(--border);
    padding: 20px 12px; display: flex; flex-direction: column; gap: 4px;
    animation: slideRight 0.5s 0.1s ease both; overflow-y: auto;
  }
  @keyframes slideRight { from { transform: translateX(-30px); opacity: 0; } to { transform: none; opacity: 1; } }
  .nav-item {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; border-radius: 12px; cursor: pointer;
    color: var(--text-muted); font-size: 14px; font-weight: 400;
    transition: background 0.2s, color 0.2s; text-decoration: none; border: 1px solid transparent;
  }
  .nav-item:hover { background: rgba(37,99,235,0.08); color: var(--text); }
  .nav-item.active { background: linear-gradient(135deg, rgba(37,99,235,0.22), rgba(37,99,235,0.08)); color: var(--blue-light); border-color: rgba(37,99,235,0.25); font-weight: 500; }
  .nav-icon { font-size: 16px; width: 20px; text-align: center; }

  /* ── Main ── */
  .main { margin-left: var(--sidebar-w); flex: 1; padding: 32px 36px 60px; display: flex; flex-direction: column; gap: 24px; }
  @keyframes fadeUp { from { transform: translateY(14px); opacity: 0; } to { transform: none; opacity: 1; } }

  /* ── Page header ── */
  .page-header-row {
    display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 14px;
    animation: fadeUp 0.5s 0.15s ease both;
  }
  .page-header h2 { font-family: 'Syne', sans-serif; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
  .page-header p { font-size: 13.5px; color: var(--text-muted); margin-top: 4px; }
  .header-actions { display: flex; gap: 12px; align-items: center; }

  .filter-btn {
    display: flex; align-items: center; gap: 7px;
    padding: 10px 20px; border-radius: 11px;
    background: var(--card-bg); border: 1px solid var(--border);
    color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500;
    cursor: pointer; transition: border-color 0.2s, background 0.2s;
  }
  .filter-btn:hover, .filter-btn.active { border-color: var(--blue); color: var(--blue-light); background: rgba(37,99,235,0.08); }

  .export-btn {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 22px; border-radius: 11px;
    background: linear-gradient(135deg, var(--blue), #1d4ed8); border: none;
    color: #fff; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500;
    cursor: pointer; box-shadow: 0 4px 20px rgba(37,99,235,0.3); transition: opacity 0.2s, transform 0.15s;
  }
  .export-btn:hover { opacity: 0.88; transform: translateY(-1px); }

  /* ── Filter panel ── */
  .filter-panel {
    background: var(--card-bg); border: 1px solid var(--border);
    border-radius: 14px; padding: 20px 24px;
    display: none; gap: 18px; flex-wrap: wrap;
    animation: fadeUp 0.3s ease both;
  }
  .filter-panel.open { display: flex; }
  .filter-group { display: flex; flex-direction: column; gap: 7px; min-width: 160px; }
  .filter-group label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.6px; font-weight: 500; }
  .filter-group select, .filter-group input {
    background: var(--navy-mid); border: 1px solid var(--border);
    border-radius: 9px; padding: 9px 12px;
    color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 13px;
    cursor: pointer; outline: none; transition: border-color 0.2s;
  }
  .filter-group select:focus, .filter-group input:focus { border-color: var(--blue); }
  .filter-group select option { background: var(--navy-mid); }
  .filter-actions { display: flex; gap: 10px; align-items: flex-end; margin-left: auto; }
  .btn-clear { padding: 9px 16px; border-radius: 9px; background: transparent; border: 1px solid var(--border); color: var(--text-muted); font-family: 'DM Sans', sans-serif; font-size: 13px; cursor: pointer; transition: border-color 0.2s, color 0.2s; }
  .btn-clear:hover { border-color: var(--red); color: var(--red); }
  .btn-apply { padding: 9px 18px; border-radius: 9px; background: var(--blue); border: none; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 13px; cursor: pointer; }

  /* ── Search bar ── */
  .search-bar {
    display: flex; align-items: center; gap: 10px;
    background: var(--card-bg); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 18px;
    transition: border-color 0.2s, box-shadow 0.2s;
    animation: fadeUp 0.5s 0.25s ease both;
  }
  .search-bar:focus-within { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
  .search-bar span { font-size: 16px; color: var(--text-muted); }
  .search-bar input { flex: 1; background: transparent; border: none; outline: none; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 14px; }
  .search-bar input::placeholder { color: var(--text-muted); }

  /* ── Summary chips ── */
  .summary-row {
    display: flex; gap: 10px; flex-wrap: wrap;
    animation: fadeUp 0.5s 0.3s ease both;
  }
  .summary-chip {
    display: flex; align-items: center; gap: 7px;
    background: var(--card-bg); border: 1px solid var(--border);
    border-radius: 40px; padding: 7px 16px; font-size: 13px;
    transition: border-color 0.2s;
  }
  .chip-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
  .chip-count { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 14px; }

  /* ── Timeline ── */
  .timeline-wrap {
    display: flex; flex-direction: column; gap: 0;
    animation: fadeUp 0.5s 0.38s ease both;
    position: relative;
  }

  .timeline-entry {
    display: flex; gap: 0; align-items: stretch;
    position: relative;
  }

  /* Left column: icon + line */
  .tl-left {
    display: flex; flex-direction: column; align-items: center;
    width: 56px; flex-shrink: 0; position: relative;
  }
  .tl-icon {
    width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px; position: relative; z-index: 2;
    border: 2px solid var(--icon-color);
    background: color-mix(in srgb, var(--icon-color) 14%, var(--navy));
    margin-top: 20px;
    box-shadow: 0 0 14px color-mix(in srgb, var(--icon-color) 30%, transparent);
  }
  .tl-line {
    width: 2px; flex: 1;
    background: linear-gradient(to bottom, color-mix(in srgb, var(--icon-color) 40%, transparent), rgba(99,140,200,0.1));
    margin-top: 4px; min-height: 20px;
  }
  .timeline-entry:last-child .tl-line { background: transparent; }

  /* Right column: card */
  .tl-right { flex: 1; padding: 14px 0 14px 16px; }

  .history-card {
    background: var(--card-bg); border: 1px solid var(--border);
    border-left: 3px solid var(--icon-color);
    border-radius: 16px; padding: 20px 22px;
    transition: transform 0.2s, border-color 0.2s;
    animation: fadeUp 0.4s ease both;
  }
  .history-card:hover { transform: translateY(-2px); border-color: color-mix(in srgb, var(--icon-color) 50%, var(--border)); }

  .card-top {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    margin-bottom: 8px;
  }
  .card-title { font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; }
  .trk-chip {
    background: rgba(37,99,235,0.12); border: 1px solid rgba(37,99,235,0.22);
    border-radius: 8px; padding: 3px 11px;
    font-family: 'Syne', sans-serif; font-size: 11.5px; font-weight: 700;
    color: var(--blue-light); white-space: nowrap; flex-shrink: 0;
  }
  .card-desc { font-size: 13.5px; color: var(--text-muted); line-height: 1.55; margin-bottom: 14px; }
  .card-desc strong { color: var(--text); }

  .card-footer { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
  .actor-chip { display: flex; align-items: center; gap: 8px; }
  .actor-avatar {
    width: 26px; height: 26px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Syne', sans-serif; font-size: 9px; font-weight: 700; color: #fff;
    flex-shrink: 0;
  }
  .actor-name { font-size: 12.5px; color: var(--text-muted); font-weight: 500; }

  .timestamp { display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--text-muted); font-weight: 300; }
  .timestamp-icon { font-size: 12px; opacity: 0.6; }

  /* Date divider */
  .date-divider {
    display: flex; align-items: center; gap: 14px;
    padding: 10px 0 6px 56px; /* align with cards */
  }
  .date-divider span {
    font-size: 11px; color: var(--text-muted); font-weight: 500;
    letter-spacing: 0.6px; text-transform: uppercase; white-space: nowrap;
  }
  .date-divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }

  /* ── Load more ── */
  .load-more-wrap { display: flex; justify-content: center; padding-top: 8px; animation: fadeUp 0.5s 0.5s ease both; }
  .load-more-btn {
    padding: 11px 32px; border-radius: 12px;
    background: var(--card-bg); border: 1px solid var(--border);
    color: var(--text-muted); font-family: 'DM Sans', sans-serif; font-size: 14px;
    cursor: pointer; transition: border-color 0.2s, color 0.2s;
  }
  .load-more-btn:hover { border-color: var(--blue); color: var(--blue-light); }

  /* ── Empty state ── */
  .empty-state { display: none; flex-direction: column; align-items: center; gap: 14px; padding: 72px 24px; text-align: center; }
  .empty-state.show { display: flex; }
  .empty-icon { font-size: 46px; opacity: 0.3; }
  .empty-state h3 { font-family: 'Syne', sans-serif; font-size: 17px; }
  .empty-state p { font-size: 13.5px; color: var(--text-muted); }

  /* ── Modal ── */
  .modal-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(10,18,32,0.78); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.25s; }
  .modal-overlay.open { opacity: 1; pointer-events: all; }
  .modal { background: var(--navy-mid); border: 1px solid var(--border); border-radius: 20px; padding: 28px 30px; width: 520px; max-width: 94vw; transform: translateY(20px); transition: transform 0.25s; box-shadow: 0 28px 80px rgba(0,0,0,0.5); }
  .modal-overlay.open .modal { transform: none; }
  .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; }
  .modal-title { font-family: 'Syne', sans-serif; font-size: 17px; font-weight: 800; }
  .modal-close { background: transparent; border: 1px solid var(--border); border-radius: 8px; width: 32px; height: 32px; color: var(--text-muted); cursor: pointer; font-size: 15px; display: flex; align-items: center; justify-content: center; transition: border-color 0.2s, color 0.2s; }
  .modal-close:hover { border-color: var(--red); color: var(--red); }
  .detail-hero { display: flex; align-items: center; gap: 16px; margin-bottom: 22px; padding: 18px; background: rgba(15,27,45,0.6); border-radius: 14px; border: 1px solid var(--border); }
  .detail-hero-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
  .detail-hero-info h3 { font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 800; margin-bottom: 4px; }
  .detail-hero-info p { font-size: 13px; color: var(--text-muted); }
  .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 11px 0; border-bottom: 1px solid var(--border); font-size: 13.5px; }
  .detail-row:last-of-type { border: none; }
  .detail-label { color: var(--text-muted); }
  .detail-value { font-weight: 500; color: var(--text); text-align: right; }
  .modal-footer { display: flex; justify-content: flex-end; margin-top: 22px; }
  .btn-ghost { padding: 10px 22px; border-radius: 10px; background: transparent; border: 1px solid var(--border); color: var(--text-muted); font-family: 'DM Sans', sans-serif; font-size: 14px; cursor: pointer; transition: border-color 0.2s; }
  .btn-ghost:hover { border-color: var(--text-muted); color: var(--text); }

  /* ── Toast ── */
  .toast { position: fixed; bottom: 28px; right: 28px; z-index: 400; background: var(--navy-mid); border: 1px solid var(--green); border-radius: 12px; padding: 13px 20px; display: flex; align-items: center; gap: 10px; font-size: 13.5px; color: var(--green); box-shadow: 0 8px 32px rgba(0,0,0,0.3); transform: translateY(80px); opacity: 0; transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s; pointer-events: none; }
  .toast.show { transform: none; opacity: 1; }

  ::-webkit-scrollbar { width: 5px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: var(--navy-light); border-radius: 4px; }
</style>
</head>
<body>

<!-- ── TOPBAR ── -->
<header class="topbar">
  <div class="logo-mark">📄</div>
  <div class="logo-text"><h1>DocuTrack</h1><span>Document Management</span></div>
  <div class="topbar-right">
    <button class="bell-btn">🔔<span class="bell-dot"></span></button>
    <div class="user-chip">
      <div class="avatar">MS</div>
      <div class="user-info"><strong>Maria Santisima</strong><small>Administrator</small></div>
    </div>
    <button class="logout-btn">↪ Logout</button>
  </div>
</header>

<div class="layout">
  <!-- ── SIDEBAR ── -->
  <nav class="sidebar">
    <a class="nav-item" href="#"><span class="nav-icon">🏠</span> Dashboard</a>
    <a class="nav-item" href="#"><span class="nav-icon">📋</span> Track Documents</a>
    <a class="nav-item" href="#"><span class="nav-icon">➕</span> Add Request</a>
    <a class="nav-item" href="#"><span class="nav-icon">🔔</span> Notifications</a>
    <a class="nav-item" href="#"><span class="nav-icon">👥</span> User Management</a>
    <a class="nav-item active" href="#"><span class="nav-icon">🕐</span> Document History</a>
  </nav>

  <!-- ── MAIN ── -->
  <main class="main">

    <!-- Header -->
    <div class="page-header-row">
      <div class="page-header">
        <h2>Document History</h2>
        <p>View all document processing activities and changes</p>
      </div>
      <div class="header-actions">
        <button class="filter-btn" id="filterToggle" onclick="toggleFilter()">⚙ Filter</button>
        <button class="export-btn" onclick="exportLogs()">⬇ Export Logs</button>
      </div>
    </div>

    <!-- Filter panel -->
    <div class="filter-panel" id="filterPanel">
      <div class="filter-group">
        <label>Event Type</label>
        <select id="fType" onchange="renderTimeline()">
          <option value="">All Types</option>
          <option value="completed">Completed</option>
          <option value="updated">Status Updated</option>
          <option value="requirements">Requirements</option>
          <option value="submitted">Submitted</option>
          <option value="rejected">Rejected</option>
          <option value="system">System</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Tracking ID</label>
        <input type="text" id="fTrk" placeholder="e.g. TRK-001" oninput="renderTimeline()">
      </div>
      <div class="filter-group">
        <label>Actor</label>
        <select id="fActor" onchange="renderTimeline()">
          <option value="">All Actors</option>
          <option value="System">System</option>
          <option value="Maria Santisima">Maria Santisima</option>
          <option value="John Doe">John Doe</option>
          <option value="Jane Smith">Jane Smith</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Date From</label>
        <input type="date" id="fDateFrom" onchange="renderTimeline()">
      </div>
      <div class="filter-group">
        <label>Date To</label>
        <input type="date" id="fDateTo" onchange="renderTimeline()">
      </div>
      <div class="filter-actions">
        <button class="btn-clear" onclick="clearFilters()">Clear</button>
        <button class="btn-apply" onclick="toggleFilter()">Apply</button>
      </div>
    </div>

    <!-- Search -->
    <div class="search-bar">
      <span>🔍</span>
      <input type="text" id="searchInput" placeholder="Search by document type, tracking ID, or actor..." oninput="renderTimeline()">
    </div>

    <!-- Summary chips -->
    <div class="summary-row" id="summaryRow"></div>

    <!-- Timeline -->
    <div class="timeline-wrap" id="timelineWrap"></div>
    <div class="empty-state" id="emptyState">
      <div class="empty-icon">🕐</div>
      <h3>No history found</h3>
      <p>Try adjusting your search or filters.</p>
    </div>

    <!-- Load more -->
    <div class="load-more-wrap" id="loadMoreWrap" style="display:none">
      <button class="load-more-btn" onclick="loadMore()">↓ Load More</button>
    </div>

  </main>
</div>

<!-- ── DETAIL MODAL ── -->
<div class="modal-overlay" id="detailModal" onclick="closeModalOutside(event)">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Activity Detail</div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="detail-hero">
      <div class="detail-hero-icon" id="dIcon"></div>
      <div class="detail-hero-info">
        <h3 id="dTitle"></h3>
        <p id="dDesc"></p>
      </div>
    </div>
    <div id="dRows"></div>
    <div class="modal-footer">
      <button class="btn-ghost" onclick="closeModal()">Close</button>
    </div>
  </div>
</div>

<!-- ── TOAST ── -->
<div class="toast" id="toast"></div>

<script>
  // ── Config ──
  const typeConfig = {
    completed:    { icon: '✅', color: '#10b981', label: 'Completed' },
    updated:      { icon: '📋', color: '#3b82f6', label: 'Status Updated' },
    requirements: { icon: '⚠️', color: '#f59e0b', label: 'Requirements' },
    submitted:    { icon: '📤', color: '#8b5cf6', label: 'Submitted' },
    rejected:     { icon: '❌', color: '#ef4444', label: 'Rejected' },
    system:       { icon: 'ℹ️', color: '#6366f1', label: 'System' },
  };

  const actorColors = {
    'System':          'linear-gradient(135deg,#6366f1,#4f46e5)',
    'Maria Santisima': 'linear-gradient(135deg,#2563eb,#8b5cf6)',
    'John Doe':        'linear-gradient(135deg,#10b981,#059669)',
    'Jane Smith':      'linear-gradient(135deg,#f59e0b,#d97706)',
  };

  function initials(name) { return name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase(); }

  // ── Dataset ──
  const allEvents = [
    { id:1,  type:'completed',    trk:'TRK-002', docType:'Birth Certificate',  title:'Document Completed',          desc:'Birth Certificate has been processed and is ready for pickup.',              actor:'System',          date:'2026-05-10', time:'14:30', details: { Stage:'Completed', Location:'Civil Registry Office', 'Pickup Window':'Window 3', 'Valid Until':'2026-05-30' } },
    { id:2,  type:'updated',      trk:'TRK-001', docType:'Business Permit',    title:'Status Updated',              desc:'Business Permit moved to <strong>Processing</strong> stage.',               actor:'Maria Santisima', date:'2026-05-10', time:'11:15', details: { 'Previous Status':'Pending', 'New Status':'Processing', Department:'Office of the Registrar', Progress:'60%' } },
    { id:3,  type:'requirements', trk:'TRK-003', docType:'Marriage License',   title:'Additional Requirements',     desc:'Applicant must submit PSA Birth Certificates of both parties.',            actor:'John Doe',        date:'2026-05-09', time:'15:00', details: { Requirement:'PSA Birth Certificates', Deadline:'2026-05-22', 'Submit At':'Window 4 – Civil Registry' } },
    { id:4,  type:'submitted',    trk:'TRK-007', docType:"Driver's License",   title:'New Request Submitted',       desc:"New Driver's License request has been logged into the system.",            actor:'Jane Smith',      date:'2026-05-09', time:'09:45', details: { Priority:'Normal', Purpose:'Personal Record', Department:'LTO Liaison Office' } },
    { id:5,  type:'rejected',     trk:'TRK-005', docType:'ID Card',            title:'Request Rejected',            desc:'ID Card request was rejected due to <strong>incomplete information</strong>.', actor:'System',       date:'2026-05-08', time:'13:20', details: { Reason:'Incomplete information', 'Resubmit By':'2026-05-28', 'Action Required':'Resubmit with complete details' } },
    { id:6,  type:'updated',      trk:'TRK-004', docType:'Tax Clearance',      title:'Status Updated',              desc:'Tax Clearance moved from Pending to <strong>Processing</strong>.',          actor:'Maria Santisima', date:'2026-05-08', time:'10:00', details: { 'Previous Status':'Pending', 'New Status':'Processing', Progress:'75%' } },
    { id:7,  type:'system',       trk:'—',       docType:'System',             title:'System Maintenance Complete', desc:'Scheduled maintenance completed. All services restored.',                  actor:'System',          date:'2026-05-07', time:'06:00', details: { Duration:'2 hours', Impact:'Read-only mode', Version:'v2.1.0' } },
    { id:8,  type:'completed',    trk:'TRK-006', docType:'Land Title',         title:'Document Completed',          desc:'Land Title request has been approved and is ready for release.',           actor:'System',          date:'2026-05-06', time:'16:45', details: { Stage:'Completed', 'Pickup Location':'Assessor\'s Office', 'Valid Until':'2026-06-06' } },
    { id:9,  type:'submitted',    trk:'TRK-008', docType:'Passport',           title:'New Request Submitted',       desc:'Passport application has been received and queued for processing.',        actor:'Jane Smith',      date:'2026-05-05', time:'14:10', details: { Priority:'Urgent', Purpose:'Travel / Visa', Estimated:'2026-05-19' } },
    { id:10, type:'requirements', trk:'TRK-003', docType:'Marriage License',   title:'Document Viewed',             desc:'Applicant was notified about missing requirements via email.',             actor:'John Doe',        date:'2026-05-04', time:'09:30', details: { Channel:'Email', Recipient:'applicant@email.com', 'Sent At':'09:30 AM' } },
    { id:11, type:'updated',      trk:'TRK-009', docType:'Birth Certificate',  title:'Status Updated',              desc:'Birth Certificate moved to <strong>Completed</strong> stage.',             actor:'Maria Santisima', date:'2026-05-03', time:'11:00', details: { 'Previous Status':'Processing', 'New Status':'Completed', Progress:'100%' } },
    { id:12, type:'submitted',    trk:'TRK-010', docType:'Tax Clearance',      title:'New Request Submitted',       desc:'Tax Clearance request submitted and assigned to Finance department.',       actor:'John Doe',        date:'2026-05-02', time:'08:55', details: { Priority:'Normal', Department:'Finance', Assigned:'Pending Review' } },
  ];

  const PAGE_SIZE = 8;
  let visibleCount = PAGE_SIZE;
  let filtered = [];

  function getFiltered() {
    const q      = document.getElementById('searchInput').value.toLowerCase();
    const type   = document.getElementById('fType').value;
    const trk    = document.getElementById('fTrk').value.toLowerCase();
    const actor  = document.getElementById('fActor').value;
    const from   = document.getElementById('fDateFrom').value;
    const to     = document.getElementById('fDateTo').value;

    return allEvents.filter(e => {
      const matchQ = !q || e.title.toLowerCase().includes(q) || e.docType.toLowerCase().includes(q) || e.trk.toLowerCase().includes(q) || e.actor.toLowerCase().includes(q);
      const matchT = !type  || e.type === type;
      const matchR = !trk   || e.trk.toLowerCase().includes(trk);
      const matchA = !actor || e.actor === actor;
      const matchF = !from  || e.date >= from;
      const matchTo= !to    || e.date <= to;
      return matchQ && matchT && matchR && matchA && matchF && matchTo;
    });
  }

  function renderSummary(events) {
    const counts = {};
    events.forEach(e => counts[e.type] = (counts[e.type] || 0) + 1);
    const row = document.getElementById('summaryRow');
    row.innerHTML = `<div class="summary-chip"><span class="chip-dot" style="background:#3b82f6"></span>Total <span class="chip-count">${events.length}</span></div>`;
    Object.entries(counts).forEach(([type, count]) => {
      const cfg = typeConfig[type];
      row.innerHTML += `<div class="summary-chip"><span class="chip-dot" style="background:${cfg.color}"></span>${cfg.label} <span class="chip-count" style="color:${cfg.color}">${count}</span></div>`;
    });
  }

  function renderTimeline() {
    filtered = getFiltered();
    renderSummary(filtered);
    const wrap = document.getElementById('timelineWrap');
    const empty = document.getElementById('emptyState');
    const loadWrap = document.getElementById('loadMoreWrap');
    const page = filtered.slice(0, visibleCount);

    wrap.innerHTML = '';
    empty.classList.toggle('show', filtered.length === 0);
    loadWrap.style.display = filtered.length > visibleCount ? '' : 'none';

    // Group by date
    let lastDate = '';
    page.forEach((e, i) => {
      const cfg = typeConfig[e.type];
      if (e.date !== lastDate) {
        lastDate = e.date;
        const div = document.createElement('div');
        div.className = 'date-divider';
        div.innerHTML = `<span>${formatDate(e.date)}</span>`;
        wrap.appendChild(div);
      }
      const entry = document.createElement('div');
      entry.className = 'timeline-entry';
      entry.style.animationDelay = `${i * 0.05}s`;
      entry.innerHTML = `
        <div class="tl-left">
          <div class="tl-icon" style="--icon-color:${cfg.color}">${cfg.icon}</div>
          <div class="tl-line" style="--icon-color:${cfg.color}"></div>
        </div>
        <div class="tl-right">
          <div class="history-card" style="--icon-color:${cfg.color}; animation-delay:${i*0.05}s">
            <div class="card-top">
              <div class="card-title">${e.title}</div>
              ${e.trk !== '—' ? `<span class="trk-chip">${e.trk}</span>` : ''}
            </div>
            <div class="card-desc">${e.desc}</div>
            <div class="card-footer">
              <div class="actor-chip">
                <div class="actor-avatar" style="background:${actorColors[e.actor] || 'linear-gradient(135deg,#3b82f6,#2563eb)'}">${initials(e.actor)}</div>
                <span class="actor-name">${e.actor}</span>
              </div>
              <div style="display:flex;align-items:center;gap:12px">
                <div class="timestamp"><span class="timestamp-icon">🕐</span>${e.date} ${e.time}</div>
                <button onclick="openDetail(${e.id})" style="background:transparent;border:1px solid var(--border);border-radius:7px;padding:4px 10px;color:var(--blue-light);font-family:'DM Sans',sans-serif;font-size:12px;cursor:pointer;transition:border-color 0.2s;" onmouseover="this.style.borderColor='var(--blue-light)'" onmouseout="this.style.borderColor='var(--border)'">Details</button>
              </div>
            </div>
          </div>
        </div>
      `;
      wrap.appendChild(entry);
    });
  }

  function formatDate(d) {
    const date = new Date(d + 'T00:00:00');
    const today = new Date(); today.setHours(0,0,0,0);
    const diff = Math.round((today - date) / 86400000);
    if (diff === 0) return 'Today';
    if (diff === 1) return 'Yesterday';
    return date.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
  }

  function loadMore() { visibleCount += PAGE_SIZE; renderTimeline(); }

  function clearFilters() {
    ['fType','fActor','fDateFrom','fDateTo'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('fTrk').value = '';
    document.getElementById('searchInput').value = '';
    visibleCount = PAGE_SIZE;
    renderTimeline();
  }

  // ── Filter panel ──
  function toggleFilter() {
    const panel = document.getElementById('filterPanel');
    const btn   = document.getElementById('filterToggle');
    panel.classList.toggle('open');
    btn.classList.toggle('active');
  }

  // ── Export ──
  function exportLogs() {
    const rows = [['Event','Tracking ID','Document Type','Actor','Date','Time']];
    filtered.forEach(e => rows.push([e.title, e.trk, e.docType, e.actor, e.date, e.time]));
    const csv = rows.map(r => r.map(c => `"${c}"`).join(',')).join('\n');
    const a = document.createElement('a');
    a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    a.download = 'docutrack-history.csv';
    a.click();
    showToast('Logs exported as CSV');
  }

  // ── Detail modal ──
  function openDetail(id) {
    const e = allEvents.find(x => x.id === id);
    if (!e) return;
    const cfg = typeConfig[e.type];
    document.getElementById('dIcon').textContent = cfg.icon;
    document.getElementById('dIcon').style.cssText = `background:color-mix(in srgb,${cfg.color} 14%,var(--navy));border:1px solid color-mix(in srgb,${cfg.color} 25%,transparent);`;
    document.getElementById('dTitle').textContent = e.title;
    document.getElementById('dDesc').textContent = e.docType + (e.trk !== '—' ? ' · ' + e.trk : '');
    document.getElementById('dRows').innerHTML = [
      { label: 'Actor', value: e.actor },
      { label: 'Date & Time', value: e.date + ' at ' + e.time },
      ...Object.entries(e.details).map(([label, value]) => ({ label, value }))
    ].map(r => `<div class="detail-row"><span class="detail-label">${r.label}</span><span class="detail-value">${r.value}</span></div>`).join('');
    document.getElementById('detailModal').classList.add('open');
  }
  function closeModal() { document.getElementById('detailModal').classList.remove('open'); }
  function closeModalOutside(e) { if (e.target === document.getElementById('detailModal')) closeModal(); }

  // ── Toast ──
  function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = '✓ ' + msg; t.classList.add('show');
    clearTimeout(t._t); t._t = setTimeout(() => t.classList.remove('show'), 3000);
  }

  renderTimeline();
</script>
</body>
</html>
