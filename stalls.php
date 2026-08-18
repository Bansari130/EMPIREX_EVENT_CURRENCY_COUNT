<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

$db = getDB();

function generateUniqueStallCode(PDO $db) {
    do {
        $code = randomCode('STALL-', 4);
        $stmt = $db->prepare('SELECT id FROM stalls WHERE stall_code = ?');
        $stmt->execute([$code]);
    } while ($stmt->fetch());
    return $code;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['stall_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $fee = max(1, (int)($_POST['entry_fee'] ?? 100));
        $icon = trim($_POST['icon'] ?? '🎮') ?: '🎮';
        if ($name === '') {
            flash('error', 'Stall name is required.');
        } else {
            $code = generateUniqueStallCode($db);
            $pin = randomPin();
            $db->prepare('INSERT INTO stalls (stall_name, stall_code, description, entry_fee, staff_pin, icon) VALUES (?,?,?,?,?,?)')
               ->execute([$name, $code, $desc, $fee, $pin, $icon]);
            flash('success', "Stall \"$name\" created with code $code and PIN $pin.");
        }
    }

    if ($action === 'update') {
        $id = (int)$_POST['stall_id'];
        $name = trim($_POST['stall_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $fee = max(1, (int)($_POST['entry_fee'] ?? 100));
        $icon = trim($_POST['icon'] ?? '🎮') ?: '🎮';
        $db->prepare('UPDATE stalls SET stall_name=?, description=?, entry_fee=?, icon=? WHERE id=?')
           ->execute([$name, $desc, $fee, $icon, $id]);
        flash('success', 'Stall updated.');
    }

    if ($action === 'regen_pin') {
        $id = (int)$_POST['stall_id'];
        $pin = randomPin();
        $db->prepare('UPDATE stalls SET staff_pin = ? WHERE id = ?')->execute([$pin, $id]);
        flash('success', "New staff PIN: $pin");
    }

    if ($action === 'toggle_active') {
        $id = (int)$_POST['stall_id'];
        $db->prepare('UPDATE stalls SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        flash('success', 'Stall status updated.');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['stall_id'];
        $db->prepare('DELETE FROM stalls WHERE id = ?')->execute([$id]);
        flash('success', 'Stall deleted.');
    }

    redirect('/admin/stalls.php');
}

$stalls = $db->query('SELECT * FROM stalls ORDER BY stall_name')->fetchAll();

$pageTitle = 'Manage Stalls';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex-between" style="margin-bottom:20px;">
  <h1>Game Stalls</h1>
  <span class="pill"><?= count($stalls) ?> stalls</span>
</div>

<div class="card" style="margin-bottom:22px;">
  <div class="card-header"><h3>+ Add New Stall</h3></div>
  <form method="POST">
    <input type="hidden" name="action" value="add">
    <div class="field-row">
      <div class="field" style="flex:0 0 90px;">
        <label>Icon</label>
        <input type="text" name="icon" value="🎮" maxlength="4">
      </div>
      <div class="field" style="flex:2;">
        <label>Stall Name</label>
        <input type="text" name="stall_name" placeholder="e.g. Ring Toss" required>
      </div>
      <div class="field" style="flex:0 0 140px;">
        <label>Entry Fee</label>
        <input type="number" name="entry_fee" min="1" value="100" required>
      </div>
    </div>
    <div class="field">
      <label>Description (optional)</label>
      <input type="text" name="description" placeholder="Short description shown to teams">
    </div>
    <button type="submit" class="btn btn-primary">+ Add Stall</button>
  </form>
</div>

<div class="grid grid-3">
<?php foreach ($stalls as $s):
  $playUrl = BASE_URL . '/team/play.php?code=' . urlencode($s['stall_code']);
?>
  <div class="card" id="stall-<?= $s['id'] ?>">
    <div class="flex-between" style="margin-bottom:10px;">
      <span class="stall-icon"><?= $s['icon'] ?></span>
      <?= $s['is_active'] ? '<span class="badge badge-win">Active</span>' : '<span class="badge badge-neutral">Inactive</span>' ?>
    </div>
    <h3 style="margin-bottom:2px;"><?= e($s['stall_name']) ?></h3>
    <p class="muted" style="font-size:12.5px; min-height:18px;"><?= e($s['description']) ?></p>

    <div class="flex-between" style="margin:14px 0;">
      <div class="qr-box"><canvas id="qr-<?= $s['id'] ?>"></canvas></div>
      <div style="text-align:right; font-size:12.5px;">
        <div class="muted">Code</div>
        <div class="code" style="margin-bottom:8px;"><?= e($s['stall_code']) ?></div>
        <div class="muted">Staff PIN</div>
        <div class="code"><?= e($s['staff_pin']) ?></div>
      </div>
    </div>

    <div class="flex-between" style="margin-bottom:14px;">
      <span class="muted">Entry Fee</span>
      <span class="mono" style="font-weight:700; color:var(--accent);">🪙<?= fmtCoins($s['entry_fee']) ?></span>
    </div>

    <details>
      <summary class="muted" style="cursor:pointer; font-size:12.5px;">Edit stall</summary>
      <form method="POST" style="margin-top:12px;">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="stall_id" value="<?= $s['id'] ?>">
        <div class="field-row">
          <div class="field" style="flex:0 0 70px;"><label>Icon</label><input type="text" name="icon" value="<?= e($s['icon']) ?>" maxlength="4"></div>
          <div class="field" style="flex:2;"><label>Name</label><input type="text" name="stall_name" value="<?= e($s['stall_name']) ?>" required></div>
        </div>
        <div class="field"><label>Description</label><input type="text" name="description" value="<?= e($s['description']) ?>"></div>
        <div class="field"><label>Entry Fee</label><input type="number" name="entry_fee" min="1" value="<?= $s['entry_fee'] ?>" required></div>
        <button type="submit" class="btn btn-sm btn-block">Save Changes</button>
      </form>
    </details>

    <div class="flex gap-8" style="margin-top:12px; flex-wrap:wrap;">
      <button class="btn btn-sm" onclick="printStall('<?= $s['id'] ?>')">🖨️ Print QR</button>
      <form method="POST" onsubmit="return confirm('Generate a new staff PIN?');">
        <input type="hidden" name="action" value="regen_pin"><input type="hidden" name="stall_id" value="<?= $s['id'] ?>">
        <button class="btn btn-sm" type="submit">New PIN</button>
      </form>
      <form method="POST">
        <input type="hidden" name="action" value="toggle_active"><input type="hidden" name="stall_id" value="<?= $s['id'] ?>">
        <button class="btn btn-sm" type="submit"><?= $s['is_active'] ? 'Deactivate' : 'Activate' ?></button>
      </form>
      <form method="POST" onsubmit="return confirm('Delete this stall permanently?');">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="stall_id" value="<?= $s['id'] ?>">
        <button class="btn btn-sm btn-danger" type="submit">Delete</button>
      </form>
    </div>
  </div>
<?php endforeach; ?>
</div>

<script src="<?= BASE_URL ?>/assets/js/vendor/qrcode.min.js"></script>
<script>
<?php foreach ($stalls as $s): $playUrl = BASE_URL . '/team/play.php?code=' . urlencode($s['stall_code']); ?>
(function(){
  var qr = qrcode(0, 'M');
  qr.addData(<?= json_encode($playUrl) ?>);
  qr.make();
  var canvas = document.getElementById('qr-<?= $s['id'] ?>');
  var size = 96, count = qr.getModuleCount(), cell = size / count;
  canvas.width = size; canvas.height = size;
  var ctx = canvas.getContext('2d');
  ctx.fillStyle = '#fff'; ctx.fillRect(0,0,size,size);
  ctx.fillStyle = '#000';
  for (var r = 0; r < count; r++) for (var c = 0; c < count; c++) {
    if (qr.isDark(r, c)) ctx.fillRect(c*cell, r*cell, cell, cell);
  }
})();
<?php endforeach; ?>

function printStall(id) {
  var el = document.getElementById('stall-' + id);
  var canvas = document.getElementById('qr-' + id);
  var dataUrl = canvas.toDataURL('image/png');
  var name = el.querySelector('h3').outerText;
  var code = el.querySelector('.code').outerText;
  var w = window.open('', '_blank');
  w.document.write('<html><head><title>Stall QR</title><style>body{font-family:sans-serif;text-align:center;padding:40px;}img{width:240px;height:240px;image-rendering:pixelated;}</style></head><body><h1>' + name + '</h1><img src="' + dataUrl + '"><p style="font-family:monospace;font-size:20px;">' + code + '</p></body></html>');
  setTimeout(function(){ w.print(); }, 300);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
