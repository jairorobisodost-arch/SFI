<?php
/**
 * SFI Queuing System - Staff/Admin Dashboard
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

requireLogin();
initPage();

$user = getSessionUser();
$isAdmin = ($user['role'] === 'admin');

admin_header('Dashboard', 'dashboard');
?>

<!-- Statistics Cards -->
<div class="stat-grid" id="statGrid">
    <div class="stat-card">
        <div class="stat-icon waiting">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-info">
            <h3 id="statWaiting">0</h3>
            <p>Waiting</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon serving">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-info">
            <h3 id="statServing">0</h3>
            <p>Serving</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon completed">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-info">
            <h3 id="statCompleted">0</h3>
            <p>Completed</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon noshow">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <div class="stat-info">
            <h3 id="statNoShow">0</h3>
            <p>No-Show</p>
        </div>
    </div>
</div>

<!-- Now Serving Panel -->
<div class="now-serving-panel empty" id="nowServingPanel">
    <div class="label" id="nsLabel">NOW SERVING</div>
    <div class="ticket-number" id="nsTicket">---</div>
    <div class="client-name" id="nsClient">No client being served</div>
    <div class="loan-type" id="nsLoanType"></div>
    <div class="counter" id="nsCounter"></div>
</div>

<!-- Staff Control Buttons -->
<div class="control-buttons">
    <button class="control-btn call-next" id="btnCallNext" onclick="callNext()">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
        Call Next
        <span class="btn-label">Next waiting client</span>
    </button>
    <button class="control-btn recall" id="btnRecall" onclick="recallTicket()" disabled>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
        Recall
        <span class="btn-label">Re-announce current</span>
    </button>
    <button class="control-btn complete" id="btnComplete" onclick="completeTicket()" disabled>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Mark Served
        <span class="btn-label">Complete transaction</span>
    </button>
    <button class="control-btn no-show" id="btnNoShow" onclick="markNoShow()" disabled>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        No-Show
        <span class="btn-label">Client did not appear</span>
    </button>
    <button class="control-btn transfer" id="btnTransfer" onclick="showTransferModal()" disabled>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
        Transfer
        <span class="btn-label">To another counter</span>
    </button>
</div>

<!-- Queue Table -->
<div class="card">
    <div class="card-header">
        <h3>Today's Queue</h3>
        <span class="text-sm text-muted" id="lastUpdated">Loading...</span>
    </div>
    <div class="card-body" style="padding:0;">
        <!-- Filter Tabs -->
        <div style="padding: 16px 24px 0;">
            <div class="filter-tabs" id="filterTabs">
                <button class="filter-tab active" data-filter="all">All</button>
                <button class="filter-tab" data-filter="PY">Payment</button>
                <button class="filter-tab" data-filter="RL">Release</button>
                <button class="filter-tab" data-filter="CS">Customer Services</button>
            </div>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Client</th>
                        <th>Loan Type</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th>Counter</th>
                    </tr>
                </thead>
                <tbody id="queueTableBody">
                    <tr>
                        <td colspan="6" class="text-center text-muted" style="padding:40px;">Loading queue data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Transfer Modal -->
<div class="modal-overlay" id="transferModal">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h3>Transfer Ticket</h3>
            <button class="modal-close" onclick="SFI.hideModal('transferModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="transferCounter">Select Counter</label>
                <select id="transferCounter" class="form-control">
                    <option value="1">Counter 1</option>
                    <option value="2">Counter 2</option>
                    <option value="3">Counter 3</option>
                    <option value="4">Counter 4</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="SFI.hideModal('transferModal')">Cancel</button>
            <button class="btn btn-primary" onclick="doTransfer()">Transfer</button>
        </div>
    </div>
</div>

<script>
// ============================================
// Dashboard JavaScript
// ============================================

let currentServingTicket = null;
let currentFilter = 'all';
let allTickets = [];

/**
 * Load dashboard statistics.
 */
