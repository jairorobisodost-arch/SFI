# SFI Queuing System

Smart Loan Queue Management System for SFI (lending/loan office, Calapan City, Oriental Mindor).

A self-service queueing system with a client kiosk, staff dashboard, live TV display, and a
public "Check Your Loan" portal.

## Requirements

- XAMPP (Apache + PHP 8.x + MySQL/MariaDB) — tested on XAMPP 8.2.12
- Node.js (for the real-time Socket.IO server, port 4000)
- PHP extensions: `pdo_mysql`, `curl`, `zip`, `xml` (XMLReader/DOM), `mbstring`, `fileinfo`
- Internet access for the AI chatbot (Cohere) and SMS (IPROG) services

## Installation

1. Copy the `SFI` folder into `htdocs`.
2. Start Apache + MySQL in XAMPP.
3. Open phpMyAdmin, create the database `sfi_queuing_db` by importing `database.sql`.
4. Create your local secret files from the templates (real keys are never stored in git):
   - Copy `config/secrets.template.php` → `config/secrets.local.php` and fill in your keys.
   - Copy `server/.env.example` → `server/.env` and fill in the values.
5. Start the real-time server:

   ```bash
   cd server
   npm install
   npm start
   ```

   The Socket.IO server listens on `http://localhost:4000` (configurable via `server/.env`).
   Every page that needs live updates (dashboard, kiosk, display) connects to it.

### Default (dev-only) accounts

| Username | Password   | Role  |
|----------|------------|-------|
| admin    | admin123   | Admin |
| teller1  | teller123  | Teller (Counter 1) |
| teller2  | teller123  | Teller (Counter 2) |
| teller3  | teller123  | Teller (Counter 3) |

All accounts are forced to change their password on first login.

## Pages

| URL                | Purpose |
|--------------------|---------|
| `/login/`          | Staff/admin login |
| `/admin/`          | Dashboard: call next, recall, complete, no-show, transfer |
| `/kiosk/`          | Client self-service kiosk (get a queue number) |
| `/display/`        | Live TV display (now serving + next in line, with audio) |
| `/website/`        | Public "Check Your Loan" portal (OTP via SMS) |

## Features

- **Queue management** — ticket numbers with prefixes (PY/RL/CS), statuses
  (waiting/serving/completed/no-show/cancelled/transferred), per-counter serving,
  race-condition-safe ticket generation.
- **Real-time updates** — Socket.IO broadcasts queue changes to the dashboard, kiosk,
  and TV display instantly.
- **Data import** — upload an Excel (.xlsx) or CSV masterlist; the system auto-detects
  the target table from the headers (clients / loan types / counters / users) and
  archives the previous data set (with a dated `.xlsx` snapshot in `Archive/`).
- **Check Your Loan** — clients verify with full name + contact number, receive a
  6-digit OTP by SMS, then view their loan information.
- **AI chatbot** — "SFI Assistant" on the website and kiosk, powered by the Cohere API.
- **Reports** — daily queue statistics, loan-type breakdown, and filterable client
  data report with Excel export.
- **Activity logs** — audit trail of user actions.
- **Admin management** — users, roles, counters, loan types, settings, forced password
  changes, avatar uploads.

## Configuration

- `config/config.php` — app constants, Socket server URL, session/security settings.
- `config/secrets.local.php` — **API keys** (Cohere, IPROG SMS, EMIT token). Created by copying
  `config/secrets.template.php`; git-ignored, so it only exists on each machine.
- `config/database.php` — MySQL credentials (default: root / empty password, `sfi_queuing_db`).
- `server/.env` — Socket.IO port, CORS origin, and `EMIT_TOKEN`. Created by copying
  `server/.env.example`; git-ignored.

### External services

- **Cohere AI** — chatbot replies (`config.php` → `COHERE_API_KEY`). Free trial key ≈ 1,000
  calls/month; the chat endpoint enforces a 200/day cap plus a 15-message per-session cap.
- **IPROG SMS** — OTP delivery (`config.php` → `SMS_API_TOKEN`). To test locally without
  spending SMS credits, uncomment `SMS_DEMO_MODE` in `config.php`; the OTP is then written
  to `sms-log.txt` and shown on the website in demo mode.

## Security notes

- The app **requires** `config/secrets.local.php` (config.php loads it) — on every fresh clone,
  copy `config/secrets.template.php` to `config/secrets.local.php` and fill in your keys.
- Keep API keys private — rotate them if they were ever committed or shared.
- The `Archive/` and `excel file/` folders contain client data and are blocked from web
  access via `.htaccess` — keep those files in place.
- The Socket.IO `/emit` endpoint accepts an optional bearer token: set `EMIT_TOKEN` in
  `server/.env` and the matching constant in `config/config.php` to require it.

## Project structure

```
SFI/
├── admin/          # Staff/admin pages
├── api/            # JSON API endpoints (auth, queue, users, import, reports, ...)
├── assets/         # CSS, JS, uploads
├── config/         # config.php + database.php
├── data/           # Runtime data (chatbot usage counter)
├── display/        # Live TV display
├── includes/       # Bootstrap, auth, middleware, helpers (SMS, Cohere, XLSX, ...)
├── kiosk/          # Client self-service kiosk
├── login/          # Login + forced password change
├── server/         # Node.js Socket.IO real-time server
├── website/        # Public "Check Your Loan" portal
├── Archive/        # Archived data snapshots (.xlsx)
└── database.sql    # Database schema + seed data
```
