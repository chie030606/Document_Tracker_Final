<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

$errors = [];
$success = '';

/* DB settings */
$db_host = '127.0.0.1';
$db_name = 'docu_tracker';
$db_user = 'root';
$db_pass = '';

// connect with mysqli, create DB/table if missing
$mysqli = @new mysqli($db_host, $db_user, $db_pass);
if ($mysqli->connect_errno) {
    die('Database connection failed.');
}
$mysqli->set_charset('utf8mb4');

// create database if missing and select it
if (!$mysqli->query("CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    die('Failed to create database.');
}
if (!$mysqli->select_db($db_name)) {
    die('Failed to select database.');
}

// create users table if missing
$createTable = "
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL DEFAULT '',
    last_name VARCHAR(100) NOT NULL DEFAULT '',
    role VARCHAR(16) NOT NULL DEFAULT 'Viewer',
    status VARCHAR(16) NOT NULL DEFAULT 'Active',
    age SMALLINT UNSIGNED DEFAULT NULL,
    address VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
if (!$mysqli->query($createTable)) {
    die('Failed to create users table.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $age        = trim($_POST['age'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $password2  = $_POST['password2'] ?? '';

    if ($first_name === '' || !preg_match('/^[A-Za-z \'\-]{1,100}$/u', $first_name)) {
        $errors[] = 'Enter a valid first name (1-100 letters, spaces, \' or - allowed).';
    }
    if ($last_name === '' || !preg_match('/^[A-Za-z \'\-]{1,100}$/u', $last_name)) {
        $errors[] = 'Enter a valid last name (1-100 letters, spaces, \' or - allowed).';
    }
    if ($address === '' || mb_strlen($address) > 255) {
        $errors[] = 'Enter an address (max 255 characters).';
    }
    if ($age !== '') {
        if (!ctype_digit($age) || (int)$age < 0 || (int)$age > 120) {
            $errors[] = 'Enter a valid age (0-120).';
        }
    }

    if ($username === '' || !preg_match('/^[A-Za-z0-9._-]{3,100}$/', $username)) {
        $errors[] = 'Enter a valid username (3+ chars; letters, numbers, . _ - allowed).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $password2) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // check duplicates
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        if (!$stmt) {
            $errors[] = 'Database error (prepare).';
        } else {
            $stmt->bind_param('ss', $username, $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = 'Username or email already taken.';
                $stmt->close();
            } else {
                $stmt->close();
                $hash = password_hash($password, PASSWORD_DEFAULT);

                // two clear branches: age omitted -> use NULL in SQL; age provided -> bind integer
                if ($age === '') {
                    $ins = $mysqli->prepare(
                        "INSERT INTO users (username, email, password, first_name, last_name, age, address) VALUES (?, ?, ?, ?, ?, NULL, ?)"
                    );
                    if ($ins) {
                        $ins->bind_param('ssssss', $username, $email, $hash, $first_name, $last_name, $address);
                    }
                } else {
                    $age_i = (int)$age;
                    $ins = $mysqli->prepare(
                        "INSERT INTO users (username, email, password, first_name, last_name, age, address) VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );
                    if ($ins) {
                        // types: s username, s email, s password, s first_name, s last_name, i age, s address => 'sssssis'
                        $ins->bind_param('sssssis', $username, $email, $hash, $first_name, $last_name, $age_i, $address);
                    }
                }

                if (!$ins) {
                    $errors[] = 'Database error (prepare).';
                } else {
                    if ($ins->execute()) {
                        $_SESSION['flash_success'] = 'Account created. You may now sign in.';
                        header('Location: Login.php');
                        exit;
                    } else {
                        $errors[] = 'Failed to create account.';
                    }
                    $ins->close();
                }
            }
        }
    }
}

function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Docu Tracker — Register</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="app-bg">
    <main class="auth-wrap" aria-labelledby="reg-title">
      <div class="auth-card" role="main">
        <div class="auth-brand">
          <img src="docutrack-logo.svg" alt="DocuTrack logo" class="brand-logo">
          <h1 id="reg-title">Create account</h1>
        </div>

        <?php if (!empty($errors)): ?>
          <div class="card" style="margin-bottom:12px; background: rgba(255,255,255,0.02); border-radius:8px; padding:10px; color:#ffd6d6;">
            <?php foreach ($errors as $err): ?>
              <div style="margin-bottom:6px"><?php echo e($err); ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form class="auth-form" action="" method="post" autocomplete="off" novalidate>
          <div style="display:flex; gap:8px;">
            <label class="field" style="flex:1;">
              <span class="label-text">First name</span>
              <div class="input-wrap">
                <input name="first_name" type="text" placeholder="First name" value="<?php echo e($_POST['first_name'] ?? ''); ?>" required />
              </div>
            </label>

            <label class="field" style="flex:1;">
              <span class="label-text">Last name</span>
              <div class="input-wrap">
                <input name="last_name" type="text" placeholder="Last name" value="<?php echo e($_POST['last_name'] ?? ''); ?>" required />
              </div>
            </label>
          </div>

          <label class="field">
            <span class="label-text">Username</span>
            <div class="input-wrap">
              <input name="username" type="text" placeholder="choose a username" value="<?php echo e($_POST['username'] ?? ''); ?>" required />
            </div>
          </label>

          <label class="field">
            <span class="label-text">Email</span>
            <div class="input-wrap">
              <input name="email" type="email" placeholder="you@company.com" value="<?php echo e($_POST['email'] ?? ''); ?>" required />
            </div>
          </label>

          <div style="display:flex; gap:8px;">
            <label class="field" style="flex:1;">
              <span class="label-text">Age</span>
              <div class="input-wrap">
                <input name="age" type="number" min="0" max="120" placeholder="Age" value="<?php echo e($_POST['age'] ?? ''); ?>" />
              </div>
            </label>

            <label class="field" style="flex:2;">
              <span class="label-text">Address</span>
              <div class="input-wrap">
                <input name="address" type="text" placeholder="Street, City, ZIP" value="<?php echo e($_POST['address'] ?? ''); ?>" required />
              </div>
            </label>
          </div>

          <label class="field">
            <span class="label-text">Password</span>
            <div class="input-wrap">
              <input name="password" type="password" placeholder="Create a strong password" required />
            </div>
          </label>

          <label class="field">
            <span class="label-text">Confirm password</span>
            <div class="input-wrap">
              <input name="password2" type="password" placeholder="Repeat password" required />
            </div>
          </label>

          <button class="btn primary" type="submit">Create account</button>

          <div class="divider" role="presentation"></div>

          <p class="small muted center">Already have an account? <a href="Login.php" class="link">Sign in</a></p>
        </form>
      </div>
    </main>
  </div>

  <footer class="page-footer">© <?php echo date('Y'); ?> Docu Tracker</footer>
</body>
</html>