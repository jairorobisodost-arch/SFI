<?php
/**
 * SFI Queuing System - User Management Page (Admin Only)
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
requireRole('admin');
initPage();
admin_header('User Management', 'users');
?>

<div class="page-header flex items-center justify-between">
    <div><h1>User Management</h1><p>Manage system users, roles, and counter assignments.</p></div>
    <button class="btn btn-primary" onclick="showAddUser()">+ Add User</button>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Counter</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody id="usersTableBody"><tr><td colspan="7" class="text-center text-muted" style="padding:40px;">Loading...</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal-overlay" id="userModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="userModalTitle">Add User</h3>
            <button class="modal-close" onclick="SFI.hideModal('userModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="userForm">
                <input type="hidden" id="userId" value="">
                <div class="form-group">
                    <label for="userName">Full Name *</label>
                    <input type="text" id="userName" class="form-control" required>
                </div>
                <div class="form-group" id="usernameGroup">
                    <label for="userUsername">Username *</label>
                    <input type="text" id="userUsername" class="form-control" required>
                </div>
                <div class="form-group" id="passwordGroup">
                    <label for="userPassword">Password *</label>
                    <input type="password" id="userPassword" class="form-control">
                    <span class="text-xs text-muted">Minimum 6 characters.</span>
                </div>
                <div class="flex gap-3">
                    <div class="form-group" style="flex:1;">
                        <label for="userRole">Role</label>
                        <select id="userRole" class="form-control">
                            <option value="teller">Teller (Queuing)</option>
                            <option value="employee">Employee (Attendance)</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label for="userCounter">Counter</label>
                        <select id="userCounter" class="form-control">
                            <option value="1">Counter 1</option>
                            <option value="2">Counter 2</option>
                            <option value="3">Counter 3</option>
                            <option value="4">Counter 4</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="SFI.hideModal('userModal')">Cancel</button>
            <button class="btn btn-primary" id="btnSaveUser" onclick="saveUser()">Save</button>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal-overlay" id="resetPasswordModal">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h3>Reset Password</h3>
            <button class="modal-close" onclick="SFI.hideModal('resetPasswordModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="resetUserId">
            <div class="form-group">
                <label for="newPassword">New Password *</label>
                <input type="password" id="newPassword" class="form-control" placeholder="Minimum 6 characters">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="SFI.hideModal('resetPasswordModal')">Cancel</button>
            <button class="btn btn-warning" onclick="doResetPassword()">Reset</button>
        </div>
    </div>
</div>

<script>
let usersList = [];

async function loadUsers() {
    try {
        const res = await SFI.get('/api/users/list.php');
        if (res.success) {
            usersList = res.data.users;
            renderUsers();
        }
    } catch (e) { SFI.toast('Failed to load users.', 'error'); }
}

function renderUsers() {
    const tbody = document.getElementById('usersTableBody');
    if (usersList.length === 0) { tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted" style="padding:40px;">No users found.</td></tr>'; return; }
    let html = '';
    usersList.forEach(u => {
        const roleBadge = u.role === 'admin' ? 'badge-admin' : (u.role === 'employee' ? 'badge-employee' : 'badge-teller');
        const statusBadge = u.status === 'active' ? 'badge-active' : 'badge-inactive';
        html += '<tr>';
        html += '<td><strong>' + SFI.escapeHtml(u.full_name) + '</strong></td>';
        html += '<td>' + SFI.escapeHtml(u.username) + '</td>';
        html += '<td><span class="badge ' + roleBadge + '">' + u.role + '</span></td>';
        html += '<td>Counter ' + u.assigned_counter + '</td>';
        html += '<td><span class="badge ' + statusBadge + '">' + u.status + '</span></td>';
        html += '<td>' + SFI.formatDate(u.created_at) + '</td>';
        html += '<td><div class="flex gap-1">';
        html += '<button class="btn btn-sm btn-secondary" onclick="editUser(' + u.id + ')">Edit</button>';
        html += '<button class="btn btn-sm btn-outline" onclick="showResetPassword(' + u.id + ')">Reset PW</button>';
        html += '<button class="btn btn-sm ' + (u.status === 'active' ? 'btn-danger' : 'btn-primary') + '" onclick="toggleStatus(' + u.id + ')">' + (u.status === 'active' ? 'Deactivate' : 'Activate') + '</button>';
        html += '</div></td></tr>';
    });
    tbody.innerHTML = html;
}

function showAddUser() {
    document.getElementById('userModalTitle').textContent = 'Add User';
    document.getElementById('userId').value = '';
    document.getElementById('userName').value = '';
    document.getElementById('userUsername').value = '';
    document.getElementById('userPassword').value = '';
    document.getElementById('userRole').value = 'teller';
    document.getElementById('userCounter').value = '1';
    document.getElementById('usernameGroup').style.display = '';
    document.getElementById('passwordGroup').style.display = '';
    SFI.showModal('userModal');
}

function editUser(id) {
    const u = usersList.find(x => x.id === id);
    if (!u) return;
    document.getElementById('userModalTitle').textContent = 'Edit User';
    document.getElementById('userId').value = u.id;
    document.getElementById('userName').value = u.full_name;
    document.getElementById('userUsername').value = u.username;
    document.getElementById('userRole').value = u.role;
    document.getElementById('userCounter').value = u.assigned_counter;
    document.getElementById('usernameGroup').style.display = 'none';
    document.getElementById('passwordGroup').style.display = 'none';
    SFI.showModal('userModal');
}

async function saveUser() {
    const id = document.getElementById('userId').value;
    const isEdit = !!id;
    const data = { full_name: document.getElementById('userName').value };

    if (isEdit) {
        data.id = id;
        data.role = document.getElementById('userRole').value;
        data.assigned_counter = document.getElementById('userCounter').value;
    } else {
        data.username = document.getElementById('userUsername').value;
        data.password = document.getElementById('userPassword').value;
        data.full_name = data.full_name;
        data.role = document.getElementById('userRole').value;
        data.assigned_counter = document.getElementById('userCounter').value;
    }

    const btn = document.getElementById('btnSaveUser');
    SFI.setButtonLoading(btn, true);
    try {
        const res = await SFI.post(isEdit ? '/api/users/update.php' : '/api/users/create.php', data);
        if (res.success) {
            SFI.toast(res.message, 'success');
            SFI.hideModal('userModal');
            loadUsers();
        } else {
            SFI.toast(res.message, 'error');
        }
    } catch (e) { SFI.toast('Failed to save user.', 'error'); }
    SFI.setButtonLoading(btn, false, 'Save');
}

async function toggleStatus(id) {
    const u = usersList.find(x => x.id === id);
    if (!u) return;
    const action = u.status === 'active' ? 'deactivate' : 'activate';
    const ok = await SFI.confirm('Confirm', 'Are you sure you want to ' + action + ' ' + u.full_name + '?');
    if (!ok) return;
    try {
        const res = await SFI.post('/api/users/toggle-status.php', { id: id });
        SFI.toast(res.message, res.success ? 'success' : 'error');
        if (res.success) loadUsers();
    } catch (e) { SFI.toast('Failed to update status.', 'error'); }
}

function showResetPassword(id) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('newPassword').value = '';
    SFI.showModal('resetPasswordModal');
}

async function doResetPassword() {
    const id = document.getElementById('resetUserId').value;
    const pw = document.getElementById('newPassword').value;
    if (!pw || pw.length < 6) { SFI.toast('Password must be at least 6 characters.', 'error'); return; }
    try {
        const res = await SFI.post('/api/users/reset-password.php', { id: id, new_password: pw });
        SFI.toast(res.message, res.success ? 'success' : 'error');
        if (res.success) SFI.hideModal('resetPasswordModal');
    } catch (e) { SFI.toast('Failed to reset password.', 'error'); }
}

window.refreshAll = loadUsers;
document.addEventListener('DOMContentLoaded', loadUsers);
</script>

<?php admin_footer(); ?>
