<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireTeamLogin();

$db = getDB();
$team = currentTeam();

$stmt = $db->prepare("SELECT tr.*, s.stall_name, s.icon FROM transactions tr JOIN stalls s ON s.id = tr.stall_id WHERE tr.team_id = ? ORDER BY tr.id DESC");
$stmt->execute([$team['id']]);
$rows = $stmt->fetchAll();

$pageTitle = 'History';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex-between" style="margin-bottom:18px;">
  <h1>Transaction History</h1>
  <span class="pill">💰 Balance: <b><?= fmtCoins($team['balance']) ?></b></span>
</div>

<div class="card">
  <?php if (!$rows): ?>
    <div class="empty-state"><div class="big-icon">🎲</div>No games played yet.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Stall</th><th>Entry Fee</th><th>Result</th><th>Prize</th><th>Net</th><th>Balance After</th><th>When</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= $r['icon'] ?> <?= e($r['stall_name']) ?></td>
            <td class="mono">🪙<?= fmtCoins($r['entry_fee']) ?></td>
            <td>
              <?php if ($r['status'] === 'pending'): ?><span class="badge badge-pending">Pending</span>
              <?php elseif ($r['status'] === 'cancelled'): ?><span class="badge badge-neutral">Cancelled</span>
              <?php elseif ($r['result'] === 'win'): ?><span class="badge badge-win">Win</span>
              <?php else: ?><span class="badge badge-loss">Loss</span><?php endif; ?>
            </td>
            <td class="mono"><?= $r['status'] === 'completed' && $r['result'] === 'win' ? '🪙' . fmtCoins($r['prize_amount']) : '—' ?></td>
            <td class="mono" style="color:<?= $r['net_change'] > 0 ? 'var(--accent)' : ($r['net_change'] < 0 ? 'var(--danger)' : 'var(--text-2)') ?>;">
              <?= $r['status'] === 'completed' ? ($r['net_change'] >= 0 ? '+' : '') . fmtCoins($r['net_change']) : '—' ?>
            </td>
            <td class="mono muted"><?= $r['balance_after'] !== null ? fmtCoins($r['balance_after']) : '—' ?></td>
            <td class="muted"><?= timeAgo($r['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
