<?php
/**
 * SFI Queuing System - Activity Logs Viewer
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin(); requireRole('admin'); initPage();
admin_header('Activity Logs', 'activity-logs');
?>

<div class="page-header">
    <h1>Activity Logs</h1>
    <p>System-wide audit trail of user actions.</p>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form id="logFilters" class="flex flex-wrap gap-3 items-center">
            <div class="form-group" style="margin:0; min-width:150px;">
                <label class="text-xs">Date From</label>
                <input type="date" id="filterDateFrom" class="form-control">
            </div>
            <div class="form-group" style="margin:0; min-width:150px;">
                <label class="text-xs">Date To</label>
                <input type="date" id="filterDateTo" class="form-control">
            </div>
            <div class="form-group" style="margin:0; min-width:150px;">
                <label class="text-xs">User</label>
                <select id="filterUser" class="form-control">
                    <option value="">All Users</option>
                </select>
            </div>
            <div class="form-group" style="margin:0; min-width:150px;">
                <label class="text-xs">Action</label>
                <select id="filterAction" class="form-control">
                    <option value="">All Actions</option>
                    <option value="login">Login</option>
                    <option value="logout">Logout</option>
                    <option value="call_next">Call Next</option>
                    <option value="recall">Recall</option>
                    <option value="complete">Complete</option>
                    <option value="no_show">No-Show</option>
                    <option value="transfer">Transfer</option>
                    <option value="create_user">Create User</option>
                    <option value="update_user">Update User</option>
                    <option value="toggle_user">Toggle User</option>
                    <option value="reset_password">Reset Password</option>
                    <option value="create_loan_type">Create Loan Type</option>
                    <option value="update_loan_type">Update Loan Type</option>
                    <option value="toggle_loan_type">Toggle Loan Type</option>
                    <option value="update_settings">Update Settings</option>
                    <option value="queue_reset">Queue Reset</option>
                </select>
            </div>
            <div class="form-group" style="margin:0; min-width:180px;">
                <label class="text-xs">Search</label>
                <input type="text" id="filterSearch" class="form-control" placeholder="Description or IP...">
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
        <h3>Log Entries <span id="totalRecords" class="text-muted text-sm"></span></h3>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody id="logsTableBody">
                    <tr><td colspan="5" class="text-center text-muted" style="padding:40px;">Loading...</td></tr>
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

document.getElementById('logFilters').addEventListener('submit', function(e) {
    e.preventDefault();
    currentPage = 1;
    loadLogs();
});

function resetFilters() {
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    document.getElementById('filterUser').value = '';
    document.getElementById('filterAction').value = '';
    document.getElementById('filterSearch').value = '';
    currentPage = 1;
    loadLogs();
}

function changePage(dir) {
    currentPage += dir;
    loadLogs();
}

async function loadUsers() {
    try {
        const res = await SFI.get('/api/users/list.php');
        if (res.success) {
            const sel = document.getElementById('filterUser');
            res.data.users.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.username;
                opt.textContent = u.full_name + ' (' + u.username + ')';
                sel.appendChild(opt);
            });
        }
    } catch (e) { /* ignore */ }
}

async function loadLogs() {
    const params = new URLSearchParams({
        date_from: document.getElementById('filterDateFrom').value,
        date_to: document.getElementById('filterDateTo').value,
        user: document.getElementById('filterUser').value,
        action: document.getElementById('filterAction').value,
        search: document.getElementById('filterSearch').value,
        page: currentPage
    });

    try {
        const res = await SFI.get('/api/activity-logs/list.php?' + params.toString());
        if (res.success) {
            renderLogs(res.data);
        }
    } catch (e) {
        SFI.toast('Failed to load activity logs.', 'error');
    }
}

function renderLogs(data) {
    const tbody = document.getElementById('logsTableBody');
    const logs = data.logs;
    totalPages = data.total_pages || 1;

    document.getElementById('totalRecords').textContent = '(' + data.total + ' entries)';
    document.getElementById('paginationInfo').textContent = 'Page ' + data.page + ' of ' + totalPages;
    document.getElementById('btnPrev').disabled = (data.page <= 1);
    document.getElementById('btnNext').disabled = (data.page >= totalPages);

    if (logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted" style="padding:40px;">No log entries found.</td></tr>';
        return;
    }

    const actionColors = {
        'login': '#0E9F6E', 'logout': '#718091',
        'call_next': '#2563EB', 'recall': '#D97706', 'complete': '#0E9F6E',
        'no_show': '#E02424', 'transfer': '#7C3AED',
        'create_user': '#2563EB', 'update_user': '#D97706',
        'toggle_user': '#718091', 'reset_password': '#E02424',
        'update_settings': '#7C3AED', 'queue_reset': '#E02424'
    };

    let html = '';
    logs.forEach(log => {
        const color = actionColors[log.action] || '#718091';
        html += '<tr>';
        html += '<td class="text-sm">' + SFI.escapeHtml(log.created_at) + '</td>';
        html += '<td>' + SFI.escapeHtml(log.username || 'system') + '</td>';
        html += '<td><span class="badge" style="background:' + color + '22;color:' + color + ';border:1px solid ' + color + '44;">' + SFI.escapeHtml(log.action).replace(/_/g, ' ') + '</span></td>';
        html += '<td class="text-sm">' + SFI.escapeHtml(log.description || '-') + '</td>';
        html += '<td class="text-sm text-muted">' + SFI.escapeHtml(log.ip_address || '-') + '</td>';
        html += '</tr>';
    });
    tbody.innerHTML = html;
}

window.refreshAll = loadLogs;
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
    loadLogs();
});
</script>

<?php admin_footer(); ?>
