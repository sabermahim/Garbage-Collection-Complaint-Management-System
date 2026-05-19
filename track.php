<?php
require 'config.php';
$complaints = [];
$user_name  = '';
$error      = '';
$searched   = false;
$uid        = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['uid'])) {
    $uid      = intval($_POST['user_id'] ?? $_GET['uid'] ?? 0);
    $searched = true;

    if ($uid < 1) {
        $error = 'Please enter a valid User ID.';
    } else {
        $us = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $us->bind_param("i", $uid);
        $us->execute();
        $urow = $us->get_result()->fetch_assoc();

        if (!$urow) {
            $error = 'User ID #' . $uid . ' not found.';
        } else {
            $user_name = $urow['name'];
            $sql = "SELECT c.id, c.location, c.description, c.status, c.created_at,
                           GROUP_CONCAT(ci.image ORDER BY ci.id ASC SEPARATOR ',') AS images
                    FROM complaints c
                    LEFT JOIN complaint_images ci ON ci.complaint_id = c.id
                    WHERE c.user_id = ?
                    GROUP BY c.id ORDER BY c.created_at DESC";
            $cs = $conn->prepare($sql);
            $cs->bind_param("i", $uid);
            $cs->execute();
            $res = $cs->get_result();
            while ($row = $res->fetch_assoc()) $complaints[] = $row;
        }
    }
}

function scc($s) { return match($s) {'In Progress'=>'progress','Resolved'=>'resolved','Rejected'=>'rejected',default=>'pending'}; }
function sci($s) { return match($s) {'In Progress'=>'🔄','Resolved'=>'✅','Rejected'=>'❌',default=>'⏳'}; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Track Complaint — Garbage Management System</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
  <a href="index.php" class="nav-brand">🗑️ Garbage <span class="dot">Management</span></a>
  <div class="nav-links">
    <a href="index.php">Home</a>
    <a href="register.php">Register</a>
    <a href="complaint.php">Complaint</a>
    <a href="track.php" class="active">Track</a>
    <a href="login.php" class="nav-admin">Admin</a>
  </div>
</nav>
<div class="page-wrap wide">

  <div class="search-box">
    <div class="pc-icon">🔍</div>
    <h1 style="font-family:'Playfair Display',serif;font-size:1.5rem;">Track Your Complaints</h1>
    <p style="font-size:13.5px;color:var(--gray-400);margin-top:3px;">Enter your User ID to view all your complaints</p>
    <form method="POST" action="track.php">
      <div class="search-row">
        <input class="form-input" type="number" name="user_id" min="1" value="<?= $uid ?: '' ?>" placeholder="Enter your User ID..." required>
        <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;">🔍 Search</button>
      </div>
    </form>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($searched && $user_name): ?>
  <div class="sec-header">
    <h2>👤 <?= htmlspecialchars($user_name) ?>'s Complaints</h2>
    <p><?= count($complaints) ?> complaint(s) found</p>
  </div>

  <?php if (empty($complaints)): ?>
  <div class="empty-state">
    <div class="ei">📭</div>
    <p>No complaints submitted yet.</p>
    <br><a href="complaint.php" class="btn btn-primary btn-sm" style="display:inline-flex;margin-top:8px;">Submit a Complaint →</a>
  </div>
  <?php else: ?>
  <div class="complaint-list">
    <?php foreach ($complaints as $idx => $c): $sc = scc($c['status']); ?>
    <div class="c-card <?= $sc ?>" style="animation-delay:<?= $idx*0.05 ?>s">
      <div class="c-top">
        <div>
          <div class="c-id">COMPLAINT #<?= $c['id'] ?></div>
          <div class="c-loc">📍 <?= htmlspecialchars($c['location']) ?></div>
        </div>
        <span class="badge badge-<?= $sc ?>"><?= sci($c['status']) ?> <?= htmlspecialchars($c['status']) ?></span>
      </div>
      <div class="c-desc"><?= nl2br(htmlspecialchars($c['description'])) ?></div>
      <?php if ($c['images']): ?>
      <div class="c-imgs">
        <?php foreach (explode(',', $c['images']) as $img): if (!trim($img)) continue; ?>
        <img src="uploads/<?= htmlspecialchars(trim($img)) ?>" onclick="openLB(this.src)" alt="Photo">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div class="c-foot">
        <span class="c-date">🕒 <?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></span>
        <a href="history.php?id=<?= $c['id'] ?>&uid=<?= $uid ?>" class="btn btn-outline btn-sm">📋 Status History</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>

</div>
<div class="lb-overlay" id="lb" onclick="this.classList.remove('open')">
  <img id="lbImg" src="" alt="">
</div>
<script>
function openLB(src) { document.getElementById('lbImg').src=src; document.getElementById('lb').classList.add('open'); }
</script>
</body>
</html>
