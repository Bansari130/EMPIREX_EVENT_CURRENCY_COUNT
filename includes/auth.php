<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isTeamLoggedIn() {
    return isset($_SESSION['team_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireTeamLogin() {
    if (!isTeamLoggedIn()) {
        header('Location: ' . BASE_URL . '/team/login.php');
        exit;
    }
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

function currentTeam() {
    if (!isTeamLoggedIn()) return null;
    $stmt = getDB()->prepare('SELECT * FROM teams WHERE id = ?');
    $stmt->execute([$_SESSION['team_id']]);
    return $stmt->fetch();
}

function currentAdmin() {
    if (!isAdminLoggedIn()) return null;
    $stmt = getDB()->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch();
}
