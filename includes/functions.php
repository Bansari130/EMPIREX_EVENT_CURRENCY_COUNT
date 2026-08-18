<?php
require_once __DIR__ . '/../config/database.php';

function flash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function renderFlash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        $type = htmlspecialchars($f['type']);
        $msg = htmlspecialchars($f['msg']);
        echo "<div class=\"flash flash-$type\">$msg</div>";
        unset($_SESSION['flash']);
    }
}

function redirect($path) {
    header('Location: ' . BASE_URL . $path);
    exit;
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function fmtCoins($n) {
    $n = (int)$n;
    return ($n >= 0 ? '' : '-') . number_format(abs($n));
}

// Generates a short, human-friendly random code, e.g. team usernames/stall codes
function randomCode($prefix, $length = 4) {
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $prefix . $code;
}

function randomPin() {
    return (string)random_int(1000, 9999);
}

// Wraps a balance-changing operation in a transaction-safe row lock
function withTeamLock(PDO $db, $teamId, callable $fn) {
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('SELECT * FROM teams WHERE id = ? FOR UPDATE');
        $stmt->execute([$teamId]);
        $team = $stmt->fetch();
        if (!$team) {
            $db->rollBack();
            return [false, 'Team not found'];
        }
        $result = $fn($team, $db);
        $db->commit();
        return [true, $result];
    } catch (Exception $ex) {
        $db->rollBack();
        return [false, $ex->getMessage()];
    }
}

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return $diff . 's ago';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}
