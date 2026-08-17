<?php
/**
 * SFI Queuing System - Public Ticket Status Page
 * URL: /website/ticket-status.php?ticket=PL-001
 *
 * Opened when a client scans the QR code on their printed ticket.
 * Shows the live status of their queue ticket with auto-refresh.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
initPage();

$appName = APP_NAME;
$ticketParam = trim($_GET['ticket'] ?? '');
$validTicket = $ticketParam !== '' && preg_match('/^[A-Z0-9-]{2,20}$/i', $ticketParam);

// Initial server-side lookup so the page renders even before JS runs
$initial = null;
if ($validTicket) {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT qt.id, qt.ticket_number, qt.queue_date, qt.client_name, qt.status,
                   qt.counter_assigned, qt.called_at, qt.completed_at, qt.created_at,
                   lt.name AS loan_type_name,
                   c.counter_number AS counter_number, c.name AS counter_name
            FROM queue_tickets qt
            LEFT JOIN loan_types lt ON lt.id = qt.loan_type_id
            LEFT JOIN counters c ON c.id = qt.counter_assigned
            WHERE qt.ticket_number = :ticket
            ORDER BY qt.queue_date DESC, qt.id DESC
            LIMIT 1
        ");
        $stmt->execute([':ticket' => strtoupper($ticketParam)]);
        $row = $stmt->fetch();
        if ($row) {
            $waitingAhead = 0;
            if ($row['status'] === 'waiting') {
                $s = $db->prepare("SELECT COUNT(*) AS cnt FROM queue_tickets WHERE queue_date = :date AND status = 'waiting' AND id < :id");
                $s->execute([':date' => $row['queue_date'], ':id' => $row['id']]);
                $waitingAhead = (int)($s->fetch()['cnt'] ?? 0);
            }
            $initial = [
                'ticket_number' => $row['ticket_number'],
                'client_name'   => $row['client_name'],
                'loan_type'     => $row['loan_type_name'] ?? 'General',
                'status'        => $row['status'],
                'counter_number'=> $row['counter_number'],
                'counter_name'  => $row['counter_name'],
                'called_at'     => $row['called_at'],
                'completed_at'  => $row['completed_at'],
                'waiting_ahead' => $waitingAhead,
            ];
        }
    } catch (Exception $e) {
        error_log('SFI Ticket Status Page Error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Status - <?= htmlspecialchars($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background:
                radial-gradient(ellipse 85% 65% at 18% 8%, rgba(47, 211, 154, 0.22) 0%, transparent 55%),
                radial-gradient(ellipse 70% 55% at 85% 25%, rgba(14, 159, 110, 0.25) 0%, transparent 55%),
                radial-gradient(ellipse 100% 75% at 50% 115%, rgba(6, 37, 27, 0.92) 0%, transparent 62%),
                linear-gradient(160deg, #06251B 0%, #0B3B2E 48%, #0E9F6E 100%);
            background-attachment: fixed;
            min-height: 100vh; color: #1e293b;
            overflow-x: hidden;
        }
        body::before, body::after {
            content: '';
            position: fixed;
            z-index: -1;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.45;
            pointer-events: none;
        }
        body::before {
            width: 480px; height: 480px;
            top: -140px; left: -120px;
            background: radial-gradient(circle, rgba(47, 211, 154, 0.6), transparent 70%);
            animation: sfiAurora1 20s ease-in-out infinite alternate;
        }
        body::after {
            width: 440px; height: 440px;
            bottom: -120px; right: -90px;
            background: radial-gradient(circle, rgba(14, 159, 110, 0.65), transparent 70%);
            animation: sfiAurora2 26s ease-in-out infinite alternate;
        }
        @keyframes sfiAurora1 {
            0%   { transform: translate(0, 0) scale(1); }
            100% { transform: translate(70px, 55px) scale(1.18); }
        }
        @keyframes sfiAurora2 {
            0%   { transform: translate(0, 0) scale(1); }
            100% { transform: translate(-65px, -45px) scale(1.12); }
        }
        .top-bar {
            background: rgba(255,255,255,0.06); color: #fff;
            padding: 14px 24px; display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 10px;
        }
        .top-bar .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1.05rem; }
        .top-bar .brand-mark {
            width: 34px; height: 34px; border-radius: 9px;
            background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center;
        }
        .top-bar a { color: rgba(255,255,255,0.85); text-decoration: none; font-size: 0.8rem; margin-left: 14px; }
        .top-bar a:hover { text-decoration: underline; }
        .container { max-width: 560px; margin: 0 auto; padding: 40px 20px 60px; }
        .hero { text-align: center; color: #fff; margin-bottom: 24px; }
        .hero h1 { font-size: 1.6rem; font-weight: 800; margin-bottom: 6px; }
        .hero p { font-size: 0.9rem; color: rgba(255,255,255,0.85); }
        .card {
            background: #fff; border-radius: 16px;
            box-shadow: 0 18px 45px rgba(0,0,0,0.25);
            padding: 28px; margin-bottom: 20px; text-align: center;
        }
        .ticket-number {
            font-size: 2.6rem; font-weight: 800; letter-spacing: 0.04em;
            color: #0B3B2E; margin: 6px 0 2px;
        }
        .loan-type { font-size: 0.85rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; }
        .client-name { font-size: 1rem; font-weight: 600; color: #334155; margin-top: 10px; }
        .status-badge {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 0.82rem; font-weight: 700; padding: 8px 18px; border-radius: 999px;
            margin-top: 14px;
        }
        .status-badge .dot { width: 9px; height: 9px; border-radius: 50%; background: currentColor; }
        .status-badge.waiting { background: #fef3c7; color: #92400e; }
        .status-badge.serving { background: #dbeafe; color: #1e40af; }
        .status-badge.serving .dot { animation: pulse 1.2s ease-in-out infinite; }
        .status-badge.completed { background: #d1fae5; color: #065f46; }
        .status-badge.no_show, .status-badge.cancelled { background: #fee2e2; color: #b91c1c; }
        .status-badge.transferred { background: #f1eafe; color: #6d28d9; }
        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.25; } }
        .wait-info {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 14px; margin-top: 16px; font-size: 0.88rem; color: #334155;
        }
        .wait-info strong { color: #0B3B2E; font-size: 1.05rem; }
        .counter-info { margin-top: 12px; font-size: 0.85rem; color: #475569; }
        .counter-info strong { color: #0E9F6E; }
        .now-serving {
            margin-top: 18px; padding-top: 16px; border-top: 1px dashed #e2e8f0;
            font-size: 0.8rem; color: #64748b;
        }
        .now-serving strong { color: #0E9F6E; font-size: 1rem; }
        .form-group { margin-bottom: 14px; }
        .form-group input {
            width: 100%; padding: 12px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: 0.95rem; font-family: inherit; outline: none; text-align: center;
            text-transform: uppercase; letter-spacing: 0.15em; font-weight: 700;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-group input:focus { border-color: #0E9F6E; box-shadow: 0 0 0 3px rgba(14,159,110,0.15); }
        .btn-check {
            width: 100%; padding: 13px; background: #0E9F6E; color: #fff; border: none;
            border-radius: 10px; font-size: 1rem; font-weight: 700; font-family: inherit;
            cursor: pointer; transition: background .15s;
        }
        .btn-check:hover { background: #0B815A; }
        .alert { border-radius: 10px; padding: 12px 14px; font-size: 0.85rem; margin-top: 16px; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .footer { text-align: center; color: rgba(255,255,255,0.7); font-size: 0.78rem; padding: 20px; }
        .footer a { color: rgba(255,255,255,0.9); }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="brand">
            <span class="brand-mark">
                <img src="<?= BASE_URL ?>/background/logo.png" alt="SFI Logo" style="width:100%;height:100%;object-fit:contain;border-radius:8px;">
            </span>
            <?= htmlspecialchars($appName) ?>
        </div>
    </div>

    <div class="container">
        <div class="hero">
            <h1>Check Your Queue Status</h1>
            <p>I-scan ang QR code o ilagay ang ticket number para makita ang inyong status.</p>
        </div>

        <?php if (!$validTicket): ?>
            <!-- No ticket param yet: show the lookup form -->
            <div class="card">
                <h2 style="font-size:1.05rem; font-weight:700; margin-bottom:4px;">Enter Ticket Number</h2>
                <p style="font-size:0.82rem; color:#64748b; margin-bottom:18px;">
                    Halimbawa: <strong>PL-001</strong> o <strong>PY-023</strong> (nasa inyong ticket)
                </p>
                <div class="form-group">
                    <input type="text" id="ticketInput" placeholder="e.g., PL-001" autocomplete="off">
                </div>
                <button class="btn-check" id="btnCheck">CHECK STATUS</button>
                <div id="alertBox"></div>
            </div>
        <?php else: ?>
            <!-- Ticket param present: show the status card -->
            <div class="card" id="statusCard">
                <?php if ($initial): ?>
                    <div id="statusBody">
                        <div class="loan-type" id="stLoanType"><?= htmlspecialchars($initial['loan_type']) ?></div>
                        <div class="ticket-number" id="stNumber"><?= htmlspecialchars($initial['ticket_number']) ?></div>
                        <div class="client-name" id="stClient"><?= htmlspecialchars($initial['client_name']) ?></div>
                        <span class="status-badge <?= htmlspecialchars($initial['status']) ?>" id="stBadge">
                            <span class="dot"></span><span id="stStatusText"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $initial['status']))) ?></span>
                        </span>
                        <div class="wait-info" id="stWait"></div>
                        <div class="counter-info" id="stCounter"></div>
                        <div class="now-serving" id="stNowServing"></div>
                    </div>
                <?php else: ?>
                    <div id="statusBody">
                        <h2 style="color:#b91c1c; font-weight:700;">Ticket Not Found</h2>
                        <p style="font-size:0.85rem; color:#64748b; margin-top:8px;">
                            Hindi namin mahanap ang ticket na ito. Pakisuri ang numero sa inyong ticket o
                            <a href="ticket-status.php" style="color:#0E9F6E;">subukan muli</a>.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        &copy; <?= date('Y') ?> <?= htmlspecialchars($appName) ?> &middot; Smart Loan Queue Management
    </div>

    <script>
        const STATUS_LABELS = {
            waiting: 'WAITING',
            serving: 'NOW SERVING',
            completed: 'COMPLETED',
            no_show: 'NO SHOW',
            cancelled: 'CANCELLED',
            transferred: 'TRANSFERRED'
        };

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function(m) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
            });
        }

        // ---- Form mode: check a ticket number ----
        const btnCheck = document.getElementById('btnCheck');
        const ticketInput = document.getElementById('ticketInput');
        const alertBox = document.getElementById('alertBox');

        function showAlert(msg, type) {
            alertBox.innerHTML = '<div class="alert alert-' + type + '">' + esc(msg) + '</div>';
        }

        if (btnCheck && ticketInput) {
            ticketInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') btnCheck.click();
            });
            btnCheck.addEventListener('click', function() {
                const t = ticketInput.value.trim();
                if (!t) { showAlert('Please enter your ticket number.', 'error'); return; }
                window.location.href = 'ticket-status.php?ticket=' + encodeURIComponent(t);
            });
        }

        // ---- Status mode: poll for updates every 8 seconds ----
        const statusBody = document.getElementById('statusBody');
        const ticketNumber = <?= $validTicket ? json_encode(strtoupper($ticketParam)) : 'null' ?>;

        function renderStatus(data) {
            const t = data.ticket;
            document.getElementById('stLoanType').textContent = t.loan_type;
            document.getElementById('stNumber').textContent = t.ticket_number;
            document.getElementById('stClient').textContent = t.client_name;

            const badge = document.getElementById('stBadge');
            badge.className = 'status-badge ' + t.status;
            document.getElementById('stStatusText').textContent = STATUS_LABELS[t.status] || t.status.toUpperCase();

            const wait = document.getElementById('stWait');
            if (t.status === 'waiting') {
                wait.innerHTML = 'May <strong>' + t.waiting_ahead + '</strong> na nauuna sa inyo.';
            } else if (t.status === 'serving') {
                wait.innerHTML = 'Kasalukuyan na kayong <strong>tinatawag</strong>. Pumunta po sa counter.';
            } else if (t.status === 'completed') {
                wait.innerHTML = 'Natapos na po ang inyong transaction. Salamat!';
            } else {
                wait.innerHTML = 'Status: ' + esc((STATUS_LABELS[t.status] || t.status).toLowerCase());
            }

            const counter = document.getElementById('stCounter');
            if (t.counter_number) {
                counter.innerHTML = 'Counter: <strong>Counter ' + esc(t.counter_number) + '</strong>' +
                    (t.counter_name ? ' (' + esc(t.counter_name) + ')' : '');
            } else {
                counter.innerHTML = '';
            }

            const nowServing = document.getElementById('stNowServing');
            if (data.now_serving) {
                nowServing.innerHTML = 'Now serving: <strong>' + esc(data.now_serving.ticket_number) + '</strong>' +
                    (data.now_serving.counter_number ? ' at Counter ' + esc(data.now_serving.counter_number) : '');
            } else {
                nowServing.innerHTML = 'Walang kasalukuyang tinatawag.';
            }
        }

        if (ticketNumber && statusBody) {
            // Initial render from server data (already rendered in HTML), then poll
            function pollStatus() {
                fetch('<?= BASE_URL ?>/api/website/ticket-status.php?ticket=' + encodeURIComponent(ticketNumber))
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        if (res && res.success) renderStatus(res.data);
                    })
                    .catch(function() { /* keep last known status on network errors */ });
            }
            setInterval(pollStatus, 8000);
        }
    </script>
</body>
</html>
