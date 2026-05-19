<?php
require 'config.php';
$error = $success = '';
$new_cid = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id     = intval($_POST['user_id']     ?? 0);
    $location    = trim($_POST['location']    ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!$user_id || !$location || !$description) {
        $error = 'All fields are required.';
    } else {
        $uchk = $conn->prepare("SELECT id, name FROM users WHERE id = ?");
        $uchk->bind_param("i", $user_id);
        $uchk->execute();
        $ur = $uchk->get_result()->fetch_assoc();

        if (!$ur) {
            $error = 'User ID #' . $user_id . ' not found. Please register first.';
        } else {
            $stmt = $conn->prepare("INSERT INTO complaints (user_id, location, description, status) VALUES (?, ?, ?, 'Pending')");
            $stmt->bind_param("iss", $user_id, $location, $description);

            if ($stmt->execute()) {
                $cid = $conn->insert_id;

                $log = $conn->prepare("INSERT INTO complaint_status_log (complaint_id, status, updated_at) VALUES (?, 'Pending', NOW())");
                $log->bind_param("i", $cid);
                $log->execute();

                if (!empty($_FILES['images']['name'][0])) {
                    $allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
                    $files   = $_FILES['images'];
                    $count   = count($files['name']);
                    for ($i = 0; $i < min($count, 5); $i++) {
                        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                        if (!in_array($files['type'][$i], $allowed)) continue;
                        if ($files['size'][$i] > 5 * 1024 * 1024) continue;
                        $ext  = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                        $fname = 'c' . $cid . '_' . $i . '_' . time() . '.' . $ext;
                        if (move_uploaded_file($files['tmp_name'][$i], __DIR__ . '/uploads/' . $fname)) {
                            $is = $conn->prepare("INSERT INTO complaint_images (complaint_id, image) VALUES (?, ?)");
                            $is->bind_param("is", $cid, $fname);
                            $is->execute();
                        }
                    }
                }

                $new_cid = $cid;
                $success = 'Complaint #' . $cid . ' submitted successfully!';
            } else {
                $error = 'Database error: ' . $stmt->error;
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
  <title>Submit Complaint — Garbage Management System</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
  <a href="index.php" class="nav-brand">🗑️ Garbage <span class="dot">Management</span></a>
  <div class="nav-links">
    <a href="index.php">Home</a>
    <a href="register.php">Register</a>
    <a href="complaint.php" class="active">Complaint</a>
    <a href="track.php">Track</a>
    <a href="login.php" class="nav-admin">Admin</a>
  </div>
</nav>
<div class="page-wrap">
  <div class="page-card">
    <div class="pc-icon">📝</div>
    <h1>Submit a Complaint</h1>
    <p class="sub">Report a garbage problem in your area</p>

    <?php if ($error): ?>
    <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success">
      ✅ <?= htmlspecialchars($success) ?>
      <div class="uid-reveal">
        <div class="uid-label">Complaint ID</div>
        <div class="uid-number">#<?= $new_cid ?></div>
        <div class="uid-warn"><a href="track.php" style="color:var(--green-dark);">🔍 Track it now →</a></div>
      </div>
    </div>
    <div class="auth-footer"><a href="complaint.php">Submit Another Complaint</a> | <a href="track.php">View All Complaints</a></div>
    <?php else: ?>
    <form method="POST" action="complaint.php" enctype="multipart/form-data">
      <div class="form-group">
        <label class="form-label">Your User ID *</label>
        <input class="form-input" type="number" name="user_id" min="1" value="<?= htmlspecialchars($_POST['user_id'] ?? '') ?>" placeholder="The ID you received after registration" required>
      </div>
      <div class="form-group">
        <label class="form-label">Problem Location *</label>
        <input class="form-input" type="text" name="location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" placeholder="e.g. Mirpur-10, Road No-5, Dhaka" required>
      </div>
      <div class="form-group">
        <label class="form-label">Detailed Description *</label>
        <textarea class="form-input" name="description" rows="4" placeholder="Describe the problem in detail..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Upload Photos <span style="font-weight:400;text-transform:none;font-size:11px;color:var(--gray-400);">(max 5 photos, 5MB each)</span></label>
        <div class="file-box">
          <input type="file" name="images[]" id="imgInput" multiple accept="image/*" onchange="showPrev(this)">
          <div class="file-inner">
            <div class="fi2">📸</div>
            <span>Drag photos here or <u>click to select</u></span>
            <small>JPG, PNG, WEBP supported</small>
          </div>
        </div>
        <div class="previews" id="prevBox"></div>
      </div>
      <button type="submit" class="btn btn-primary">📤 Submit Complaint</button>
    </form>
    <div class="auth-footer">Not registered yet? <a href="register.php">Register here</a></div>
    <?php endif; ?>
  </div>
</div>
<script>
function showPrev(input) {
  const box = document.getElementById('prevBox');
  box.innerHTML = '';
  Array.from(input.files).slice(0,5).forEach(f => {
    const r = new FileReader();
    r.onload = e => { const img = document.createElement('img'); img.src = e.target.result; box.appendChild(img); };
    r.readAsDataURL(f);
  });
}
</script>
</body>
</html>
