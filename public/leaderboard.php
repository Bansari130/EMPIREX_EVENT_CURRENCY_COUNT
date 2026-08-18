<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$teams = $db->query("
  SELECT t.*, 
    (SELECT COUNT(*) FROM transactions x WHERE x.team_id = t.id AND x.status='completed') AS games_played,
    (SELECT COUNT(*) FROM transactions x WHERE x.team_id = t.id AND x.status='completed' AND x.result='win') AS games_won
  FROM teams t
  WHERE t.is_active = 1
  ORDER BY t.balance DESC, t.id ASC
")->fetchAll();

$pageTitle = 'Live Leaderboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex-between" style="margin-bottom:18px;">
  <h1>🏆 Leaderboard</h1>
  <span class="pill"><span class="livedot"></span> Live — updates every 8s</span>
</div>

<div class="card" style="padding:0;" id="lb-card">
  <?php if (!$teams): ?>
    <div class="empty-state"><div class="big-icon">🏆</div>No teams yet.</div>
  <?php else: ?>
    <div id="lb-rows"><?php foreach ($teams as $i => $t): $rank = $i + 1; ?>
      <div class="lb-row">
        <div class="lb-rank <?= $rank === 1 ? 'r1' : ($rank === 2 ? 'r2' : ($rank === 3 ? 'r3' : '')) ?>">
          <?= $rank <= 3 ? ['🥇','🥈','🥉'][$rank-1] : $rank ?>
        </div>
        <div>
          <div class="lb-name"><?= e($t['team_name']) ?></div>
          <div class="lb-sub"><?= (int)$t['games_played'] ?> games · <?= (int)$t['games_won'] ?> won</div>
        </div>
        <div class="lb-balance">🪙<?= fmtCoins($t['balance']) ?></div>
        <div class="lb-games"><?= $t['balance'] >= $t['starting_balance'] ? '▲ up' : '▼ down' ?></div>
      </div>
    <?php endforeach; ?></div>
  <?php endif; ?>
</div>

<script>
function rankBadge(i) {
  if (i === 0) return '🥇'; if (i === 1) return '🥈'; if (i === 2) return '🥉';
  return (i + 1);
}
function rankClass(i) {
  if (i === 0) return 'r1'; if (i === 1) return 'r2'; if (i === 2) return 'r3'; return '';
}
async function refreshLeaderboard() {
  try {
    const res = await fetch("<?= BASE_URL ?>/api/leaderboard.php", { cache: 'no-store' });
    const data = await res.json();
    if (!data.ok) return;
    const wrap = document.getElementById('lb-rows');
    if (!wrap) return;
    wrap.innerHTML = data.teams.map((t, i) => `
      <div class="lb-row">
        <div class="lb-rank ${rankClass(i)}">${rankBadge(i)}</div>
        <div>
          <div class="lb-name">${t.team_name}</div>
          <div class="lb-sub">${t.games_played} games · ${t.games_won} won</div>
        </div>
        <div class="lb-balance">🪙${Number(t.balance).toLocaleString()}</div>
        <div class="lb-games">${t.balance >= t.starting_balance ? '▲ up' : '▼ down'}</div>
      </div>
    `).join('');
  } catch (e) { /* silent — keep last known state */ }
}
setInterval(refreshLeaderboard, 8000);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
