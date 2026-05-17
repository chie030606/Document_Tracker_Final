<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$users = [];
$flashMessage = $_SESSION['flash_success'] ?? '';
if ($flashMessage !== '') {
    unset($_SESSION['flash_success']);
}
$flashError = '';
$dbPaths = [__DIR__ . '/database.php/db.php', __DIR__ . '/db.php'];
foreach ($dbPaths as $dbPath) {
    if (file_exists($dbPath)) {
        include_once $dbPath;
        break;
    }
}
if (isset($conn) && $conn instanceof mysqli) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_user') {
        $userId    = trim((string)($_POST['user_id'] ?? ''));
        $username  = trim((string)($_POST['username'] ?? ''));
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName  = trim((string)($_POST['last_name'] ?? ''));
        $email     = trim((string)($_POST['email'] ?? ''));
        $password  = $_POST['password'] ?? '';
        $role      = trim((string)($_POST['role'] ?? 'Viewer'));
        $status    = trim((string)($_POST['status'] ?? 'Active'));
        $address   = trim((string)($_POST['address'] ?? ''));
        $errors = [];

        if ($firstName === '') {
            $errors[] = 'First name is required.';
        }
        if ($lastName === '') {
            $errors[] = 'Last name is required.';
        }
        if ($username === '' || !preg_match('/^[A-Za-z0-9._-]{3,100}$/', $username)) {
            $errors[] = 'Enter a valid username (3+ characters).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if ($userId === '' && strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($userId !== '' && $password !== '' && strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (!in_array($role, ['Administrator', 'Staff', 'Viewer'], true)) {
            $role = 'Viewer';
        }
        if (!in_array($status, ['Active', 'Inactive'], true)) {
            $status = 'Active';
        }

        if (empty($errors)) {
            $duplicateSql = $userId === ''
                ? 'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1'
                : 'SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1';
            $dupStmt = mysqli_prepare($conn, $duplicateSql);
            if ($dupStmt) {
                if ($userId === '') {
                    mysqli_stmt_bind_param($dupStmt, 'ss', $username, $email);
                } else {
                    $idInt = (int)$userId;
                    mysqli_stmt_bind_param($dupStmt, 'ssi', $username, $email, $idInt);
                }
                mysqli_stmt_execute($dupStmt);
                mysqli_stmt_store_result($dupStmt);
                if (mysqli_stmt_num_rows($dupStmt) > 0) {
                    $errors[] = 'Username or email is already taken.';
                }
                mysqli_stmt_close($dupStmt);
            } else {
                $errors[] = 'Database error (duplicate check).';
            }
        }

        if (empty($errors)) {
            if ($userId === '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = mysqli_prepare(
                    $conn,
                    'INSERT INTO users (username, email, password, first_name, last_name, address, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)' 
                );
                if ($ins) {
                    mysqli_stmt_bind_param($ins, 'ssssssss', $username, $email, $hash, $firstName, $lastName, $address, $role, $status);
                }
            } else {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $ins = mysqli_prepare(
                        $conn,
                        'UPDATE users SET username = ?, email = ?, password = ?, first_name = ?, last_name = ?, address = ?, role = ?, status = ? WHERE id = ?'
                    );
                    $idInt = (int)$userId;
                    if ($ins) {
                        mysqli_stmt_bind_param($ins, 'ssssssssi', $username, $email, $hash, $firstName, $lastName, $address, $role, $status, $idInt);
                    }
                } else {
                    $ins = mysqli_prepare(
                        $conn,
                        'UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, address = ?, role = ?, status = ? WHERE id = ?'
                    );
                    $idInt = (int)$userId;
                    if ($ins) {
                        mysqli_stmt_bind_param($ins, 'sssssssi', $username, $email, $firstName, $lastName, $address, $role, $status, $idInt);
                    }
                }
            }

            if (!isset($ins) || !$ins) {
                $errors[] = 'Database error (prepare save).';
            } else {
                if (mysqli_stmt_execute($ins)) {
                    mysqli_stmt_close($ins);
                    $_SESSION['flash_success'] = $userId === '' ? 'New user created successfully.' : 'User updated successfully.';
                    header('Location: User Management.php');
                    exit;
                }
                mysqli_stmt_close($ins);
                $errors[] = 'Failed to save user.';
            }
        }

        if (!empty($errors)) {
            $flashError = implode(' ', $errors);
        }
    }

    $ensureColumn = function (string $column, string $definition) use ($conn) {
        $columnEscaped = mysqli_real_escape_string($conn, $column);
        $check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE '{$columnEscaped}'");
        if ($check && mysqli_num_rows($check) === 0) {
            mysqli_query($conn, "ALTER TABLE users ADD COLUMN {$column} {$definition}");
        }
    };
    $ensureColumn('role', "VARCHAR(16) NOT NULL DEFAULT 'Viewer'");
    $ensureColumn('status', "VARCHAR(16) NOT NULL DEFAULT 'Active'");

    $result = mysqli_query($conn, "SELECT id, username, email, first_name, last_name, address, role, status FROM users ORDER BY id ASC");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
        mysqli_free_result($result);
    }

    $hasAdministrator = false;
    foreach ($users as $user) {
        if (isset($user['role']) && strtolower($user['role']) === 'administrator') {
            $hasAdministrator = true;
            break;
        }
    }
    if (!$hasAdministrator && !empty($users)) {
        $firstId = (int)$users[0]['id'];
        mysqli_query($conn, "UPDATE users SET role = 'Administrator' WHERE id = {$firstId}");
        $users[0]['role'] = 'Administrator';
    }
}
$usersForJs = array_map(function ($user) {
    $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    if ($name === '') {
        $name = $user['username'] ?? '';
    }
    $role = $user['role'] ?? 'Viewer';
    $status = $user['status'] ?? 'Active';
    if ($role === '') {
        $role = 'Viewer';
    }
    if ($status === '') {
        $status = 'Active';
    }
    return [
        'id' => (int)($user['id'] ?? 0),
        'username' => $user['username'] ?? '',
        'name' => $name,
        'email' => $user['email'] ?? '',
        'role' => in_array($role, ['Administrator', 'Staff', 'Viewer'], true) ? $role : 'Viewer',
        'status' => in_array($status, ['Active', 'Inactive'], true) ? $status : 'Active',
        'requests' => 0,
        'dept' => $user['address'] ?? '',
    ];
}, $users);
$usersJson = json_encode($usersForJs, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DocuTrack — User Management</title>
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
    color: var(--text-muted); font-size: 16px; transition: border-color 0.2s, color 0.2s;
  }
  .bell-btn:hover { border-color: var(--blue); color: var(--blue-light); }
  .bell-dot { position: absolute; top: 6px; right: 7px; width: 8px; height: 8px; border-radius: 50%; background: var(--red); border: 2px solid var(--navy); }
  .user-chip {
    display: flex; align-items: center; gap: 10px;
    padding: 6px 14px 6px 6px;
    background: var(--card-bg); border: 1px solid var(--border);
    border-radius: 40px; cursor: pointer; transition: border-color 0.2s;
  }
  .user-chip:hover { border-color: var(--blue); }
  .avatar {
    width: 30px; height: 30px; border-radius: 50%;
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
    transition: opacity 0.2s, transform 0.15s; box-shadow: 0 4px 16px rgba(37,99,235,0.3);
  }
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
    padding: 11px 14px; border-radius: 12px;
    cursor: pointer; color: var(--text-muted);
    font-size: 14px; font-weight: 400;
    transition: background 0.2s, color 0.2s;
    text-decoration: none; border: 1px solid transparent;
  }
  .nav-item:hover { background: rgba(37,99,235,0.08); color: var(--text); }
  .nav-item.active { background: linear-gradient(135deg, rgba(37,99,235,0.22), rgba(37,99,235,0.08)); color: var(--blue-light); border-color: rgba(37,99,235,0.25); font-weight: 500; }
  .nav-icon { font-size: 16px; width: 20px; text-align: center; }

  /* ── Main ── */
  .main { margin-left: var(--sidebar-w); flex: 1; padding: 32px 36px 60px; display: flex; flex-direction: column; gap: 24px; }
  @keyframes fadeUp { from { transform: translateY(14px); opacity: 0; } to { transform: none; opacity: 1; } }

  /* ── Page header ── */
  .page-header-row {
    display: flex; align-items: flex-end; justify-content: space-between;
    animation: fadeUp 0.5s 0.15s ease both;
  }
  .page-header h2 { font-family: 'Syne', sans-serif; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
  .page-header p { font-size: 13.5px; color: var(--text-muted); margin-top: 4px; }
  .add-user-btn {
    display: flex; align-items: center; gap: 8px;
    padding: 11px 22px; border-radius: 12px;
    background: linear-gradient(135deg, var(--blue), #1d4ed8); border: none;
    color: #fff; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500;
    cursor: pointer; box-shadow: 0 6px 24px rgba(37,99,235,0.35);
    transition: opacity 0.2s, transform 0.15s; white-space: nowrap;
  }
  .add-user-btn:hover { opacity: 0.9; transform: translateY(-1px); }

  /* ── Stat cards ── */
  .stats-row {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
    animation: fadeUp 0.5s 0.25s ease both;
  }
  .stat-card {
    background: var(--card-bg); border: 1px solid var(--border);
    border-radius: 18px; padding: 22px 24px;
    display: flex; align-items: center; justify-content: space-between;
    transition: transform 0.2s, border-color 0.2s; cursor: default;
    position: relative; overflow: hidden;
  }
  .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: var(--accent); opacity: 0.8; }
  .stat-card:hover { transform: translateY(-2px); border-color: rgba(99,140,200,0.22); }
  .stat-label { font-size: 12.5px; color: var(--text-muted); margin-bottom: 8px; }
  .stat-num { font-family: 'Syne', sans-serif; font-size: 38px; font-weight: 800; letter-spacing: -1px; background: linear-gradient(135deg, #fff, var(--text-muted)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
  .stat-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; background: color-mix(in srgb, var(--accent) 15%, transparent); }

  /* ── Toolbar ── */
  .toolbar {
    display: flex; gap: 12px; align-items: center; flex-wrap: wrap;
    animation: fadeUp 0.5s 0.3s ease both;
  }
  .search-box {
    flex: 1; min-width: 220px;
    display: flex; align-items: center; gap: 10px;
    background: var(--card-bg); border: 1px solid var(--border);
    border-radius: 12px; padding: 11px 16px;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .search-box:focus-within { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
  .search-box span { font-size: 15px; color: var(--text-muted); }
  .search-box input { flex: 1; background: transparent; border: none; outline: none; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 14px; }
  .search-box input::placeholder { color: var(--text-muted); }
  .filter-select {
    background: var(--card-bg); border: 1px solid var(--border);
    border-radius: 12px; padding: 11px 16px;
    color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 14px;
    cursor: pointer; outline: none; transition: border-color 0.2s;
    min-width: 140px;
  }
  .filter-select:focus { border-color: var(--blue); }
  .filter-select option { background: var(--navy-mid); }

  /* ── Table card ── */
  .table-card {
    background: var(--card-bg); border: 1px solid var(--border);
    border-radius: 18px; overflow: hidden;
    animation: fadeUp 0.5s 0.38s ease both;
  }
  .table-header {
    padding: 20px 24px; display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--border);
  }
  .table-title {
    font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700;
    display: flex; align-items: center; gap: 10px;
  }
  .table-title::before { content: ''; display: block; width: 3px; height: 16px; background: var(--blue); border-radius: 2px; }
  .record-badge {
    background: rgba(37,99,235,0.15); color: var(--blue-light);
    font-size: 11px; font-weight: 600; padding: 2px 9px; border-radius: 20px;
  }

  table { width: 100%; border-collapse: collapse; }
  thead tr { border-bottom: 1px solid var(--border); }
  th {
    padding: 12px 20px; text-align: left;
    font-size: 10.5px; font-weight: 500; letter-spacing: 0.8px;
    color: var(--text-muted); text-transform: uppercase; white-space: nowrap;
  }
  tbody tr { border-bottom: 1px solid rgba(99,140,200,0.06); transition: background 0.15s; }
  tbody tr:last-child { border: none; }
  tbody tr:hover { background: rgba(37,99,235,0.05); }
  td { padding: 16px 20px; font-size: 13.5px; vertical-align: middle; }

  /* User cell */
  .user-cell { display: flex; align-items: center; gap: 12px; }
  .user-avatar {
    width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 700; color: #fff;
  }
  .user-name { font-weight: 500; font-size: 14px; }
  .user-handle { font-size: 11.5px; color: var(--text-muted); }

  .email-cell { color: var(--text-muted); font-size: 13px; }

  /* Role badge */
  .role-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;
  }
  .role-admin { background: rgba(139,92,246,0.15); color: #a78bfa; }
  .role-staff  { background: rgba(37,99,235,0.15); color: var(--blue-light); }
  .role-viewer { background: rgba(245,158,11,0.15); color: var(--amber); }

  /* Status badge */
  .status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 11px; border-radius: 20px; font-size: 12px; font-weight: 500;
  }
  .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
  .status-active   { background: rgba(16,185,129,0.15); color: var(--green); }
  .status-inactive { background: rgba(239,68,68,0.12);  color: #f87171; }

  .requests-num { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 14px; }

  /* Action buttons */
  .action-group { display: flex; align-items: center; gap: 6px; }
  .icon-btn {
    width: 32px; height: 32px; border-radius: 8px;
    background: transparent; border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; cursor: pointer; transition: border-color 0.2s, background 0.2s;
  }
  .icon-btn:hover { border-color: rgba(99,140,200,0.3); background: rgba(99,140,200,0.08); }
  .icon-btn.edit:hover { border-color: var(--blue-light); background: rgba(37,99,235,0.1); }
  .icon-btn.delete:hover { border-color: var(--red); background: rgba(239,68,68,0.08); }
  .icon-btn.more:hover { border-color: rgba(99,140,200,0.3); }

  /* Empty state */
  .empty-state { display: none; flex-direction: column; align-items: center; gap: 12px; padding: 64px 24px; text-align: center; }
  .empty-state.show { display: flex; }
  .empty-icon { font-size: 44px; opacity: 0.3; }
  .empty-state h3 { font-family: 'Syne', sans-serif; font-size: 16px; }
  .empty-state p { font-size: 13px; color: var(--text-muted); }

  /* ── Modal ── */
  .modal-overlay {
    position: fixed; inset: 0; z-index: 200;
    background: rgba(10,18,32,0.78); backdrop-filter: blur(10px);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; pointer-events: none; transition: opacity 0.25s;
  }
  .modal-overlay.open { opacity: 1; pointer-events: all; }
  .modal {
    background: var(--navy-mid); border: 1px solid var(--border);
    border-radius: 20px; padding: 30px 32px; width: 520px; max-width: 95vw;
    transform: translateY(20px); transition: transform 0.25s;
    box-shadow: 0 28px 80px rgba(0,0,0,0.5);
  }
  .modal-overlay.open .modal { transform: none; }
  .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 26px; }
  .modal-title { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 800; }
  .modal-close {
    background: transparent; border: 1px solid var(--border);
    border-radius: 8px; width: 32px; height: 32px;
    color: var(--text-muted); cursor: pointer; font-size: 15px;
    display: flex; align-items: center; justify-content: center;
    transition: border-color 0.2s, color 0.2s;
  }
  .modal-close:hover { border-color: var(--red); color: var(--red); }
  .modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .modal-field { display: flex; flex-direction: column; gap: 7px; }
  .modal-field.full { grid-column: 1 / -1; }
  .modal-field label { font-size: 11.5px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500; }
  .modal-field input, .modal-field select {
    background: rgba(15,27,45,0.7); border: 1px solid var(--border);
    border-radius: 10px; padding: 11px 14px;
    color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 14px;
    outline: none; transition: border-color 0.2s, box-shadow 0.2s;
  }
  .modal-field input:focus, .modal-field select:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
  .modal-field input::placeholder { color: rgba(122,146,176,0.5); }
  .modal-field select option { background: var(--navy-mid); }
  .modal-field input.error { border-color: var(--red); }
  .field-err { font-size: 11px; color: var(--red); display: none; }
  .field-err.show { display: block; }
  .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; }
  .btn-ghost { padding: 10px 20px; border-radius: 10px; background: transparent; border: 1px solid var(--border); color: var(--text-muted); font-family: 'DM Sans', sans-serif; font-size: 14px; cursor: pointer; transition: border-color 0.2s; }
  .btn-ghost:hover { border-color: var(--text-muted); color: var(--text); }
  .btn-primary { padding: 10px 24px; border-radius: 10px; background: linear-gradient(135deg, var(--blue), #1d4ed8); border: none; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500; cursor: pointer; transition: opacity 0.2s; }
  .btn-primary:hover { opacity: 0.88; }

  /* ── Confirm modal ── */
  .confirm-modal { max-width: 400px; text-align: center; }
  .confirm-icon { font-size: 44px; margin-bottom: 14px; }
  .confirm-title { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 800; margin-bottom: 10px; }
  .confirm-body { font-size: 13.5px; color: var(--text-muted); line-height: 1.6; margin-bottom: 24px; }
  .confirm-actions { display: flex; gap: 12px; justify-content: center; }
  .btn-danger { padding: 10px 24px; border-radius: 10px; background: var(--red); border: none; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500; cursor: pointer; transition: opacity 0.2s; }
  .btn-danger:hover { opacity: 0.88; }

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
  .toast.error { border-color: var(--red); color: #f87171; }

  ::-webkit-scrollbar { width: 5px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: var(--navy-light); border-radius: 4px; }
</style>
</head>
<body>
<?php if ($flashMessage !== '' || $flashError !== ''): ?>
  <div class="toast <?php echo $flashError !== '' ? 'error show' : 'show'; ?>" id="serverToast">
    <?php echo htmlspecialchars($flashError !== '' ? $flashError : $flashMessage, ENT_QUOTES, 'UTF-8'); ?>
  </div>
<?php endif; ?>

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

    <!-- Header -->
    <div class="page-header-row">
      <div class="page-header">
        <h2>User Management</h2>
        <p>Manage system users and their permissions</p>
      </div>
      <button class="add-user-btn" onclick="openAddModal()">👤+ &nbsp;Add New User</button>
    </div>

    <!-- Stat cards -->
    <div class="stats-row">
      <div class="stat-card" style="--accent:#3b82f6">
        <div>
          <div class="stat-label">Total Users</div>
          <div class="stat-num" id="statTotal">0</div>
        </div>
        <div class="stat-icon" style="--accent:#3b82f6">👥</div>
      </div>
      <div class="stat-card" style="--accent:#10b981">
        <div>
          <div class="stat-label">Active Users</div>
          <div class="stat-num" id="statActive">0</div>
        </div>
        <div class="stat-icon" style="--accent:#10b981">✅</div>
      </div>
      <div class="stat-card" style="--accent:#8b5cf6">
        <div>
          <div class="stat-label">Administrators</div>
          <div class="stat-num" id="statAdmins">0</div>
        </div>
        <div class="stat-icon" style="--accent:#8b5cf6">🛡️</div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="search-box">
        <span>🔍</span>
        <input type="text" id="searchInput" placeholder="Search by name or email..." oninput="renderTable()">
      </div>
      <select class="filter-select" id="roleFilter" onchange="renderTable()">
        <option value="">All Roles</option>
        <option value="Administrator">Administrator</option>
        <option value="Staff">Staff</option>
        <option value="Viewer">Viewer</option>
      </select>
      <select class="filter-select" id="statusFilter" onchange="renderTable()">
        <option value="">All Statuses</option>
        <option value="Active">Active</option>
        <option value="Inactive">Inactive</option>
      </select>
    </div>

    <!-- Table -->
    <div class="table-card">
      <div class="table-header">
        <div class="table-title">All Users <span class="record-badge" id="recordBadge">0</span></div>
      </div>
      <table>
        <thead>
          <tr>
            <th>User</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Requests</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="tableBody"></tbody>
      </table>
      <div class="empty-state" id="emptyState">
        <div class="empty-icon">👥</div>
        <h3>No users found</h3>
        <p>Try adjusting your search or filters.</p>
      </div>
    </div>

  </main>
</div>

<!-- ── ADD / EDIT MODAL ── -->
<div class="modal-overlay" id="userModal" onclick="closeModalOutside(event,'userModal')">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modalTitle">Add New User</div>
      <button class="modal-close" onclick="closeModal('userModal')">✕</button>
    </div>
    <form id="userForm" method="post" novalidate>
      <input type="hidden" id="mUserId" name="user_id" value="">
      <input type="hidden" name="action" value="save_user">
      <div class="modal-grid">
        <div class="modal-field">
          <label>First Name <span style="color:var(--blue-light)">*</span></label>
          <input type="text" id="mFirstName" name="first_name" placeholder="e.g. Maria">
          <span class="field-err" id="err-fname">Required.</span>
        </div>
        <div class="modal-field">
          <label>Last Name <span style="color:var(--blue-light)">*</span></label>
          <input type="text" id="mLastName" name="last_name" placeholder="e.g. Santisima">
          <span class="field-err" id="err-lname">Required.</span>
        </div>
        <div class="modal-field full">
          <label>Username <span style="color:var(--blue-light)">*</span></label>
          <input type="text" id="mUsername" name="username" placeholder="e.g. maria.santisima">
          <span class="field-err" id="err-username">Required.</span>
        </div>
        <div class="modal-field full">
          <label>Email Address <span style="color:var(--blue-light)">*</span></label>
          <input type="email" id="mEmail" name="email" placeholder="email@docutrack.com">
          <span class="field-err" id="err-email">Enter a valid email.</span>
        </div>
        <div class="modal-field full">
          <label>Password <span style="color:var(--blue-light)">*</span></label>
          <input type="password" id="mPassword" name="password" placeholder="Enter a strong password">
          <span class="field-err" id="err-password">Password must be at least 8 chars.</span>
        </div>
        <div class="modal-field">
          <label>Role <span style="color:var(--blue-light)">*</span></label>
          <select id="mRole" name="role">
            <option value="">Select role</option>
            <option value="Administrator">Administrator</option>
            <option value="Staff">Staff</option>
            <option value="Viewer">Viewer</option>
          </select>
          <span class="field-err" id="err-role">Required.</span>
        </div>
        <div class="modal-field">
          <label>Status</label>
          <select id="mStatus" name="status">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
        <div class="modal-field full">
          <label>Department</label>
          <input type="text" id="mDept" name="address" placeholder="e.g. Civil Registry">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeModal('userModal')">Cancel</button>
        <button type="button" class="btn-primary" onclick="saveUser()">Save User</button>
      </div>
    </form>
  </div>
</div>

<!-- ── CONFIRM DELETE MODAL ── -->
<div class="modal-overlay" id="confirmModal" onclick="closeModalOutside(event,'confirmModal')">
  <div class="modal confirm-modal">
    <div class="confirm-icon">🗑️</div>
    <div class="confirm-title">Delete User?</div>
    <div class="confirm-body" id="confirmBody">This action cannot be undone.</div>
    <div class="confirm-actions">
      <button class="btn-ghost" onclick="closeModal('confirmModal')">Cancel</button>
      <button class="btn-danger" onclick="confirmDelete()">Delete User</button>
    </div>
  </div>
</div>

<!-- ── TOAST ── -->
<div class="toast" id="toast"></div>

<script>
  // ── Avatar colors per role ──
  const roleColors = { Administrator: 'linear-gradient(135deg,#8b5cf6,#6d28d9)', Staff: 'linear-gradient(135deg,#2563eb,#1d4ed8)', Viewer: 'linear-gradient(135deg,#f59e0b,#d97706)' };
  const roleClass  = { Administrator: 'role-admin', Staff: 'role-staff', Viewer: 'role-viewer' };

  function initials(name) { return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0,2); }

  // ── Dataset ──
  let users = <?php echo $usersJson ?: '[]'; ?>;
  let nextId = <?php echo count($usersForJs) + 1; ?>;
  let editingId = null;
  let deletingId = null;

  function updateStats() {
    document.getElementById('statTotal').textContent  = users.length;
    document.getElementById('statActive').textContent = users.filter(u => u.status === 'Active').length;
    document.getElementById('statAdmins').textContent = users.filter(u => u.role === 'Administrator').length;
  }

  function renderTable() {
    const q      = document.getElementById('searchInput').value.toLowerCase();
    const role   = document.getElementById('roleFilter').value;
    const status = document.getElementById('statusFilter').value;

    const filtered = users.filter(u => {
      const matchQ = !q || u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q);
      const matchR = !role   || u.role === role;
      const matchS = !status || u.status === status;
      return matchQ && matchR && matchS;
    });

    document.getElementById('recordBadge').textContent = filtered.length;
    document.getElementById('emptyState').classList.toggle('show', filtered.length === 0);

    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';
    filtered.forEach((u, i) => {
      const tr = document.createElement('tr');
      tr.style.animationDelay = `${i * 0.04}s`;
      tr.innerHTML = `
        <td>
          <div class="user-cell">
            <div class="user-avatar" style="background:${roleColors[u.role]}">${initials(u.name)}</div>
            <div>
              <div class="user-name">${u.name}</div>
              <div class="user-handle">${u.dept || '—'}</div>
            </div>
          </div>
        </td>
        <td class="email-cell">${u.email}</td>
        <td><span class="role-badge ${roleClass[u.role]}">${u.role}</span></td>
        <td><span class="status-badge ${u.status === 'Active' ? 'status-active' : 'status-inactive'}">${u.status}</span></td>
        <td><span class="requests-num">${u.requests}</span></td>
        <td>
          <div class="action-group">
            <button class="icon-btn edit" title="Edit" onclick="openEditModal(${u.id})">✏️</button>
            <button class="icon-btn delete" title="Delete" onclick="openConfirmDelete(${u.id})">🗑️</button>
            <button class="icon-btn more" title="Toggle Status" onclick="toggleStatus(${u.id})">${u.status === 'Active' ? '⏸' : '▶'}</button>
          </div>
        </td>
      `;
      tbody.appendChild(tr);
    });
    updateStats();
  }

  // ── Toggle status ──
  function toggleStatus(id) {
    const u = users.find(x => x.id === id);
    if (!u) return;
    u.status = u.status === 'Active' ? 'Inactive' : 'Active';
    renderTable();
    showToast(`${u.name} set to ${u.status}`);
  }

  // ── Add / Edit Modal ──
  function openAddModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Add New User';
    ['mFirstName','mLastName','mUsername','mEmail','mPassword','mDept'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('mRole').value   = '';
    document.getElementById('mStatus').value = 'Active';
    document.getElementById('mUserId').value = '';
    clearErrors();
    document.getElementById('userModal').classList.add('open');
  }

  function openEditModal(id) {
    const u = users.find(x => x.id === id);
    if (!u) return;
    editingId = id;
    const parts = u.name.split(' ');
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('mFirstName').value = parts[0] || '';
    document.getElementById('mLastName').value  = parts.slice(1).join(' ') || '';
    document.getElementById('mUsername').value = u.username || '';
    document.getElementById('mEmail').value  = u.email;
    document.getElementById('mRole').value   = u.role;
    document.getElementById('mStatus').value = u.status;
    document.getElementById('mDept').value   = u.dept || '';
    document.getElementById('mUserId').value = id;
    document.getElementById('mPassword').value = '';
    clearErrors();
    document.getElementById('userModal').classList.add('open');
  }

  function clearErrors() {
    document.querySelectorAll('.field-err').forEach(e => e.classList.remove('show'));
    document.querySelectorAll('.modal-field input.error, .modal-field select.error').forEach(e => e.classList.remove('error'));
  }

  function saveUser() {
    const fname  = document.getElementById('mFirstName').value.trim();
    const lname  = document.getElementById('mLastName').value.trim();
    const username = document.getElementById('mUsername').value.trim();
    const email  = document.getElementById('mEmail').value.trim();
    const password = document.getElementById('mPassword').value;
    const role   = document.getElementById('mRole').value;
    const status = document.getElementById('mStatus').value;

    let valid = true;
    const set = (field, errId, ok) => {
      document.getElementById(field).classList.toggle('error', !ok);
      document.getElementById(errId).classList.toggle('show', !ok);
      if (!ok) valid = false;
    };
    set('mFirstName', 'err-fname', fname.length > 0);
    set('mLastName',  'err-lname', lname.length > 0);
    set('mUsername', 'err-username', username.length >= 3);
    set('mEmail',     'err-email', /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email));
    set('mRole',      'err-role',  role !== '');
    if (!editingId && password.length < 8) {
      set('mPassword', 'err-password', false);
    } else {
      set('mPassword', 'err-password', password.length === 0 || password.length >= 8);
    }
    if (!valid) return;

    document.getElementById('mUserId').value = editingId || '';
    document.getElementById('userForm').submit();
  }

  // ── Delete ──
  function openConfirmDelete(id) {
    deletingId = id;
    const u = users.find(x => x.id === id);
    document.getElementById('confirmBody').textContent = `Are you sure you want to delete "${u?.name}"? This action cannot be undone.`;
    document.getElementById('confirmModal').classList.add('open');
  }
  function confirmDelete() {
    if (!deletingId) return;
    const u = users.find(x => x.id === deletingId);
    users = users.filter(x => x.id !== deletingId);
    deletingId = null;
    closeModal('confirmModal');
    renderTable();
    showToast(`${u?.name} deleted`);
  }

  // ── Modal helpers ──
  function closeModal(id) { document.getElementById(id).classList.remove('open'); }
  function closeModalOutside(e, id) { if (e.target === document.getElementById(id)) closeModal(id); }

  // ── Toast ──
  function showToast(msg, type='') {
    const t = document.getElementById('toast');
    t.textContent = (type === 'error' ? '⚠ ' : '✓ ') + msg;
    t.className = `toast ${type} show`;
    clearTimeout(t._t);
    t._t = setTimeout(() => t.classList.remove('show'), 3200);
  }

  // ── Init ──
  renderTable();
</script>
</body>
</html>
