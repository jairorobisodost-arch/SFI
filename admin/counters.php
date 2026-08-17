<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin(); requireRole('admin'); initPage();
admin_header('Counter Management', 'counters');

// Fetch counters
$db = Database::getConnection();
$stmt = $db->query("SELECT * FROM counters WHERE is_archived = 0 ORDER BY counter_number ASC");
$counters = $stmt->fetchAll();
?>

<div class="page-header flex items-center justify-between">
    <div><h1>Counter Management</h1><p>Manage service counters and teller assignments.</p></div>
    <button class="btn btn-primary" onclick="showAddCounter()">+ Add Counter</button>
</div>

<div class="stat-grid">
    <?php foreach ($counters as $c): ?>
    <div class="stat-card">
        <div class="stat-icon" style="background:<?= $c['status'] === 'active' ? 'var(--success-soft)' : 'var(--bg)' ?>; color:<?= $c['status'] === 'active' ? 'var(--success)' : 'var(--muted)' ?>;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= htmlspecialchars($c['name']) ?></h3>
            <p><?= ucfirst($c['status']) ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Assigned Tellers -->
<div class="card">
    <div class="card-header"><h3>Teller Counter Assignments</h3></div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Teller</th><th>Username</th><th>Assigned Counter</th><th>Status</th></tr></thead>
                <tbody id="tellerAssignments"><tr><td colspan="4" class="text-center text-muted" style="padding:40px;">Loading...</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Counter Modal -->
<div class="modal-overlay" id="counterModal">
    <div class="modal modal-sm">
        <div class="modal-header"><h3>Add Counter</h3><button class="modal-close" onclick="SFI.hideModal('counterModal')">&times;</button></div>
        <div class="modal-body">
            <div class="form-group"><label>Counter Name *</label><input type="text" id="counterName" class="form-control" placeholder="e.g., Counter 5"></div>
            <div class="form-group"><label>Counter Number *</label><input type="number" id="counterNumber" class="form-control" min="1" max="20"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="SFI.hideModal('counterModal')">Cancel</button>
            <button class="btn btn-primary" onclick="addCounter()">Add</button>
        </div>
    </div>
</div>

<script>
async function loadTellers() {
    try {
        const res = await SFI.get('/api/users/list.php');
        if (res.success) {
            const tellers = res.data.users.filter(u => u.role === 'teller');
            const tbody = document.getElementById('tellerAssignments');
            if (tellers.length === 0) { tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted" style="padding:40px;">No tellers.</td></tr>'; return; }
            let html = '';
            tellers.forEach(t => {
                const badge = t.status === 'active' ? 'badge-active' : 'badge-inactive';
                html += '<tr>';
                html += '<td><strong>' + SFI.escapeHtml(t.full_name) + '</strong></td>';
                html += '<td>' + SFI.escapeHtml(t.username) + '</td>';
                html += '<td>Counter ' + t.assigned_counter + '</td>';
                html += '<td><span class="badge ' + badge + '">' + t.status + '</span></td>';
                html += '</tr>';
            });
            tbody.innerHTML = html;
        }
    } catch (e) { SFI.toast('Failed to load tellers.', 'error'); }
}

function showAddCounter() {
    document.getElementById('counterName').value = '';
    document.getElementById('counterNumber').value = '';
    SFI.showModal('counterModal');
}

async function addCounter() {
    const name = document.getElementById('counterName').value.trim();
    const number = document.getElementById('counterNumber').value;
    if (!name || !number) { SFI.toast('Please fill in all fields.', 'error'); return; }
    try {
        const res = await SFI.post('/api/counters/create.php', { name: name, counter_number: number });
        if (res.success) {
            SFI.toast(res.message, 'success');
            SFI.hideModal('counterModal');
            setTimeout(() => location.reload(), 500);
        } else { SFI.toast(res.message, 'error'); }
    } catch (e) { SFI.toast('Failed to add counter.', 'error'); }
}

window.refreshAll = loadTellers;
document.addEventListener('DOMContentLoaded', loadTellers);
</script>

<?php admin_footer(); ?>
