<?php
// Expects optional $pageTitle to be set before include
$pageTitle = $pageTitle ?? 'EventCoin';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> · EventCoin</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🪙</text></svg>">
</head>
<body>
<div class="topbar">
  <div class="topbar-inner">
    <a href="<?= BASE_URL ?>/index.php" class="brand"><span class="dot"></span>EventCoin <small>// stall currency system</small></a>
    <div class="nav-links">
      <?php if (isTeamLoggedIn()): $__t = currentTeam(); ?>
        <span class="pill">💰 <b><?= fmtCoins($__t['balance']) ?></b></span>
        <a href="<?= BASE_URL ?>/team/dashboard.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/team/history.php">History</a>
        <a href="<?= BASE_URL ?>/public/leaderboard.php">Leaderboard</a>
        <a href="<?= BASE_URL ?>/team/logout.php">Logout</a>
      <?php elseif (isAdminLoggedIn()): ?>
        <a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/admin/teams.php">Teams</a>
        <a href="<?= BASE_URL ?>/admin/stalls.php">Stalls</a>
        <a href="<?= BASE_URL ?>/admin/transactions.php">Transactions</a>
        <a href="<?= BASE_URL ?>/public/leaderboard.php">Leaderboard</a>
        <a href="<?= BASE_URL ?>/admin/logout.php">Logout</a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/public/leaderboard.php">Leaderboard</a>
        <a href="<?= BASE_URL ?>/team/login.php">Team Login</a>
        <a href="<?= BASE_URL ?>/admin/login.php">Admin</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<div class="wrap" style="padding-top:28px; padding-bottom:50px;">
<?php renderFlash(); ?>
