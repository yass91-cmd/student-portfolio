<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireGuest();

$error = '';
$email = '';

// ── Rate limiting (session-based, 5 attempts per 15 min) ─────────────────────
$now = time();
if (!isset($_SESSION['login_attempts']))    $_SESSION['login_attempts']    = 0;
if (!isset($_SESSION['login_locked_until'])) $_SESSION['login_locked_until'] = 0;

$isLocked = $now < $_SESSION['login_locked_until'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if ($isLocked) {
        $wait  = ceil(($_SESSION['login_locked_until'] - $now) / 60);
        $error = "Too many failed attempts. Please wait {$wait} minute(s) and try again.";
    } else {
        $email    = strtolower(trim($_POST['email']    ?? ''));
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $error = 'Please enter your email and password.';
        } else {
            $user = getUserByEmail($email);
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['login_attempts']    = 0;
                $_SESSION['login_locked_until'] = 0;
                setUserSession($user);
                $redirect = $_GET['redirect'] ?? 'dashboard.php';
                if (!preg_match('/^[a-z0-9_\-\.\/]+\.php$/i', $redirect)) {
                    $redirect = 'dashboard.php';
                }
                header('Location: ' . $redirect);
                exit;
            } else {
                password_hash('dummy', PASSWORD_BCRYPT); // constant-time padding
                $_SESSION['login_attempts']++;
                if ($_SESSION['login_attempts'] >= 5) {
                    $_SESSION['login_locked_until'] = $now + 900; // 15 min
                    $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
                } else {
                    $remaining = 5 - $_SESSION['login_attempts'];
                    $error     = "Invalid email or password. {$remaining} attempt(s) remaining.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — PortfolioHub</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">

<nav class="auth-nav">
  <a href="index.php" class="logo">Portfolio<span>Hub</span></a>
</nav>

<main class="auth-wrap">
  <div class="auth-card">
    <div class="auth-card-head">
      <div class="auth-logo">👋</div>
      <h1>Welcome Back</h1>
      <p>Sign in to manage your portfolio.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error">⚠ <?= e($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
      <div class="alert alert-success">✓ Account created! Please sign in.</div>
    <?php endif; ?>

    <form method="POST" action="login.php" novalidate>
      <?= csrfField() ?>

      <div class="form-group">
        <label class="form-label" for="emailLogin">Email Address <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="icon">✉</span>
          <input type="email" id="emailLogin" name="email" class="form-input"
                 placeholder="alex@example.com" value="<?= e($email) ?>"
                 required autocomplete="email">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="loginPw">
          Password <span class="req">*</span>
        </label>
        <div class="input-wrap">
          <span class="icon">🔒</span>
          <input type="password" id="loginPw" name="password" class="form-input"
                 placeholder="Your password" required autocomplete="current-password">
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
    </form>

    <p class="auth-footer" style="margin-top:16px;">
      Don't have an account? <a href="register.php">Create one free</a>
    </p>

    <?php if (in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true)): ?>
    <hr style="margin:16px 0;border-color:var(--border);">
    <p style="font-size:13px;text-align:center;color:var(--text-muted);">
      Demo account: <code>demo@portfoliohub.com</code> / <code>Demo@1234</code>
    </p>
    <?php endif; ?>
  </div>
</main>

<script src="assets/js/main.js"></script>
</body>
</html>
