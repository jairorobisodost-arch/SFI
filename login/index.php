<?php
/**
 * SFI Queuing System - Login Page
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Redirect if already logged in (role-based)
// admin -> Admin Dashboard | teller -> Employee Queuing | employee -> Employee Attendance
if (isLoggedIn()) {
    $u = getSessionUser();
    $role = $u['role'] ?? '';
    if ($role === 'admin') {
        redirect('admin/dashboard.php');
    } else {
        redirect('employee/queuing.php');
    }
}

initPage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
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
            background:
                radial-gradient(ellipse 85% 65% at 18% 8%, rgba(47, 211, 154, 0.22) 0%, transparent 55%),
                radial-gradient(ellipse 70% 55% at 85% 25%, rgba(14, 159, 110, 0.25) 0%, transparent 55%),
                radial-gradient(ellipse 100% 75% at 50% 115%, rgba(6, 37, 27, 0.92) 0%, transparent 62%),
                linear-gradient(160deg, #06251B 0%, #0B3B2E 48%, #0E9F6E 100%);
            padding: 20px;
        }
        .login-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            padding: 44px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: var(--shadow-xl);
        }
        .login-brand {
            text-align: center;
            margin-bottom: 34px;
        }
        .login-brand .logo-icon {
            width: 64px;
            height: 64px;
            background: var(--primary-soft);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .login-brand h1 {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--primary-deep);
            margin-bottom: 4px;
        }
        .login-brand p {
            color: var(--muted);
            font-size: 0.85rem;
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
            margin-bottom: 20px;
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
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <h1><?= APP_NAME ?></h1>
                <p><?= APP_SUBTITLE ?></p>
            </div>

            <div class="login-error" id="loginError">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <span id="loginErrorText">Invalid username or password.</span>
            </div>

            <form class="login-form" id="loginForm" autocomplete="off">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-login" id="btnLogin">
                    <span class="btn-text">LOGIN</span>
                </button>
            </form>

            <div class="login-footer">
                <span class="text-muted" style="font-size:0.85rem;">Contact the administrator if you cannot login.</span>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const form = document.getElementById('loginForm');
        const btnLogin = document.getElementById('btnLogin');
        const loginError = document.getElementById('loginError');
        const loginErrorText = document.getElementById('loginErrorText');
        const btnText = btnLogin.querySelector('.btn-text');

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Reset error
            loginError.classList.remove('visible');

            // Validate
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();

            if (!username || !password) {
                loginErrorText.textContent = 'Please enter your username and password.';
                loginError.classList.add('visible');
                return;
            }

            // Disable button, show loading
            btnLogin.disabled = true;
            btnLogin.classList.add('btn-loading');
            btnText.textContent = 'SIGNING IN...';

            try {
                const formData = new FormData();
                formData.append('username', username);
                formData.append('password', password);

                const response = await fetch('<?= BASE_URL ?>/api/auth/login.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();

                if (data.success) {
                    btnText.textContent = 'SUCCESS!';
                    // Force the user to change their password first (after an admin reset)
                    if (data.data.user && data.data.user.force_password_change) {
                        window.location.href = '<?= BASE_URL ?>/login/change-password.php';
                    } else {
                        window.location.href = data.data.redirect;
                    }
                } else {
                    loginErrorText.textContent = data.message;
                    loginError.classList.add('visible');
                    btnLogin.disabled = false;
                    btnLogin.classList.remove('btn-loading');
                    btnText.textContent = 'LOGIN';
                }
            } catch (err) {
                loginErrorText.textContent = 'A network error occurred. Please try again.';
                loginError.classList.add('visible');
                btnLogin.disabled = false;
                btnLogin.classList.remove('btn-loading');
                btnText.textContent = 'LOGIN';
            }
        });
    })();
    </script>
</body>
</html>
