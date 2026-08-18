<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

$db = getDB();

$statusFilter = $_GET['status'] ?? '';
$stallFilter = $_GET['stall_id'] ?? '';

$where = [];
$params = [];
if ($statusFilter !== '') { $where[] = 't.status = ?'; $params[] = $statusFilter; }
if ($stallFilter !== '') { $where[] = 't.stall_id = ?'; $params[] = $stallFilter; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $db->prepare("
  SELECT t.*, tm.team_name, s.stall_name, s.icon
  FROM transactions t
  JOIN teams tm ON tm.id = t.team_id
  JOIN stalls s ON s.id = t.stall_id
  $whereSql
  ORDER BY t.id DESC
  LIMIT 500
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="transactions.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Team','Stall','Entry Fee','Status','Result','Prize','Net Change','Balance After','Created At']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['id'], $r['team_name'], $r['stall_name'], $r['entry_fee'], $r['status'], $r['result'], $r['prize_amount'], $r['net_change'], $r['balance_after'], $r['created_at']]);
    }
    fclose($out);
    exit;
}

$stalls = $db->query('SELECT id, stall_name FROM stalls ORDER BY stall_name')->fetchAll();

$pageTitle = 'Transactions';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex-between" style="margin-bottom:20px; flex-wrap:wrap; gap:12px;">
  <h1>Transactions</h1>
  <a class="btn btn-sm" href="?<?= http_build_query(array_merge($_GET, ['export' => 1])) ?>">⬇️ Export CSV</a>
</div>

<form method="GET" class="card card-tight" style="margin-bottom:18px; display:flex; gap:12px; flex-wrap:wrap; align-items:end;">
  <div class="field" style="margin:0; min-width:160px;">
    <label>Status</label>
    <select name="status">
      <option value="">All</option>
      <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
      <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
      <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
    </select>
  </div>
  <div class="field" style="margin:0; min-width:200px;">
    <label>Stall</label>
    <select name="stall_id">
      <option value="">All Stalls</option>
      <?php foreach ($stalls as $s): ?>
        <option value="<?= $s['id'] ?>" <?= $stallFilter == $s['id'] ? 'selected' : '' ?>><?= e($s['stall_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn-sm btn-primary" type="submit">Filter</button>
  <a class="btn btn-sm" href="<?= BASE_URL ?>/admin/transactions.php">Clear</a>
</form>

<div class="card">
  <?php if (!$rows): ?>
    <div class="empty-state"><div class="big-icon">📄</div>No transactions match your filters.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Team</th><th>Stall</th><th>Fee</th><th>Status</th><th>Net</th><th>Balance After</th><th>When</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><b><?= e($r['team_name']) ?></b></td>
          <td><?= $r['icon'] ?> <?= e($r['stall_name']) ?></td>
          <td class="mono">🪙<?= fmtCoins($r['entry_fee']) ?></td>
          <td>
            <?php if ($r['status'] === 'pending'): ?><span class="badge badge-pending">Pending</span>
            <?php elseif ($r['status'] === 'cancelled'): ?><span class="badge badge-neutral">Cancelled</span>
            <?php elseif ($r['result'] === 'win'): ?><span class="badge badge-win">Win</span>
            <?php else: ?><span class="badge badge-loss">Loss</span><?php endif; ?>
          </td>
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
