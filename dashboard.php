<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

$db = getDB();

$teamCount = $db->query('SELECT COUNT(*) c FROM teams')->fetch()['c'];
$stallCount = $db->query("SELECT COUNT(*) c FROM stalls WHERE is_active = 1")->fetch()['c'];
$gamesPlayed = $db->query("SELECT COUNT(*) c FROM transactions WHERE status='completed'")->fetch()['c'];
$pendingCount = $db->query("SELECT COUNT(*) c FROM transactions WHERE status='pending'")->fetch()['c'];
$totalInCirculation = $db->query('SELECT COALESCE(SUM(balance),0) s FROM teams')->fetch()['s'];
$totalWagered = $db->query("SELECT COALESCE(SUM(entry_fee),0) s FROM transactions WHERE status IN ('completed','pending')")->fetch()['s'];
$totalPaidOut = $db->query("SELECT COALESCE(SUM(prize_amount),0) s FROM transactions WHERE status='completed' AND result='win'")->fetch()['s'];

$topStalls = $db->query("
  SELECT s.stall_name, s.icon, COUNT(*) plays
  FROM transactions t JOIN stalls s ON s.id = t.stall_id
  WHERE t.status = 'completed'
  GROUP BY t.stall_id ORDER BY plays DESC LIMIT 5
")->fetchAll();

$recentTx = $db->query("
  SELECT t.*, tm.team_name, s.stall_name, s.icon
  FROM transactions t
  JOIN teams tm ON tm.id = t.team_id
  JOIN stalls s ON s.id = t.stall_id
  ORDER BY t.id DESC LIMIT 8
")->fetchAll();

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex-between" style="margin-bottom:20px; flex-wrap:wrap; gap:12px;">
  <h1>Event Overview</h1>
  <div class="flex gap-8">
    <a href="<?= BASE_URL ?>/admin/teams.php" class="btn btn-sm">👥 Teams</a>
    <a href="<?= BASE_URL ?>/admin/stalls.php" class="btn btn-sm">🎮 Stalls</a>
    <a href="<?= BASE_URL ?>/public/leaderboard.php" class="btn btn-sm btn-primary" target="_blank">🏆 Leaderboard</a>
  </div>
</div>

<div class="grid grid-4" style="margin-bottom:22px;">
  <div class="stat"><div class="label">Teams</div><div class="value"><?= (int)$teamCount ?></div></div>
  <div class="stat"><div class="label">Active Stalls</div><div class="value"><?= (int)$stallCount ?></div></div>
  <div class="stat"><div class="label">Games Completed</div><div class="value"><?= (int)$gamesPlayed ?></div></div>
  <div class="stat"><div class="label">Awaiting Result</div><div class="value" style="color:var(--warn);"><?= (int)$pendingCount ?></div></div>
</div>

<div class="grid grid-3" style="margin-bottom:22px;">
  <div class="stat"><div class="label">Coins In Circulation</div><div class="value accent">🪙<?= fmtCoins($totalInCirculation) ?></div></div>
  <div class="stat"><div class="label">Total Wagered</div><div class="value">🪙<?= fmtCoins($totalWagered) ?></div></div>
  <div class="stat"><div class="label">Total Paid Out (Wins)</div><div class="value" style="color:var(--gold);">🪙<?= fmtCoins($totalPaidOut) ?></div></div>
</div>

<div class="grid grid-2" style="align-items:start;">
  <div class="card">
    <div class="card-header"><h3>🔥 Most Popular Stalls</h3></div>
    <?php if (!$topStalls): ?>
      <div class="empty-state">No games played yet.</div>
    <?php else: foreach ($topStalls as $s): ?>
      <div class="flex-between" style="padding:9px 0; border-bottom:1px solid var(--border-soft);">
        <span><?= $s['icon'] ?> <?= e($s['stall_name']) ?></span>
        <span class="mono muted"><?= (int)$s['plays'] ?> plays</span>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <h3>Recent Transactions</h3>
      <a href="<?= BASE_URL ?>/admin/transactions.php" class="muted" style="font-size:12.5px;">View all →</a>
    </div>
    <?php if (!$recentTx): ?>
      <div class="empty-state">Nothing yet.</div>
    <?php else: foreach ($recentTx as $r): ?>
      <div class="flex-between" style="padding:9px 0; border-bottom:1px solid var(--border-soft); font-size:13.5px;">
        <span><?= $r['icon'] ?> <b><?= e($r['team_name']) ?></b> <span class="muted">@ <?= e($r['stall_name']) ?></span></span>
        <?php if ($r['status'] === 'pending'): ?><span class="badge badge-pending">Pending</span>
        <?php elseif ($r['status'] === 'cancelled'): ?><span class="badge badge-neutral">Cancelled</span>
        <?php elseif ($r['result'] === 'win'): ?><span class="badge badge-win">+<?= fmtCoins($r['prize_amount']) ?></span>
        <?php else: ?><span class="badge badge-loss">Loss</span><?php endif; ?>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
