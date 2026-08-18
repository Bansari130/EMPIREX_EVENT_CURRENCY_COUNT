<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireTeamLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    if ($code === '') {
        flash('error', 'Enter or scan a stall code.');
        redirect('/team/scan.php');
    }
    redirect('/team/play.php?code=' . urlencode($code));
}

$pageTitle = 'Scan Stall';
include __DIR__ . '/../includes/header.php';
?>

<div style="max-width:480px; margin:0 auto;">
  <h2 style="text-align:center;">📷 Scan a Stall QR</h2>
  <p class="muted" style="text-align:center;">Point your camera at the QR code posted on the stall, or type the code shown underneath it.</p>

  <div class="card" style="padding:14px;">
    <div id="qr-reader" style="width:100%; border-radius:10px; overflow:hidden;"></div>
    <div id="qr-status" class="muted" style="text-align:center; font-size:12.5px; margin-top:10px;">Requesting camera access…</div>
  </div>

  <div class="flex-between" style="margin:18px 0; gap:10px;">
    <hr style="flex:1;"><span class="muted" style="font-size:12px;">OR ENTER MANUALLY</span><hr style="flex:1;">
  </div>

  <form method="POST" class="card">
    <div class="field">
      <label>Stall Code</label>
      <input type="text" name="code" placeholder="e.g. STALL01" required autofocus style="text-transform:uppercase; letter-spacing:.08em; text-align:center; font-size:18px;">
    </div>
    <button type="submit" class="btn btn-primary btn-block">Go to Stall →</button>
  </form>
</div>

<script src="<?= BASE_URL ?>/assets/js/vendor/html5-qrcode.min.js"></script>
<script>
(function(){
  var statusEl = document.getElementById('qr-status');
  try {
    var scanner = new Html5Qrcode("qr-reader");
    var running = false;
    Html5Qrcode.getCameras().then(function(cameras){
      if (!cameras || !cameras.length) {
        statusEl.textContent = 'No camera found — use manual entry below.';
        return;
      }
      var camId = cameras.length > 1 ? cameras[cameras.length - 1].id : cameras[0].id;
      running = true;
      scanner.start(camId, { fps: 10, qrbox: 220 }, function(decodedText){
        if (!running) return;
        running = false;
        scanner.stop().catch(function(){});
        var code = decodedText.trim();
        // Accept either a raw stall code or a full play.php?code=XXXX URL
        var match = code.match(/code=([A-Za-z0-9]+)/);
        if (match) code = match[1];
        window.location.href = "<?= BASE_URL ?>/team/play.php?code=" + encodeURIComponent(code.toUpperCase());
      }, function(){ /* ignore per-frame scan errors */ });
      statusEl.innerHTML = '<span class="livedot"></span> Camera live — align the QR code in the box';
    }).catch(function(){
      statusEl.textContent = 'Camera unavailable — use manual entry below.';
    });
  } catch (e) {
    statusEl.textContent = 'Camera unavailable — use manual entry below.';
  }
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
