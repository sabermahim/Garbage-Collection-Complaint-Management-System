<?php
require 'config.php';
$error = $success = '';
$new_uid = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']  ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if (!$name || !$phone) {
        $error = 'Both name and phone number are required.';
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE phone = ?");
        $chk->bind_param("s", $phone);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $error = 'This phone number is already registered.';
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, phone) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $phone);
            if ($stmt->execute()) {
                $new_uid = $conn->insert_id;
                $success = 'Registration successful!';
            } else {
                $error = 'Error: ' . $stmt->error;
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
  <title>Register — Garbage Management System</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
  <a href="index.php" class="nav-brand">🗑️ Garbage <span class="dot">Management</span></a>
  <div class="nav-links">
    <a href="index.php">Home</a>
    <a href="register.php" class="active">Register</a>
    <a href="complaint.php">Complaint</a>
    <a href="track.php">Track</a>
    <a href="login.php" class="nav-admin">Admin</a>
  </div>
</nav>
<div class="page-wrap">
  <div class="page-card">
    <div class="pc-icon">👤</div>
    <h1>Create New Account</h1>
    <p class="sub">Register to submit and track your complaints</p>

    <?php if ($error): ?>
    <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success">
      ✅ Registration successful!
      <div class="uid-reveal">
        <div class="uid-label">Your User ID</div>
        <div class="uid-number"><?= $new_uid ?></div>
        <div class="uid-warn">⚠️ Save this number — you need it to submit and track complaints!</div>
      </div>
    </div>
    <div class="auth-footer"><a href="complaint.php">Submit a Complaint Now →</a></div>
    <?php else: ?>
    <form method="POST" action="register.php">
      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input class="form-input" type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="Your full name" required>
      </div>
      <div class="form-group">
        <label class="form-label">Phone Number *</label>
        <input class="form-input" type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="01XXXXXXXXX" required>
      </div>
      <button type="submit" class="btn btn-primary">✅ Register Now</button>
    </form>
    <div class="auth-footer">Already registered? <a href="complaint.php">Submit a Complaint</a> or <a href="track.php">Track Status</a></div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
