<?php
require_once __DIR__ . '/auth.php';
startAdminSession();
header('Cache-Control: no-store, private');

if (adminIsLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(403);
        $error = 'Your form expired. Refresh the page and try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $recent = (int) ($_SESSION['login_attempt_window'] ?? 0);
        if ($recent === 0 || time() - $recent > 900) {
            $_SESSION['login_attempt_window'] = time();
            $_SESSION['login_attempts'] = 0;
        }

        if ((int) ($_SESSION['login_attempts'] ?? 0) >= 5) {
            $error = 'Too many attempts. Try again in 15 minutes.';
        } else {
            require __DIR__ . '/../config/db.php';
            $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                session_regenerate_id(true); // Prevent session fixation.
                $_SESSION['admin_id'] = (int) $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['last_activity'] = time();
                unset($_SESSION['login_attempts'], $_SESSION['login_attempt_window']);
                header('Location: index.php');
                exit;
            }

            $_SESSION['login_attempts'] = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log in | Brew Co. Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
</head>
<body class="auth-page">
  <main class="auth-card card shadow-sm">
    <div class="card-body p-4 p-sm-5">
      <div class="auth-brand">
        <img src="../images/icon-logo.svg" alt="" class="auth-logo">
        <div class="auth-brand-name">BREW CO.</div>
        <div class="text-muted small">Admin dashboard</div>
      </div>
      <h1 class="h4 mb-2">Welcome back</h1>
      <p class="text-muted mb-4">Log in to manage your digital menu.</p>

      <?php if (isset($_GET['created'])): ?>
        <div class="alert alert-success" role="status">Admin account created. You can log in now.</div>
      <?php elseif (isset($_GET['expired'])): ?>
        <div class="alert alert-warning" role="status">Your session expired. Please log in again.</div>
      <?php elseif (isset($_GET['logged_out'])): ?>
        <div class="alert alert-success" role="status">You have been logged out.</div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <form method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="mb-3">
          <label class="form-label" for="username">Username</label>
          <input class="form-control" id="username" name="username" autocomplete="username" maxlength="30" required autofocus value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="mb-4">
          <label class="form-label" for="password">Password</label>
          <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn btn-brew w-100 py-2">Log in</button>
      </form>
      <div class="auth-footer small"><a href="../index.php">← Back to customer menu</a></div>
    </div>
  </main>
</body>
</html>
