<?php
/**
 * SFI Queuing System - Admin Layout Include
 * Call admin_header() at the top and admin_footer() at the bottom of each admin page.
 *
 * @param string $pageTitle  - Page title
 * @param string $activePage - Active sidebar link identifier
 * @param string $extraCSS   - Additional CSS files (comma-separated paths relative to BASE_URL)
 * @param string $extraHead  - Additional HTML for <head>
 */

function admin_header($pageTitle = 'Dashboard', $activePage = 'dashboard', $extraCSS = '', $extraHead = '') {
    $user = getSessionUser();
    $isAdmin = ($user['role'] === 'admin');
    $initials = '';
    $userAvatar = '';
    if ($user) {
        $parts = explode(' ', $user['full_name']);
        $initials = strtoupper(substr($parts[0][0] ?? '', 0, 1) . substr($parts[1][0] ?? '', 0, 1));
        // Profile picture (from the database so it is always up to date)
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT avatar FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => (int)$user['id']]);
            $userAvatar = (string)$stmt->fetchColumn();
        } catch (Exception $e) { /* ignore - fall back to initials */ }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(ROOT_PATH . '/assets/css/style.css') ?>">
    <script>
        // Apply saved theme before render to avoid flash
        (function () {
            try {
                if (localStorage.getItem('sfi_theme') === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (e) {}
        })();
    </script>
    <?php if ($extraCSS): ?>
        <?php foreach (explode(',', $extraCSS) as $css): ?>
            <link rel="stylesheet" href="<?= BASE_URL ?>/<?= trim($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <?= $extraHead ?>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark">
                <img src="<?= BASE_URL ?>/background/logo.png" alt="SFI Logo" style="width:100%;height:100%;object-fit:contain;border-radius:6px;">
            </div>
            <div>
                <h2>SFI QUEUING</h2>
                <p>Smart Loan Queue Management</p>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">Queue</div>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg></span> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-link <?= $activePage === 'queue' ? 'active' : '' ?>">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></span> Queue Management
            </a>
            <a href="<?= BASE_URL ?>/admin/history.php" class="sidebar-link <?= $activePage === 'history' ? 'active' : '' ?>">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span> Queue History
            </a>
            <a href="<?= BASE_URL ?>/admin/reports.php" class="sidebar-link <?= $activePage === 'reports' ? 'active' : '' ?>">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span> Reports
            </a>

            <?php if ($isAdmin): ?>
            <div class="sidebar-section">Management</div>
            <a href="<?= BASE_URL ?>/admin/users.php" class="sidebar-link <?= $activePage === 'users' ? 'active' : '' ?>">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span> Users
            </a>
            <a href="<?= BASE_URL ?>/admin/loan-types.php" class="sidebar-link <?= $activePage === 'loan-types' ? 'active' : '' ?>">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span> Loan Types
            </a>
            <a href="<?= BASE_URL ?>/admin/counters.php" class="sidebar-link <?= $activePage === 'counters' ? 'active' : '' ?>">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></span> Counters
            </a>
            <a href="<?= BASE_URL ?>/admin/import.php" class="sidebar-link <?= $activePage === 'import' ? 'active' : '' ?>">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></span> Data Import
            </a>

            <div class="sidebar-section">System</div>
            <a href="<?= BASE_URL ?>/admin/settings.php" class="sidebar-link <?= $activePage === 'settings' ? 'active' : '' ?>">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span> Settings
            </a>
            <a href="<?= BASE_URL ?>/admin/activity-logs.php" class="sidebar-link <?= $activePage === 'activity-logs' ? 'active' : '' ?>">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg></span> Activity Logs
            </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= BASE_URL ?>/website/" target="_blank" rel="noopener" class="sidebar-link" title="Open the public website in a new tab">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></span> Go to Website
            </a>
            <a href="#" class="sidebar-link" data-logout>
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span> Logout
            </a>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu">&#9776;</button>
                <div>
                    <strong style="font-size:0.9rem;"><?= htmlspecialchars($pageTitle) ?></strong>
                </div>
            </div>
            <div class="topbar-right">
                <button class="theme-toggle" id="themeToggle" title="Toggle dark mode" aria-label="Toggle dark mode">
                    <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                    <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
                <span class="connection-status disconnected">
                    <span class="connection-dot"></span> OFFLINE
                </span>
                <span style="color:var(--border-strong)">|</span>
                <span class="text-sm text-muted" style="font-weight:500;">Counter <?= (int)$user['assigned_counter'] ?></span>
                <div class="topbar-user-wrap" id="userMenuWrap">
                    <button class="topbar-user" id="userMenuBtn" aria-haspopup="true" aria-expanded="false">
                        <div class="topbar-user-avatar">
                            <?php if ($userAvatar): ?>
                                <img src="<?= BASE_URL ?>/assets/uploads/avatars/<?= htmlspecialchars($userAvatar) ?>" alt="">
                            <?php else: ?>
                                <?= htmlspecialchars($initials) ?>
                            <?php endif; ?>
                        </div>
                        <div class="topbar-user-info">
                            <strong><?= htmlspecialchars($user['full_name']) ?></strong><br>
                            <span><?= ucfirst($user['role']) ?></span>
                        </div>
                        <svg class="caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-dropdown-head">
                            <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                            <span>@<?= htmlspecialchars($user['username']) ?></span>
                        </div>
                        <a href="<?= BASE_URL ?>/admin/profile.php" class="user-dropdown-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            My Profile
                        </a>
                        <a href="<?= BASE_URL ?>/admin/profile.php#change-password" class="user-dropdown-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Change Password
                        </a>
                        <div class="user-dropdown-divider"></div>
                        <a href="#" class="user-dropdown-item danger" data-logout>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="main-content">
<?php
} // end admin_header

function admin_footer($extraJS = '') {
?>
        </div><!-- /.main-content -->
    </div><!-- /.main-wrapper -->

    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
    <script>
        // Set SFI base config
        var SFI_CONFIG = {
            baseUrl: '<?= BASE_URL ?>',
            socketServer: '<?= SOCKET_SERVER ?>'
        };
    </script>
    <script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= filemtime(ROOT_PATH . '/assets/js/app.js') ?>"></script>
    <script src="<?= BASE_URL ?>/assets/js/socket.js?v=<?= filemtime(ROOT_PATH . '/assets/js/socket.js') ?>"></script>
    <script>
        SFI.init(SFI_CONFIG);
        SFISocket.connect(SFI_CONFIG.socketServer);
        // Sync data on reconnect
        SFISocket.on('reconnected', function() {
            if (typeof window.refreshAll === 'function') window.refreshAll();
        });
    </script>
    <?php if ($extraJS): ?>
        <?php foreach (explode(',', $extraJS) as $js): ?>
            <script src="<?= BASE_URL ?>/<?= trim($js) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
<?php
} // end admin_footer
