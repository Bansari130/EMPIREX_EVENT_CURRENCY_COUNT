<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireTeamLogin();

$db = getDB();
$team = currentTeam();

// stats
$stmt = $db->prepare("SELECT COUNT(*) c FROM transactions WHERE team_id = ? AND status='completed'");
$stmt->execute([$team['id']]);
$gamesPlayed = $stmt->fetch()['c'];

$stmt = $db->prepare("SELECT COUNT(*) c FROM transactions WHERE team_id = ? AND status='completed' AND result='win'");
$stmt->execute([$team['id']]);
$gamesWon = $stmt->fetch()['c'];

$stmt = $db->prepare("SELECT * FROM transactions WHERE team_id = ? AND status='pending' ORDER BY id DESC LIMIT 1");
$stmt->execute([$team['id']]);
$pendingTx = $stmt->fetch();
$pendingStall = null;
if ($pendingTx) {
    $s = $db->prepare('SELECT * FROM stalls WHERE id = ?');
    $s->execute([$pendingTx['stall_id']]);
    $pendingStall = $s->fetch();
}

// rank
$stmt = $db->query('SELECT id, balance FROM teams WHERE is_active = 1 ORDER BY balance DESC, id ASC');
$all = $stmt->fetchAll();
$rank = null;
foreach ($all as $i => $t) { if ($t['id'] == $team['id']) { $rank = $i + 1; break; } }

// recent history
$stmt = $db->prepare("SELECT tr.*, s.stall_name, s.icon FROM transactions tr JOIN stalls s ON s.id = tr.stall_id WHERE tr.team_id = ? ORDER BY tr.id DESC LIMIT 6");
$stmt->execute([$team['id']]);
$recent = $stmt->fetchAll();

// active stalls list
$stalls = $db->query('SELECT * FROM stalls WHERE is_active = 1 ORDER BY stall_name')->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex-between" style="margin-bottom:20px; flex-wrap:wrap; gap:12px;">
  <div>
    <h1 style="margin-bottom:2px;"><?= e($team['team_name']) ?></h1>
    <p class="muted" style="margin:0; font-size:13px;">Team code: <span class="code"><?= e($team['username']) ?></span></p>
  </div>
  <a href="<?= BASE_URL ?>/team/scan.php" class="btn btn-primary">📷 Scan Stall to Play</a>
</div>

<div class="grid grid-3" style="margin-bottom:22px;">
  <div class="stat">
    <div class="label">Balance</div>
    <div class="value accent">🪙 <?= fmtCoins($team['balance']) ?></div>
  </div>
  <div class="stat">
    <div class="label">Leaderboard Rank</div>
    <div class="value">#<?= (int)$rank ?> <span class="muted" style="font-size:14px;">/ <?= count($all) ?></span></div>
  </div>
  <div class="stat">
    <div class="label">Games Played / Won</div>
    <div class="value"><?= (int)$gamesPlayed ?> <span class="muted" style="font-size:14px;">/ <?= (int)$gamesWon ?> won</span></div>
  </div>
</div>

<?php if ($pendingTx && $pendingStall): ?>
<div class="card" style="border-color:var(--warn); margin-bottom:22px;">
  <div class="flex-between">
    <div>
      <span class="badge badge-pending">● IN PROGRESS</span>
      <div style="margin-top:8px; font-weight:700; font-size:16px;"><?= $pendingStall['icon'] ?> <?= e($pendingStall['stall_name']) ?></div>
      <div class="muted" style="font-size:12.5px;">Paid 🪙<?= fmtCoins($pendingTx['entry_fee']) ?> — waiting for stall staff to record the result</div>
    </div>
    <a href="<?= BASE_URL ?>/team/play.php?code=<?= urlencode($pendingStall['stall_code']) ?>" class="btn btn-gold btn-sm">Resume →</a>
  </div>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:22px;">
  <div class="card-header">
    <h3>🎮 Game Stalls</h3>
    <span class="muted" style="font-size:12.5px;"><?= count($stalls) ?> active</span>
  </div>
  <div class="grid grid-3">
    <?php foreach ($stalls as $s): ?>
      <a href="<?= BASE_URL ?>/team/play.php?code=<?= urlencode($s['stall_code']) ?>" class="card card-tight" style="text-decoration:none; display:block;">
        <div style="font-size:24px;"><?= $s['icon'] ?></div>
        <div style="font-weight:700; color:var(--text-0); margin-top:6px; font-size:14px;"><?= e($s['stall_name']) ?></div>
        <div class="muted" style="font-size:12px; margin-top:2px;">Entry: 🪙<?= fmtCoins($s['entry_fee']) ?></div>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>Recent Activity</h3>
    <a href="<?= BASE_URL ?>/team/history.php" class="muted" style="font-size:12.5px;">View all →</a>
  </div>
  <?php if (!$recent): ?>
    <div class="empty-state"><div class="big-icon">🎲</div>No games played yet — scan a stall to get started.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Stall</th><th>Result</th><th>Net</th><th>When</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><?= $r['icon'] ?> <?= e($r['stall_name']) ?></td>
            <td>
              <?php if ($r['status'] === 'pending'): ?><span class="badge badge-pending">Pending</span>
              <?php elseif ($r['status'] === 'cancelled'): ?><span class="badge badge-neutral">Cancelled</span>
              <?php elseif ($r['result'] === 'win'): ?><span class="badge badge-win">Win</span>
              <?php else: ?><span class="badge badge-loss">Loss</span><?php endif; ?>
            </td>
            <td class="mono" style="color:<?= $r['net_change'] > 0 ? 'var(--accent)' : ($r['net_change'] < 0 ? 'var(--danger)' : 'var(--text-2)') ?>;">
              <?= $r['status'] === 'completed' ? ($r['net_change'] >= 0 ? '+' : '') . fmtCoins($r['net_change']) : '—' ?>
            </td>
            <td class="muted"><?= timeAgo($r['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
