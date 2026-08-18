<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$db = getDB();
$rows = $db->query("
  SELECT t.id, t.team_name, t.balance, t.starting_balance,
         (SELECT COUNT(*) FROM transactions x WHERE x.team_id = t.id AND x.status='completed') AS games_played,
         (SELECT COUNT(*) FROM transactions x WHERE x.team_id = t.id AND x.status='completed' AND x.result='win') AS games_won
  FROM teams t
  WHERE t.is_active = 1
  ORDER BY t.balance DESC, t.id ASC
")->fetchAll();

echo json_encode(['ok' => true, 'teams' => $rows, 'ts' => time()]);
