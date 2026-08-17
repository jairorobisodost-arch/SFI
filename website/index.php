<?php
/**
 * SFI Queuing System - Public Client Portal
 * URL: /website/
 * Clients enter their FULL NAME + CONTACT NUMBER (their personal access code)
 * to view their loan information. Data is verified against the masterlist.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
initPage();

// Never cache this page — it must always reflect the latest client data & JS
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$appName = APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Your Loan - <?= $appName ?></title>
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
        /* Aurora glow blobs - slow drifting brand-green light */
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

        .container { max-width: 720px; margin: 0 auto; padding: 40px 20px 60px; }

        .hero { text-align: center; color: #fff; margin-bottom: 28px; }
        .hero h1 { font-size: 2rem; font-weight: 800; margin-bottom: 8px; }
        .hero p { font-size: 0.95rem; color: rgba(255,255,255,0.85); }

        .card {
            background: #fff; border-radius: 16px;
            box-shadow: 0 18px 45px rgba(0,0,0,0.25);
            padding: 28px; margin-bottom: 20px;
        }
        .card h2 { font-size: 1.05rem; font-weight: 700; margin-bottom: 4px; }
        .card .hint { font-size: 0.82rem; color: #64748b; margin-bottom: 18px; }

        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 0.82rem; font-weight: 600; color: #334155; margin-bottom: 5px; }
        .form-group input {
            width: 100%; padding: 12px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: 0.95rem; font-family: inherit; outline: none; transition: border-color .15s, box-shadow .15s;
        }
        .form-group input:focus { border-color: #0E9F6E; box-shadow: 0 0 0 3px rgba(14,159,110,0.15); }

        /* Name autocomplete dropdown */
        .name-suggest-wrap { position: relative; }
        .client-suggestions {
            display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0;
            background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.18); z-index: 60;
            max-height: 270px; overflow-y: auto; text-align: left;
        }
        .client-suggestions.show { display: block; }
        .client-suggestions .sug-item {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f1f5f9;
        }
        .client-suggestions .sug-item:last-child { border-bottom: none; }
        .client-suggestions .sug-item:hover, .client-suggestions .sug-item.active { background: #f0f6ff; }
        .client-suggestions .sug-name { font-weight: 600; font-size: 0.88rem; color: #1e293b; }
        .client-suggestions .sug-sub { font-size: 0.74rem; color: #94a3b8; margin-top: 2px; }
        .sug-badge { font-size: 0.66rem; font-weight: 700; padding: 3px 8px; border-radius: 999px; white-space: nowrap; }
        .sug-badge.approved { background: #d1fae5; color: #065f46; }
        .sug-badge.pending { background: #fef3c7; color: #92400e; }
        .sug-badge.other { background: #dbeafe; color: #1e40af; }
        .sug-badge.none { background: #f1f5f9; color: #64748b; }
        .lock-note {
            display: flex; align-items: center; gap: 8px;
            background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;
            padding: 10px 12px; font-size: 0.78rem; color: #166534; margin-bottom: 16px;
        }
        .btn-check {
            width: 100%; padding: 13px; background: #0E9F6E; color: #fff; border: none;
            border-radius: 10px; font-size: 1rem; font-weight: 700; font-family: inherit;
            cursor: pointer; transition: background .15s;
        }
        .btn-check:hover { background: #0B815A; }
        .btn-check:disabled { opacity: 0.6; cursor: not-allowed; }

        .alert { border-radius: 10px; padding: 12px 14px; font-size: 0.85rem; margin-top: 16px; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-error.dim { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

        .result-head {
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
            margin-bottom: 16px;
        }
        .result-head h3 { font-size: 1.1rem; font-weight: 700; }
        .status-badge { font-size: 0.72rem; font-weight: 700; padding: 5px 12px; border-radius: 999px; }
        .status-badge.approved { background: #d1fae5; color: #065f46; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.other { background: #dbeafe; color: #1e40af; }
        .status-badge.none { background: #f1f5f9; color: #64748b; }

        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 18px; }
        .stat-box {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;
        }
        .stat-box .stat-label { font-size: 0.7rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; }
        .stat-box .stat-value { font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-top: 4px; word-break: break-word; }
        .stat-box .stat-value.green { color: #0E9F6E; }

        .details-toggle {
            background: none; border: none; color: #0E9F6E; font-size: 0.82rem; font-weight: 600;
            cursor: pointer; font-family: inherit; padding: 0;
        }
        .details-toggle:hover { text-decoration: underline; }
        .details-wrap { overflow-x: auto; margin-top: 12px; display: none; }
        .details-wrap.show { display: block; }
        .details-wrap table { border-collapse: collapse; font-size: 0.78rem; white-space: nowrap; }
        .details-wrap th, .details-wrap td { border: 1px solid #e2e8f0; padding: 6px 10px; text-align: left; }
        .details-wrap th { background: #f1f5f9; font-weight: 600; }

        .footer { text-align: center; color: rgba(255,255,255,0.7); font-size: 0.78rem; padding: 20px; }
        .footer a { color: rgba(255,255,255,0.9); }

        /* ===== Chatbot Widget ===== */
        .chat-bubble {
            position: fixed; bottom: 22px; right: 22px; z-index: 999;
            width: 60px; height: 60px; border-radius: 50%;
            background: #0E9F6E; color: #fff; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 24px rgba(14,159,110,0.45);
            transition: transform .2s ease;
        }
        .chat-bubble:hover { transform: scale(1.06); }
        .chat-window {
            position: fixed; bottom: 94px; right: 22px; z-index: 999;
            width: 360px; max-width: calc(100vw - 32px); height: 480px; max-height: calc(100vh - 130px);
            background: #fff; border-radius: 16px; overflow: hidden;
            display: none; flex-direction: column;
            box-shadow: 0 24px 60px rgba(0,0,0,0.28);
            border: 1px solid #e2e8f0;
        }
        .chat-window.open { display: flex; }
        .chat-header {
            background: linear-gradient(135deg, #0B3B2E, #0E9F6E);
            color: #fff; padding: 14px 16px; display: flex; align-items: center; gap: 10px;
        }
        .chat-header .chat-avatar {
            width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
        }
        .chat-header strong { font-size: .95rem; display: block; line-height: 1.2; }
        .chat-header span { font-size: .72rem; opacity: .85; }
        .chat-close { margin-left: auto; background: none; border: none; color: #fff; font-size: 1.3rem; cursor: pointer; line-height: 1; }
        .chat-messages {
            flex: 1; overflow-y: auto; padding: 16px; background: #f8fafc;
            display: flex; flex-direction: column; gap: 10px;
        }
        .chat-msg {
            max-width: 82%; padding: 10px 14px; border-radius: 14px;
            font-size: .85rem; line-height: 1.5; white-space: pre-wrap; word-break: break-word;
        }
        .chat-msg.user {
            align-self: flex-end; background: #0E9F6E; color: #fff;
            border-bottom-right-radius: 4px;
        }
        .chat-msg.bot {
            align-self: flex-start; background: #fff; color: #1e293b;
            border: 1px solid #e2e8f0; border-bottom-left-radius: 4px;
        }
        .chat-msg.bot.typing { color: #94a3b8; font-style: italic; }
        .chat-input-bar {
            display: flex; gap: 8px; padding: 12px; border-top: 1px solid #e2e8f0; background: #fff;
        }
        .chat-input-bar input {
            flex: 1; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: .9rem; font-family: inherit; outline: none;
        }
        .chat-input-bar input:focus { border-color: #0E9F6E; }
        .chat-input-bar button {
            padding: 10px 18px; border: none; border-radius: 10px;
            background: #0E9F6E; color: #fff; font-weight: 600; cursor: pointer; font-family: inherit;
        }
        .chat-input-bar button:disabled { opacity: .6; cursor: not-allowed; }
        @media (max-width: 480px) {
            .chat-window { right: 10px; left: 10px; width: auto; bottom: 84px; }
            .chat-bubble { right: 16px; bottom: 16px; }
        }
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
        <div>
            <a href="<?= BASE_URL ?>/kiosk/">Kiosk</a>
            <a href="<?= BASE_URL ?>/display/">Live Display</a>
        </div>
    </div>

    <div class="container">
        <div class="hero">
            <h1>Check Your Loan</h1>
            <p>Ipasok ang inyong <strong>buong pangalan</strong> at <strong>contact number</strong> para makita ang inyong loan information.</p>
        </div>

        <div class="card">
            <h2>Enter Your Information</h2>
            <p class="hint">Ang inyong contact number ang inyong personal access code — kaya ito lamang ang makakatingin ng inyong data.</p>

            <div class="lock-note">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Ligtas po ang inyong impormasyon. Hindi ito makikita ng iba maliban kung alam nila ang inyong number.
            </div>

            <div class="form-group">
                <label for="name">Full Name *</label>
                <div class="name-suggest-wrap">
                    <input type="text" id="name" placeholder="e.g., Juan Dela Cruz" autocomplete="off">
                    <div class="client-suggestions" id="clientSuggestions"></div>
                </div>
            </div>
            <div class="form-group">
                <label for="number">Contact Number (access code) *</label>
                <input type="tel" id="number" placeholder="e.g., 09171234567" autocomplete="off">
            </div>

            <button class="btn-check" id="btnSendOtp">SEND VERIFICATION CODE</button>
            <div id="alertBox"></div>

            <!-- OTP Step -->
            <div id="otpStep" style="display:none; margin-top:18px;">
                <div class="otp-note" style="display:flex; align-items:center; gap:8px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:10px 12px; font-size:0.78rem; color:#1e40af; margin-bottom:14px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Nagpadala kami ng <strong>6-digit verification code</strong> sa inyong number. Ipasok ito dito para makita ang inyong loan info.
                </div>
                <div class="form-group">
                    <label for="otp">Verification Code *</label>
                    <input type="text" id="otp" placeholder="e.g., 123456" autocomplete="off" inputmode="numeric" maxlength="6" style="letter-spacing:0.35em; font-size:1.2rem; text-align:center; font-weight:700;">
                </div>
                <button class="btn-check" id="btnVerify" style="background:#2563EB;">VERIFY &amp; VIEW MY LOAN</button>
                <div style="text-align:center; margin-top:12px;">
                    <button class="details-toggle" id="btnResend" style="color:#2563EB;">Hindi nakatanggap? Ipadala muli</button>
                </div>
            </div>
        </div>

        <!-- Result -->
        <div class="card" id="resultCard" style="display:none;">
            <!-- Success prompt: lalabas muna bago ang loan details -->
            <div id="otpSuccess" style="display:none; align-items:center; gap:14px; background:linear-gradient(135deg,#f0fdf4,#d1fae5); border:1.5px solid #6ee7b7; border-radius:14px; padding:20px 22px; margin-bottom:20px;">
                <div style="width:52px; height:52px; border-radius:50%; background:#10B981; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div>
                    <div style="font-size:1.05rem; font-weight:800; color:#065F46;">VERIFICATION SUCCESSFUL!</div>
                    <div style="font-size:0.85rem; color:#047857; margin-top:2px;">Na-verify ang inyong code. Ipinapakita na ang inyong loan details...</div>
                </div>
            </div>

            <div class="result-head">
                <h3 id="resultName"></h3>
                <span class="status-badge" id="resultStatus"></span>
            </div>
            <div class="stat-grid" id="resultStats"></div>

            <!-- CBU / VS Balances -->
            <div id="cbuSection" style="display:none; margin-top:18px;">
                <h4 style="font-size:0.9rem; font-weight:700; color:#0B3B2E; margin-bottom:10px; text-align:left;">Voluntary Savings (VS) &amp; Credit Build-Up (CBU)</h4>
                <div class="stat-grid" id="cbuStats"></div>
            </div>

            <!-- Loan List (kung maraming loans) -->
            <div id="loansSection" style="display:none; margin-top:18px;">
                <h4 style="font-size:0.9rem; font-weight:700; color:#0B3B2E; margin-bottom:10px; text-align:left;">All Loans</h4>
                <div style="overflow-x:auto; text-align:left;">
                    <table style="border-collapse:collapse; font-size:0.78rem; width:100%; white-space:nowrap;">
                        <thead><tr id="loansHead"></tr></thead>
                        <tbody id="loansBody"></tbody>
                    </table>
                </div>
            </div>

            <button class="details-toggle" id="detailsToggle">View Full Details &#9660;</button>
            <div class="details-wrap" id="detailsWrap"></div>
        </div>
    </div>

    <div class="footer">
        &copy; <?= date('Y') ?> <?= htmlspecialchars($appName) ?> &middot; Smart Loan Queue Management
    </div>

    <script>
        const btnSendOtp = document.getElementById('btnSendOtp');
        const btnVerify = document.getElementById('btnVerify');
        const btnResend = document.getElementById('btnResend');
        const alertBox = document.getElementById('alertBox');
        const otpStep = document.getElementById('otpStep');

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function(m) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
            });
        }
        function fmtMoney(v) {
            const n = parseFloat(v);
            return isNaN(n) ? (v || '') : '&#8369;' + Number(n).toLocaleString('en-PH');
        }

        document.getElementById('number').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });

        // ---------------- Name autocomplete from the masterlist ----------------
        // Note: the contact number is NOT shown in the suggestions (privacy — it's
        // the client's personal access code). The user types their own number.
        const nameField = document.getElementById('name');
        const suggBox = document.getElementById('clientSuggestions');
        let suggTimer = null;
        let suggItems = [];
        let suggActive = -1;

        nameField.addEventListener('input', function() {
            clearTimeout(suggTimer);
            const q = this.value.trim();
            if (q.length < 2) { hideSuggestions(); return; }
            suggTimer = setTimeout(() => searchClients(q), 250);
        });

        async function searchClients(q) {
            try {
                const res = await fetch('<?= BASE_URL ?>/api/queue/client-search.php?q=' + encodeURIComponent(q))
                    .then(r => r.json());
                if (!res.success) { hideSuggestions(); return; }
                renderSuggestions(res.data.clients);
            } catch (e) { hideSuggestions(); }
        }

        function renderSuggestions(clients) {
            suggItems = clients || [];
            suggActive = -1;
            if (suggItems.length === 0) { hideSuggestions(); return; }

            let html = '';
            suggItems.forEach((c, i) => {
                const status = c.loan_status;
                let badge = '<span class="sug-badge none">No loan</span>';
                if (status === 'approved') badge = '<span class="sug-badge approved">APPROVED</span>';
                else if (status === 'pending') badge = '<span class="sug-badge pending">PENDING</span>';
                else if (status) badge = '<span class="sug-badge other">' + esc(status.toUpperCase()) + '</span>';

                html += '<div class="sug-item" data-index="' + i + '">';
                html += '<div><div class="sug-name">' + esc(c.full_name) + '</div>';
                html += '<div class="sug-sub">' + (c.address ? esc(c.address) : '') + '</div></div>';
                html += badge + '</div>';
            });

            suggBox.innerHTML = html;
            suggBox.classList.add('show');
            suggBox.querySelectorAll('.sug-item').forEach(el => {
                el.addEventListener('mousedown', e => {
                    e.preventDefault();
                    selectSuggestion(parseInt(el.dataset.index, 10));
                });
            });
        }

        function selectSuggestion(i) {
            const c = suggItems[i];
            if (!c) return;
            nameField.value = c.full_name;
            hideSuggestions();
        }

        function hideSuggestions() {
            suggBox.classList.remove('show');
            suggBox.innerHTML = '';
            suggItems = [];
            suggActive = -1;
        }

        function highlightSug() {
            suggBox.querySelectorAll('.sug-item').forEach((el, i) => {
                el.classList.toggle('active', i === suggActive);
            });
        }

        nameField.addEventListener('keydown', function(e) {
            if (!suggBox.classList.contains('show') || suggItems.length === 0) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); suggActive = Math.min(suggActive + 1, suggItems.length - 1); highlightSug(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); suggActive = Math.max(suggActive - 1, 0); highlightSug(); }
            else if (e.key === 'Enter' && suggActive >= 0) { e.preventDefault(); selectSuggestion(suggActive); }
            else if (e.key === 'Escape') { hideSuggestions(); }
        });

        nameField.addEventListener('blur', function() {
            setTimeout(hideSuggestions, 150);
        });

        function getFormValues() {
            return {
                name: document.getElementById('name').value.trim(),
                number: document.getElementById('number').value.trim()
            };
        }

        function validateForm(values) {
            if (values.name.length < 2) { showAlert('Please enter your full name.', 'error'); return false; }
            if (values.number.replace(/\D/g, '').length < 10) { showAlert('Please enter a valid contact number.', 'error'); return false; }
            return true;
        }

        /** Step 1: send the verification code */
        async function sendOtp() {
            const values = getFormValues();
            alertBox.innerHTML = '';
            if (!validateForm(values)) return;

            btnSendOtp.disabled = true;
            btnSendOtp.textContent = 'SENDING...';
            try {
                const formData = new FormData();
                formData.append('name', values.name);
                formData.append('number', values.number);
                const res = await fetch('<?= BASE_URL ?>/api/website/send-otp.php', { method: 'POST', body: formData })
                    .then(r => r.json());

                if (res.success) {
                    document.getElementById('resultCard').style.display = 'none';
                    document.getElementById('otpSuccess').style.display = 'none';
                    otpStep.style.display = 'block';
                    document.getElementById('otp').value = '';
                    document.getElementById('otp').focus();
                    showAlert(res.message, 'success');
                    // Demo mode: show the code so local testing works without real SMS
                    if (res.data && res.data.demo && res.data.demo_otp) {
                        showAlert('DEMO MODE: Ang inyong code ay <strong>' + esc(res.data.demo_otp) + '</strong>', 'success', false, true);
                    }
                } else {
                    showAlert(res.message, 'error', true);
                    document.getElementById('resultCard').style.display = 'none';
                    otpStep.style.display = 'none';
                }
            } catch (e) {
                showAlert('A network error occurred. Please try again.', 'error');
            } finally {
                btnSendOtp.disabled = false;
                btnSendOtp.textContent = 'SEND VERIFICATION CODE';
            }
        }

        /** Step 2: verify the code and show the loan info */
        async function verifyOtp() {
            const values = getFormValues();
            const otp = document.getElementById('otp').value.trim();
            alertBox.innerHTML = '';

            if (!validateForm(values)) return;
            if (otp.length < 4) { showAlert('Please enter the verification code.', 'error'); return; }

            btnVerify.disabled = true;
            btnVerify.textContent = 'VERIFYING...';
            try {
                const formData = new FormData();
                formData.append('name', values.name);
                formData.append('number', values.number);
                formData.append('otp', otp);
                const res = await fetch('<?= BASE_URL ?>/api/website/verify-otp.php', { method: 'POST', body: formData })
                    .then(r => r.json());

                if (res.success) {
                    // 1) Ipakita muna ang SUCCESS prompt
                    otpStep.style.display = 'none';
                    document.getElementById('otpSuccess').style.display = 'flex';
                    document.getElementById('resultCard').style.display = 'block';
                    document.getElementById('resultCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    btnVerify.disabled = true;
                    // 2) Pagkatapos ng 1.5s, saka lumabas ang loan details
                    setTimeout(() => {
                        renderResult(res.data);
                    }, 1500);
                } else {
                    showAlert(res.message, 'error', true);
                }
            } catch (e) {
                showAlert('A network error occurred. Please try again.', 'error');
            } finally {
                btnVerify.disabled = false;
                btnVerify.textContent = 'VERIFY & VIEW MY LOAN';
            }
        }

        btnSendOtp.addEventListener('click', sendOtp);
        btnVerify.addEventListener('click', verifyOtp);
        btnResend.addEventListener('click', sendOtp);
        document.getElementById('otp').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') verifyOtp();
        });

        function showAlert(msg, type, dim, html) {
            const cls = type === 'error' ? 'alert-error' : (type === 'success' ? 'alert-success' : '');
            const content = html ? msg : esc(msg);
            alertBox.innerHTML = '<div class="alert ' + cls + (dim ? ' dim' : '') + '">' + content + '</div>';
        }

        function renderResult(data) {
            document.getElementById('resultCard').style.display = 'block';
            const c = data.client || {};
            const l = data.loan || {};
            const loans = data.loans || [];
            const cbu = data.cbu || null;

            document.getElementById('resultName').textContent = c.full_name || '';

            const statusEl = document.getElementById('resultStatus');
            const st = c.loan_status;
            if (st === 'approved') { statusEl.className = 'status-badge approved'; statusEl.textContent = 'APPROVED'; }
            else if (st === 'pending') { statusEl.className = 'status-badge pending'; statusEl.textContent = 'PENDING'; }
            else if (st) { statusEl.className = 'status-badge other'; statusEl.textContent = (c.loan_status_label || st).toUpperCase(); }
            else { statusEl.className = 'status-badge none'; statusEl.textContent = 'NO LOAN ON RECORD'; }

            const stats = [
                { label: 'Loan Amount', value: fmtMoney(l.loan_amount), green: true },
                { label: 'Principal Balance', value: fmtMoney(l.principal_balance) },
                { label: 'Loan Product', value: esc(l.loan_product || '-') },
                { label: 'Loan Reference', value: esc(l.loan_reference || '-') },
                { label: 'Date Granted', value: esc(l.date_granted || '-') },
                { label: 'Due Date', value: esc(l.date_due || '-') }
            ];
            document.getElementById('resultStats').innerHTML = stats.map(s =>
                '<div class="stat-box"><div class="stat-label">' + s.label + '</div>' +
                '<div class="stat-value' + (s.green ? ' green' : '') + '">' + s.value + '</div></div>'
            ).join('');

            // CBU / VS section
            const cbuSection = document.getElementById('cbuSection');
            if (cbu) {
                const cbuStats = [
                    { label: 'VS Balance', value: fmtMoney(cbu.vs_balance) },
                    { label: 'VS Deposits', value: fmtMoney(cbu.vs_deposits) },
                    { label: 'VS Withdrawals', value: fmtMoney(cbu.vs_withdrawals) },
                    { label: 'CBU Balance', value: fmtMoney(cbu.cbu_balance), green: true },
                    { label: 'CBU Deposits', value: fmtMoney(cbu.cbu_deposits) },
                    { label: 'CBU Withdrawals', value: fmtMoney(cbu.cbu_withdrawals) }
                ];
                document.getElementById('cbuStats').innerHTML = cbuStats.map(s =>
                    '<div class="stat-box"><div class="stat-label">' + s.label + '</div>' +
                    '<div class="stat-value' + (s.green ? ' green' : '') + '">' + s.value + '</div></div>'
                ).join('');
                cbuSection.style.display = 'block';
            } else {
                cbuSection.style.display = 'none';
            }

            // All loans table
            const loansSection = document.getElementById('loansSection');
            if (loans.length > 0) {
                const head = ['Product', 'Category', 'Cycle', 'Amount', 'Principal Bal.', 'Interest Bal.', 'Arrears', 'Released', 'Matured'];
                document.getElementById('loansHead').innerHTML = head.map(h => '<th style="background:#f1f5f9; padding:6px 10px; border:1px solid #e2e8f0;">' + esc(h) + '</th>').join('');
                document.getElementById('loansBody').innerHTML = loans.map(lr => {
                    const cells = [
                        lr.loan_product, lr.loan_category, lr.cycle_no,
                        fmtMoney(lr.principal), fmtMoney(lr.principal_balance),
                        fmtMoney(lr.interest_balance), fmtMoney(lr.total_arrears),
                        lr.date_release ? new Date(lr.date_release).toLocaleDateString('en-PH') : '-',
                        lr.date_matured ? new Date(lr.date_matured).toLocaleDateString('en-PH') : '-'
                    ];
                    return '<tr>' + cells.map(x => '<td style="padding:6px 10px; border:1px solid #e2e8f0;">' + esc(x) + '</td>').join('') + '</tr>';
                }).join('');
                loansSection.style.display = 'block';
            } else {
                loansSection.style.display = 'none';
            }

            // Full details table (Excel-style raw data)
            const raw = data.raw || {};
            const keys = Object.keys(raw);
            let html = '<table><thead><tr>';
            keys.forEach(k => { html += '<th>' + esc(k) + '</th>'; });
            html += '</tr></thead><tbody><tr>';
            keys.forEach(k => { html += '<td>' + esc(raw[k]) + '</td>'; });
            html += '</tr></tbody></table>';
            document.getElementById('detailsWrap').innerHTML = html;
            document.getElementById('detailsWrap').classList.remove('show');
            document.getElementById('detailsToggle').textContent = 'View Full Details \u25BC';

            alertBox.innerHTML = '';
            document.getElementById('resultCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        document.getElementById('detailsToggle').addEventListener('click', function() {
            const wrap = document.getElementById('detailsWrap');
            const show = !wrap.classList.contains('show');
            wrap.classList.toggle('show', show);
            this.textContent = show ? 'Hide Full Details \u25B2' : 'View Full Details \u25BC';
        });
    </script>

    <!-- ===== Chatbot Widget ===== -->
    <button class="chat-bubble" id="chatBubble" aria-label="Open chat">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    </button>
    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <div class="chat-avatar">🤖</div>
            <div>
                <strong>SFI Assistant</strong>
                <span>Online • sumasagot kaagad</span>
            </div>
            <button class="chat-close" id="chatClose" aria-label="Close chat">&times;</button>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div class="chat-msg bot">Magandang araw! 👋 Ako si SFI Assistant. Paano po kita matutulungan? Halimbawa: "Paano kumuha ng queue number?" o "Ano ang mga loan products ninyo?"</div>
        </div>
        <div class="chat-input-bar">
            <input type="text" id="chatInput" placeholder="Mag-type ng tanong..." autocomplete="off">
            <button id="chatSend">Send</button>
        </div>
    </div>
    <script>
    (function () {
        const bubble = document.getElementById('chatBubble');
        const windowEl = document.getElementById('chatWindow');
        const closeBtn = document.getElementById('chatClose');
        const messagesEl = document.getElementById('chatMessages');
        const input = document.getElementById('chatInput');
        const sendBtn = document.getElementById('chatSend');

        function toggle(open) {
            windowEl.classList.toggle('open', open);
            if (open) input.focus();
        }
        bubble.addEventListener('click', () => toggle(!windowEl.classList.contains('open')));
        closeBtn.addEventListener('click', () => toggle(false));

        function addMsg(text, who) {
            const div = document.createElement('div');
            div.className = 'chat-msg ' + who;
            div.textContent = text;
            messagesEl.appendChild(div);
            messagesEl.scrollTop = messagesEl.scrollHeight;
            return div;
        }

        async function send() {
            const text = input.value.trim();
            if (!text) return;
            input.value = '';
            addMsg(text, 'user');

            const typing = addMsg('Tina-type ang sagot...', 'bot typing');
            sendBtn.disabled = true;

            try {
                const fd = new FormData();
                fd.append('message', text);
                const res = await fetch('<?= BASE_URL ?>/api/chat/send.php', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                typing.remove();
                if (data.success) {
                    addMsg(data.data.reply, 'bot');
                } else {
                    addMsg(data.message || 'Pasensya na, may error. Subukan ulit.', 'bot');
                }
            } catch (err) {
                typing.remove();
                addMsg('Pasensya na, may problema sa koneksyon. Subukan ulit.', 'bot');
            }
            sendBtn.disabled = false;
            input.focus();
        }

        sendBtn.addEventListener('click', send);
        input.addEventListener('keydown', (e) => { if (e.key === 'Enter') send(); });
    })();
    </script>

    <!--Start of Tawk.to Script-->
    <script type="text/javascript">
    var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
    // Position the Tawk.to widget on the bottom-LEFT so it does not overlap the AI chatbot (bottom-right)
    Tawk_API.customStyle = {
        visibility: {
            desktop: { xOffset: '16px', yOffset: '16px', position: 'bl' },
            mobile:  { xOffset: '10px', yOffset: '10px', position: 'bl' }
        }
    };
    (function(){
    var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
    s1.async=true;
    s1.src='https://embed.tawk.to/6a7eb5269429b61d4ff63767/1jvvf77rd';
    s1.charset='UTF-8';
    s1.setAttribute('crossorigin','*');
    s0.parentNode.insertBefore(s1,s0);
    })();
    </script>
    <!--End of Tawk.to Script-->
</body>
</html>
