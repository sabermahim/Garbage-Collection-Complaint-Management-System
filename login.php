<?php
require 'config.php';
if (isset($_SESSION['admin'])) { header("Location: admin.php"); exit; }
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');
    $stmt = $conn->prepare("SELECT id FROM admin WHERE username=? AND password=?");
    $stmt->bind_param("ss", $u, $p);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['admin'] = $u;
        header("Location: admin.php"); exit;
    } else {
        $error = 'Incorrect username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Garbage Management System</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-page">
  <div class="login-left">
    <div class="ll-icon">🗑️</div>
    <h1>Garbage Management System</h1>
    <p>Login to the Admin Panel to manage all complaints and update their status.</p>
    <div class="ll-features">
      <div>✅ View and manage all complaints</div>
      <div>📊 View dashboard statistics</div>
      <div>🔄 Update complaint status</div>
      <div>📋 View status history</div>
      <div>📸 View photo evidence</div>
    </div>
  </div>
  <div class="login-right">
    <div class="login-box">
      <div class="lb-icon">🔐</div>
      <h2>Admin Login</h2>
      <p class="lb-sub">Login to access the dashboard</p>
      <?php if ($error): ?>
      <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST" action="login.php">
        <div class="form-group">
          <label class="form-label">Username</label>
          <input class="form-input" type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input class="form-input" type="password" name="password" value="1234" required>
        </div>
        <button type="submit" class="btn btn-primary">Login →</button>
      </form>
      <div class="auth-footer" style="margin-top:16px;"><a href="index.php">← Go to User Portal</a></div>
    </div>
  </div>
</div>
</body>
</html>
