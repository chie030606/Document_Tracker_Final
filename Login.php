<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/database.php/db.php';

$flashSuccess = $_SESSION['flash_success'] ?? '';
if ($flashSuccess !== '') {
    unset($_SESSION['flash_success']);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '') {
        $errors[] = 'Username is required.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        // users table schema comes from Register.php
        $sql = "SELECT id, username, password FROM users WHERE username = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            $errors[] = 'Database error.';
        } else {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $user = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);

            if (!$user || !password_verify($password, (string)$user['password'])) {
                $errors[] = 'Invalid username or password.';
            } else {
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = (string)$user['username'];

                // Basic remember-me support (optional): store a flag
                $remember = isset($_POST['remember']) ? true : false;
                $_SESSION['remember_me'] = $remember;

                header('Location: Dashboard.php');
                exit;
            }
        }
    }
}

function e($v): string {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Docu Tracker — Sign in</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-bg">
    <main class="auth-wrap">
        <div class="auth-card" role="main" aria-labelledby="site-title">
            <div class="auth-brand">
                <img src="docutrack-logo.svg" alt="DocuTrack logo" class="brand-logo">
                <h1 id="site-title">DocuTrack</h1>
            </div>

            <?php if ($flashSuccess !== ''): ?>
                <div class="card" style="margin-bottom:12px; background: rgba(16,185,129,0.12); border-radius:8px; padding:10px; color:#a7f3d0; text-align:left;">
                    <div><?php echo e($flashSuccess); ?></div>
                </div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="card" style="margin-bottom:12px; background: rgba(255,255,255,0.02); border-radius:8px; padding:10px; color:#ffd6d6; text-align:left;">
                    <?php foreach ($errors as $err): ?>
                        <div style="margin-bottom:6px"><?php echo e($err); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" action="" method="post" autocomplete="off" novalidate>
                <label class="field">
                    <span class="label-text">Username</span>
                    <div class="input-wrap">
                        <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10zM3 21a9 9 0 0 1 18 0" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </svg>
                        <input name="username" type="text" placeholder="Enter username" required value="<?php echo e($_POST['username'] ?? ''); ?>">
                    </div>
                </label>

                <label class="field">
                    <span class="label-text">Password</span>
                    <div class="input-wrap">
                        <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.2" fill="none"/>
                            <path d="M7 11V8a5 5 0 0 1 10 0v3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </svg>
                        <input name="password" type="password" placeholder="Enter your password" required>
                    </div>
                </label>

                <div class="form-row">
                    <label class="checkbox">
                        <input type="checkbox" name="remember"<?php echo isset($_POST['remember']) ? ' checked' : ''; ?>>
                        <span>Remember me</span>
                    </label>
                </div>

                <button class="btn primary" type="submit">Sign In</button>

                <div class="divider" role="presentation"></div>

                <p class="small muted center">
                    Don't have an account?
                    <a href="register.php" class="link">Register here</a>
                </p>
            </form>
        </div>
    </main>
</div>
</body>
</html>
