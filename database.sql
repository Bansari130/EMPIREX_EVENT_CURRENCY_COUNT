-- ============================================================
--  EventCoin — Event Stall Currency & Leaderboard System
--  Database schema + starter seed data
-- ============================================================

CREATE DATABASE IF NOT EXISTS event_currency CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE event_currency;

-- ---------- Admins ----------
CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default login: username = admin / password = admin123
-- (hash generated with PHP password_hash, change this after first login)
INSERT INTO admins (username, password) VALUES
('admin', '$2y$10$fJNLt/6tdHvd2yLNZD.tWOZMEo/4mqQXvmBX.gkDz.yiFi/tBLxhG');

-- ---------- Teams ----------
CREATE TABLE teams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  team_name VARCHAR(100) NOT NULL,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  members VARCHAR(255) DEFAULT NULL,
  balance INT NOT NULL DEFAULT 2000,
  starting_balance INT NOT NULL DEFAULT 2000,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Stalls ----------
CREATE TABLE stalls (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stall_name VARCHAR(100) NOT NULL,
  stall_code VARCHAR(20) UNIQUE NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  entry_fee INT NOT NULL DEFAULT 100,
  staff_pin VARCHAR(10) NOT NULL,
  icon VARCHAR(10) DEFAULT '🎮',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Transactions (one row per play, pending -> completed) ----------
CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  team_id INT NOT NULL,
  stall_id INT NOT NULL,
  entry_fee INT NOT NULL,
  status ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  result ENUM('win','loss') DEFAULT NULL,
  prize_amount INT NOT NULL DEFAULT 0,
  net_change INT NOT NULL DEFAULT 0,
  balance_after INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_tx_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
  CONSTRAINT fk_tx_stall FOREIGN KEY (stall_id) REFERENCES stalls(id) ON DELETE CASCADE,
  INDEX idx_team (team_id),
  INDEX idx_stall (stall_id),
  INDEX idx_status (status)
) ENGINE=InnoDB;

-- ---------- Seed: 15 sample game stalls ----------
-- Staff PINs below are placeholders — change them from the Admin > Stalls page
INSERT INTO stalls (stall_name, stall_code, description, entry_fee, staff_pin, icon) VALUES
('Ring Toss',            'STALL01', 'Classic ring toss over bottles',        100, '1001', '🎯'),
('Balloon Dart',         'STALL02', 'Pop the balloon to win',                100, '1002', '🎈'),
('Basketball Shootout',  'STALL03', 'Score 3 baskets in 30 seconds',         150, '1003', '🏀'),
('Tic Tac Toe Blitz',    'STALL04', 'Beat the stall master in tic-tac-toe',   80, '1004', '❌'),
('Memory Match',         'STALL05', 'Flip and match the cards',              100, '1005', '🃏'),
('Coin Pusher',          'STALL06', 'Push the coins for a bonus payout',     120, '1006', '🪙'),
('Bottle Bowling',       'STALL07', 'Knock down all the bottles',            100, '1007', '🎳'),
('Puzzle Rush',          'STALL08', 'Solve the puzzle against the clock',    120, '1008', '🧩'),
('Arm Wrestle Arena',    'STALL09', 'Best of 3 arm wrestling rounds',        150, '1009', '💪'),
('Spin the Wheel',       'STALL10', 'Spin for a random multiplier',          100, '1010', '🎡'),
('Quiz Corner',          'STALL11', 'Answer 3 trivia questions correctly',    80, '1011', '❓'),
('Dart Board',           'STALL12', 'Hit the bullseye for the win',          100, '1012', '🎯'),
('Tug of War',           'STALL13', 'Team pull-off, best of 3',              150, '1013', '🪢'),
('Rock Paper Scissors',  'STALL14', 'Best of 3 against the stall master',     60, '1014', '✂️'),
('Treasure Dig',         'STALL15', 'Dig for the hidden treasure token',     100, '1015', '🏆');
