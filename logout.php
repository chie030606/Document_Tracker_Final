<?php
// logout.php
// Place this file in /c:/xampp/htdocs/Docu Tracker/logout.php

session_start();

// If POST -> perform logout, otherwise show confirmation page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Include DB only when needed
    @include_once __DIR__ . '/db.php';

    // Capture current user id before destroying session (if set)
    $user_id = $_SESSION['user_id'] ?? null;

    // If a DB connection is available, try to record logout time (optional).
    if ($user_id) {
        if (isset($conn) && $conn instanceof mysqli) {
            if ($stmt = $conn->prepare("UPDATE users SET last_logout = NOW() WHERE id = ?")) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }
        } elseif (isset($mysqli) && $mysqli instanceof mysqli) {
            if ($stmt = $mysqli->prepare("UPDATE users SET last_logout = NOW() WHERE id = ?")) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }
        } elseif (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->prepare("UPDATE users SET last_logout = NOW() WHERE id = :id");
            $stmt->execute([':id' => $user_id]);
        }
    }

    // Clear session and cookies
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    // If this was an AJAX request return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    // Show simple logged-out page and redirect to login
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Logged out</title>
        <meta http-equiv="refresh" content="3;url=login.php">
        <style>
            body { font-family: Arial, Helvetica, sans-serif; background:#f5f7fb; color:#333; display:flex; align-items:center; justify-content:center; height:100vh; margin:0; }
            .card { background:#fff; padding:24px; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.06); text-align:center; width:320px; }
            a.button { display:inline-block; margin-top:12px; padding:10px 16px; background:#0069d9; color:#fff; text-decoration:none; border-radius:6px; }
        </style>
    </head>
    <body>
        <div class="card">
            <h2>You have been logged out</h2>
            <p>Redirecting to login page...</p>
            <a class="button" href="login.php">Go to Login Now</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// GET -> Show confirmation page (also used if user opens logout.php directly)
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Confirm logout</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background:#0f1724; color:#e6eef8; display:flex; align-items:center; justify-content:center; height:100vh; margin:0; }
        .modal { background:linear-gradient(180deg,#0b1220,#0e1a2b); border:1px solid rgba(255,255,255,0.04); padding:22px; border-radius:10px; width:420px; box-shadow:0 10px 40px rgba(2,6,23,0.6); text-align:left; }
        .modal h2 { margin:0 0 8px; font-size:18px; }
        .modal p { margin:0 0 18px; color:#9fb2d6; }
        .actions { display:flex; justify-content:flex-end; gap:10px; }
        .btn { padding:8px 14px; border-radius:8px; border:0; cursor:pointer; font-weight:600; }
        .btn.secondary { background:transparent; border:1px solid rgba(255,255,255,0.06); color:#cfe3ff; }
        .btn.danger { background:linear-gradient(135deg,#f43f5e,#ef4444); color:#fff; box-shadow:0 8px 20px rgba(244,63,94,0.18); }
    </style>
</head>
<body>
    <div class="modal" role="dialog" aria-labelledby="confirm-title" aria-modal="true">
        <h2 id="confirm-title">Confirm logout</h2>
        <p>Are you sure you want to log out of your account?</p>

        <div class="actions">
            <button class="btn secondary" onclick="window.history.back();">Cancel</button>

            <form method="post" style="display:inline">
                <button class="btn danger" type="submit">Logout</button>
            </form>
        </div>
    </div>
</body>
</html>