async function loadStats() {
    try {
        const res = await SFI.get('/api/queue/statistics.php');
        if (res.success) {
            const s = res.data;
            document.getElementById('statWaiting').textContent = s.waiting || 0;
            document.getElementById('statServing').textContent = s.serving || 0;
            document.getElementById('statCompleted').textContent = s.completed || 0;
            document.getElementById('statNoShow').textContent = s.no_show || 0;
        }
    } catch (e) {
        console.error('Load stats error:', e);
    }
}

/**
 * Load today's queue table.
 */
async function loadQueue() {
    try {
        const res = await SFI.get('/api/queue/today.php');
        if (res.success) {
            allTickets = res.data.tickets || [];
            renderQueueTable();
            updateNowServing(res.data.serving_ticket || null);
            document.getElementById('lastUpdated').textContent = 'Updated: ' + new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        }
    } catch (e) {
        console.error('Load queue error:', e);
    }
}

/**
 * Render the queue table body.
 */
function renderQueueTable() {
    const tbody = document.getElementById('queueTableBody');
    let filtered = allTickets;

    if (currentFilter !== 'all') {
        filtered = allTickets.filter(t => t.prefix === currentFilter);
    }

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted" style="padding:40px;">No tickets found.</td></tr>';
        return;
    }

    let html = '';
    filtered.forEach(t => {
        const badgeClass = {
            'waiting': 'badge-waiting',
            'serving': 'badge-serving',
            'completed': 'badge-completed',
            'no_show': 'badge-no-show',
            'cancelled': 'badge-cancelled',
            'transferred': 'badge-transferred'
        }[t.status] || '';

        html += '<tr>';
        html += '<td><strong>' + SFI.escapeHtml(t.ticket_number) + '</strong></td>';
        html += '<td>' + SFI.escapeHtml(t.client_name) + '</td>';
        html += '<td>' + SFI.escapeHtml(t.loan_type_name || t.prefix) + '</td>';
        html += '<td><span class="badge ' + badgeClass + '">' + t.status.replace('_', ' ') + '</span></td>';
        html += '<td>' + SFI.formatTime(t.created_at) + '</td>';
        html += '<td>' + (t.counter_assigned ? 'Counter ' + t.counter_assigned : '-') + '</td>';
        html += '</tr>';
    });

    tbody.innerHTML = html;
}

/**
 * Update the Now Serving panel.
 */
function updateNowServing(ticket) {
    const panel = document.getElementById('nowServingPanel');
    const btnRecall = document.getElementById('btnRecall');
    const btnComplete = document.getElementById('btnComplete');
    const btnNoShow = document.getElementById('btnNoShow');
    const btnTransfer = document.getElementById('btnTransfer');
    const btnCallNext = document.getElementById('btnCallNext');

    if (ticket && ticket.id) {
        currentServingTicket = ticket;
        panel.classList.remove('empty');
        document.getElementById('nsTicket').textContent = ticket.ticket_number;
        document.getElementById('nsClient').textContent = ticket.client_name;
        document.getElementById('nsLoanType').textContent = ticket.loan_type_name || '';
        document.getElementById('nsCounter').textContent = 'Counter ' + (ticket.counter_assigned || '-');

        // Enable serving controls, disable call next
        btnRecall.disabled = false;
        btnComplete.disabled = false;
        btnNoShow.disabled = false;
        btnTransfer.disabled = false;
        btnCallNext.disabled = true;
    } else {
        currentServingTicket = null;
        panel.classList.add('empty');
        document.getElementById('nsTicket').textContent = '---';
        document.getElementById('nsClient').textContent = 'No client being served';
        document.getElementById('nsLoanType').textContent = '';
        document.getElementById('nsCounter').textContent = '';

        // Disable serving controls, enable call next
        btnRecall.disabled = true;
        btnComplete.disabled = true;
        btnNoShow.disabled = true;
        btnTransfer.disabled = true;
        btnCallNext.disabled = false;
    }
}

