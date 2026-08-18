<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

$db = getDB();

function generateUniqueUsername(PDO $db) {
    do {
        $u = 'team' . random_int(100, 999);
        $stmt = $db->prepare('SELECT id FROM teams WHERE username = ?');
        $stmt->execute([$u]);
    } while ($stmt->fetch());
    return $u;
}
function generatePassword() {
    return (string)random_int(1000, 9999) . chr(random_int(65,90)) . chr(random_int(97,122));
}

$newlyCreated = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_single') {
        $name = trim($_POST['team_name'] ?? '');
        $members = trim($_POST['members'] ?? '');
        if ($name === '') {
            flash('error', 'Team name is required.');
        } else {
            $username = generateUniqueUsername($db);
            $plainPass = generatePassword();
            $hash = password_hash($plainPass, PASSWORD_BCRYPT);
            $db->prepare('INSERT INTO teams (team_name, username, password, members, balance, starting_balance) VALUES (?,?,?,?,?,?)')
               ->execute([$name, $username, $hash, $members, DEFAULT_BALANCE, DEFAULT_BALANCE]);
            $newlyCreated[] = ['team_name' => $name, 'username' => $username, 'password' => $plainPass];
            flash('success', "Team \"$name\" created. Username: $username / Password: $plainPass");
        }
    }

    if ($action === 'bulk_add') {
        $count = max(1, min(100, (int)($_POST['bulk_count'] ?? 0)));
        $prefix = trim($_POST['bulk_prefix'] ?? 'Team');
        for ($i = 1; $i <= $count; $i++) {
            $name = $prefix . ' ' . $i;
            $username = generateUniqueUsername($db);
            $plainPass = generatePassword();
            $hash = password_hash($plainPass, PASSWORD_BCRYPT);
            $db->prepare('INSERT INTO teams (team_name, username, password, balance, starting_balance) VALUES (?,?,?,?,?)')
               ->execute([$name, $username, $hash, DEFAULT_BALANCE, DEFAULT_BALANCE]);
            $newlyCreated[] = ['team_name' => $name, 'username' => $username, 'password' => $plainPass];
        }
        flash('success', "$count teams created — credentials listed below, save/print them now.");
    }

    if ($action === 'reset_balance') {
        $id = (int)$_POST['team_id'];
        $stmt = $db->prepare('SELECT starting_balance FROM teams WHERE id = ?');
        $stmt->execute([$id]);
        $sb = $stmt->fetch()['starting_balance'] ?? DEFAULT_BALANCE;
        $db->prepare('UPDATE teams SET balance = ? WHERE id = ?')->execute([$sb, $id]);
        flash('success', 'Balance reset.');
    }

    if ($action === 'toggle_active') {
        $id = (int)$_POST['team_id'];
        $db->prepare('UPDATE teams SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        flash('success', 'Team status updated.');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['team_id'];
        $db->prepare('DELETE FROM teams WHERE id = ?')->execute([$id]);
        flash('success', 'Team deleted.');
    }

    if ($action === 'reset_password') {
        $id = (int)$_POST['team_id'];
        $plainPass = generatePassword();
        $hash = password_hash($plainPass, PASSWORD_BCRYPT);
        $db->prepare('UPDATE teams SET password = ? WHERE id = ?')->execute([$hash, $id]);
        $stmt = $db->prepare('SELECT team_name, username FROM teams WHERE id = ?');
        $stmt->execute([$id]);
        $t = $stmt->fetch();
        flash('success', "New password for {$t['team_name']} ({$t['username']}): $plainPass");
    }

    if (empty($newlyCreated)) {
        redirect('/admin/teams.php');
    }
}

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $db->prepare("SELECT * FROM teams WHERE team_name LIKE ? OR username LIKE ? ORDER BY balance DESC");
    $like = "%$search%";
    $stmt->execute([$like, $like]);
} else {
    $stmt = $db->query('SELECT * FROM teams ORDER BY balance DESC');
}
$teams = $stmt->fetchAll();

$pageTitle = 'Manage Teams';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex-between" style="margin-bottom:20px;">
  <h1>Teams</h1>
  <span class="pill"><?= count($teams) ?> total</span>
</div>

