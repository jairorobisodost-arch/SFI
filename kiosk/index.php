<?php
/**
 * SFI Queuing System - Client Self-Service Kiosk
 * URL: /kiosk/
 */

require_once __DIR__ . '/../includes/bootstrap.php';
initPage();

// Fetch active TRANSACTION types for the card selector (loan products are separate)
$db = Database::getConnection();
$stmt = $db->query("SELECT id, name, prefix, description FROM loan_types WHERE status = 'active' AND category = 'transaction' AND is_archived = 0 ORDER BY id ASC");
$loanTypes = $stmt->fetchAll();

$icons = [
    'PY' => '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>',
    'RL' => '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3.3 8.3L12 13l8.7-4.7"/><path d="M12 22V13"/></svg>',
    'CS' => '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiosk - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/kiosk.css">
    <script src="<?= BASE_URL ?>/assets/js/qrcode.min.js"></script>
    <style>
    .loan-status-banner { margin: 0 0 14px; padding: 12px 14px; border-radius: 10px; font-size: 0.9rem; font-weight: 600; text-align: left; line-height: 1.45; }
    .loan-status-banner.status-approved { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .loan-status-banner.status-pending { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
    .loan-status-banner.status-other { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }

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

    /* ===== Chatbot Widget (same as website) ===== */
    .chat-bubble {
        position: fixed; bottom: 20px; right: 20px; z-index: 999;
        width: 56px; height: 56px; border-radius: 50%;
        background: #0E9F6E; color: #fff; border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 8px 24px rgba(14,159,110,0.45);
        transition: transform .2s ease;
    }
    .chat-bubble:hover { transform: scale(1.06); }
    .chat-window {
        position: fixed; bottom: 88px; right: 20px; z-index: 999;
        width: 340px; max-width: calc(100vw - 32px); height: 440px; max-height: calc(100vh - 130px);
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
        .chat-window { right: 10px; left: 10px; width: auto; bottom: 80px; }
        .chat-bubble { right: 16px; bottom: 14px; }
    }
    </style>
</head>
<body>
    <div class="kiosk-wrapper">
        <!-- Header -->
        <div class="kiosk-header">
            <h1><?= APP_NAME ?></h1>
            <p>Please enter your information and select your transaction.</p>
        </div>

        <!-- Body -->
        <div class="kiosk-body">
            <form id="kioskForm" class="kiosk-form" autocomplete="off">
                <!-- Client Information -->
                <div class="kiosk-form-section">
                    <h3>Client Information</h3>

                    <div class="form-group">
                        <label for="clientName">Full Name <span class="text-danger">*</span></label>
                        <div class="name-suggest-wrap">
                            <input type="text" id="clientName" name="client_name" class="form-control"
                                   placeholder="e.g., Juan Dela Cruz" maxlength="150" required autocomplete="off">
                            <div class="client-suggestions" id="clientSuggestions"></div>
                        </div>
                        <div class="form-error" id="nameError"></div>
                    </div>

                    <div class="form-group">
                        <label for="contactNumber">Contact Number <span class="text-danger">*</span></label>
                        <input type="tel" id="contactNumber" name="contact_number" class="form-control"
                               placeholder="e.g., 09171234567" maxlength="11" required>
                        <div class="form-error" id="contactError"></div>
                    </div>
                </div>

                <!-- Loan Type Selection -->
                <div class="kiosk-form-section">
                    <h3>Select Transaction Type</h3>
                    <div class="loan-type-grid" id="loanTypeGrid">
                        <?php foreach ($loanTypes as $lt): ?>
                        <div class="loan-type-card" data-id="<?= $lt['id'] ?>" data-prefix="<?= $lt['prefix'] ?>" tabindex="0" role="button" aria-label="Select <?= htmlspecialchars($lt['name']) ?>">
                            <span class="card-icon"><?= $icons[$lt['prefix']] ?? '&#128196;' ?></span>
                            <div class="card-name"><?= htmlspecialchars($lt['name']) ?></div>
                            <span class="card-prefix"><?= $lt['prefix'] ?></span>
                            <div class="card-desc"><?= htmlspecialchars($lt['description']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="selectedLoanType" name="loan_type_id" value="">
                    <div class="form-error" id="loanTypeError"></div>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn btn-primary kiosk-submit" id="btnSubmit">
                    <span id="btnSubmitText">GET QUEUE NUMBER</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Success Modal Overlay -->
    <div class="kiosk-success-overlay" id="successOverlay">
        <div class="kiosk-success-card">
            <div class="success-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <h2>Your Queue Number</h2>
            <div class="ticket-display" id="successTicket">PL-001</div>
            <div class="loan-name" id="successLoanType">Personal Loan</div>
            <div class="client-name" id="successClient">Juan Dela Cruz</div>
            <div class="loan-status-banner" id="loanStatusBanner" style="display:none;"></div>
            <div class="wait-info" id="successWait">
                <span id="successWaitText">Please wait for your number to be called.</span>
            </div>
            <div style="color:var(--muted); font-size:0.85rem; margin-bottom:16px;">
                <span id="successDateTime"></span>
            </div>
            <div class="kiosk-qr-wrap" id="successQrWrap" style="display:none;">
                <div style="font-size:0.75rem; color:var(--muted); margin-bottom:6px;">SCAN TO TRACK YOUR QUEUE</div>
                <div id="successQr" style="background:#fff; padding:8px; border-radius:10px; display:inline-block;"></div>
            </div>
            <div class="success-buttons">
                <button class="btn btn-primary" onclick="printTicket()">
                    PRINT TICKET
                </button>
                <button class="btn btn-secondary" onclick="resetKiosk()">
                    DONE
                </button>
            </div>
        </div>
    </div>

    <!-- Print-only Ticket -->
    <div class="print-ticket" id="printTicket">
        <div class="ticket-header">
            <h2>SFI QUEUING SYSTEM</h2>
            <p>Smart Loan Queue Management</p>
        </div>
        <div style="text-align:center; padding:4px 0; font-size:11px;">YOUR NUMBER</div>
        <div class="ticket-number" id="printNumber">PL-001</div>
        <div class="ticket-details">
            <p><strong id="printLoanType">PERSONAL LOAN</strong></p>
            <p>Client: <span id="printClient">Juan Dela Cruz</span></p>
            <p>Date: <span id="printDate">August 10, 2026</span></p>
            <p>Time: <span id="printTime">3:24 PM</span></p>
        </div>
        <div class="ticket-qr-wrap">
            <div style="text-align:center; font-size:9px; margin-bottom:4px;">SCAN TO TRACK QUEUE STATUS</div>
            <div id="printQr" style="display:flex; justify-content:center;"></div>
        </div>
        <div class="ticket-footer">
            <p>Please wait for your number to be called.</p>
            <p>Thank you!</p>
        </div>
    </div>

    <script>
    (function() {
        const form = document.getElementById('kioskForm');
        const btnSubmit = document.getElementById('btnSubmit');
        const btnSubmitText = document.getElementById('btnSubmitText');
        let selectedLoanTypeId = '';
        let isSubmitting = false;
        let lastTicketData = null;

        // Loan type card selection
        document.querySelectorAll('.loan-type-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.loan-type-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                selectedLoanTypeId = this.dataset.id;
                document.getElementById('selectedLoanType').value = selectedLoanTypeId;
                clearError('loanTypeError');
            });

            // Keyboard accessibility
            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });

        // Contact number: only allow digits
        document.getElementById('contactNumber').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 11);
        });

        // ---------------- Name autocomplete from the imported masterlist ----------------
        const nameInput = document.getElementById('clientName');
        const suggBox = document.getElementById('clientSuggestions');
        let suggTimer = null;
        let suggItems = [];
        let suggActive = -1;

        function escHtml(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function(m) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
            });
        }

        nameInput.addEventListener('input', function() {
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
                else if (status) badge = '<span class="sug-badge other">' + escHtml(status.toUpperCase()) + '</span>';

                html += '<div class="sug-item" data-index="' + i + '">';
                html += '<div><div class="sug-name">' + escHtml(c.full_name) + '</div>';
                html += '<div class="sug-sub">' + (c.contact_number ? escHtml(c.contact_number) : '') +
                        (c.address ? ' &middot; ' + escHtml(c.address) : '') + '</div></div>';
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
            nameInput.value = c.full_name;
            const contactField = document.getElementById('contactNumber');
            if (!contactField.value) {
                contactField.value = String(c.contact_number || '').replace(/\D/g, '').substring(0, 11);
            }
            hideSuggestions();
            clearError('nameError');
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

        nameInput.addEventListener('keydown', function(e) {
            if (!suggBox.classList.contains('show') || suggItems.length === 0) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); suggActive = Math.min(suggActive + 1, suggItems.length - 1); highlightSug(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); suggActive = Math.max(suggActive - 1, 0); highlightSug(); }
            else if (e.key === 'Enter' && suggActive >= 0) { e.preventDefault(); selectSuggestion(suggActive); }
            else if (e.key === 'Escape') { hideSuggestions(); }
        });

        nameInput.addEventListener('blur', function() {
            setTimeout(hideSuggestions, 150);
        });

        // Form submission
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (isSubmitting) return;

            // Clear errors
            clearError('nameError');
            clearError('contactError');
            clearError('loanTypeError');

            // Validate
            const name = document.getElementById('clientName').value.trim();
            const contact = document.getElementById('contactNumber').value.trim();
            let hasError = false;

            if (!name || name.length < 2) {
                showError('nameError', 'Please enter your full name.');
                hasError = true;
            }

            if (!contact || !/^\d{11}$/.test(contact)) {
                showError('contactError', 'Contact number must be exactly 11 digits.');
                hasError = true;
            }

            if (!selectedLoanTypeId) {
                showError('loanTypeError', 'Please select a transaction type.');
                hasError = true;
            }

            if (hasError) return;

            // Disable button, show loading
            isSubmitting = true;
            btnSubmit.disabled = true;
            btnSubmit.classList.add('btn-loading');
            btnSubmitText.textContent = 'PROCESSING...';

            try {
                const formData = new FormData();
                formData.append('client_name', name);
                formData.append('contact_number', contact);
                formData.append('loan_type_id', selectedLoanTypeId);

                const response = await fetch('<?= BASE_URL ?>/api/queue/create.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();

                if (data.success) {
                    lastTicketData = data.data;
                    showSuccess(data.data);
                } else {
                    alert(data.message);
                    resetButton();
                }
            } catch (err) {
                alert('A network error occurred. Please try again.');
                resetButton();
            }
        });

        function showError(id, msg) {
            const el = document.getElementById(id);
            el.textContent = msg;
            el.classList.add('visible');
        }

        function clearError(id) {
            const el = document.getElementById(id);
            el.textContent = '';
            el.classList.remove('visible');
        }

        function resetButton() {
            isSubmitting = false;
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('btn-loading');
            btnSubmitText.textContent = 'GET QUEUE NUMBER';
        }

        function showSuccess(data) {
            document.getElementById('successTicket').textContent = data.ticket_number;
            document.getElementById('successLoanType').textContent = data.loan_type;
            document.getElementById('successClient').textContent = data.client_name;
            document.getElementById('successDateTime').textContent = data.date + ' at ' + data.time;

            // Show the client's loan status banner if found in the masterlist
            const lookup = data.client_lookup;
            const banner = document.getElementById('loanStatusBanner');
            if (lookup && lookup.found && lookup.loan_status) {
                let msg = '', cls = '';
                if (lookup.loan_status === 'approved') {
                    cls = 'status-approved';
                    msg = 'Congratulations, ' + lookup.full_name + '! Approved na po ang inyong loan' +
                          (lookup.loan_type ? ' (' + lookup.loan_type + ')' : '') +
                          '. Pumunta po kayo sa Release counter.';
                } else if (lookup.loan_status === 'pending') {
                    cls = 'status-pending';
                    msg = 'Ang inyong loan application' + (lookup.loan_type ? ' (' + lookup.loan_type + ')' : '') +
                          ' ay under review pa po. Abangan po ang abiso.';
                } else {
                    cls = 'status-other';
                    msg = 'Welcome back, ' + lookup.full_name + '!' +
                          (lookup.loan_type ? ' Inyong loan: ' + lookup.loan_type : '');
                }
                banner.textContent = msg;
                banner.className = 'loan-status-banner ' + cls;
                banner.style.display = 'block';
            } else {
                banner.style.display = 'none';
            }

            if (data.waiting_ahead > 0) {
                document.getElementById('successWaitText').textContent =
                    'There ' + (data.waiting_ahead === 1 ? 'is' : 'are') + ' ' + data.waiting_ahead +
                    ' client' + (data.waiting_ahead > 1 ? 's' : '') + ' ahead of you.';
            } else {
                document.getElementById('successWaitText').textContent = 'You are next in line!';
            }

            // Populate print ticket
            document.getElementById('printNumber').textContent = data.ticket_number;
            document.getElementById('printLoanType').textContent = data.loan_type.toUpperCase();
            document.getElementById('printClient').textContent = data.client_name;
            document.getElementById('printDate').textContent = data.date;
            document.getElementById('printTime').textContent = data.time;

            // Generate QR code pointing to the public ticket status page
            generateQr(data.ticket_number);

            document.getElementById('successOverlay').classList.add('active');

            // Auto-print after 1 second
            setTimeout(function() {
                window.print();
            }, 1000);

            // Auto-reset after configured time (default 6 seconds after print)
            setTimeout(function() {
                resetKiosk();
            }, 8000);
        }

        function generateQr(ticketNumber) {
            var base = '<?= BASE_URL ?>';
            var url = base + '/website/ticket-status.php?ticket=' + encodeURIComponent(ticketNumber);
            var qrEl = document.getElementById('printQr');
            var successEl = document.getElementById('successQr');
            qrEl.innerHTML = '';
            successEl.innerHTML = '';
            try {
                new QRCode(qrEl, {
                    text: url,
                    width: 130,
                    height: 130,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
                new QRCode(successEl, {
                    text: url,
                    width: 140,
                    height: 140,
                    colorDark: '#0B3B2E',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
                document.getElementById('successQrWrap').style.display = 'block';
            } catch (e) {
                console.error('QR generation failed:', e);
            }
        }

        // Expose to global scope for button onclick
        window.printTicket = function() {
            window.print();
        };

        window.resetKiosk = function() {
            document.getElementById('successOverlay').classList.remove('active');
            document.getElementById('loanStatusBanner').style.display = 'none';
            hideSuggestions();
            form.reset();
            selectedLoanTypeId = '';
            document.getElementById('selectedLoanType').value = '';
            document.querySelectorAll('.loan-type-card').forEach(c => c.classList.remove('selected'));
            resetButton();
            lastTicketData = null;
            document.getElementById('successQrWrap').style.display = 'none';
            document.getElementById('printQr').innerHTML = '';
            document.getElementById('successQr').innerHTML = '';
        };
    })();
    </script>

    <!-- ===== Chatbot Widget ===== -->
    <button class="chat-bubble" id="chatBubble" aria-label="Open chat">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    </button>
    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <div class="chat-avatar">🤖</div>
            <div>
                <strong>SFI Assistant</strong>
                <span>May tanong? Itanong mo!</span>
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
</body>
</html>