/**
 * CALL NEXT - Call the next waiting client.
 */
async function callNext() {
    const btn = document.getElementById('btnCallNext');
    btn.disabled = true;

    try {
        const res = await SFI.post('/api/queue/call-next.php');
        if (res.success) {
            SFI.toast(res.message, 'success');
            await loadStats();
            await loadQueue();
        } else {
            SFI.toast(res.message, 'error');
            btn.disabled = false;
        }
    } catch (e) {
        SFI.toast('Failed to call next. Please try again.', 'error');
        btn.disabled = false;
    }
}

/**
 * RECALL - Re-announce current ticket.
 */
async function recallTicket() {
    if (!currentServingTicket) return;

    try {
        const res = await SFI.post('/api/queue/recall.php');
        if (res.success) {
            SFI.toast('Ticket recalled: ' + currentServingTicket.ticket_number, 'info');
        } else {
            SFI.toast(res.message, 'error');
        }
    } catch (e) {
        SFI.toast('Failed to recall. Please try again.', 'error');
    }
}

/**
 * MARK SERVED - Complete the current ticket.
 */
async function completeTicket() {
    if (!currentServingTicket) return;

    const confirmed = await SFI.confirm('Mark as Served', 'Mark ' + currentServingTicket.ticket_number + ' as completed?');
    if (!confirmed) return;

    try {
        const res = await SFI.post('/api/queue/complete.php');
        if (res.success) {
            SFI.toast(res.message, 'success');
            await loadStats();
            await loadQueue();
        } else {
            SFI.toast(res.message, 'error');
        }
    } catch (e) {
        SFI.toast('Failed to complete. Please try again.', 'error');
    }
}

/**
 * NO-SHOW - Mark current ticket as no-show.
 */
async function markNoShow() {
    if (!currentServingTicket) return;

    const confirmed = await SFI.confirm('Mark as No-Show', 'Are you sure you want to mark ' + currentServingTicket.ticket_number + ' as NO-SHOW?');
    if (!confirmed) return;

    try {
        const res = await SFI.post('/api/queue/no-show.php');
        if (res.success) {
            SFI.toast(res.message, 'warning');
            await loadStats();
            await loadQueue();
        } else {
            SFI.toast(res.message, 'error');
        }
    } catch (e) {
        SFI.toast('Failed to mark no-show. Please try again.', 'error');
    }
}

/**
 * Show transfer modal.
 */
function showTransferModal() {
    if (!currentServingTicket) return;
    SFI.showModal('transferModal');
}

/**
 * Execute transfer.
 */
async function doTransfer() {
    if (!currentServingTicket) return;

    const counter = document.getElementById('transferCounter').value;

    try {
        const res = await SFI.post('/api/queue/transfer.php', { counter: counter });
        if (res.success) {
            SFI.hideModal('transferModal');
            SFI.toast(res.message, 'success');
            await loadStats();
            await loadQueue();
        } else {
            SFI.toast(res.message, 'error');
        }
    } catch (e) {
        SFI.toast('Failed to transfer. Please try again.', 'error');
    }
}

/**
 * Refresh all data (called on socket reconnect).
 */
window.refreshAll = async function() {
    await loadStats();
    await loadQueue();
};

// ============================================
// Socket.IO Event Listeners
// ============================================
SFISocket.on('queue_updated', function() {
    loadStats();
    loadQueue();
});

SFISocket.on('announce_ticket', function(data) {
    loadStats();
    loadQueue();
});

// ============================================
// Filter Tabs
// ============================================
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        currentFilter = this.dataset.filter;
        renderQueueTable();
    });
});

// ============================================
// Initial Load
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadQueue();

    // Auto-refresh every 30 seconds as fallback
    setInterval(function() {
        loadStats();
        loadQueue();
    }, 30000);
});
</script>

<?php admin_footer(); ?>
