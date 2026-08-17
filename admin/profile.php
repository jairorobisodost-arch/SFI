<?php
/**
 * SFI Queuing System - My Profile Page
 * Shows the logged-in user's profile and lets them update their
 * full name and change their password.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

requireLogin();
initPage();

$user = getSessionUser();
$isAdmin = ($user['role'] === 'admin');

// Fetch latest details from the database (role, counter, created_at)
$db = Database::getConnection();
$stmt = $db->prepare("SELECT full_name, username, role, assigned_counter, avatar, created_at FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => (int)$user['id']]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$profile) $profile = $user;

$avatarUrl = !empty($profile['avatar'])
    ? BASE_URL . '/assets/uploads/avatars/' . htmlspecialchars($profile['avatar'])
    : '';

$parts = explode(' ', trim($profile['full_name']));
$initials = '';
if (count($parts) >= 2) {
    $initials = strtoupper(substr($parts[0][0] ?? '', 0, 1) . substr($parts[1][0] ?? '', 0, 1));
} elseif (!empty($parts[0])) {
    $initials = strtoupper(substr($parts[0], 0, 2));
}

$roleLabel = ucfirst($profile['role']);

admin_header('My Profile', 'profile');
?>
<style>
    .profile-grid {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 20px;
        align-items: start;
    }
    .profile-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 28px 24px;
        text-align: center;
    }
    .profile-avatar {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        background: var(--primary-soft);
        color: var(--primary-deep);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        font-weight: 800;
        margin: 0 auto 12px;
        overflow: hidden;
        position: relative;
    }
    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .avatar-actions {
        display: flex;
        flex-direction: column;
        gap: 6px;
        align-items: center;
        margin-bottom: 16px;
    }
    .avatar-actions .btn {
        font-size: 0.8rem;
    }
    .profile-card h2 {
        font-size: 1.15rem;
        margin-bottom: 4px;
    }
    .role-badge {
        display: inline-block;
        padding: 4px 14px;
        border-radius: var(--radius-pill);
        background: var(--primary-soft);
        color: var(--primary-deep);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 18px;
    }
    .profile-meta {
        text-align: left;
        border-top: 1px solid var(--border);
        padding-top: 16px;
    }
    .profile-meta .meta-row {
        display: flex;
        justify-content: space-between;
        font-size: 0.85rem;
        padding: 6px 0;
    }
    .profile-meta .meta-row span:first-child {
        color: var(--muted);
    }
    .profile-meta .meta-row span:last-child {
        font-weight: 600;
        color: var(--ink);
    }
    .profile-panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 24px;
        margin-bottom: 20px;
    }
    .profile-panel h3 {
        font-size: 1rem;
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border);
    }
    .profile-panel .form-group {
        margin-bottom: 14px;
    }
    .profile-panel input[readonly] {
        background: var(--bg);
        color: var(--muted);
    }
    .form-success {
        background: var(--success-soft);
        color: var(--success);
        padding: 12px 16px;
        border-radius: var(--radius-sm);
        font-size: 0.85rem;
        margin-bottom: 16px;
        display: none;
    }
    .form-success.visible { display: block; }
    @media (max-width: 768px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-header">
    <h1>My Profile</h1>
    <p>I-update ang iyong personal na impormasyon at password.</p>
</div>

<div class="profile-grid">
    <!-- Left: Profile card -->
    <div class="profile-card">
        <div class="profile-avatar" id="profileAvatar">
            <?php if ($avatarUrl): ?>
                <img src="<?= $avatarUrl ?>" alt="Profile picture" id="avatarImg">
            <?php else: ?>
                <span id="avatarInitials"><?= htmlspecialchars($initials) ?></span>
            <?php endif; ?>
        </div>
        <div class="avatar-actions">
            <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
            <button class="btn btn-sm btn-secondary" id="btnUploadAvatar">Upload Photo</button>
            <?php if ($avatarUrl): ?>
                <button class="btn btn-sm btn-ghost" id="btnRemoveAvatar" style="color:var(--danger);">Remove</button>
            <?php endif; ?>
        </div>
        <h2><?= htmlspecialchars($profile['full_name']) ?></h2>
        <div class="role-badge"><?= htmlspecialchars($roleLabel) ?></div>
        <div class="profile-meta">
            <div class="meta-row"><span>Username</span><span>@<?= htmlspecialchars($profile['username']) ?></span></div>
            <div class="meta-row"><span>Assigned Counter</span><span>Counter <?= (int)$profile['assigned_counter'] ?></span></div>
            <div class="meta-row"><span>Member Since</span><span><?= date('M d, Y', strtotime($profile['created_at'])) ?></span></div>
        </div>
    </div>

    <!-- Right: Edit forms -->
    <div>
        <!-- Edit profile -->
        <div class="profile-panel">
            <h3>Personal Information</h3>
            <div class="form-success" id="profileSuccess">Profile updated successfully.</div>
            <form id="profileForm" autocomplete="off">
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" class="form-control" value="<?= htmlspecialchars($profile['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" class="form-control" value="<?= htmlspecialchars($profile['username']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="roleField">Role</label>
                    <input type="text" id="roleField" class="form-control" value="<?= htmlspecialchars($roleLabel) ?>" readonly>
                </div>
                <button type="submit" class="btn btn-primary" id="btnSaveProfile">Save Changes</button>
            </form>
        </div>

        <!-- Change password -->
        <div class="profile-panel" id="change-password">
            <h3>Change Password</h3>
            <div class="form-success" id="pwSuccess">Password changed successfully.</div>
            <form id="changePwForm" autocomplete="off">
                <div class="form-group">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" class="form-control" placeholder="Enter your current password" required>
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" class="form-control" placeholder="Minimum 6 characters" required>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password</label>
                    <input type="password" id="confirmPassword" class="form-control" placeholder="Repeat the new password" required>
                </div>
                <button type="submit" class="btn btn-primary" id="btnChangePw">Update Password</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ---- Update profile ----
    const profileForm = document.getElementById('profileForm');
    const profileSuccess = document.getElementById('profileSuccess');

    profileForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        profileSuccess.classList.remove('visible');
        const fullName = document.getElementById('fullName').value.trim();
        if (!fullName) { SFI.toast('Please enter your full name.', 'error'); return; }

        const btn = document.getElementById('btnSaveProfile');
        SFI.setButtonLoading(btn, true);
        try {
            const res = await SFI.post('/api/auth/update-profile.php', { full_name: fullName });
            if (res.success) {
                profileSuccess.classList.add('visible');
                SFI.toast('Profile updated.', 'success');
            } else {
                SFI.toast(res.message || 'Failed to update profile.', 'error');
            }
        } catch (err) {
            SFI.toast('Failed to update profile.', 'error');
        }
        SFI.setButtonLoading(btn, false);
    });

    // ---- Avatar upload ----
    const avatarInput = document.getElementById('avatarInput');
    const btnUploadAvatar = document.getElementById('btnUploadAvatar');
    const btnRemoveAvatar = document.getElementById('btnRemoveAvatar');

    if (btnUploadAvatar) {
        btnUploadAvatar.addEventListener('click', () => avatarInput.click());

        avatarInput.addEventListener('change', async function () {
            if (!this.files.length) return;
            const file = this.files[0];

            if (file.size > 2 * 1024 * 1024) {
                SFI.toast('Image is too large. Maximum size is 2MB.', 'error');
                this.value = '';
                return;
            }

            const btn = btnUploadAvatar;
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.textContent = 'Uploading...';

            try {
                const fd = new FormData();
                fd.append('avatar', file);
                const res = await fetch('<?= BASE_URL ?>/api/auth/upload-avatar.php', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success) {
                    SFI.toast('Profile picture updated.', 'success');
                    // Update the avatar preview + show Remove button
                    const avatarBox = document.getElementById('profileAvatar');
                    avatarBox.innerHTML = '<img src="<?= BASE_URL ?>/assets/uploads/avatars/' + data.data.avatar + '?t=' + Date.now() + '" alt="Profile picture">';
                    if (!btnRemoveAvatar) {
                        const removeBtn = document.createElement('button');
                        removeBtn.className = 'btn btn-sm btn-ghost';
                        removeBtn.style.color = 'var(--danger)';
                        removeBtn.textContent = 'Remove';
                        removeBtn.id = 'btnRemoveAvatar';
                        removeBtn.addEventListener('click', removeAvatar);
                        btnUploadAvatar.parentElement.appendChild(removeBtn);
                    }
                    window.location.reload(); // refresh topbar avatar too
                } else {
                    SFI.toast(data.message || 'Upload failed.', 'error');
                }
            } catch (err) {
                SFI.toast('Upload failed. Please try again.', 'error');
            }
            btn.disabled = false;
            btn.innerHTML = original;
            this.value = '';
        });
    }

    // ---- Remove avatar ----
    async function removeAvatar() {
        if (!confirm('Remove your profile picture?')) return;
        try {
            const res = await SFI.post('/api/auth/upload-avatar.php', { action: 'remove' });
            if (res.success) {
                SFI.toast('Profile picture removed.', 'success');
                window.location.reload();
            } else {
                SFI.toast(res.message || 'Failed to remove.', 'error');
            }
        } catch (err) {
            SFI.toast('Failed to remove profile picture.', 'error');
        }
    }
    if (btnRemoveAvatar) {
        btnRemoveAvatar.addEventListener('click', removeAvatar);
    }

    // ---- Change password ----
    const pwForm = document.getElementById('changePwForm');
    const pwSuccess = document.getElementById('pwSuccess');

    pwForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        pwSuccess.classList.remove('visible');

        const currentPassword = document.getElementById('currentPassword').value.trim();
        const newPassword = document.getElementById('newPassword').value.trim();
        const confirmPassword = document.getElementById('confirmPassword').value.trim();

        if (!currentPassword || !newPassword || !confirmPassword) {
            SFI.toast('Please fill in all fields.', 'error');
            return;
        }
        if (newPassword.length < 6) {
            SFI.toast('New password must be at least 6 characters.', 'error');
            return;
        }
        if (newPassword !== confirmPassword) {
            SFI.toast('New password and confirmation do not match.', 'error');
            return;
        }

        const btn = document.getElementById('btnChangePw');
        SFI.setButtonLoading(btn, true);
        try {
            const res = await SFI.post('/api/auth/change-password.php', {
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword
            });
            if (res.success) {
                pwSuccess.classList.add('visible');
                SFI.toast('Password changed successfully.', 'success');
                pwForm.reset();
            } else {
                SFI.toast(res.message || 'Failed to change password.', 'error');
            }
        } catch (err) {
            SFI.toast('Failed to change password.', 'error');
        }
        SFI.setButtonLoading(btn, false);
    });
});
</script>
<?php admin_footer(); ?>
