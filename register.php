<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireGuest();

$errors = [];
$old    = ['name' => '', 'email' => '', 'username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name     = trim($_POST['name']     ?? '');
    $email    = strtolower(trim($_POST['email']    ?? ''));
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    $old = compact('name', 'email', 'username');

    // Validation
    if (strlen($name) < 2 || strlen($name) > 100)
        $errors['name'] = 'Name must be between 2 and 100 characters.';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors['email'] = 'Please enter a valid email address.';
    elseif (emailExists($email))
        $errors['email'] = 'This email address is already registered.';

    if (!preg_match('/^[a-z0-9]{3,30}$/', $username))
        $errors['username'] = 'Username must be 3–30 lowercase letters and numbers only.';
    elseif (usernameExists($username))
        $errors['username'] = 'This username is already taken.';

    if (strlen($password) < 8)
        $errors['password'] = 'Password must be at least 8 characters.';
    elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password))
        $errors['password'] = 'Password must contain at least one uppercase letter and one number.';

    if ($password !== $confirm)
        $errors['confirm'] = 'Passwords do not match.';

    if (empty($errors)) {
        $userId = createUser($name, $email, $username, $password);
        $user   = ['id' => $userId, 'name' => $name, 'email' => $email, 'username' => $username];
        setUserSession($user);
        header('Location: dashboard.php?welcome=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — PortfolioHub</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">

<nav class="auth-nav">
  <a href="index.php" class="logo">Portfolio<span>Hub</span></a>
</nav>

<main class="auth-wrap">
  <div class="auth-card">
    <div class="auth-card-head">
      <div class="auth-logo">🎓</div>
      <h1>Create Your Account</h1>
      <p>Start building your free student portfolio today.</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        ⚠ Please fix the errors below and try again.
      </div>
    <?php endif; ?>

    <form method="POST" action="register.php" novalidate>
      <?= csrfField() ?>

      <!-- Full name -->
      <div class="form-group">
        <label class="form-label" for="nameInput">Full Name <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="icon">👤</span>
          <input type="text" id="nameInput" name="name" class="form-input <?= isset($errors['name']) ? 'error' : '' ?>"
                 placeholder="Alex Johnson" value="<?= e($old['name']) ?>"
                 data-validate="name" required autocomplete="name">
        </div>
        <?php if (isset($errors['name'])): ?>
          <p class="form-error show"><?= e($errors['name']) ?></p>
        <?php endif; ?>
      </div>

      <!-- Email -->
      <div class="form-group">
        <label class="form-label" for="emailReg">Email Address <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="icon">✉</span>
          <input type="email" id="emailReg" name="email" class="form-input <?= isset($errors['email']) ? 'error' : '' ?>"
                 placeholder="alex@example.com" value="<?= e($old['email']) ?>"
                 data-validate="email" required autocomplete="email">
        </div>
        <?php if (isset($errors['email'])): ?>
          <p class="form-error show"><?= e($errors['email']) ?></p>
        <?php endif; ?>
      </div>

      <!-- Username -->
      <div class="form-group">
        <label class="form-label" for="usernameInput">Username <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="icon">@</span>
          <input type="text" id="usernameInput" name="username" class="form-input <?= isset($errors['username']) ? 'error' : '' ?>"
                 placeholder="alexjohnson" value="<?= e($old['username']) ?>"
                 data-validate="username" required autocomplete="username"
                 pattern="[a-z0-9]{3,30}">
        </div>
        <p class="form-hint" id="urlPreview" style="font-size:12px;color:var(--primary);margin-top:5px;">
          <?= e(explode('?', ($_SERVER['HTTP_HOST'] ?? 'yourdomain.com'))[0]) ?>/portfolio.php?u=yourusername
        </p>
        <?php if (isset($errors['username'])): ?>
          <p class="form-error show"><?= e($errors['username']) ?></p>
        <?php endif; ?>
      </div>

      <!-- Password -->
      <div class="form-group">
        <label class="form-label" for="pwInput">Password <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="icon">🔒</span>
          <input type="password" id="pwInput" name="password" class="form-input <?= isset($errors['password']) ? 'error' : '' ?>"
                 placeholder="Min. 8 chars, 1 uppercase, 1 number" required autocomplete="new-password">
        </div>
        <div class="pw-strength"><div class="pw-strength-bar" id="pwBar"></div></div>
        <p class="pw-label" id="pwLabel"></p>
        <?php if (isset($errors['password'])): ?>
          <p class="form-error show"><?= e($errors['password']) ?></p>
        <?php endif; ?>
      </div>

      <!-- Confirm password -->
      <div class="form-group">
        <label class="form-label" for="confirmPw">Confirm Password <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="icon">🔒</span>
          <input type="password" id="confirmPw" name="confirm" class="form-input <?= isset($errors['confirm']) ? 'error' : '' ?>"
                 placeholder="Repeat your password" required autocomplete="new-password">
        </div>
        <?php if (isset($errors['confirm'])): ?>
          <p class="form-error show"><?= e($errors['confirm']) ?></p>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:8px;">
        Create My Portfolio
      </button>
    </form>

    <p class="auth-footer">
      Already have an account? <a href="login.php">Sign in</a>
    </p>
  </div>
</main>

<script src="assets/js/main.js"></script>
</body>
</html>
