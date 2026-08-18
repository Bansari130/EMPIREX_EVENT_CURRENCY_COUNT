<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireTeamLogin();

$db = getDB();
$team = currentTeam();

$code = strtoupper(trim($_GET['code'] ?? $_POST['code'] ?? ''));
if ($code === '') redirect('/team/scan.php');

$stmt = $db->prepare('SELECT * FROM stalls WHERE stall_code = ?');
$stmt->execute([$code]);
$stall = $stmt->fetch();

if (!$stall || !$stall['is_active']) {
    flash('error', 'Stall not found or inactive. Double-check the code.');
    redirect('/team/scan.php');
}

// Is there already a pending transaction for this team+stall?
$stmt = $db->prepare("SELECT * FROM transactions WHERE team_id = ? AND stall_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
$stmt->execute([$team['id'], $stall['id']]);
$pending = $stmt->fetch();

$error = null;

// ---- Handle actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'pay' && !$pending) {
        [$ok, $result] = withTeamLock($db, $team['id'], function($t, $db) use ($stall) {
            if ($t['balance'] < $stall['entry_fee']) {
                throw new Exception('Not enough balance to play this stall.');
            }
            $newBalance = $t['balance'] - $stall['entry_fee'];
            $db->prepare('UPDATE teams SET balance = ? WHERE id = ?')->execute([$newBalance, $t['id']]);
            $db->prepare("INSERT INTO transactions (team_id, stall_id, entry_fee, status) VALUES (?, ?, ?, 'pending')")
               ->execute([$t['id'], $stall['id'], $stall['entry_fee']]);
            return $db->lastInsertId();
        });
        if (!$ok) {
            $error = $result;
        } else {
            redirect('/team/play.php?code=' . urlencode($code));
        }
    }

    if ($action === 'declare' && $pending) {
        $pin = trim($_POST['pin'] ?? '');
        $result_choice = $_POST['result'] ?? '';
        $prize = max(0, (int)($_POST['prize_amount'] ?? 0));

        if ($pin !== $stall['staff_pin']) {
            $error = 'Incorrect stall staff PIN.';
        } elseif (!in_array($result_choice, ['win', 'loss'], true)) {
            $error = 'Select win or loss.';
        } else {
            [$ok, $res] = withTeamLock($db, $team['id'], function($t, $db) use ($pending, $result_choice, $prize) {
                $creditBack = ($result_choice === 'win') ? $prize : 0;
                $newBalance = $t['balance'] + $creditBack;
                $netChange = $creditBack - $pending['entry_fee'];
                $db->prepare('UPDATE teams SET balance = ? WHERE id = ?')->execute([$newBalance, $t['id']]);
                $db->prepare("UPDATE transactions SET status='completed', result=?, prize_amount=?, net_change=?, balance_after=?, completed_at=NOW() WHERE id=?")
                   ->execute([$result_choice, $creditBack, $netChange, $newBalance, $pending['id']]);
                return $newBalance;
            });
            if (!$ok) {
                $error = $res;
            } else {
                flash('success', $result_choice === 'win' ? "🎉 Win recorded! +$prize coins credited." : '📝 Loss recorded.');
                redirect('/team/dashboard.php');
            }
        }
    }

    if ($action === 'cancel' && $pending) {
        $pin = trim($_POST['pin'] ?? '');
        if ($pin !== $stall['staff_pin']) {
            $error = 'Incorrect stall staff PIN — cannot cancel.';
        } else {
            [$ok, $res] = withTeamLock($db, $team['id'], function($t, $db) use ($pending) {
                $newBalance = $t['balance'] + $pending['entry_fee']; // refund
                $db->prepare('UPDATE teams SET balance = ? WHERE id = ?')->execute([$newBalance, $t['id']]);
                $db->prepare("UPDATE transactions SET status='cancelled', balance_after=?, completed_at=NOW() WHERE id=?")
                   ->execute([$newBalance, $pending['id']]);
                return $newBalance;
            });
            if ($ok) {
                flash('info', 'Play cancelled, entry fee refunded.');
                redirect('/team/play.php?code=' . urlencode($code));
            } else {
                $error = $res;
            }
        }
    }

    // refresh team + pending after any action
    $team = currentTeam();
    $stmt = $db->prepare("SELECT * FROM transactions WHERE team_id = ? AND stall_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$team['id'], $stall['id']]);
    $pending = $stmt->fetch();
}

