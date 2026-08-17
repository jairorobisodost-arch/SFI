<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin(); requireRole('admin'); initPage();
admin_header('Loan Type Management', 'loan-types');
?>

<div class="page-header flex items-center justify-between">
    <div><h1>Loan Type Management</h1><p>Manage transaction types and their prefixes.</p></div>
    <button class="btn btn-primary" onclick="showAdd()">+ Add Loan Type</button>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Name</th><th>Prefix</th><th>Category</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody id="ltTable"><tr><td colspan="6" class="text-center text-muted" style="padding:40px;">Loading...</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="ltModal">
    <div class="modal">
        <div class="modal-header"><h3 id="ltModalTitle">Add Loan Type</h3><button class="modal-close" onclick="SFI.hideModal('ltModal')">&times;</button></div>
        <div class="modal-body">
            <input type="hidden" id="ltId">
            <div class="form-group"><label>Name *</label><input type="text" id="ltName" class="form-control"></div>
            <div class="form-group"><label>Prefix * (max 5 chars)</label><input type="text" id="ltPrefix" class="form-control" maxlength="5" style="text-transform:uppercase;"></div>
            <div class="form-group"><label>Description</label><textarea id="ltDesc" class="form-control" rows="2"></textarea></div>
            <div class="form-group">
                <label>Category *</label>
                <select id="ltCategory" class="form-control">
                    <option value="transaction">Transaction (kiosk - Payment, Release, etc.)</option>
                    <option value="product">Loan Product (Productive, Negosyo, Educational)</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="SFI.hideModal('ltModal')">Cancel</button>
            <button class="btn btn-primary" onclick="saveLT()">Save</button>
        </div>
    </div>
</div>

<script>
let ltList = [];
async function loadLT() {
    try {
        const res = await SFI.get('/api/loan-types/list.php');
        if (res.success) { ltList = res.data.loan_types; renderLT(); }
    } catch (e) { SFI.toast('Failed to load.', 'error'); }
}
function renderLT() {
    const tbody = document.getElementById('ltTable');
    if (ltList.length === 0) { tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted" style="padding:40px;">No loan types.</td></tr>'; return; }
    let html = '';
    ltList.forEach(lt => {
        const badge = lt.status === 'active' ? 'badge-active' : 'badge-inactive';
        const catBadge = lt.category === 'product' ? 'badge-active' : 'badge-teller';
        html += '<tr><td><strong>' + SFI.escapeHtml(lt.name) + '</strong></td>';
        html += '<td><span class="badge badge-teller">' + lt.prefix + '</span></td>';
        html += '<td><span class="badge ' + catBadge + '">' + SFI.escapeHtml(lt.category) + '</span></td>';
        html += '<td class="text-muted">' + SFI.escapeHtml(lt.description || '-') + '</td>';
        html += '<td><span class="badge ' + badge + '">' + lt.status + '</span></td>';
        html += '<td><div class="flex gap-1">';
        html += '<button class="btn btn-sm btn-secondary" onclick="editLT(' + lt.id + ')">Edit</button>';
        html += '<button class="btn btn-sm ' + (lt.status === 'active' ? 'btn-danger' : 'btn-primary') + '" onclick="toggleLT(' + lt.id + ')">' + (lt.status === 'active' ? 'Disable' : 'Enable') + '</button>';
        html += '</div></td></tr>';
    });
    tbody.innerHTML = html;
}
function showAdd() {
    document.getElementById('ltModalTitle').textContent = 'Add Loan Type';
    document.getElementById('ltId').value = '';
    document.getElementById('ltName').value = '';
    document.getElementById('ltPrefix').value = '';
    document.getElementById('ltDesc').value = '';
    document.getElementById('ltCategory').value = 'transaction';
    SFI.showModal('ltModal');
}
function editLT(id) {
    const lt = ltList.find(x => x.id === id);
    if (!lt) return;
    document.getElementById('ltModalTitle').textContent = 'Edit Loan Type';
    document.getElementById('ltId').value = lt.id;
    document.getElementById('ltName').value = lt.name;
    document.getElementById('ltPrefix').value = lt.prefix;
    document.getElementById('ltDesc').value = lt.description || '';
    document.getElementById('ltCategory').value = lt.category || 'transaction';
    SFI.showModal('ltModal');
}
async function saveLT() {
    const id = document.getElementById('ltId').value;
    const data = { name: document.getElementById('ltName').value, prefix: document.getElementById('ltPrefix').value, description: document.getElementById('ltDesc').value, category: document.getElementById('ltCategory').value };
    const endpoint = id ? '/api/loan-types/update.php' : '/api/loan-types/create.php';
    if (id) data.id = id;
    try {
        const res = await SFI.post(endpoint, data);
        if (res.success) { SFI.toast(res.message, 'success'); SFI.hideModal('ltModal'); loadLT(); }
        else SFI.toast(res.message, 'error');
    } catch (e) { SFI.toast('Failed to save.', 'error'); }
}
async function toggleLT(id) {
    const lt = ltList.find(x => x.id === id);
    if (!lt) return;
    const action = lt.status === 'active' ? 'disable' : 'enable';
    const ok = await SFI.confirm('Confirm', 'Are you sure you want to ' + action + ' ' + lt.name + '?');
    if (!ok) return;
    try {
        const res = await SFI.post('/api/loan-types/toggle.php', { id: id });
        SFI.toast(res.message, res.success ? 'success' : 'error');
        if (res.success) loadLT();
    } catch (e) { SFI.toast('Failed.', 'error'); }
}
window.refreshAll = loadLT;
document.addEventListener('DOMContentLoaded', loadLT);
</script>

<?php admin_footer(); ?>
