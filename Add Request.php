<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DocuTrack — Add Request</title>
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
    flex: 1; padding: 32px 40px 60px;
    display: flex; flex-direction: column; gap: 24px;
    max-width: 960px;
  }

  @keyframes fadeUp { from { transform: translateY(16px); opacity: 0; } to { transform: none; opacity: 1; } }

  .page-header { animation: fadeUp 0.5s 0.15s ease both; }
  .page-header h2 { font-family: 'Syne', sans-serif; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
  .page-header p { font-size: 13.5px; color: var(--text-muted); margin-top: 4px; }

  /* ── Section card ── */
  .section-card {
    border-radius: 18px; overflow: hidden;
    border: 1px solid var(--border);
    animation: fadeUp 0.5s ease both;
  }

  .section-header {
    padding: 18px 26px;
    background: linear-gradient(135deg, var(--blue) 0%, #1d4ed8 100%);
    display: flex; align-items: center; gap: 10px;
  }
  .section-header h3 {
    font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700;
    color: #fff; letter-spacing: 0.2px;
  }
  .section-header-icon { font-size: 17px; }

  .section-body {
    background: var(--card-bg);
    padding: 28px 26px;
    display: flex; flex-direction: column; gap: 20px;
  }

  /* ── Form fields ── */
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
  .form-row.single { grid-template-columns: 1fr; }

  .field { display: flex; flex-direction: column; gap: 7px; }

  .field label {
    font-size: 12px; font-weight: 500; color: var(--text-muted);
    letter-spacing: 0.3px;
    display: flex; align-items: center; gap: 5px;
  }
  .field label .req { color: var(--blue-light); font-size: 13px; }

  .field input,
  .field select,
  .field textarea {
    background: rgba(15,27,45,0.7);
    border: 1px solid var(--border);
    border-radius: 11px; padding: 12px 15px;
    color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 14px;
    outline: none; transition: border-color 0.2s, box-shadow 0.2s;
    width: 100%;
  }
  .field input::placeholder,
  .field textarea::placeholder { color: rgba(122,146,176,0.55); }
  .field input:focus,
  .field select:focus,
  .field textarea:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
  }
  .field input.error,
  .field select.error,
  .field textarea.error {
    border-color: var(--red);
    box-shadow: 0 0 0 3px rgba(239,68,68,0.10);
  }
  .field select { cursor: pointer; }
  .field select option { background: var(--navy-mid); }
  .field textarea { resize: vertical; min-height: 100px; }

  .field-hint { font-size: 11px; color: var(--text-muted); margin-top: -3px; }
  .field-error { font-size: 11.5px; color: var(--red); margin-top: -3px; display: none; }
  .field-error.show { display: block; }

  /* Phone prefix row */
  .phone-wrap { display: flex; gap: 0; }
  .phone-prefix {
    background: rgba(15,27,45,0.9);
    border: 1px solid var(--border); border-right: none;
    border-radius: 11px 0 0 11px; padding: 12px 14px;
    font-size: 14px; color: var(--text-muted);
    white-space: nowrap; display: flex; align-items: center;
  }
  .phone-wrap input {
    border-radius: 0 11px 11px 0 !important;
    flex: 1;
  }

  /* ── Upload zone ── */
  .upload-zone {
    border: 2px dashed rgba(99,140,200,0.25);
    border-radius: 14px; padding: 40px 24px;
    display: flex; flex-direction: column; align-items: center; gap: 12px;
    cursor: pointer; transition: border-color 0.25s, background 0.25s;
    text-align: center; position: relative;
  }
  .upload-zone:hover,
  .upload-zone.dragover {
    border-color: var(--blue);
    background: rgba(37,99,235,0.06);
  }
  .upload-zone input[type="file"] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
  }
  .upload-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(37,99,235,0.12);
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
  }
  .upload-title { font-size: 14px; font-weight: 500; color: var(--text); }
  .upload-sub { font-size: 12px; color: var(--text-muted); }

  .file-list { display: flex; flex-direction: column; gap: 8px; margin-top: 4px; }
  .file-item {
    display: flex; align-items: center; gap: 10px;
    background: rgba(37,99,235,0.08); border: 1px solid rgba(37,99,235,0.2);
    border-radius: 10px; padding: 10px 14px;
    animation: fadeUp 0.3s ease both;
  }
  .file-icon { font-size: 18px; }
  .file-info { flex: 1; }
  .file-name { font-size: 13px; font-weight: 500; color: var(--text); }
  .file-size { font-size: 11px; color: var(--text-muted); }
  .file-remove {
    background: transparent; border: 1px solid rgba(239,68,68,0.3);
    border-radius: 6px; padding: 4px 8px; color: var(--red);
    font-size: 12px; cursor: pointer; transition: background 0.15s;
  }
  .file-remove:hover { background: rgba(239,68,68,0.1); }

  /* ── Tracking ID ── */
  .tracking-row { display: flex; gap: 12px; align-items: stretch; }
  .tracking-input {
    flex: 1; background: rgba(15,27,45,0.7);
    border: 1px solid var(--border); border-radius: 11px; padding: 12px 16px;
    color: var(--text-muted); font-family: 'Syne', sans-serif; font-size: 14px;
    font-style: italic; outline: none; cursor: default;
    transition: border-color 0.2s;
  }
  .tracking-input.generated {
    color: var(--blue-light); font-style: normal; font-weight: 700;
    border-color: rgba(37,99,235,0.3);
    background: rgba(37,99,235,0.06);
    letter-spacing: 0.5px;
  }
  .generate-btn {
    padding: 12px 22px; border-radius: 11px;
    background: var(--card-bg); border: 1px solid var(--border);
    color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 500;
    cursor: pointer; white-space: nowrap;
    transition: border-color 0.2s, background 0.2s, color 0.2s;
  }
  .generate-btn:hover { border-color: var(--blue); color: var(--blue-light); background: rgba(37,99,235,0.08); }

  /* ── Action bar ── */
  .action-bar {
    display: flex; gap: 14px;
    animation: fadeUp 0.5s 0.55s ease both;
  }

  .btn-cancel {
    flex: 1; padding: 14px;
    border-radius: 13px; border: 1px solid var(--border);
    background: var(--card-bg); color: var(--text-muted);
    font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 500;
    cursor: pointer; transition: border-color 0.2s, color 0.2s;
  }
  .btn-cancel:hover { border-color: var(--text-muted); color: var(--text); }

  .btn-submit {
    flex: 2; padding: 14px;
    border-radius: 13px; border: none;
    background: linear-gradient(135deg, var(--blue), #1d4ed8);
    color: #fff; font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 600;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
    box-shadow: 0 6px 24px rgba(37,99,235,0.35);
    transition: opacity 0.2s, transform 0.15s;
  }
  .btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }
  .btn-submit:active { transform: none; }

  /* ── Success overlay ── */
  .success-overlay {
    position: fixed; inset: 0; z-index: 300;
    background: rgba(10,18,32,0.82); backdrop-filter: blur(10px);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; pointer-events: none; transition: opacity 0.3s;
  }
  .success-overlay.open { opacity: 1; pointer-events: all; }

  .success-card {
    background: var(--navy-mid); border: 1px solid var(--border);
    border-radius: 22px; padding: 44px 40px; text-align: center;
    width: 420px; max-width: 92vw;
    transform: translateY(24px) scale(0.97); transition: transform 0.3s;
    box-shadow: 0 30px 90px rgba(0,0,0,0.5);
  }
  .success-overlay.open .success-card { transform: none; }

  .success-icon-wrap {
    width: 72px; height: 72px; border-radius: 50%;
    background: rgba(16,185,129,0.12); border: 2px solid rgba(16,185,129,0.3);
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; margin: 0 auto 20px;
    animation: popIn 0.5s 0.1s ease both;
  }
  @keyframes popIn { from { transform: scale(0.5); opacity: 0; } to { transform: none; opacity: 1; } }

  .success-title { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 800; margin-bottom: 8px; }
  .success-sub { font-size: 13.5px; color: var(--text-muted); line-height: 1.6; margin-bottom: 10px; }
  .success-id {
    display: inline-block; margin: 8px auto 24px;
    background: rgba(37,99,235,0.12); border: 1px solid rgba(37,99,235,0.25);
    border-radius: 10px; padding: 10px 22px;
    font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 800;
    color: var(--blue-light); letter-spacing: 1px;
  }
  .success-actions { display: flex; gap: 12px; justify-content: center; }
  .success-btn-secondary {
    padding: 10px 22px; border-radius: 10px;
    background: transparent; border: 1px solid var(--border);
    color: var(--text-muted); font-family: 'DM Sans', sans-serif; font-size: 14px;
    cursor: pointer; transition: border-color 0.2s;
  }
  .success-btn-secondary:hover { border-color: var(--text-muted); color: var(--text); }
  .success-btn-primary {
    padding: 10px 26px; border-radius: 10px;
    background: linear-gradient(135deg, var(--blue), #1d4ed8); border: none;
    color: #fff; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500;
    cursor: pointer;
  }

  /* ── Toast ── */
  .toast {
    position: fixed; bottom: 28px; right: 28px; z-index: 400;
    background: var(--navy-mid); border: 1px solid;
    border-radius: 12px; padding: 14px 20px;
    display: flex; align-items: center; gap: 10px;
    font-size: 13.5px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    transform: translateY(80px); opacity: 0;
    transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s;
    pointer-events: none; max-width: 340px;
  }
  .toast.show { transform: none; opacity: 1; }
  .toast.success { border-color: var(--green); color: var(--green); }
  .toast.error   { border-color: var(--red);   color: var(--red); }

  ::-webkit-scrollbar { width: 5px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: var(--navy-light); border-radius: 4px; }
</style>
</head>
<body>

<?php
// Server-side handler for AJAX form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    // include database connection
    include __DIR__ . '/database.php/db.php';

    // Basic required fields
    $fullName = $_POST['fullName'] ?? '';
    $docType  = $_POST['docType'] ?? '';
    $subDate  = $_POST['subDate'] ?? '';
    $phone    = $_POST['phone'] ?? '';
    $email    = $_POST['email'] ?? '';
    $purpose  = $_POST['purpose'] ?? '';
    $priority = $_POST['priority'] ?? 'Normal';
    $notes    = $_POST['notes'] ?? '';
    $tracking = $_POST['trackingID'] ?? '';

    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit;
    }

    // Basic server-side validation
    if (trim($fullName) === '' || trim($docType) === '' || trim($subDate) === '' || trim($phone) === '' || trim($email) === '') {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    // Create tables if they do not exist
    $sql1 = "CREATE TABLE IF NOT EXISTS requests (
      id INT AUTO_INCREMENT PRIMARY KEY,
      tracking_id VARCHAR(64) UNIQUE,
      fullname VARCHAR(255),
      document_type VARCHAR(128),
      submission_date DATE,
      phone VARCHAR(32),
      email VARCHAR(255),
      purpose VARCHAR(128),
      priority VARCHAR(64),
      status VARCHAR(32) NOT NULL DEFAULT 'Pending',
      progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
      remarks TEXT,
      notes TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $sql2 = "CREATE TABLE IF NOT EXISTS documents (
      id INT AUTO_INCREMENT PRIMARY KEY,
      request_id INT,
      file_name VARCHAR(255),
      file_path VARCHAR(255),
      file_size INT,
      uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    mysqli_query($conn, $sql1);
    mysqli_query($conn, $sql2);

    // Begin transaction
    mysqli_begin_transaction($conn);
    try {
        // Ensure tracking ID exists
        if (empty($tracking)) {
            $tracking = 'TRK-' . rand(100,999) . '-' . strtoupper(substr(bin2hex(random_bytes(2)),0,3));
        }

        // Insert request
        $stmt = mysqli_prepare($conn, "INSERT INTO requests (tracking_id, fullname, document_type, submission_date, phone, email, purpose, priority, status, notes) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $defaultStatus = 'Pending';
        mysqli_stmt_bind_param($stmt, 'ssssssssss', $tracking, $fullName, $docType, $subDate, $phone, $email, $purpose, $priority, $defaultStatus, $notes);
        mysqli_stmt_execute($stmt);
        $request_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Handle file uploads
        $uploadDir = __DIR__ . '/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!empty($_FILES['files'])) {
            $files = $_FILES['files'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                $origName = basename($files['name'][$i]);
                $tmpName  = $files['tmp_name'][$i];
                $ext      = pathinfo($origName, PATHINFO_EXTENSION);
                $safeName = time() . '_' . bin2hex(random_bytes(4)) . ($ext ? '.' . $ext : '');
                $dstPath  = $uploadDir . '/' . $safeName;

                if (move_uploaded_file($tmpName, $dstPath)) {
                    $size = filesize($dstPath);
                    $pstmt = mysqli_prepare($conn, "INSERT INTO documents (request_id, file_name, file_path, file_size) VALUES (?,?,?,?)");
                    mysqli_stmt_bind_param($pstmt, 'issi', $request_id, $origName, $safeName, $size);
                    mysqli_stmt_execute($pstmt);
                    mysqli_stmt_close($pstmt);
                }
            }
        }

        mysqli_commit($conn);
        echo json_encode(['success' => true, 'tracking_id' => $tracking, 'message' => 'Request submitted.']);
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}
?>

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

<div class="layout">

  <!-- ── SIDEBAR ── -->
  <?php include 'sidebar.php'; ?>

  <!-- ── MAIN ── -->
  <main class="main">

    <div class="page-header">
      <h2>New Document Request</h2>
      <p>Submit a new document processing request</p>
    </div>

    <!-- ── REQUEST DETAILS ── -->
    <div class="section-card" style="animation-delay:0.2s;">
      <div class="section-header">
        <span class="section-header-icon">📝</span>
        <h3>Request Details</h3>
      </div>
      <div class="section-body">

        <!-- Full Name -->
        <div class="form-row single">
          <div class="field" id="field-name">
            <label>Full Name <span class="req">*</span></label>
            <input type="text" id="fullName" name="fullName" placeholder="Enter full name">
            <span class="field-error" id="err-name">Full name is required.</span>
          </div>
        </div>

        <!-- Document Type -->
        <div class="form-row single">
          <div class="field" id="field-type">
            <label>Document Type <span class="req">*</span></label>
            <select id="docType" name="docType">
              <option value="">Select document type</option>
              <option value="Birth Certificate">🏥 Birth Certificate</option>
              <option value="Marriage License">💍 Marriage License</option>
              <option value="Business Permit">🏢 Business Permit</option>
              <option value="Tax Clearance">📊 Tax Clearance</option>
              <option value="ID Card">🪪 ID Card</option>
              <option value="Land Title">🏡 Land Title</option>
              <option value="Driver's License">🚗 Driver's License</option>
              <option value="Passport">✈️ Passport</option>
              <option value="Police Clearance">🚔 Police Clearance</option>
              <option value="NBI Clearance">📁 NBI Clearance</option>
            </select>
            <span class="field-error" id="err-type">Please select a document type.</span>
          </div>
        </div>

        <!-- Submission Date + Contact -->
        <div class="form-row">
          <div class="field" id="field-date">
            <label>Submission Date <span class="req">*</span></label>
            <input type="date" id="subDate" name="subDate">
            <span class="field-error" id="err-date">Please select a submission date.</span>
          </div>
          <div class="field" id="field-phone">
            <label>Contact Number <span class="req">*</span></label>
            <div class="phone-wrap">
              <span class="phone-prefix">🇵🇭 +63</span>
              <input type="tel" id="phone" name="phone" placeholder="XXX XXX XXXX" maxlength="12">
            </div>
            <span class="field-error" id="err-phone">Please enter a valid contact number.</span>
          </div>
        </div>

        <!-- Email -->
        <div class="form-row single">
          <div class="field" id="field-email">
            <label>Email Address <span class="req">*</span></label>
            <input type="email" id="email" name="email" placeholder="email@example.com">
            <span class="field-error" id="err-email">Please enter a valid email address.</span>
          </div>
        </div>

        <!-- Purpose -->
        <div class="form-row">
          <div class="field">
            <label>Purpose of Request</label>
            <select id="purpose" name="purpose">
              <option value="">Select purpose</option>
              <option>Employment</option>
              <option>Travel / Visa</option>
              <option>School Enrollment</option>
              <option>Legal / Court</option>
              <option>Government Benefit</option>
              <option>Personal Record</option>
              <option>Other</option>
            </select>
          </div>
          <div class="field">
            <label>Priority Level</label>
            <select id="priority" name="priority">
              <option value="Normal">🔵 Normal</option>
              <option value="Urgent">🟠 Urgent</option>
              <option value="Rush">🔴 Rush</option>
            </select>
          </div>
        </div>

        <!-- Notes -->
        <div class="form-row single">
          <div class="field">
            <label>Additional Notes</label>
            <textarea id="notes" name="notes" placeholder="Enter any additional information or special requests..."></textarea>
          </div>
        </div>

      </div>
    </div>

    <!-- ── UPLOAD DOCUMENTS ── -->
    <div class="section-card" style="animation-delay:0.35s;">
      <div class="section-header">
        <span class="section-header-icon">📎</span>
        <h3>Upload Documents</h3>
      </div>
      <div class="section-body">
        <div class="upload-zone" id="uploadZone"
             ondragover="handleDragOver(event)"
             ondragleave="handleDragLeave(event)"
             ondrop="handleDrop(event)">
          <input type="file" id="fileInput" name="files[]" multiple
                 accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                 onchange="handleFiles(this.files)">
          <div class="upload-icon">⬆️</div>
          <div class="upload-title">Click to upload or drag and drop</div>
          <div class="upload-sub">PDF, DOC, DOCX, JPG, PNG (Max 10MB per file)</div>
        </div>
        <div class="file-list" id="fileList"></div>
      </div>
    </div>

    <!-- ── TRACKING ID ── -->
    <div class="section-card" style="animation-delay:0.45s;">
      <div class="section-header">
        <span class="section-header-icon">🔖</span>
        <h3>Tracking ID</h3>
      </div>
      <div class="section-body">
        <div class="tracking-row">
          <div class="tracking-input" id="trackingDisplay">Click "Generate ID" to create your tracking ID</div>
          <button class="generate-btn" onclick="generateID()">✦ Generate ID</button>
        </div>
        <span class="field-hint">Your tracking ID will be used to monitor your document request status.</span>
      </div>
    </div>

    <!-- ── ACTION BAR ── -->
    <div class="action-bar">
      <button class="btn-cancel" onclick="resetForm()">✕ Cancel</button>
      <button class="btn-submit" onclick="submitRequest()">
        <span>📤</span> Submit Request
      </button>
    </div>

  </main>
</div>

<!-- ── SUCCESS OVERLAY ── -->
<div class="success-overlay" id="successOverlay">
  <div class="success-card">
    <div class="success-icon-wrap">✅</div>
    <div class="success-title">Request Submitted!</div>
    <div class="success-sub">Your document request has been received and is now being processed. Use your tracking ID to monitor its status.</div>
    <div class="success-id" id="successTrackingId">TRK-000</div>
    <div class="success-actions">
      <button class="success-btn-secondary" onclick="closeSuccess()">Add Another</button>
      <button class="success-btn-primary" onclick="closeSuccess()">Track Document →</button>
    </div>
  </div>
</div>

<!-- ── TOAST ── -->
<div class="toast" id="toast"></div>

<script>
  // Set today's date as default
  document.getElementById('subDate').value = new Date().toISOString().split('T')[0];

  // File handling
  let uploadedFiles = [];

  function fileIcon(name) {
    const ext = name.split('.').pop().toLowerCase();
    return { pdf:'📄', doc:'📝', docx:'📝', jpg:'🖼', jpeg:'🖼', png:'🖼' }[ext] || '📁';
  }

  function formatSize(bytes) {
    return bytes < 1024 * 1024
      ? (bytes / 1024).toFixed(1) + ' KB'
      : (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }

  function renderFiles() {
    const list = document.getElementById('fileList');
    list.innerHTML = '';
    uploadedFiles.forEach((f, i) => {
      const div = document.createElement('div');
      div.className = 'file-item';
      div.innerHTML = `
        <span class="file-icon">${fileIcon(f.name)}</span>
        <div class="file-info">
          <div class="file-name">${f.name}</div>
          <div class="file-size">${formatSize(f.size)}</div>
        </div>
        <button class="file-remove" onclick="removeFile(${i})">✕ Remove</button>
      `;
      list.appendChild(div);
    });
  }

  function handleFiles(files) {
    const MAX = 10 * 1024 * 1024;
    Array.from(files).forEach(f => {
      if (f.size > MAX) { showToast('⚠ ' + f.name + ' exceeds 10MB limit.', 'error'); return; }
      if (!uploadedFiles.find(u => u.name === f.name)) uploadedFiles.push(f);
    });
    renderFiles();
  }

  function removeFile(i) { uploadedFiles.splice(i, 1); renderFiles(); }

  function handleDragOver(e) {
    e.preventDefault();
    document.getElementById('uploadZone').classList.add('dragover');
  }
  function handleDragLeave(e) {
    document.getElementById('uploadZone').classList.remove('dragover');
  }
  function handleDrop(e) {
    e.preventDefault();
    document.getElementById('uploadZone').classList.remove('dragover');
    handleFiles(e.dataTransfer.files);
  }

  // Generate tracking ID
  let generatedID = '';
  function generateID() {
    const prefix = 'TRK';
    const num = String(Math.floor(Math.random() * 900) + 100);
    const suffix = Math.random().toString(36).substring(2,5).toUpperCase();
    generatedID = `${prefix}-${num}-${suffix}`;
    const el = document.getElementById('trackingDisplay');
    el.textContent = generatedID;
    el.classList.add('generated');
    showToast('✦ Tracking ID generated: ' + generatedID, 'success');
  }

  // Validation
  function validate() {
    let valid = true;
    const checks = [
      { id: 'fullName',  err: 'err-name',  wrap: 'field-name',  test: v => v.trim().length > 1 },
      { id: 'docType',   err: 'err-type',  wrap: 'field-type',  test: v => v !== '' },
      { id: 'subDate',   err: 'err-date',  wrap: 'field-date',  test: v => v !== '' },
      { id: 'phone',     err: 'err-phone', wrap: 'field-phone', test: v => /^\d{10,11}$/.test(v.replace(/\s/g,'')) },
      { id: 'email',     err: 'err-email', wrap: 'field-email', test: v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) },
    ];

    checks.forEach(c => {
      const el  = document.getElementById(c.id);
      const err = document.getElementById(c.err);
      const ok  = c.test(el.value);
      el.classList.toggle('error', !ok);
      err.classList.toggle('show', !ok);
      if (!ok) valid = false;
    });

    return valid;
  }

  // Submit (replaces inline-only behavior: sends data to PHP)
  async function submitRequest() {
    if (!validate()) {
      showToast('⚠ Please fill in all required fields.', 'error');
      return;
    }

    if (!generatedID) {
      generateID();
    }

    const formData = new FormData();
    formData.append('fullName', document.getElementById('fullName').value);
    formData.append('docType', document.getElementById('docType').value);
    formData.append('subDate', document.getElementById('subDate').value);
    formData.append('phone', document.getElementById('phone').value.replace(/\s/g,''));
    formData.append('email', document.getElementById('email').value);
    formData.append('purpose', document.getElementById('purpose').value);
    formData.append('priority', document.getElementById('priority').value);
    formData.append('notes', document.getElementById('notes').value);
    formData.append('trackingID', generatedID);

    // append files from uploadedFiles (keeps drag-drop UX)
    uploadedFiles.forEach((f, idx) => {
      formData.append('files[]', f, f.name);
    });

    try {
      const res = await fetch(window.location.href, { method: 'POST', body: formData });
      const text = await res.text();
      let json;
      try {
        json = JSON.parse(text);
      } catch (parseErr) {
        json = { success: res.ok, message: text };
      }

      if (json.success) {
        document.getElementById('successTrackingId').textContent = json.tracking_id || generatedID;
        document.getElementById('successOverlay').classList.add('open');
        showToast('✅ Request submitted: ' + (json.tracking_id || generatedID), 'success');
      } else {
        showToast('⚠ ' + (json.message || 'Submission failed.'), 'error');
      }
    } catch (err) {
      console.error(err);
      showToast('⚠ Unable to submit request. Please check your connection and try again.', 'error');
    }
  }

  function closeSuccess() {
    document.getElementById('successOverlay').classList.remove('open');
    resetForm();
  }

  function resetForm() {
    ['fullName','docType','phone','email','notes','purpose','priority'].forEach(id => {
      const el = document.getElementById(id);
      if (el.tagName === 'SELECT') el.selectedIndex = 0;
      else el.value = id === 'priority' ? 'Normal' : '';
    });
    document.getElementById('subDate').value = new Date().toISOString().split('T')[0];
    document.querySelectorAll('.field-error').forEach(e => e.classList.remove('show'));
    document.querySelectorAll('input.error, select.error, textarea.error').forEach(e => e.classList.remove('error'));
    uploadedFiles = [];
    renderFiles();
    generatedID = '';
    const td = document.getElementById('trackingDisplay');
    td.textContent = 'Click "Generate ID" to create your tracking ID';
    td.classList.remove('generated');
  }

  // Toast
  function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = `toast ${type} show`;
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 3500);
  }

</script>
</body>
</html>
