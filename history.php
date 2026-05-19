<?php
require 'config.php';
$cid = intval($_GET['id']  ?? 0);
$uid = intval($_GET['uid'] ?? 0);
if (!$cid) { header("Location: track.php"); exit; }

$cstmt = $conn->prepare("SELECT c.*, u.name AS uname FROM complaints c LEFT JOIN users u ON u.id=c.user_id WHERE c.id=?");
$cstmt->bind_param("i", $cid);
$cstmt->execute();
$c = $cstmt->get_result()->fetch_assoc();
if (!$c) { header("Location: track.php"); exit; }

$hstmt = $conn->prepare("SELECT * FROM complaint_status_log WHERE complaint_id=? ORDER BY updated_at DESC");
$hstmt->bind_param("i", $cid);
$hstmt->execute();
$history = $hstmt->get_result()->fetch_all(MYSQLI_ASSOC);

function sc2($s){return match($s){'In Progress'=>'progress','Resolved'=>'resolved','Rejected'=>'rejected',default=>'pending'};}
function si2($s){return match($s){'In Progress'=>'🔄','Resolved'=>'✅','Rejected'=>'❌',default=>'⏳'};}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Status History — Garbage Management System</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
  <a href="index.php" class="nav-brand">🗑️ Garbage <span class="dot">Management</span></a>
  <div class="nav-links">
    <a href="index.php">Home</a><a href="register.php">Register</a>
    <a href="complaint.php">Complaint</a><a href="track.php" class="active">Track</a>
    <a href="login.php" class="nav-admin">Admin</a>
  </div>
</nav>
<div class="page-wrap">
  <a href="track.php<?= $uid ? '?uid='.$uid : '' ?>" class="back-link">← Back to Complaints</a>
  <div class="page-card">
    <div class="pc-icon">📋</div>
    <h1>Complaint #<?= $cid ?> History</h1>
    <p class="sub">Complete status change history</p>
    <div class="complaint-mini">
      <strong>📍 <?= htmlspecialchars($c['location']) ?></strong>
      <div style="font-size:13px;color:var(--gray-600);margin-top:4px;"><?= htmlspecialchars($c['description']) ?></div>
      <div style="margin-top:10px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <span class="badge badge-<?= sc2($c['status']) ?>"><?= si2($c['status']) ?> <?= htmlspecialchars($c['status']) ?></span>
        <span style="font-size:12px;color:var(--gray-400);">👤 <?= htmlspecialchars($c['uname']) ?></span>
      </div>
    </div>
    <div class="divider"></div>
    <?php if (empty($history)): ?>
    <div class="empty-state"><div class="ei">📭</div><p>No history found.</p></div>
    <?php else: ?>
    <div class="timeline">
      <?php foreach ($history as $h): $s=sc2($h['status']); ?>
      <div class="tl-item">
        <div class="tl-dot <?= $s ?>"><?= si2($h['status']) ?></div>
        <div class="tl-body">
          <span class="badge badge-<?= $s ?>"><?= htmlspecialchars($h['status']) ?></span>
          <div class="tl-date">🕒 <?= date('d M Y, h:i A', strtotime($h['updated_at'])) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
