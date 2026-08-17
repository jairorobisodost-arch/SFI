<?php
/**
 * SFI Queuing System - Queue History Page
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
initPage();
admin_header('Queue History', 'history');
?>

<div class="page-header">
    <h1>Queue History</h1>
    <p>View and filter historical queue records.</p>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form id="historyFilters" class="flex flex-wrap gap-3 items-center">
            <div class="form-group" style="margin:0; min-width:150px;">
                <label class="text-xs">Date</label>
                <input type="date" id="filterDate" class="form-control" value="<?= today() ?>">
            </div>
            <div class="form-group" style="margin:0; min-width:120px;">
                <label class="text-xs">Loan Type</label>
                <select id="filterLoanType" class="form-control">
                    <option value="">All</option>
                    <option value="PY">PY - Payment</option>
                    <option value="RL">RL - Release</option>
                    <option value="CS">CS - Customer Services</option>
                </select>
            </div>
            <div class="form-group" style="margin:0; min-width:120px;">
                <label class="text-xs">Counter</label>
                <select id="filterCounter" class="form-control">
                    <option value="">All</option>
                    <option value="1">Counter 1</option>
                    <option value="2">Counter 2</option>
                    <option value="3">Counter 3</option>
                    <option value="4">Counter 4</option>
                </select>
            </div>
            <div class="form-group" style="margin:0; min-width:120px;">
                <label class="text-xs">Status</label>
                <select id="filterStatus" class="form-control">
                    <option value="">All</option>
                    <option value="completed">Completed</option>
                    <option value="no_show">No-Show</option>
                    <option value="waiting">Waiting</option>
                    <option value="serving">Serving</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="form-group" style="margin:0; min-width:180px;">
                <label class="text-xs">Search</label>
                <input type="text" id="filterSearch" class="form-control" placeholder="Ticket # or name">
            </div>
            <div style="align-self: flex-end;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <button type="button" class="btn btn-secondary" onclick="resetFilters()">Reset</button>
            </div>
        </form>
    </div>
</div>

<!-- Results Table -->
<div class="card">
    <div class="card-header">
        <h3>Records <span id="totalRecords" class="text-muted text-sm"></span></h3>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Client</th>
                        <th>Loan Type</th>
                        <th>Counter</th>
                        <th>Teller</th>
                        <th>Created</th>
                        <th>Called</th>
                        <th>Completed</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <tr><td colspan="9" class="text-center text-muted" style="padding:40px;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="flex items-center justify-between">
            <span id="paginationInfo" class="text-sm text-muted"></span>
            <div class="flex gap-2">
                <button class="btn btn-sm btn-secondary" id="btnPrev" onclick="changePage(-1)" disabled>Previous</button>
                <button class="btn btn-sm btn-secondary" id="btnNext" onclick="changePage(1)" disabled>Next</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let totalPages = 1;

document.getElementById('historyFilters').addEventListener('submit', function(e) {
    e.preventDefault();
    currentPage = 1;
    loadHistory();
});

function resetFilters() {
    document.getElementById('filterDate').value = '<?= today() ?>';
    document.getElementById('filterLoanType').value = '';
    document.getElementById('filterCounter').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterSearch').value = '';
    currentPage = 1;
    loadHistory();
}

function changePage(dir) {
    currentPage += dir;
    loadHistory();
}

async function loadHistory() {
    const params = new URLSearchParams({
        date: document.getElementById('filterDate').value,
        loan_type: document.getElementById('filterLoanType').value,
        counter: document.getElementById('filterCounter').value,
        status: document.getElementById('filterStatus').value,
        search: document.getElementById('filterSearch').value,
        page: currentPage
    });

    try {
        const res = await SFI.get('/api/queue/history.php?' + params.toString());
        if (res.success) {
            renderHistory(res.data);
        }
    } catch (e) {
        SFI.toast('Failed to load history.', 'error');
    }
}

function renderHistory(data) {
    const tbody = document.getElementById('historyTableBody');
    const tickets = data.tickets;
    totalPages = data.total_pages || 1;

    document.getElementById('totalRecords').textContent = '(' + data.total + ' records)';
    document.getElementById('paginationInfo').textContent = 'Page ' + data.page + ' of ' + totalPages;
    document.getElementById('btnPrev').disabled = (data.page <= 1);
    document.getElementById('btnNext').disabled = (data.page >= totalPages);

    if (tickets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted" style="padding:40px;">No records found.</td></tr>';
        return;
    }

    let html = '';
    tickets.forEach(t => {
        const badgeClass = {
            'waiting':'badge-waiting','serving':'badge-serving','completed':'badge-completed',
            'no_show':'badge-no-show','cancelled':'badge-cancelled','transferred':'badge-transferred'
        }[t.status] || '';

        html += '<tr>';
        html += '<td><strong>' + SFI.escapeHtml(t.ticket_number) + '</strong></td>';
        html += '<td>' + SFI.escapeHtml(t.client_name) + '</td>';
        html += '<td>' + SFI.escapeHtml(t.loan_type_name || t.prefix) + '</td>';
        html += '<td>' + (t.counter_assigned ? 'Counter ' + t.counter_assigned : '-') + '</td>';
        html += '<td>' + SFI.escapeHtml(t.served_by_name || '-') + '</td>';
        html += '<td>' + SFI.formatTime(t.created_at) + '</td>';
        html += '<td>' + SFI.formatTime(t.called_at) + '</td>';
        html += '<td>' + SFI.formatTime(t.completed_at) + '</td>';
        html += '<td><span class="badge ' + badgeClass + '">' + t.status.replace('_',' ') + '</span></td>';
        html += '</tr>';
    });
    tbody.innerHTML = html;
}

window.refreshAll = loadHistory;
document.addEventListener('DOMContentLoaded', loadHistory);
</script>

<?php admin_footer(); ?>
