<?php
require 'config.php';
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['update_status'])) {
    $cid    = intval($_POST['complaint_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $allowed = ['Pending','In Progress','Resolved','Rejected'];
    if ($cid > 0 && in_array($status, $allowed)) {
        $upd = $conn->prepare("UPDATE complaints SET status=? WHERE id=?");
        $upd->bind_param("si", $status, $cid);
        $upd->execute();
        $log = $conn->prepare("INSERT INTO complaint_status_log (complaint_id, status, updated_at) VALUES (?, ?, NOW())");
        $log->bind_param("is", $cid, $status);
        $log->execute();
        $_SESSION['flash'] = "Complaint #$cid status updated to '$status'.";
    }
    $qs = isset($_GET['s']) ? '?s='.urlencode($_GET['s']) : '';
    header("Location: admin.php$qs"); exit;
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// Stats
$total      = $conn->query("SELECT COUNT(*) c FROM complaints")->fetch_assoc()['c'];
$pending    = $conn->query("SELECT COUNT(*) c FROM complaints WHERE status='Pending'")->fetch_assoc()['c'];
$inprogress = $conn->query("SELECT COUNT(*) c FROM complaints WHERE status='In Progress'")->fetch_assoc()['c'];
$resolved   = $conn->query("SELECT COUNT(*) c FROM complaints WHERE status='Resolved'")->fetch_assoc()['c'];
$rejected   = $conn->query("SELECT COUNT(*) c FROM complaints WHERE status='Rejected'")->fetch_assoc()['c'];
$users      = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];

// Filter
$fs    = trim($_GET['s'] ?? '');
$where = '';
$allowed_s = ['Pending','In Progress','Resolved','Rejected'];
if ($fs && in_array($fs, $allowed_s)) {
    $fse   = $conn->real_escape_string($fs);
    $where = "WHERE c.status='$fse'";
}

// Complaints — fixed query without fetch_all issue
$sql = "SELECT c.id, c.location, c.description, c.status, c.created_at,
               u.name AS uname, u.phone AS uphone
        FROM complaints c
        LEFT JOIN users u ON u.id = c.user_id
        $where
        ORDER BY c.created_at DESC";
$res = $conn->query($sql);
$complaints = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        // Get images separately to avoid GROUP_CONCAT issues
        $iRes = $conn->query("SELECT image FROM complaint_images WHERE complaint_id=" . $row['id'] . " ORDER BY id ASC LIMIT 3");
        $imgs = [];
        if ($iRes) { while ($ir = $iRes->fetch_assoc()) $imgs[] = $ir['image']; }
        $row['imgs'] = $imgs;
        $complaints[] = $row;
    }
}

function sc3($s){return match($s){'In Progress'=>'progress','Resolved'=>'resolved','Rejected'=>'rejected',default=>'pending'};}
function si3($s){return match($s){'In Progress'=>'🔄','Resolved'=>'✅','Rejected'=>'❌',default=>'⏳'};}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — Garbage Management System</title>
  <link rel="stylesheet" href="style.css">
