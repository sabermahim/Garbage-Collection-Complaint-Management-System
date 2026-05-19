<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Garbage Management System</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
  <a href="index.php" class="nav-brand">🗑️ Garbage <span class="dot">Management</span></a>
  <div class="nav-links">
    <a href="index.php" class="active">Home</a>
    <a href="register.php">Register</a>
    <a href="complaint.php">Complaint</a>
    <a href="track.php">Track</a>
    <a href="login.php" class="nav-admin">Admin</a>
  </div>
</nav>
<section class="hero">
  <div class="hero-icon">🗑️</div>
  <h1>Garbage Complaint<br><span>Management System</span></h1>
  <p>Report garbage issues in your area and track them in real-time</p>
  <div class="hero-btns">
    <a href="register.php" class="btn-hero-p">👤 Register Now</a>
    <a href="complaint.php" class="btn-hero-o">📝 Submit Complaint</a>
    <a href="track.php" class="btn-hero-o">🔍 Track Status</a>
  </div>
</section>
<section class="features">
  <h2>How It Works</h2>
  <div class="feat-grid">
    <div class="feat-card">
      <div class="fi">👤</div>
      <h3>Step 1: Register</h3>
      <p>Register with your name and phone number to get a unique User ID.</p>
      <a href="register.php" class="btn-card">Register Now →</a>
    </div>
    <div class="feat-card">
      <div class="fi">📝</div>
      <h3>Step 2: Complaint</h3>
      <p>Submit a complaint with location, description, and photos.</p>
      <a href="complaint.php" class="btn-card">Submit Now →</a>
    </div>
    <div class="feat-card">
      <div class="fi">🔍</div>
      <h3>Step 3: Track</h3>
      <p>Use your User ID to track the status of all your complaints.</p>
      <a href="track.php" class="btn-card">Track Now →</a>
    </div>
  </div>
</section>
</body>
</html>