<?php if ($newlyCreated): ?>
<div class="card" style="border-color:var(--accent); margin-bottom:22px;">
  <div class="card-header">
    <h3>✅ New Credentials — save or print now</h3>
    <button onclick="window.print()" class="btn btn-sm">🖨️ Print</button>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Team</th><th>Username</th><th>Password</th></tr></thead>
      <tbody>
        <?php foreach ($newlyCreated as $nc): ?>
          <tr><td><?= e($nc['team_name']) ?></td><td class="mono"><?= e($nc['username']) ?></td><td class="mono"><?= e($nc['password']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="grid grid-2" style="margin-bottom:22px; align-items:start;">
  <div class="card">
    <div class="card-header"><h3>+ Add Single Team</h3></div>
    <form method="POST">
      <input type="hidden" name="action" value="add_single">
      <div class="field">
        <label>Team Name</label>
        <input type="text" name="team_name" placeholder="e.g. Byte Busters" required>
      </div>
      <div class="field">
        <label>Members (optional)</label>
        <input type="text" name="members" placeholder="e.g. Aditi, Rahul, Priya">
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create Team</button>
    </form>
  </div>

  <div class="card">
    <div class="card-header"><h3>⚡ Bulk Create Teams</h3></div>
    <form method="POST">
      <input type="hidden" name="action" value="bulk_add">
      <div class="field-row">
        <div class="field">
          <label>How many teams</label>
          <input type="number" name="bulk_count" min="1" max="100" value="10" required>
        </div>
        <div class="field">
          <label>Name prefix</label>
          <input type="text" name="bulk_prefix" value="Team" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Generate Teams</button>
      <p class="muted" style="font-size:11.5px; margin-top:10px; margin-bottom:0;">Creates "Prefix 1", "Prefix 2"... each with a random username &amp; password and starting balance of <?= fmtCoins(DEFAULT_BALANCE) ?>.</p>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>All Teams</h3>
    <form method="GET" style="display:flex; gap:8px;">
      <input type="text" name="q" placeholder="Search teams…" value="<?= e($search) ?>" style="width:200px;">
      <button class="btn btn-sm" type="submit">Search</button>
    </form>
  </div>

  <?php if (!$teams): ?>
    <div class="empty-state"><div class="big-icon">👥</div>No teams yet — create some above.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Team</th><th>Username</th><th>Balance</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($teams as $t): ?>
        <tr>
          <td><b><?= e($t['team_name']) ?></b><?php if ($t['members']): ?><div class="muted" style="font-size:11.5px;"><?= e($t['members']) ?></div><?php endif; ?></td>
          <td class="mono"><?= e($t['username']) ?></td>
          <td class="mono" style="color:<?= $t['balance'] >= $t['starting_balance'] ? 'var(--accent)' : 'var(--danger)' ?>;">🪙<?= fmtCoins($t['balance']) ?></td>
          <td><?= $t['is_active'] ? '<span class="badge badge-win">Active</span>' : '<span class="badge badge-neutral">Inactive</span>' ?></td>
          <td class="muted"><?= timeAgo($t['created_at']) ?></td>
          <td>
            <div class="flex gap-8" style="flex-wrap:wrap;">
              <form method="POST" onsubmit="return confirm('Reset balance to <?= fmtCoins($t['starting_balance']) ?>?');">
                <input type="hidden" name="action" value="reset_balance"><input type="hidden" name="team_id" value="<?= $t['id'] ?>">
                <button class="btn btn-sm" type="submit">Reset ₹</button>
              </form>
              <form method="POST">
                <input type="hidden" name="action" value="toggle_active"><input type="hidden" name="team_id" value="<?= $t['id'] ?>">
                <button class="btn btn-sm" type="submit"><?= $t['is_active'] ? 'Deactivate' : 'Activate' ?></button>
              </form>
              <form method="POST" onsubmit="return confirm('Generate a new password for this team?');">
                <input type="hidden" name="action" value="reset_password"><input type="hidden" name="team_id" value="<?= $t['id'] ?>">
                <button class="btn btn-sm" type="submit">New Pass</button>
              </form>
              <form method="POST" onsubmit="return confirm('Delete this team permanently? This cannot be undone.');">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="team_id" value="<?= $t['id'] ?>">
                <button class="btn btn-sm btn-danger" type="submit">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
