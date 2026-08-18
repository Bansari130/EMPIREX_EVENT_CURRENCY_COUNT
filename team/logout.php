<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
unset($_SESSION['team_id']);
redirect('/team/login.php');
