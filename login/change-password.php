<?php
/**
 * SFI Queuing System - Change Password Page
 * URL: /login/change-password.php
 *
 * Shown after login when the admin reset the user's password
 * (force_password_change = 1). The user must set a new password
 * before they can use the rest of the system.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

initPage();

$user = getSessionUser();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(ROOT_PATH . '/assets/css/style.css') ?>">
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-deep) 0%, #0C5C44 50%, var(--primary-dark) 100%);
            padding: 20px;
        }
        .login-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            padding: 44px 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: var(--shadow-xl);
        }
        .login-brand {
            text-align: center;
            margin-bottom: 28px;
        }
        .login-brand .logo-icon {
            width: 64px;
            height: 64px;
            background: var(--warning-soft, #FEF3C7);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .login-brand h1 {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--primary-deep);
            margin-bottom: 4px;
        }
        .login-brand p {
            color: var(--muted);
            font-size: 0.85rem;
        }
        .login-brand .who {
            margin-top: 10px;
            font-size: 0.8rem;
            color: var(--muted);
        }
        .login-error {
            background: var(--danger-soft);
            color: var(--danger);
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 8px;
        }
        .login-error.visible {
            display: flex;
        }
        .login-form .form-group {
            margin-bottom: 18px;
        }
        .login-form .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
        }
        .login-form .form-control {
            padding: 12px 16px;
            font-size: 0.95rem;
        }
        .login-form .btn-login {
            width: 100%;
            padding: 14px;
            font-size: 1rem;
            font-weight: 700;
            margin-top: 8px;
        }
        .login-footer {
            text-align: center;
            margin-top: 24px;
        }
        .login-footer a {
            font-size: 0.85rem;
            color: var(--primary);
            font-weight: 500;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
        .pw-hint {
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 5px;
        }
        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-brand">
                <div class="logo-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#B45309" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
                <h1>Change Password</h1>
                <p>Kinakailangang magpalit ng password bago magpatuloy.</p>
                <div class="who">Logged in as: <strong><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></strong></div>
            </div>

            <div class="login-error" id="loginError">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <span id="loginErrorText">Error</span>
            </div>

            <form class="login-form" id="changeForm" autocomplete="off">
                <div class="form-group">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" name="current_password" class="form-control" placeholder="Enter your current password" required autofocus>
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" name="new_password" class="form-control" placeholder="Minimum 6 characters" required>
                    <div class="pw-hint">Minimum of 6 characters.</div>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password</label>
                    <input type="password" id="confirmPassword" name="confirm_password" class="form-control" placeholder="Repeat the new password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-login" id="btnChange">
                    <span class="btn-text">CHANGE PASSWORD</span>
                </button>
            </form>

            <div class="login-footer">
                <a href="#" id="logoutLink">Logout</a>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const form = document.getElementById('changeForm');
        const btnChange = document.getElementById('btnChange');
        const loginError = document.getElementById('loginError');
        const loginErrorText = document.getElementById('loginErrorText');
        const btnText = btnChange.querySelector('.btn-text');

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            loginError.classList.remove('visible');

            const currentPassword = document.getElementById('currentPassword').value.trim();
            const newPassword = document.getElementById('newPassword').value.trim();
            const confirmPassword = document.getElementById('confirmPassword').value.trim();

            if (!currentPassword || !newPassword || !confirmPassword) {
                loginErrorText.textContent = 'Please fill in all fields.';
                loginError.classList.add('visible');
                return;
            }
            if (newPassword.length < 6) {
                loginErrorText.textContent = 'New password must be at least 6 characters.';
                loginError.classList.add('visible');
                return;
            }
            if (newPassword !== confirmPassword) {
                loginErrorText.textContent = 'New password and confirmation do not match.';
                loginError.classList.add('visible');
                return;
            }

            btnChange.disabled = true;
            btnChange.classList.add('btn-loading');
            btnText.textContent = 'SAVING...';

            try {
                const formData = new FormData();
                formData.append('current_password', currentPassword);
                formData.append('new_password', newPassword);
                formData.append('confirm_password', confirmPassword);

                const response = await fetch('<?= BASE_URL ?>/api/auth/change-password.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();

                if (data.success) {
                    btnText.textContent = 'SUCCESS!';
                    setTimeout(() => {
                        window.location.href = '<?= BASE_URL ?>/admin/dashboard.php';
                    }, 800);
                } else {
                    loginErrorText.textContent = data.message;
                    loginError.classList.add('visible');
                    btnChange.disabled = false;
                    btnChange.classList.remove('btn-loading');
                    btnText.textContent = 'CHANGE PASSWORD';
                }
            } catch (err) {
                loginErrorText.textContent = 'A network error occurred. Please try again.';
                loginError.classList.add('visible');
                btnChange.disabled = false;
                btnChange.classList.remove('btn-loading');
                btnText.textContent = 'CHANGE PASSWORD';
            }
        });

        document.getElementById('logoutLink').addEventListener('click', async function(e) {
            e.preventDefault();
            await fetch('<?= BASE_URL ?>/api/auth/logout.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            window.location.href = '<?= BASE_URL ?>/login/';
        });
    })();
    </script>
</body>
</html>
