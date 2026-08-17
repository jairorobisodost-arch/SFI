<?php
/**
 * SFI Queuing System - Data Import (Excel / CSV Upload)
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

requireLogin();
requireRole('admin');
initPage();

admin_header('Data Import', 'import');
?>

<style>
.import-grid { display: grid; grid-template-columns: 380px 1fr; gap: 24px; align-items: start; }
@media (max-width: 1100px) { .import-grid { grid-template-columns: 1fr; } }

.upload-dropzone {
    border: 2px dashed var(--border-strong);
    border-radius: var(--radius-lg);
    background: var(--bg);
    padding: 32px 20px;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
}
.upload-dropzone:hover, .upload-dropzone.dragover {
    border-color: var(--primary);
    background: var(--primary-softer);
}
.upload-dropzone .dz-icon {
    display: flex; align-items: center; justify-content: center;
    width: 56px; height: 56px; margin: 0 auto 12px;
    border-radius: var(--radius); background: var(--primary-soft); color: var(--primary);
}
.upload-dropzone h4 { margin: 0 0 4px; font-size: 0.95rem; color: var(--ink); }
.upload-dropzone p { margin: 0; font-size: 0.8rem; color: var(--muted); }
.upload-filename {
    display: none; margin-top: 12px; padding: 8px 12px;
    background: var(--primary-soft); color: var(--primary-deep);
    border-radius: var(--radius-sm); font-size: 0.82rem; font-weight: 500;
    word-break: break-all;
}

.import-summary { display: none; margin-top: 16px; }
.import-summary .summary-box {
    padding: 12px 14px; border-radius: var(--radius-sm);
    font-size: 0.85rem; margin-bottom: 8px;
}
.summary-box.ok { background: var(--success-soft); color: var(--primary-deep); }
.summary-box.warn { background: var(--warning-soft); color: #92400E; }
.summary-box ul { margin: 6px 0 0 16px; padding: 0; font-size: 0.8rem; }

#importProgressWrap {
    position: sticky;
    top: 10px;
    z-index: 50;
    display: none;
    background: var(--card, #fff);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 12px 14px;
    margin-bottom: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,.10);
}
#importProgressWrap.active { display: block; }
.import-progress {
    display: block;
    background: var(--bg);
    border-radius: 999px;
    overflow: hidden;
    height: 10px;
}
.import-progress .ip-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, var(--primary), #2FD39A);
    border-radius: 999px;
    transition: width .2s ease;
}
.import-progress-label {
    display: none;
    margin-top: 6px;
    font-size: 0.75rem;
    color: var(--muted);
    text-align: center;
}
.import-progress-label.active { display: block; }

.format-hint { margin-top: 16px; font-size: 0.78rem; color: var(--muted); line-height: 1.6; }
.format-hint strong { color: var(--ink-soft); }

.pager { display: flex; align-items: center; justify-content: flex-end; gap: 8px; margin-top: 16px; }
.pager .page-info { font-size: 0.8rem; color: var(--muted); }

.view-toggle .view-btn {
    background: transparent; border: none; color: var(--muted, #64748b);
    font-weight: 600; font-size: 0.8rem; padding: 6px 14px; cursor: pointer;
    transition: var(--transition);
}
.view-toggle .view-btn.active {
    background: var(--primary, #0E9F6E); color: #fff;
    box-shadow: 0 2px 6px rgba(14,159,110,0.3);
}

.name-link {
    background: none; border: none; padding: 0; margin: 0;
    color: var(--primary); font-weight: 600; font-size: 0.9rem;
    cursor: pointer; text-align: left;
}
.name-link:hover { text-decoration: underline; }

/* Client detail modal */
.client-modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 1000;
    background: rgba(15, 23, 42, 0.55);
    align-items: flex-start; justify-content: center;
    padding: 40px 16px; overflow-y: auto;
}
.client-modal-overlay.show { display: flex; }
.client-modal {
    background: #fff; border-radius: var(--radius-lg);
    box-shadow: 0 20px 50px rgba(0,0,0,0.25);
    width: 100%; max-width: 1100px; overflow: hidden;
}
.client-modal .modal-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid var(--border);
    background: var(--bg);
}
.client-modal .modal-head h3 { margin: 0; font-size: 1rem; color: var(--ink); }
.client-modal .modal-close {
    background: none; border: none; font-size: 1.4rem; line-height: 1;
    color: var(--muted); cursor: pointer; padding: 4px 8px;
}
.client-modal .modal-close:hover { color: var(--ink); }
.client-modal .modal-body { padding: 0; max-height: 65vh; overflow-y: auto; }
.detail-scroll { overflow-x: auto; }
/* Excel-style: header row across the top, one data row below */
.detail-table {
    border-collapse: collapse; width: 100%;
    white-space: nowrap; font-size: 0.78rem;
}
.detail-table th, .detail-table td {
    border: 1px solid var(--border-strong, #e2e8f0);
    padding: 6px 10px; text-align: left;
}
.detail-table th {
    background: var(--bg, #f1f5f9); color: var(--ink-soft, #334155);
    font-weight: 600; position: sticky; top: 0; z-index: 1;
}
.detail-table td { color: var(--ink); }
.detail-empty { padding: 30px; text-align: center; color: var(--muted); font-size: 0.85rem; }

/* Archive All progress bar */
.archive-progress { display: none; margin-top: 16px; }
.archive-progress .ap-track {
    height: 10px; background: var(--bg, #e2e8f0); border-radius: 999px; overflow: hidden;
}
.archive-progress .ap-fill {
    height: 100%; width: 0%; background: var(--primary, #0E9F6E); border-radius: 999px;
    transition: width .2s ease; position: relative;
}
.archive-progress .ap-fill.indeterminate {
    width: 40% !important; margin-left: -40%;
    animation: ap-slide 1.2s ease-in-out infinite;
}
@keyframes ap-slide {
    0% { margin-left: -40%; }
    100% { margin-left: 100%; }
}
.archive-progress .ap-label {
    font-size: 0.78rem; color: var(--muted); margin-top: 8px;
    display: flex; justify-content: space-between; gap: 10px;
}
.archive-progress .ap-label strong { color: var(--ink-soft); }
</style>

<div class="import-grid">
    <!-- Upload Panel -->
    <div class="card">
        <div class="card-header"><h3>Data Import (3 Files)</h3></div>
        <div class="card-body">
            <p style="font-size:0.82rem; color:var(--muted); margin-bottom:16px; line-height:1.6;">
                Mag-upload ng <strong>3 magkahiwalay na Excel files</strong>. Ang data ay awtomatikong magkaka-link gamit ang
                <strong>ClientReference</strong> (hal. B0005-0000454). I-upload ang lahat ng 3 para kumpleto ang impormasyon ng bawat client.
            </p>

            <!-- Upload progress bar (sticky: laging nakikita habang nag-u-upload) -->
            <div id="importProgressWrap">
                <div class="import-progress" id="importProgress"><div class="ip-fill" id="importProgressFill"></div></div>
                <div class="import-progress-label" id="importProgressLabel"></div>
            </div>

            <!-- ===== Section 1: Client Information ===== -->
            <div class="import-section" style="border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px; margin-bottom:16px;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                    <span style="width:26px; height:26px; border-radius:8px; background:var(--primary-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.8rem;">1</span>
                    <h4 style="margin:0; font-size:0.95rem; color:var(--ink);">Client Information</h4>
                </div>
                <p style="font-size:0.75rem; color:var(--muted); margin:4px 0 10px;">client_id, client_lastname, client_firstname, birthday, contact_no, address, atbp.</p>
                <div class="upload-dropzone" data-import="client_info" id="dropzone_client_info">
                    <div class="dz-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    </div>
                    <h4>Click or drag <em>Client Information.xlsx</em> here</h4>
                    <p>Excel (.xlsx) o CSV (.csv)</p>
                    <div class="upload-filename" id="filename_client_info"></div>
                </div>
                <input type="file" id="file_client_info" accept=".xlsx,.csv" hidden>
                <button class="btn btn-primary" id="btn_client_info" style="width:100%; margin-top:10px;" disabled>Upload Client Information</button>
            </div>

            <!-- ===== Section 2: Client Loan Report ===== -->
            <div class="import-section" style="border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px; margin-bottom:16px;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                    <span style="width:26px; height:26px; border-radius:8px; background:var(--primary-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.8rem;">2</span>
                    <h4 style="margin:0; font-size:0.95rem; color:var(--ink);">Client Loan Report</h4>
                </div>
                <p style="font-size:0.75rem; color:var(--muted); margin:4px 0 10px;">ClientReference, Loan_Product, CycleNo, Date_Release, Principal, Principal_Balance, atbp.</p>
                <div class="upload-dropzone" data-import="loan_report" id="dropzone_loan_report">
                    <div class="dz-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    </div>
                    <h4>Click or drag <em>Client Loan Report.xlsx</em> here</h4>
                    <p>Excel (.xlsx) o CSV (.csv)</p>
                    <div class="upload-filename" id="filename_loan_report"></div>
                </div>
                <input type="file" id="file_loan_report" accept=".xlsx,.csv" hidden>
                <button class="btn btn-primary" id="btn_loan_report" style="width:100%; margin-top:10px;" disabled>Upload Loan Report</button>
            </div>

            <!-- ===== Section 3: Client VS And CBU Report ===== -->
            <div class="import-section" style="border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px; margin-bottom:16px;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                    <span style="width:26px; height:26px; border-radius:8px; background:var(--primary-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.8rem;">3</span>
                    <h4 style="margin:0; font-size:0.95rem; color:var(--ink);">Client VS And CBU Report</h4>
                </div>
                <p style="font-size:0.75rem; color:var(--muted); margin:4px 0 10px;">ClientReference, VS_BALANCE, CBU_Deposits, CBU_Balance, atbp.</p>
                <div class="upload-dropzone" data-import="cbu_report" id="dropzone_cbu_report">
                    <div class="dz-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    </div>
                    <h4>Click or drag <em>Client VS And CBU Report.xlsx</em> here</h4>
                    <p>Excel (.xlsx) o CSV (.csv)</p>
                    <div class="upload-filename" id="filename_cbu_report"></div>
                </div>
                <input type="file" id="file_cbu_report" accept=".xlsx,.csv" hidden>
                <button class="btn btn-primary" id="btn_cbu_report" style="width:100%; margin-top:10px;" disabled>Upload CBU Report</button>
            </div>

            <div class="import-summary" id="importSummary"></div>

            <div class="format-hint">
                <strong>Paano gumagana:</strong> I-upload ang 3 files sa tamang order (Client Information &rarr; Loan Report &rarr; CBU Report).
                Bawat upload ay mag-i-archive ng dating data ng parehong uri. Ang client, loans, at CBU ay magkaka-link sa pamamagitan ng
                <strong>ClientReference</strong>. Pagkatapos mag-verify sa <em>Check Your Loan</em> website, makikita ng client ang kanilang loan at CBU/VS balances.
            </div>
        </div>
    </div>

    <!-- Records Panel -->
    <div class="card">
        <div class="card-header">
            <h3>Imported Records <span class="text-sm text-muted" id="recordCount"></span></h3>
            <div style="display:flex; align-items:center; gap:10px;">
                <div class="view-toggle" style="display:flex; background:var(--bg, #f1f5f9); border-radius:10px; padding:3px;">
                    <button type="button" class="btn btn-sm view-btn active" id="viewCurrent" style="border-radius:8px;">Current</button>
                    <button type="button" class="btn btn-sm view-btn" id="viewArchived" style="border-radius:8px;">Archived</button>
                </div>
                <input type="text" class="form-control" id="searchInput" placeholder="Search records..." style="max-width:240px;">
            </div>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Transaction</th>
                            <th>Remarks</th>
                            <th>Date Added</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="recordsBody">
                        <tr><td colspan="7" class="text-center text-muted" style="padding:40px;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="pager">
                <span class="page-info" id="pageInfo"></span>
                <button class="btn btn-sm btn-secondary" id="btnPrev" disabled>&laquo; Prev</button>
                <button class="btn btn-sm btn-secondary" id="btnNext" disabled>Next &raquo;</button>
            </div>
        </div>
    </div>
</div>

<!-- Import History Panel -->
<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <h3>Import History <span class="text-sm text-muted">(mga na-upload, kahit mag-refresh)</span></h3>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody id="historyBody">
                    <tr><td colspan="4" class="text-center text-muted" style="padding:40px;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Client Detail Modal -->
<div class="client-modal-overlay" id="clientModalOverlay">
    <div class="client-modal">
        <div class="modal-head">
            <h3>Client Information</h3>
            <button class="modal-close" id="clientModalClose" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Loan info editor (persistent, above the detail table) -->
            <div id="clientLoanEdit" style="display:none; padding:14px 20px; background:var(--bg, #f8fafc); border-bottom:1px solid var(--border);">
                <div style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
                    <div style="flex:1; min-width:170px;">
                        <label style="font-size:0.72rem; font-weight:600; color:var(--muted); display:block; margin-bottom:4px;">Loan Type (Produkto)</label>
                        <select id="editLoanType" class="form-control"><option value="">-- None --</option></select>
                    </div>
                    <div style="flex:1; min-width:150px;">
                        <label style="font-size:0.72rem; font-weight:600; color:var(--muted); display:block; margin-bottom:4px;">Loan Status</label>
                        <select id="editLoanStatus" class="form-control">
                            <option value="">-- None --</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="released">Released</option>
                            <option value="active">Active</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <button class="btn btn-primary btn-sm" id="btnSaveLoan">Save</button>
                </div>
            </div>
            <div id="clientModalBody">
                <div class="detail-empty">Loading...</div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let totalPages = 1;
let searchTimer = null;
let clientData = {};      // id -> { raw, loan_status, loan_type_id }
let loanTypeOptions = []; // loan products for the edit dropdown
let editingClientId = null;
let currentView = 'current'; // 'current' or 'archived'

// Current / Archived toggle
function setView(view) {
    currentView = view;
    document.getElementById('viewCurrent').classList.toggle('active', view === 'current');
    document.getElementById('viewArchived').classList.toggle('active', view === 'archived');
    loadRecords(1);
}

document.getElementById('viewCurrent').addEventListener('click', () => setView('current'));
document.getElementById('viewArchived').addEventListener('click', () => setView('archived'));

/**
 * Open the client detail modal showing all stored fields.
 */
function viewClient(id) {
    const overlay = document.getElementById('clientModalOverlay');
    const body = document.getElementById('clientModalBody');
    const info = clientData[id] || {};
    const raw = info.raw || null;

    // Populate the loan info editor
    editingClientId = id;
    const ltSel = document.getElementById('editLoanType');
    let ltHtml = '<option value="">-- None --</option>';
    loanTypeOptions.forEach(lt => {
        ltHtml += '<option value="' + lt.id + '">' + SFI.escapeHtml(lt.name) + '</option>';
    });
    if (ltSel.innerHTML !== ltHtml) ltSel.innerHTML = ltHtml;
    ltSel.value = info.loan_type_id ? String(info.loan_type_id) : '';
    document.getElementById('editLoanStatus').value = info.loan_status || '';
    document.getElementById('clientLoanEdit').style.display = 'block';

    if (!raw || Object.keys(raw).length === 0) {
        body.innerHTML = '<div class="detail-empty">No additional details stored for this record.</div>';
    } else {
        // Excel-style layout: field names as columns across the top,
        // one row of values below - exactly how the data looks in Excel
        const keys = Object.keys(raw);
        let html = '<div class="detail-scroll"><table class="detail-table"><thead><tr>';
        keys.forEach(key => { html += '<th>' + SFI.escapeHtml(key) + '</th>'; });
        html += '</tr></thead><tbody><tr>';
        keys.forEach(key => {
            const val = raw[key] !== null && raw[key] !== undefined ? String(raw[key]) : '';
            html += '<td>' + SFI.escapeHtml(val) + '</td>';
        });
        html += '</tr></tbody></table></div>';
        body.innerHTML = html;
    }
    overlay.classList.add('show');
}

function closeClientModal() {
    document.getElementById('clientModalOverlay').classList.remove('show');
    document.getElementById('clientLoanEdit').style.display = 'none';
}

async function saveClientLoan() {
    if (!editingClientId) return;
    try {
        const res = await SFI.post('/api/import/update.php', {
            id: editingClientId,
            loan_status: document.getElementById('editLoanStatus').value,
            loan_type_id: document.getElementById('editLoanType').value
        });
        if (res.success) {
            SFI.toast(res.message, 'success');
            loadRecords(currentPage);
        } else {
            SFI.toast(res.message, 'error');
        }
    } catch (e) {
        SFI.toast('Failed to save.', 'error');
    }
}

document.getElementById('btnSaveLoan').addEventListener('click', saveClientLoan);
document.getElementById('clientModalClose').addEventListener('click', closeClientModal);
document.getElementById('clientModalOverlay').addEventListener('click', e => {
    if (e.target === document.getElementById('clientModalOverlay')) closeClientModal();
});

/**
 * Load the imported records table.
 */
async function loadRecords(page) {
    currentPage = page || 1;
    const search = document.getElementById('searchInput').value.trim();

    try {
        const params = new URLSearchParams({ page: currentPage, view: currentView });
        if (search) params.set('search', search);
        const res = await fetch('<?= BASE_URL ?>/api/import/list.php?' + params.toString()).then(r => r.json());

        if (!res.success) { SFI.toast(res.message, 'error'); return; }

        if (res.data.loan_types) loanTypeOptions = res.data.loan_types;

        const tbody = document.getElementById('recordsBody');
        const rows = res.data.rows;

        // Show counts on the toggle buttons
        const cur = document.getElementById('viewCurrent');
        const arc = document.getElementById('viewArchived');
        if (res.data.current_count !== undefined) cur.textContent = 'Current (' + res.data.current_count + ')';
        if (res.data.archived_count !== undefined) arc.textContent = 'Archived (' + res.data.archived_count + ')';

        document.getElementById('recordCount').textContent = '(' + res.data.total + ' total)';
        totalPages = Math.max(1, res.data.total_pages);

        if (rows.length === 0) {
            const msg = currentView === 'archived'
                ? 'No archived records. Upload a new file and the previous data will be archived automatically.'
                : 'No records found. Upload a file to get started.';
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted" style="padding:40px;">' + msg + '</td></tr>';
        } else {
            clientData = {};
            let html = '';
            rows.forEach(r => {
                clientData[r.id] = { raw: r.raw_data || null, loan_status: r.loan_status, loan_type_id: r.loan_type_id };
                const type = r.loan_type_name ? SFI.escapeHtml(r.loan_type_name) : '<span class="text-muted">-</span>';
                const archivedBadge = (currentView === 'archived')
                    ? ' <span class="badge" style="background:var(--warning-soft, #fef3c7); color:#92400E; font-size:0.62rem; padding:2px 8px; border-radius:999px;">ARCHIVED</span>'
                    : '';
                html += '<tr>';
                html += '<td><button class="name-link" onclick="viewClient(' + r.id + ')" title="Click to view full information">' + SFI.escapeHtml(r.full_name) + '</button>' + archivedBadge + '</td>';
                html += '<td>' + (r.contact_number ? SFI.escapeHtml(r.contact_number) : '-') + '</td>';
                html += '<td>' + (r.address ? SFI.escapeHtml(r.address) : '-') + '</td>';
                html += '<td>' + type + '</td>';
                html += '<td>' + (r.remarks ? SFI.escapeHtml(r.remarks) : '-') + '</td>';
                html += '<td class="text-sm text-muted">' + SFI.escapeHtml(r.created_at) + '</td>';
                html += '<td><button class="btn btn-sm btn-danger" onclick="deleteRecord(' + r.id + ')">Delete</button></td>';
                html += '</tr>';
            });
            tbody.innerHTML = html;
        }

        document.getElementById('pageInfo').textContent = 'Page ' + currentPage + ' of ' + totalPages;
        document.getElementById('btnPrev').disabled = currentPage <= 1;
        document.getElementById('btnNext').disabled = currentPage >= totalPages;
    } catch (e) {
        SFI.toast('Failed to load records.', 'error');
    }
}

/**
 * Delete a single record.
 */
async function deleteRecord(id) {
    const confirmed = await SFI.confirm('Delete Record', 'Are you sure you want to delete this record? This cannot be undone.');
    if (!confirmed) return;

    try {
        const res = await SFI.post('/api/import/delete.php', { id: id });
        if (res.success) {
            SFI.toast(res.message, 'success');
            loadRecords(currentPage);
        } else {
            SFI.toast(res.message, 'error');
        }
    } catch (e) {
        SFI.toast('Failed to delete record.', 'error');
    }
}

/**
 * Upload the selected file for the given import type, with a live progress bar.
 */
function uploadFile(type) {
    const input = document.getElementById('file_' + type);
    if (!input.files.length) return;

    const btn = document.getElementById('btn_' + type);
    btn.disabled = true;
    btn.textContent = 'Uploading...';

    const progress = document.getElementById('importProgress');
    const progressFill = document.getElementById('importProgressFill');
    const progressLabel = document.getElementById('importProgressLabel');
    document.getElementById('importProgressWrap').classList.add('active');
    progressLabel.classList.add('active');
    progressFill.style.width = '0%';
    progressLabel.textContent = 'Uploading ' + input.files[0].name + '...';

    const formData = new FormData();
    formData.append('file', input.files[0]);
    formData.append('type', type);

    const summary = document.getElementById('importSummary');
    summary.style.display = 'none';
    summary.innerHTML = '';

    const xhr = new XMLHttpRequest();

    // Real upload progress
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const pct = Math.round((e.loaded / e.total) * 100);
            progressFill.style.width = pct + '%';
            progressLabel.textContent = pct >= 100
                ? 'Uploaded. Processing records... (maaaring tumagal ng ilang segundo)'
                : 'Uploading ' + input.files[0].name + '... ' + pct + '%';
        }
    });

    xhr.addEventListener('load', function() {
        let res = null;
        try { res = JSON.parse(xhr.responseText); } catch (e2) { res = null; }

        let html = '';
        if (res && res.success) {
            progressFill.style.width = '100%';
            progressLabel.textContent = 'Done! Importing records...';

            html += '<div class="summary-box ok">' + SFI.escapeHtml(res.message) + '</div>';
            if (res.data.archived > 0) {
                html += '<div class="summary-box warn">' + res.data.archived + ' previous record(s) moved to <strong>Archive</strong>.</div>';
            }
            if (res.data.duplicates_count > 0) {
                html += '<div class="summary-box warn">' + res.data.duplicates_count + ' duplicate(s) skipped:<ul>';
                res.data.duplicates.slice(0, 10).forEach(s => { html += '<li>' + SFI.escapeHtml(s) + '</li>'; });
                html += '</ul></div>';
            }
            if (res.data.skipped_count > 0) {
                html += '<div class="summary-box warn">' + res.data.skipped_count + ' row(s) skipped:<ul>';
                res.data.skipped.slice(0, 10).forEach(s => { html += '<li>' + SFI.escapeHtml(s) + '</li>'; });
                html += '</ul></div>';
            }
            loadRecords(1);
            loadImportHistory();
            // Hide the progress bar after a moment so the screen is clean
            setTimeout(function() {
                document.getElementById('importProgressWrap').classList.remove('active');
                progressLabel.classList.remove('active');
                progressFill.style.width = '0%';
            }, 2500);
        } else {
            progressFill.style.width = '0%';
            progressLabel.textContent = '';
            const msg = (res && res.message) ? res.message : 'Upload failed. Please try again.';
            html += '<div class="summary-box warn">' + SFI.escapeHtml(msg);
            if (res && res.errors && res.errors.length) {
                html += '<ul>';
                res.errors.slice(0, 10).forEach(s => { html += '<li>' + SFI.escapeHtml(s) + '</li>'; });
                html += '</ul>';
            }
            html += '</div>';
            SFI.toast(msg, 'error');
        }
        summary.innerHTML = html;
        summary.style.display = 'block';

        btn.disabled = !input.files.length;
        btn.textContent = input.files.length ? 'Upload again' : 'Upload';
    });

    xhr.addEventListener('error', function() {
        progressFill.style.width = '0%';
        progressLabel.textContent = '';
        summary.innerHTML = '<div class="summary-box warn">Upload failed. Please try again.</div>';
        summary.style.display = 'block';
        SFI.toast('Upload failed. Please try again.', 'error');
        btn.disabled = !input.files.length;
        btn.textContent = input.files.length ? 'Upload again' : 'Upload';
    });

    xhr.open('POST', '<?= BASE_URL ?>/api/import/upload.php');
    xhr.send(formData);
}

// ============================================
// Per-section file selection / drag & drop
// ============================================
const importTypes = ['client_info', 'loan_report', 'cbu_report'];

importTypes.forEach(type => {
    const dropzone = document.getElementById('dropzone_' + type);
    const fileInput = document.getElementById('file_' + type);
    const btn = document.getElementById('btn_' + type);
    const label = document.getElementById('filename_' + type);

    dropzone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function() {
        if (this.files.length) {
            label.textContent = this.files[0].name;
            label.style.display = 'block';
            btn.disabled = false;
        } else {
            label.style.display = 'none';
            btn.disabled = true;
        }
    });

    ['dragenter', 'dragover'].forEach(ev => dropzone.addEventListener(ev, e => {
        e.preventDefault();
        dropzone.classList.add('dragover');
    }));
    ['dragleave', 'drop'].forEach(ev => dropzone.addEventListener(ev, e => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
    }));
    dropzone.addEventListener('drop', e => {
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });

    btn.addEventListener('click', () => uploadFile(type));
});

/**
 * Archive ALL current data (clients, loan types, counters, users) in batches,
 * showing a live progress bar. After archiving, the page shows 0 active records
 * so a fresh Excel file can be uploaded as the new active data set.
 */
async function archiveAllData() {
    const confirmed = await SFI.confirm('Archive All Old Data',
        'Ililipat ang LAHAT ng kasalukuyang records (clients, loan types, counters, users) sa Archive. ' +
        'Pagkatapos nito, walang active na records hangga\'t hindi ka mag-uupload ng bagong Excel. Sige?');
    if (!confirmed) return;

    const btn = document.getElementById('btnArchiveAll');
    const prog = document.getElementById('archiveProgress');
    const fill = document.getElementById('archiveFill');
    const label = document.getElementById('archiveLabel');
    const count = document.getElementById('archiveCount');

    btn.disabled = true;
    prog.style.display = 'block';
    fill.className = 'ap-fill indeterminate';
    label.textContent = 'Kumukuha ng bilang ng records...';
    count.textContent = '';

    try {
        // Get how many active records exist per table
        const countsRes = await SFI.post('/api/import/archive.php', { action: 'counts' });
        if (!countsRes.success) throw new Error(countsRes.message);

        const tables = countsRes.data.tables;
        const total = countsRes.data.total;
        const archivedTotal = countsRes.data.archivedTotal || 0;
        if (total === 0 && archivedTotal === 0) {
            label.textContent = 'Walang laman ang database - wala pang ma-a-archive.';
            SFI.toast('Wala pang data na ma-a-archive.', 'info');
            btn.disabled = false;
            return;
        }

        // Create ONE dated archive file (ALL the data) BEFORE archiving.
        // If everything is already archived, the file is built from the archived data.
        label.textContent = 'Gumagawa ng archive file...';
        let archiveFile = '';
        const snapRes = await SFI.post('/api/import/archive.php', { action: 'snapshot' });
        if (!snapRes.success) throw new Error(snapRes.message);
        archiveFile = snapRes.data.file || '';

        if (total === 0) {
            // Nothing active to move - the file was created from the archived data
            fill.className = 'ap-fill';
            fill.style.width = '100%';
            label.textContent = 'Wala nang active na records. Nakagawa ng archive file ng lumang data.';
            count.textContent = '\ud83d\udcc1 ' + archiveFile + ' (sa Archive folder)';
            SFI.toast('Archive file: ' + archiveFile, 'success');
            setView('current');
            loadRecords(1);
            btn.disabled = false;
            return;
        }

        const BATCH = 500;
        let movedTotal = 0;
        const order = ['clients', 'loan_types', 'counters', 'users'];

        for (const table of order) {
            let remaining = tables[table] || 0;
            while (remaining > 0) {
                const res = await SFI.post('/api/import/archive.php', { action: 'archive', table: table, batch: BATCH });
                if (!res.success) throw new Error(res.message);

                movedTotal += res.data.archived;
                remaining = res.data.remaining;

                const pct = Math.min(100, Math.round((movedTotal / total) * 100));
                fill.className = 'ap-fill';
                fill.style.width = pct + '%';
                label.textContent = 'Ina-archive ang ' + table.replace('_', ' ') + '... ' + pct + '%';
                count.textContent = movedTotal.toLocaleString() + ' / ' + total.toLocaleString() + ' records';

                // Small pause so the progress bar is visible
                await new Promise(r => setTimeout(r, 120));
            }
        }

        fill.style.width = '100%';
        label.textContent = 'Tapos na! Lahat ng ' + total.toLocaleString() + ' records ay nasa Archive.';
        count.textContent = archiveFile ? '\ud83d\udcc1 Archive file: ' + archiveFile + ' (sa Archive folder)' : '';
        SFI.toast('Na-archive ang lahat ng records. File: ' + archiveFile, 'success');

        // Switch to Current view so the user sees 0 active records + the archive counts
        setView('current');
        loadRecords(1);

    } catch (e) {
        label.textContent = 'Nag-error ang pag-archive: ' + (e.message || 'unknown error');
        fill.className = 'ap-fill';
        fill.style.width = '100%';
        SFI.toast('Nag-error ang pag-archive.', 'error');
    } finally {
        btn.disabled = false;
    }
}

document.getElementById('btnArchiveAll').addEventListener('click', archiveAllData);
document.getElementById('btnPrev').addEventListener('click', () => loadRecords(currentPage - 1));
document.getElementById('btnNext').addEventListener('click', () => loadRecords(currentPage + 1));
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadRecords(1), 350);
});

async function loadImportHistory() {
    const body = document.getElementById('historyBody');
    try {
        const res = await fetch('/SFI/api/import/history.php');
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed');
        const logs = data.data.history || [];
        if (logs.length === 0) {
            body.innerHTML = '<tr><td colspan="4" class="text-center text-muted" style="padding:30px;">Wala pang na-upload. Mag-upload ng Excel file sa itaas.</td></tr>';
            return;
        }
        body.innerHTML = logs.map(l => {
            const date = new Date(l.created_at.replace(' ', 'T'));
            const dateStr = date.toLocaleString();
            const desc = (l.description || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return '<tr>' +
                '<td style="white-space:nowrap;">' + dateStr + '</td>' +
                '<td>' + (l.username || '') + '</td>' +
                '<td>' + desc + '</td>' +
                '<td>' + (l.ip_address || '') + '</td>' +
            '</tr>';
        }).join('');
    } catch (e) {
        body.innerHTML = '<tr><td colspan="4" class="text-center text-muted" style="padding:30px;">Hindi ma-load ang import history.</td></tr>';
    }
}

// Initial load
loadRecords(1);
loadImportHistory();
</script>

<?php admin_footer(); ?>