</head>
<body style="background:var(--gray-50);">
<div class="admin-layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sb-logo">
      <h2>🗑️ Garbage <span>Mgmt</span></h2>
      <p>Admin Panel</p>
    </div>
    <nav class="sb-nav">
      <div class="sb-lbl">Navigation</div>
      <a href="admin.php" class="sb-link <?= !$fs ? 'active' : '' ?>">📊 Dashboard <span class="sb-cnt"><?= $total ?></span></a>
      <div class="sb-lbl" style="margin-top:8px;">Filter by Status</div>
      <a href="admin.php?s=Pending"     class="sb-link <?= $fs==='Pending'     ? 'active':'' ?>">⏳ Pending     <span class="sb-cnt"><?= $pending ?></span></a>
      <a href="admin.php?s=In+Progress" class="sb-link <?= $fs==='In Progress' ? 'active':'' ?>">🔄 In Progress <span class="sb-cnt"><?= $inprogress ?></span></a>
      <a href="admin.php?s=Resolved"    class="sb-link <?= $fs==='Resolved'    ? 'active':'' ?>">✅ Resolved    <span class="sb-cnt"><?= $resolved ?></span></a>
      <a href="admin.php?s=Rejected"    class="sb-link <?= $fs==='Rejected'    ? 'active':'' ?>">❌ Rejected    <span class="sb-cnt"><?= $rejected ?></span></a>
    </nav>
    <div class="sb-foot">
      <div class="sf-label">Logged in as</div>
      <div class="sf-name">👤 <?= htmlspecialchars($_SESSION['admin']) ?></div>
      <a href="logout.php">⬅ Logout</a>
      <a href="index.php">🌐 User Portal</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="admin-main">
    <div class="adm-header">
      <div>
        <h1>Dashboard</h1>
        <p>Manage all complaints from here</p>
      </div>
      <div class="adm-date"><?= date('d M Y, h:i A') ?></div>
    </div>

    <?php if ($flash): ?>
    <div class="flash">✅ <?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card"><div class="st-icon g">🗑️</div><div><div class="st-val"><?= $total ?></div><div class="st-lbl">Total Complaints</div></div></div>
      <div class="stat-card"><div class="st-icon a">⏳</div><div><div class="st-val"><?= $pending ?></div><div class="st-lbl">Pending</div></div></div>
      <div class="stat-card"><div class="st-icon b">🔄</div><div><div class="st-val"><?= $inprogress ?></div><div class="st-lbl">In Progress</div></div></div>
      <div class="stat-card"><div class="st-icon g">✅</div><div><div class="st-val"><?= $resolved ?></div><div class="st-lbl">Resolved</div></div></div>
      <div class="stat-card"><div class="st-icon r">❌</div><div><div class="st-val"><?= $rejected ?></div><div class="st-lbl">Rejected</div></div></div>
      <div class="stat-card"><div class="st-icon gr">👥</div><div><div class="st-val"><?= $users ?></div><div class="st-lbl">Total Users</div></div></div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-row">
      <a href="admin.php"             class="ftab <?= !$fs           ? 'active':'' ?>">All (<?= $total ?>)</a>
      <a href="admin.php?s=Pending"   class="ftab <?= $fs==='Pending'   ? 'active':'' ?>">⏳ Pending</a>
      <a href="admin.php?s=In+Progress" class="ftab <?= $fs==='In Progress' ? 'active':'' ?>">🔄 In Progress</a>
      <a href="admin.php?s=Resolved"  class="ftab <?= $fs==='Resolved'  ? 'active':'' ?>">✅ Resolved</a>
      <a href="admin.php?s=Rejected"  class="ftab <?= $fs==='Rejected'  ? 'active':'' ?>">❌ Rejected</a>
    </div>

    <!-- Table -->
    <div class="tbl-card">
      <div class="tbl-hd">
        <h3>Complaints List</h3>
        <span><?= count($complaints) ?> complaint(s)</span>
      </div>
      <div class="tbl-scroll">
        <table>
          <thead>
            <tr>
              <th>#ID</th><th>User</th><th>Location</th><th>Description</th>
              <th>Photos</th><th>Date</th><th>Status</th><th>Update Status</th><th>Log</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($complaints)): ?>
          <tr><td colspan="9" style="text-align:center;padding:2.5rem;color:var(--gray-400);">No complaints found.</td></tr>
          <?php else: ?>
          <?php foreach ($complaints as $c): $sc = sc3($c['status']); ?>
          <tr>
            <td class="td-id">#<?= $c['id'] ?></td>
            <td>
              <div class="td-un"><?= htmlspecialchars($c['uname'] ?? '—') ?></div>
              <div class="td-ph"><?= htmlspecialchars($c['uphone'] ?? '') ?></div>
            </td>
            <td class="td-loc"><?= htmlspecialchars($c['location']) ?></td>
            <td><div class="td-desc" title="<?= htmlspecialchars($c['description']) ?>"><?= htmlspecialchars($c['description']) ?></div></td>
            <td>
              <?php if ($c['imgs']): ?>
              <div class="td-thumbs">
                <?php foreach ($c['imgs'] as $img): ?>
                <img src="uploads/<?= htmlspecialchars($img) ?>" onclick="openLB(this.src)" alt="">
                <?php endforeach; ?>
              </div>
              <?php else: ?>
              <span style="font-size:11px;color:var(--gray-400);">No photos</span>
              <?php endif; ?>
            </td>
            <td class="td-date"><?= date('d M Y', strtotime($c['created_at'])) ?><br><?= date('h:i A', strtotime($c['created_at'])) ?></td>
            <td><span class="badge badge-<?= $sc ?>"><?= si3($c['status']) ?> <?= htmlspecialchars($c['status']) ?></span></td>
            <td>
              <form method="POST" action="admin.php<?= $fs ? '?s='.urlencode($fs) : '' ?>" class="st-form">
                <input type="hidden" name="complaint_id" value="<?= $c['id'] ?>">
                <input type="hidden" name="update_status" value="1">
                <select name="status" class="st-sel">
                  <option value="Pending"     <?= $c['status']==='Pending'     ? 'selected':'' ?>>⏳ Pending</option>
                  <option value="In Progress" <?= $c['status']==='In Progress' ? 'selected':'' ?>>🔄 In Progress</option>
                  <option value="Resolved"    <?= $c['status']==='Resolved'    ? 'selected':'' ?>>✅ Resolved</option>
                  <option value="Rejected"    <?= $c['status']==='Rejected'    ? 'selected':'' ?>>❌ Rejected</option>
                </select>
                <button type="submit" class="btn-save">Save</button>
              </form>
            </td>
            <td><a href="history.php?id=<?= $c['id'] ?>" class="btn-log">📋 Log</a></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<!-- Lightbox -->
<div class="lb-overlay" id="lb" onclick="this.classList.remove('open')">
  <img id="lbImg" src="" alt="">
</div>
<script>
function openLB(src){ document.getElementById('lbImg').src=src; document.getElementById('lb').classList.add('open'); }
</script>
</body>
</html>
