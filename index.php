<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isTeamLoggedIn()) redirect('/team/dashboard.php');
if (isAdminLoggedIn()) redirect('/admin/dashboard.php');

$pageTitle = 'Welcome';
include __DIR__ . '/includes/header.php';
?>

<div class="center-screen" style="min-height:70vh;">
  <div style="max-width:560px; width:100%; text-align:center;">
    <div style="font-size:52px; margin-bottom:6px;">🪙</div>
    <h1 style="font-size:34px;">Event<span style="color:var(--accent);">Coin</span></h1>
    <p style="font-size:15px;">Every team starts with <b class="mono" style="color:var(--accent);"><?= fmtCoins(DEFAULT_BALANCE) ?></b> coins. Play the stalls, win big, climb the leaderboard.</p>

    <div class="grid grid-2" style="margin-top:30px;">
      <a href="<?= BASE_URL ?>/team/login.php" class="card card-tight" style="text-decoration:none;">
        <div style="font-size:26px; margin-bottom:6px;">🕹️</div>
        <div style="font-weight:700; color:var(--text-0);">Team Login</div>
        <div class="muted" style="font-size:12.5px; margin-top:2px;">Play stalls &amp; track your balance</div>
      </a>
      <a href="<?= BASE_URL ?>/public/leaderboard.php" class="card card-tight" style="text-decoration:none;">
        <div style="font-size:26px; margin-bottom:6px;">🏆</div>
        <div style="font-weight:700; color:var(--text-0);">Leaderboard</div>
        <div class="muted" style="font-size:12.5px; margin-top:2px;">Live rankings — put it on the big screen</div>
      </a>
    </div>

    <div style="margin-top:24px;">
      <a href="<?= BASE_URL ?>/admin/login.php" class="muted" style="font-size:12.5px;">Event organizer / stall admin →</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
