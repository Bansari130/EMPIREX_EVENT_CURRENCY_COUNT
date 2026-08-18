# 🪙 EventCoin — Event Stall Currency & Leaderboard System

A full PHP + MySQL system for running a stall-based event game economy:
every team starts with **2000 coins**, pays an entry fee to play any of your
**15 game stalls**, wins or loses coins, and climbs a **live leaderboard**.

---

## How it works

1. **Admin** creates teams (auto-generated username/password) and stalls
   (each stall gets a unique QR code + a 4-digit staff PIN).
2. **Team** logs in on their phone with their username/password.
3. At a stall, the team taps **"Scan Stall to Play"**, scans the stall's
   printed QR code (or types its code manually), sees the entry fee, and
   pays it — balance is deducted immediately.
4. The team hands their phone to the **stall staff**, who enters the
   stall's **PIN** and records **Win / Loss** (and the prize amount if won).
   Balance updates instantly and the transaction is logged.
5. The **Leaderboard** (`/public/leaderboard.php`) ranks every team by
   balance live — great for a projector/big screen, auto-refreshes every
   8 seconds.

No result can be entered without the correct stall PIN, so teams can't
self-report wins.

---

## Setup

### 1. Requirements
- PHP 7.4+ with PDO MySQL extension
- MySQL / MariaDB
- A web server (Apache/Nginx) or just `php -S localhost:8000` for testing

### 2. Import the database
```bash
mysql -u root -p < database.sql
```
This creates the `event_currency` database, all tables, a default admin
account, and 15 sample game stalls (edit/replace these from the admin panel).

### 3. Configure
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'event_currency');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', 'http://localhost/event-currency-system'); // no trailing slash
```
`BASE_URL` must be correct — it's used to build the QR code links that
teams scan, so it needs to be reachable from phones at your event (use
your machine's LAN IP, e.g. `http://192.168.1.10/event-currency-system`,
not `localhost`, if teams will scan on their own devices).

### 4. Run
Point your web server's document root at this folder, or for quick local
testing:
```bash
php -S 0.0.0.0:8000
```
Then visit `http://<your-ip>:8000/`.

### 5. Log in
- **Admin:** `admin` / `admin123` (change this immediately — see below)
- Create teams from **Admin → Teams** (single or bulk), print/share their
  credentials.
- Review/edit the 15 seeded stalls from **Admin → Stalls**, print each
  stall's QR code card and place it at the physical stall.

### 6. Change the default admin password
There's no in-app "change password" screen yet — update it directly in
the database:
```sql
UPDATE admins SET password = '$2y$10$YOUR_NEW_BCRYPT_HASH' WHERE username = 'admin';
```
Generate a hash with:
```php
<?php echo password_hash('your-new-password', PASSWORD_BCRYPT);
```

---

## Project structure

```
config/database.php     — DB connection + site settings (edit this first)
database.sql             — full schema + seed data (import this)
includes/                — auth, helpers, shared header/footer
admin/                   — admin login, dashboard, teams, stalls, transactions
team/                    — team login, dashboard, scan, play (pay + result), history
public/leaderboard.php   — live public leaderboard
api/leaderboard.php      — JSON endpoint the leaderboard polls
assets/                  — CSS + vendored JS (QR generate + QR scan, no CDN needed)
```

## Notes
- All money math happens inside DB transactions with row locking
  (`SELECT ... FOR UPDATE`), so simultaneous plays can't corrupt balances.
- Stall entry fees and staff PINs are editable any time from
  **Admin → Stalls**.
- "Cancel & Refund" (needs the stall PIN) exists on the play screen in
  case staff start a play by mistake.
- The QR scanner uses the device camera via the browser; if camera access
  isn't available it automatically falls back to manual code entry.
