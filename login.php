<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (isAdminLoggedIn()) redirect('/admin/dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = getDB()->prepare('SELECT * FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        redirect('/admin/dashboard.php');
    } else {
        flash('error', 'Invalid admin credentials.');
        redirect('/admin/login.php');
    }
}

$pageTitle = 'Admin Login';
include __DIR__ . '/../includes/header.php';
?>

<div class="center-screen" style="min-height:60vh;">
  <form method="POST" class="card" style="max-width:400px; width:100%;">
    <div style="text-align:center; margin-bottom:22px;">
      <div style="font-size:34px;">🛠️</div>
      <h2>Admin Login</h2>
      <p class="muted" style="font-size:13px;">Event organizer access</p>
    </div>
    <div class="field">
      <label>Username</label>
      <input type="text" name="username" placeholder="admin" required autofocus>
    </div>
    <div class="field">
      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Login →</button>
    <p class="muted" style="text-align:center; font-size:11.5px; margin-top:16px; margin-bottom:0;">
      Default: admin / admin123 — change this after first login
    </p>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
