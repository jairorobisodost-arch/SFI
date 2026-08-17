<?php
/**
 * SFI Queuing System - Reports Page
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
initPage();
admin_header('Reports', 'reports');
?>

<div class="page-header flex items-center justify-between">
    <div>
        <h1>Reports</h1>
        <p>Daily queue statistics and loan type breakdown.</p>
    </div>
    <div class="flex gap-2 items-center">
        <label class="text-sm font-semibold">Date:</label>
        <input type="date" id="reportDate" class="form-control" style="width:auto;" value="<?= today() ?>" onchange="loadReports()">
    </div>
</div>

<!-- Daily Summary Cards -->
<div class="stat-grid" id="dailySummary">
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--primary-soft); color:var(--primary-deep);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-info"><h3 id="rTotal">0</h3><p>Total Tickets</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon completed"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div class="stat-info"><h3 id="rCompleted">0</h3><p>Completed</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon waiting"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="stat-info"><h3 id="rWaiting">0</h3><p>Waiting</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon noshow"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></div>
        <div class="stat-info"><h3 id="rNoShow">0</h3><p>No-Show</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--info-soft); color:var(--info);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-info"><h3 id="rAvgWait">0s</h3><p>Avg Wait Time</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--violet-soft); color:var(--violet);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-info"><h3 id="rAvgService">0s</h3><p>Avg Service Time</p></div>
    </div>
</div>

<!-- Loan Type Breakdown -->
<div class="card">
    <div class="card-header">
        <h3>Loan Type Breakdown</h3>
    </div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:24px;">
            <!-- Chart -->
            <div>
                <canvas id="loanTypeChart" height="280"></canvas>
            </div>
            <!-- Table -->
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>Loan Type</th><th>Total</th><th>Completed</th><th>Waiting</th><th>No-Show</th></tr>
                    </thead>
                    <tbody id="loanTypeTableBody">
                        <tr><td colspan="5" class="text-center text-muted" style="padding:30px;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Client Data Report (query dropdown + Excel-style preview) -->
<style>
.excel-preview {
    overflow: auto; max-height: 560px; border: 1px solid var(--border);
    border-radius: var(--radius); background: #fff;
}
.excel-preview table { border-collapse: collapse; font-size: 0.75rem; white-space: nowrap; }
.excel-preview th, .excel-preview td {
    border: 1px solid var(--border-strong, #e2e8f0); padding: 5px 9px; text-align: left;
}
.excel-preview th {
    background: var(--bg, #f1f5f9); color: var(--ink-soft, #334155);
    font-weight: 600; position: sticky; top: 0; z-index: 1;
}
.excel-preview td { color: var(--ink); }
.excel-preview tr:hover td { background: var(--primary-soft); }
</style>

<div class="card" style="margin-top:24px;">
    <div class="card-header" style="flex-wrap:wrap; gap:10px;">
        <h3>Client Data Report <span class="text-sm text-muted">(from imported Excel masterlist)</span></h3>
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-left:auto;">
            <select id="qSelect" class="form-control" style="min-width:280px;" title="Pumili ng query">
                <option value="">Loading queries...</option>
            </select>
            <button class="btn btn-primary" id="qExport">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="vertical-align:-3px; margin-right:6px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export to Excel
            </button>
        </div>
    </div>
    <div class="card-body">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; flex-wrap:wrap; gap:8px;">
            <strong id="activeQueryLabel" style="font-size:0.9rem;">All Clients</strong>
            <span class="text-sm text-muted" id="qCountInfo"></span>
        </div>
        <div class="excel-preview" id="excelPreview">
            <div style="padding:30px; text-align:center; color:var(--muted); font-size:0.85rem;">Pumili ng query mula sa dropdown...</div>
        </div>
        <div class="pager">
            <span class="page-info" id="qPageInfo"></span>
            <button class="btn btn-sm btn-secondary" id="qPrev" disabled>&laquo; Prev</button>
            <button class="btn btn-sm btn-secondary" id="qNext" disabled>Next &raquo;</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
let chart = null;
let queryList = [];
let activeQuery = { key: 'all', label: 'All Clients', params: {} };
let qPage = 1;
let qTotalPages = 1;
let qTotal = 0;

// ---------------- Preset queries ----------------

async function loadQueries() {
    try {
        const res = await SFI.get('/api/reports/clients.php?queries=1');
        if (!res.success) { SFI.toast(res.message, 'error'); return; }
        queryList = res.data.queries;
        populateQuerySelect();
        runQuery('all');
    } catch (e) {
        SFI.toast('Failed to load queries.', 'error');
    }
}

function populateQuerySelect() {
    const sel = document.getElementById('qSelect');
    const groups = {};
    queryList.forEach(q => {
        const group = q.key === 'all' ? 'General'
            : q.key.startsWith('gender_') ? 'Gender'
            : q.key.startsWith('civil_') ? 'Civil Status'
            : q.key.startsWith('barangay_') ? 'Barangay'
            : 'Loan Type';
        if (!groups[group]) groups[group] = [];
        groups[group].push(q);
    });

    let html = '';
    Object.keys(groups).forEach(g => {
        html += '<optgroup label="' + SFI.escapeHtml(g) + '">';
        groups[g].forEach(q => {
            html += '<option value="' + q.key + '">' + SFI.escapeHtml(q.label) + ' (' + q.count + ')</option>';
        });
        html += '</optgroup>';
    });
    sel.innerHTML = html;
    sel.value = activeQuery.key;
}

function runQuery(key) {
    const q = queryList.find(x => x.key === key) || queryList[0];
    activeQuery = q;
    qPage = 1;
    document.getElementById('activeQueryLabel').textContent = q.label;
    document.getElementById('qSelect').value = q.key;
    loadQueryData();
}

// ---------------- Excel-style preview ----------------

async function loadQueryData() {
    try {
        const p = new URLSearchParams();
        Object.keys(activeQuery.params).forEach(k => p.set(k, activeQuery.params[k]));
        p.set('page', qPage);
        const res = await SFI.get('/api/reports/clients.php?' + p.toString());
        if (!res.success) { SFI.toast(res.message, 'error'); return; }

        qTotal = res.data.total;
        qTotalPages = Math.max(1, res.data.total_pages);
        renderExcelTable(res.data.rows);

        document.getElementById('qCountInfo').textContent = qTotal + ' record(s)';
        document.getElementById('qPageInfo').textContent = 'Page ' + qPage + ' of ' + qTotalPages;
        document.getElementById('qPrev').disabled = qPage <= 1;
        document.getElementById('qNext').disabled = qPage >= qTotalPages;
    } catch (e) {
        SFI.toast('Failed to load data.', 'error');
    }
}

function renderExcelTable(rows) {
    const wrap = document.getElementById('excelPreview');
    if (!rows || rows.length === 0) {
        wrap.innerHTML = '<div style="padding:30px; text-align:center; color:var(--muted); font-size:0.85rem;">No records match this query.</div>';
        return;
    }

    // Column order mirrors the Excel export: raw fields first, then standard fields
    const rawKeys = Object.keys(rows[0].raw_data || {});
    const headers = rawKeys.concat(['Full Name', 'Contact Number', 'Address', 'Remarks', 'Date Added']);

    let html = '<table><thead><tr>';
    headers.forEach(h => { html += '<th>' + SFI.escapeHtml(h) + '</th>'; });
    html += '</tr></thead><tbody>';

    rows.forEach(r => {
        const raw = r.raw_data || {};
        html += '<tr>';
        rawKeys.forEach(k => { html += '<td>' + SFI.escapeHtml(raw[k] !== undefined && raw[k] !== null ? String(raw[k]) : '') + '</td>'; });
        html += '<td>' + SFI.escapeHtml(r.full_name || '') + '</td>';
        html += '<td>' + SFI.escapeHtml(r.contact_number || '') + '</td>';
        html += '<td>' + SFI.escapeHtml(r.address || '') + '</td>';
        html += '<td>' + SFI.escapeHtml(r.remarks || '') + '</td>';
        html += '<td>' + SFI.escapeHtml(r.created_at || '') + '</td>';
        html += '</tr>';
    });
    html += '</tbody></table>';
    wrap.innerHTML = html;
}

// ---------------- Controls ----------------

document.getElementById('qSelect').addEventListener('change', e => runQuery(e.target.value));

document.getElementById('qExport').addEventListener('click', () => {
    const p = new URLSearchParams();
    Object.keys(activeQuery.params).forEach(k => p.set(k, activeQuery.params[k]));
    window.location.href = SFI.baseUrl + '/api/reports/clients-export.php?' + p.toString();
});

document.getElementById('qPrev').addEventListener('click', () => { qPage--; loadQueryData(); });
document.getElementById('qNext').addEventListener('click', () => { qPage++; loadQueryData(); });

function formatSeconds(secs) {
    if (!secs || secs <= 0) return '0s';
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    return m > 0 ? m + 'm ' + s + 's' : s + 's';
}

async function loadReports() {
    const date = document.getElementById('reportDate').value;
    try {
        const [dailyRes, ltRes] = await Promise.all([
            SFI.get('/api/reports/daily.php?date=' + date),
            SFI.get('/api/reports/loan-types.php?date=' + date)
        ]);

        if (dailyRes.success) {
            const d = dailyRes.data;
            document.getElementById('rTotal').textContent = d.total;
            document.getElementById('rCompleted').textContent = d.completed;
            document.getElementById('rWaiting').textContent = d.waiting;
            document.getElementById('rNoShow').textContent = d.no_show;
            document.getElementById('rAvgWait').textContent = formatSeconds(d.avg_wait);
            document.getElementById('rAvgService').textContent = formatSeconds(d.avg_service);
        }

        if (ltRes.success) {
            renderLoanTypes(ltRes.data.stats);
        }
    } catch (e) {
        SFI.toast('Failed to load reports.', 'error');
    }
}

function renderLoanTypes(stats) {
    const tbody = document.getElementById('loanTypeTableBody');
    if (!stats || stats.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted" style="padding:30px;">No data available.</td></tr>';
        return;
    }

    let html = '';
    stats.forEach(s => {
        html += '<tr>';
        html += '<td><strong>' + SFI.escapeHtml(s.name) + '</strong> <span class="badge badge-teller">' + s.prefix + '</span></td>';
        html += '<td>' + (s.total || 0) + '</td>';
        html += '<td>' + (s.completed || 0) + '</td>';
        html += '<td>' + (s.waiting || 0) + '</td>';
        html += '<td>' + (s.no_show || 0) + '</td>';
        html += '</tr>';
    });
    tbody.innerHTML = html;

    // Update chart (vertical bar)
    const labels = stats.map(s => s.name);
    const data = stats.map(s => parseInt(s.total) || 0);
    const colors = ['#0E9F6E','#0B815A','#0B3B2E','#5BBFA4','#B7E6D8'];

    if (chart) chart.destroy();
    const ctx = document.getElementById('loanTypeChart').getContext('2d');
    chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Tickets',
                data: data,
                backgroundColor: colors.slice(0, data.length),
                borderWidth: 0,
                borderRadius: 6,
                maxBarThickness: 60
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (item) => ' ' + item.parsed.y + ' ticket(s)'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0, font: { family: 'Poppins', size: 11 } },
                    grid: { color: 'rgba(15, 23, 42, 0.06)' }
                },
                x: {
                    ticks: { font: { family: 'Poppins', size: 11 }, maxRotation: 45, minRotation: 0 },
                    grid: { display: false }
                }
            }
        }
    });
}

window.refreshAll = loadReports;
document.addEventListener('DOMContentLoaded', () => {
    loadReports();
    loadQueries();
});
</script>

<?php admin_footer(); ?>
