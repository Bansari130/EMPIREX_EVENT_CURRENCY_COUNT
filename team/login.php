<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (isTeamLoggedIn()) redirect('/team/dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = getDB()->prepare('SELECT * FROM teams WHERE username = ?');
    $stmt->execute([$username]);
    $team = $stmt->fetch();

    if ($team && password_verify($password, $team['password'])) {
        if (!$team['is_active']) {
            flash('error', 'This team account has been deactivated. See the event admin.');
            redirect('/team/login.php');
        }
        $_SESSION['team_id'] = $team['id'];
        redirect('/team/dashboard.php');
    } else {
        flash('error', 'Invalid username or password.');
        redirect('/team/login.php');
    }
}

$pageTitle = 'Team Login';
include __DIR__ . '/../includes/header.php';
?>

<div class="center-screen" style="min-height:60vh;">
  <form method="POST" class="card" style="max-width:400px; width:100%;">
    <div style="text-align:center; margin-bottom:22px;">
      <div style="font-size:34px;">🕹️</div>
      <h2>Team Login</h2>
      <p class="muted" style="font-size:13px;">Use the credentials given to you at registration</p>
    </div>
    <div class="field">
      <label>Username</label>
      <input type="text" name="username" placeholder="e.g. team07" required autofocus autocomplete="username">
    </div>
    <div class="field">
      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
    </div>
    <button type="submit" class="btn btn-primary btn-block">Login →</button>
    <p class="muted" style="text-align:center; font-size:12px; margin-top:16px; margin-bottom:0;">
      Lost your credentials? Ask the event admin desk.
    </p>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