$pageTitle = $stall['stall_name'];
include __DIR__ . '/../includes/header.php';
?>

<div style="max-width:520px; margin:0 auto;">

  <div class="card" style="text-align:center; margin-bottom:18px;">
    <div class="stall-icon"><?= $stall['icon'] ?></div>
    <h2 style="margin-top:6px;"><?= e($stall['stall_name']) ?></h2>
    <p class="muted" style="font-size:13px;"><?= e($stall['description']) ?></p>
    <span class="badge badge-neutral mono"><?= e($stall['stall_code']) ?></span>
  </div>

  <?php if ($error): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (!$pending): ?>
    <!-- STEP 1: PAY TO PLAY -->
    <div class="card">
      <div class="flex-between" style="margin-bottom:16px;">
        <span class="muted">Entry fee</span>
        <span class="mono" style="font-size:22px; font-weight:800; color:var(--accent);">🪙 <?= fmtCoins($stall['entry_fee']) ?></span>
      </div>
      <div class="flex-between" style="margin-bottom:20px;">
        <span class="muted">Your balance</span>
        <span class="mono" style="font-weight:700;"><?= fmtCoins($team['balance']) ?></span>
      </div>
      <?php if ($team['balance'] < $stall['entry_fee']): ?>
        <div class="flash flash-error">Not enough balance to play this stall.</div>
      <?php else: ?>
        <form method="POST">
          <input type="hidden" name="action" value="pay">
          <button type="submit" class="btn btn-primary btn-block">Pay &amp; Play →</button>
        </form>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <!-- STEP 2: WAITING FOR STALL STAFF TO DECLARE RESULT -->
    <div class="card">
      <div class="flex-between" style="margin-bottom:16px;">
        <span class="badge badge-pending">● AWAITING RESULT</span>
        <span class="muted mono" style="font-size:12px;">paid 🪙<?= fmtCoins($pending['entry_fee']) ?></span>
      </div>
      <p style="font-size:13.5px;">Hand this screen to the stall staff. They'll enter the stall PIN and record the result.</p>

      <form method="POST">
        <input type="hidden" name="action" value="declare">
        <div class="field">
          <label>Stall Staff PIN</label>
          <input type="text" name="pin" inputmode="numeric" placeholder="••••" maxlength="10" required>
        </div>
        <div class="field">
          <label>Result</label>
          <div class="grid grid-2" style="gap:10px;">
            <label style="display:flex; align-items:center; gap:8px; background:var(--bg-0); border:1px solid var(--border); border-radius:10px; padding:12px; cursor:pointer; text-transform:none;">
              <input type="radio" name="result" value="win" required style="width:auto;"> 🏆 Win
            </label>
            <label style="display:flex; align-items:center; gap:8px; background:var(--bg-0); border:1px solid var(--border); border-radius:10px; padding:12px; cursor:pointer; text-transform:none;">
              <input type="radio" name="result" value="loss" required style="width:auto;"> ❌ Loss
            </label>
          </div>
        </div>
        <div class="field">
          <label>Prize Amount (if win, 0 if loss)</label>
          <input type="number" name="prize_amount" min="0" value="0" placeholder="e.g. 200">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Submit Result</button>
      </form>

      <details style="margin-top:16px;">
        <summary class="muted" style="cursor:pointer; font-size:12.5px;">Made a mistake? Cancel &amp; refund</summary>
        <form method="POST" style="margin-top:12px;">
          <input type="hidden" name="action" value="cancel">
          <div class="field">
            <label>Stall Staff PIN</label>
            <input type="text" name="pin" inputmode="numeric" placeholder="••••" maxlength="10" required>
          </div>
          <button type="submit" class="btn btn-danger btn-block">Cancel This Play &amp; Refund</button>
        </form>
      </details>
    </div>
  <?php endif; ?>

  <div style="text-align:center; margin-top:16px;">
    <a href="<?= BASE_URL ?>/team/dashboard.php" class="muted">← Back to dashboard</a>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
