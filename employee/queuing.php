<?php
/**
 * SFI Queuing System - Employee Queuing Dashboard
 * For tellers. Blue-themed design (based on the example dashboard).
 * Features: queue stats, now serving, call next / recall / complete / no-show / transfer.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
initPage();

$user   = getSessionUser();
$role   = $user['role'] ?? 'teller';
$name   = $user['full_name'] ?? ($user['username'] ?? 'Employee');
$first  = ucwords(strtolower(explode(' ', trim($name))[0] ?? ''));
$counter = getAssignedCounter();

$parts = explode(' ', trim($name));
$initials = strtoupper(substr($parts[0][0] ?? '', 0, 1) . substr($parts[1][0] ?? '', 0, 1));

$userAvatar = '';
try {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT avatar FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => (int)$user['id']]);
    $userAvatar = (string)$stmt->fetchColumn();
} catch (Exception $e) { /* ignore */ }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queuing Dashboard - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --sd-blue:  #042698;
            --sd-blue2: #03206e;
            --hd-blue:  #0A3992;
            --acc-blue: #1E5AE8;
            --acc-soft: #DBE2EB;
            --acc-lilac:#8B8FD5;
            --ink:      #1e293b;
            --muted:    #64748b;
            --border:   #e2e8f0;
            --bg:       #f1f5f9;
            --green:    #10b981;
            --amber:    #f59e0b;
            --red:      #ef4444;
            --purple:   #8b5cf6;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
        }

        /* ---------- Sidebar ---------- */
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: linear-gradient(180deg, var(--sd-blue) 0%, var(--sd-blue2) 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            transition: transform .25s ease;
        }
        .sidebar-brand {
            display: flex; align-items: center; gap: 12px;
            padding: 20px 18px;
            border-bottom: 1px solid rgba(255,255,255,.12);
        }
        .brand-mark {
            width: 44px; height: 44px;
            background: rgba(255,255,255,.15);
            border-radius: 10px;
            padding: 4px;
        }
        .brand-mark img { width: 100%; height: 100%; object-fit: contain; border-radius: 6px; }
        .sidebar-brand h2 { font-size: .92rem; font-weight: 700; line-height: 1.2; }
        .sidebar-brand p  { font-size: .62rem; color: rgba(255,255,255,.65); }
        .sidebar-nav { flex: 1; padding: 14px 12px; overflow-y: auto; }
        .sidebar-section {
            font-size: .62rem; text-transform: uppercase; letter-spacing: 1.5px;
            color: rgba(255,255,255,.45); padding: 12px 10px 6px;
        }
        .sidebar-link {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 12px; border-radius: 10px;
            color: rgba(255,255,255,.82);
            text-decoration: none; font-size: .82rem; font-weight: 500;
            margin-bottom: 2px; transition: background .15s ease;
        }
        .sidebar-link:hover { background: rgba(255,255,255,.10); color: #fff; }
        .sidebar-link.active { background: rgba(255,255,255,.18); color: #fff; font-weight: 600; }
        .sidebar-link .icon { width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; }
        .sidebar-link .icon svg { width: 18px; height: 18px; }
        .sidebar-footer { padding: 14px 12px; border-top: 1px solid rgba(255,255,255,.12); }

        /* ---------- Main ---------- */
        .main { flex: 1; margin-left: 250px; display: flex; flex-direction: column; min-height: 100vh; }

        /* ---------- Header ---------- */
        .topbar {
            background: linear-gradient(90deg, var(--hd-blue) 0%, var(--acc-blue) 100%);
            color: #fff; padding: 14px 26px;
            display: flex; align-items: center; gap: 16px;
            box-shadow: 0 2px 12px rgba(4,38,152,.25);
            position: sticky; top: 0; z-index: 90;
        }
        .hamburger {
            display: none; background: rgba(255,255,255,.15); border: none; color: #fff;
            font-size: 1.1rem; width: 38px; height: 38px; border-radius: 9px; cursor: pointer;
        }
        .topbar-title h1 { font-size: 1.02rem; font-weight: 700; }
        .topbar-title p  { font-size: .68rem; color: rgba(255,255,255,.75); }
        .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 14px; }
        .conn-pill {
            font-size: .62rem; padding: 5px 12px; border-radius: 999px;
            background: rgba(255,255,255,.14); display: flex; align-items: center; gap: 6px;
        }
        .conn-pill .dot { width: 8px; height: 8px; border-radius: 50%; background: #f87171; }
        .conn-pill.connected .dot { background: #4ade80; }
        .user-chip {
            display: flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,.12); padding: 6px 14px 6px 6px; border-radius: 999px;
        }
        .user-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--acc-lilac); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .75rem; overflow: hidden;
        }
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-chip .u-name { font-size: .75rem; font-weight: 600; line-height: 1.1; }
        .user-chip .u-role { font-size: .62rem; color: rgba(255,255,255,.7); }

        /* ---------- Content ---------- */
        .content { padding: 24px 26px; flex: 1; }

        .welcome-banner {
            background: linear-gradient(120deg, var(--sd-blue) 0%, var(--acc-blue) 70%, var(--acc-lilac) 130%);
            color: #fff; border-radius: 18px; padding: 24px 30px;
            display: flex; align-items: center; justify-content: space-between; gap: 18px;
            margin-bottom: 24px; box-shadow: 0 8px 24px rgba(4,38,152,.28);
            position: relative; overflow: hidden;
        }
        .welcome-banner::after {
            content: ''; position: absolute; right: -60px; top: -80px;
            width: 240px; height: 240px; border-radius: 50%; background: rgba(255,255,255,.08);
        }
        .welcome-banner h2 { font-size: 1.25rem; font-weight: 700; }
        .welcome-banner p  { font-size: .78rem; color: rgba(255,255,255,.82); margin-top: 4px; }
        .welcome-chip {
            background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.25);
            padding: 10px 18px; border-radius: 12px; font-size: .78rem; font-weight: 600;
            display: flex; align-items: center; gap: 8px; white-space: nowrap; z-index: 1;
        }

        /* Stat cards */
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 24px; }
        .stat-card {
            background: #fff; border-radius: 14px; padding: 18px 20px;
            display: flex; align-items: center; gap: 14px;
            border: 1px solid var(--border); box-shadow: 0 2px 10px rgba(4,38,152,.06);
        }
        .stat-icon {
            width: 46px; height: 46px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .stat-icon svg { width: 22px; height: 22px; }
        .stat-icon.blue  { background: rgba(30,90,232,.12); color: var(--acc-blue); }
        .stat-icon.amber { background: rgba(245,158,11,.14); color: var(--amber); }
        .stat-icon.green { background: rgba(16,185,129,.14); color: var(--green); }
        .stat-icon.red   { background: rgba(239,68,68,.12); color: var(--red); }
        .stat-info h3 { font-size: 1.55rem; font-weight: 800; line-height: 1; }
        .stat-info p  { font-size: .7rem; color: var(--muted); margin-top: 4px; font-weight: 500; }

        /* Serving + actions */
        .serving-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 24px; }
        .now-serving {
            background: #fff; border-radius: 16px; border: 1px solid var(--border);
            padding: 24px 26px; text-align: center;
            box-shadow: 0 2px 10px rgba(4,38,152,.06);
        }
        .now-serving .ns-label {
            font-size: .68rem; font-weight: 700; letter-spacing: 2px;
            color: var(--muted); text-transform: uppercase;
        }
        .now-serving .ns-ticket {
            font-size: 2.6rem; font-weight: 800; color: var(--sd-blue);
            margin: 6px 0 2px; letter-spacing: 1px;
        }
        .now-serving .ns-client { font-size: .92rem; font-weight: 600; }
        .now-serving .ns-sub { font-size: .72rem; color: var(--muted); margin-top: 2px; }
        .now-serving.empty .ns-ticket { color: #cbd5e1; }

        .actions-panel {
            background: #fff; border-radius: 16px; border: 1px solid var(--border);
            padding: 22px 24px; box-shadow: 0 2px 10px rgba(4,38,152,.06);
        }
        .actions-panel h3 { font-size: .9rem; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
        .actions-panel h3 .n {
            width: 22px; height: 22px; border-radius: 7px; background: var(--sd-blue);
            color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: .7rem;
        }
        .control-buttons { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; }
        .control-btn {
            border: none; border-radius: 12px; padding: 14px 10px;
            font-family: inherit; font-size: .8rem; font-weight: 700; color: #fff;
            cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 4px;
            transition: transform .12s ease, box-shadow .12s ease, opacity .12s ease;
        }
        .control-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 14px rgba(4,38,152,.22); }
        .control-btn:disabled { opacity: .45; cursor: not-allowed; }
        .control-btn svg { width: 20px; height: 20px; }
        .control-btn .btn-label { font-size: .6rem; font-weight: 500; opacity: .85; }
        .control-btn.call-next { background: linear-gradient(135deg, var(--acc-blue), var(--sd-blue)); }
        .control-btn.recall    { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .control-btn.complete  { background: linear-gradient(135deg, #10b981, #059669); }
        .control-btn.no-show   { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .control-btn.transfer  { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

        /* Card/table */
        .card {
            background: #fff; border-radius: 16px; border: 1px solid var(--border);
            box-shadow: 0 2px 10px rgba(4,38,152,.06); overflow: hidden;
        }
        .card-header {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 16px 22px; border-bottom: 1px solid var(--border);
        }
        .card-header h3 { font-size: .95rem; font-weight: 700; }
        .card-header .text-sm { font-size: .7rem; color: var(--muted); }
        .filter-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
        .filter-tab {
            border: 1px solid var(--border); background: #fff; color: var(--muted);
            border-radius: 999px; padding: 6px 14px; font-size: .7rem; font-weight: 600;
            font-family: inherit; cursor: pointer; transition: all .12s ease;
        }
        .filter-tab.active { background: var(--sd-blue); border-color: var(--sd-blue); color: #fff; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .78rem; }
        thead th {
            text-align: left; padding: 12px 22px; background: #f8fafc;
            color: var(--muted); font-size: .66rem; text-transform: uppercase; letter-spacing: .8px;
            border-bottom: 1px solid var(--border);
        }
        tbody td { padding: 12px 22px; border-bottom: 1px solid #f1f5f9; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8fafc; }
        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 999px;
            font-size: .64rem; font-weight: 700; text-transform: capitalize;
        }
        .badge-waiting    { background: rgba(245,158,11,.14); color: #b45309; }
        .badge-serving    { background: rgba(30,90,232,.12); color: var(--acc-blue); }
        .badge-completed  { background: rgba(16,185,129,.14); color: #047857; }
        .badge-no-show    { background: rgba(239,68,68,.12); color: #b91c1c; }
        .badge-cancelled  { background: rgba(100,116,139,.14); color: #475569; }
        .badge-transferred{ background: rgba(139,92,246,.14); color: #6d28d9; }
        .text-muted { color: var(--muted); }
        .text-center { text-align: center; }

        /* Modal */
        .modal-overlay { position: fixed; inset: 0; background: rgba(2,12,40,.55); display: none; align-items: center; justify-content: center; z-index: 200; }
        .modal-overlay.show { display: flex; }
        .modal { background: #fff; border-radius: 16px; width: min(420px, 92vw); overflow: hidden; box-shadow: 0 20px 50px rgba(2,12,40,.35); }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 22px; background: var(--sd-blue); color: #fff; }
        .modal-header h3 { font-size: .92rem; }
        .modal-close { background: rgba(255,255,255,.15); border: none; color: #fff; width: 30px; height: 30px; border-radius: 8px; font-size: 1rem; cursor: pointer; }
        .modal-body { padding: 20px 22px; }
        .modal-body label { font-size: .72rem; font-weight: 600; color: var(--muted); display: block; margin-bottom: 6px; }
        .modal-body select { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 10px; font-family: inherit; font-size: .82rem; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 14px 22px; border-top: 1px solid var(--border); }
        .btn { border: none; border-radius: 10px; padding: 9px 18px; font-family: inherit; font-size: .78rem; font-weight: 600; cursor: pointer; }
        .btn-secondary { background: #e2e8f0; color: var(--ink); }
        .btn-primary   { background: var(--sd-blue); color: #fff; }

        @media (max-width: 1100px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } .serving-row { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .hamburger { display: block; }
            .content { padding: 16px; }
            .welcome-banner { flex-direction: column; align-items: flex-start; }
            .conn-pill { display: none; }
        }
    </style>
</head>
<body>

    <!-- ===== Sidebar (dark blue) ===== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark"><img src="<?= BASE_URL ?>/background/logo.png" alt="SFI Logo"></div>
            <div>
                <h2>SFI QUEUING</h2>
                <p>Employee Portal</p>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">Menu</div>
            <a href="<?= BASE_URL ?>/employee/queuing.php" class="sidebar-link active">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg></span> Queuing
            </a>
            <a href="<?= BASE_URL ?>/admin/history.php" class="sidebar-link">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span> Queue History
            </a>
            <a href="<?= BASE_URL ?>/admin/profile.php" class="sidebar-link">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span> My Profile
            </a>
            <?php if ($role === 'admin'): ?>
            <div class="sidebar-section">Admin</div>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-link">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span> Admin Panel
            </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="#" class="sidebar-link" data-logout>
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span> Logout
            </a>
        </div>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(2,12,40,.5);z-index:95;"></div>

    <!-- ===== Main ===== -->
    <div class="main">
        <header class="topbar">
            <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu">&#9776;</button>
            <div class="topbar-title">
                <h1>Queuing Dashboard</h1>
                <p>Smart Loan Queue Management System</p>
            </div>
            <div class="topbar-right">
                <span class="conn-pill" id="connStatus"><span class="dot"></span> CONNECTING</span>
                <div class="user-chip">
                    <div class="user-avatar">
                        <?php if ($userAvatar): ?>
                            <img src="data:image/png;base64,<?= htmlspecialchars($userAvatar) ?>" alt="Avatar">
                        <?php else: ?>
                            <?= htmlspecialchars($initials) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="u-name"><?= htmlspecialchars($name) ?></div>
                        <div class="u-role"><?= ucfirst(htmlspecialchars($role)) ?></div>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            <!-- Welcome banner -->
            <div class="welcome-banner">
                <div>
                    <h2>Welcome back, <?= htmlspecialchars($first) ?>! 👋</h2>
                    <p>Manage today's queue and serve your clients from here.</p>
                </div>
                <div class="welcome-chip">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    Counter <?= (int)$counter ?>
                </div>
            </div>

            <!-- Stat cards -->
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                    <div class="stat-info"><h3 id="statWaiting">0</h3><p>Waiting Clients</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                    <div class="stat-info"><h3 id="statServing">0</h3><p>Now Serving</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                    <div class="stat-info"><h3 id="statCompleted">0</h3><p>Completed Today</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
                    <div class="stat-info"><h3 id="statNoShow">0</h3><p>No-Shows</p></div>
                </div>
            </div>

            <!-- Now serving + actions -->
            <div class="serving-row">
                <div class="now-serving empty" id="nowServingPanel">
                    <div class="ns-label">NOW SERVING</div>
                    <div class="ns-ticket" id="nsTicket">---</div>
                    <div class="ns-client" id="nsClient">No client being served</div>
                    <div class="ns-sub" id="nsSub"></div>
                </div>
                <div class="actions-panel">
                    <h3><span class="n">1</span> Queue Controls</h3>
                    <div class="control-buttons">
                        <button class="control-btn call-next" id="btnCallNext" onclick="callNext()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            Call Next
                            <span class="btn-label">Next waiting client</span>
                        </button>
                        <button class="control-btn recall" id="btnRecall" onclick="recallTicket()" disabled>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                            Recall
                            <span class="btn-label">Re-announce current</span>
                        </button>
                        <button class="control-btn complete" id="btnComplete" onclick="completeTicket()" disabled>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            Mark Served
                            <span class="btn-label">Complete transaction</span>
                        </button>
                        <button class="control-btn no-show" id="btnNoShow" onclick="markNoShow()" disabled>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                            No-Show
                            <span class="btn-label">Client did not appear</span>
                        </button>
                        <button class="control-btn transfer" id="btnTransfer" onclick="showTransferModal()" disabled>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                            Transfer
                            <span class="btn-label">To another counter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Today's queue -->
            <div class="card">
                <div class="card-header">
                    <h3>Today's Queue</h3>
                    <span class="text-sm" id="lastUpdated">Loading...</span>
                </div>
                <div style="padding:14px 22px 0;">
                    <div class="filter-tabs" id="filterTabs">
                        <button class="filter-tab active" data-filter="all">All</button>
                        <button class="filter-tab" data-filter="PY">Payment</button>
                        <button class="filter-tab" data-filter="RL">Release</button>
                        <button class="filter-tab" data-filter="CS">Customer Services</button>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Client</th>
                                <th>Loan Type</th>
                                <th>Status</th>
                                <th>Time</th>
                                <th>Counter</th>
                            </tr>
                        </thead>
                        <tbody id="queueTableBody">
                            <tr><td colspan="6" class="text-center text-muted" style="padding:40px;">Loading queue data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer modal -->
    <div class="modal-overlay" id="transferModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Transfer Ticket</h3>
                <button class="modal-close" onclick="hideTransferModal()">&times;</button>
            </div>
            <div class="modal-body">
                <label for="transferCounter">Select Counter</label>
                <select id="transferCounter">
                    <option value="1">Counter 1</option>
                    <option value="2">Counter 2</option>
                    <option value="3">Counter 3</option>
                    <option value="4">Counter 4</option>
                </select>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideTransferModal()">Cancel</button>
                <button class="btn btn-primary" onclick="doTransfer()">Transfer</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= filemtime(ROOT_PATH . '/assets/js/app.js') ?>"></script>
    <script src="<?= BASE_URL ?>/assets/js/socket.js?v=<?= filemtime(ROOT_PATH . '/assets/js/socket.js') ?>"></script>
    <script>
        // ============================================
        // Employee Queuing Dashboard JavaScript
        // ============================================
        let currentServingTicket = null;
        let currentFilter = 'all';
        let allTickets = [];

        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        document.getElementById('hamburgerBtn').addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
        });
        overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.style.display = 'none'; });

        async function loadStats() {
            try {
                const res = await SFI.get('/api/queue/statistics.php');
                if (res.success) {
                    const s = res.data;
                    document.getElementById('statWaiting').textContent = s.waiting || 0;
                    document.getElementById('statServing').textContent = s.serving || 0;
                    document.getElementById('statCompleted').textContent = s.completed || 0;
                    document.getElementById('statNoShow').textContent = s.no_show || 0;
                }
            } catch (e) { console.error('Load stats error:', e); }
        }

        async function loadQueue() {
            try {
                const res = await SFI.get('/api/queue/today.php');
                if (res.success) {
                    allTickets = res.data.tickets || [];
                    renderQueueTable();
                    updateNowServing(res.data.serving_ticket || null);
                    document.getElementById('lastUpdated').textContent =
                        'Updated: ' + new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                }
            } catch (e) { console.error('Load queue error:', e); }
        }

        function renderQueueTable() {
            const tbody = document.getElementById('queueTableBody');
            let filtered = allTickets;
            if (currentFilter !== 'all') {
                filtered = allTickets.filter(t => t.prefix === currentFilter);
            }
            if (filtered.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted" style="padding:40px;">No tickets found.</td></tr>';
                return;
            }
            let html = '';
            filtered.forEach(t => {
                const badgeClass = {
                    'waiting': 'badge-waiting', 'serving': 'badge-serving', 'completed': 'badge-completed',
                    'no_show': 'badge-no-show', 'cancelled': 'badge-cancelled', 'transferred': 'badge-transferred'
                }[t.status] || '';
                html += '<tr>';
                html += '<td><strong>' + SFI.escapeHtml(t.ticket_number) + '</strong></td>';
                html += '<td>' + SFI.escapeHtml(t.client_name) + '</td>';
                html += '<td>' + SFI.escapeHtml(t.loan_type_name || t.prefix) + '</td>';
                html += '<td><span class="badge ' + badgeClass + '">' + t.status.replace('_', ' ') + '</span></td>';
                html += '<td>' + SFI.formatTime(t.created_at) + '</td>';
                html += '<td>' + (t.counter_assigned ? 'Counter ' + t.counter_assigned : '-') + '</td>';
                html += '</tr>';
            });
            tbody.innerHTML = html;
        }

        function updateNowServing(ticket) {
            const panel = document.getElementById('nowServingPanel');
            const btnRecall = document.getElementById('btnRecall');
            const btnComplete = document.getElementById('btnComplete');
            const btnNoShow = document.getElementById('btnNoShow');
            const btnTransfer = document.getElementById('btnTransfer');
            const btnCallNext = document.getElementById('btnCallNext');

            if (ticket && ticket.id) {
                currentServingTicket = ticket;
                panel.classList.remove('empty');
                document.getElementById('nsTicket').textContent = ticket.ticket_number;
                document.getElementById('nsClient').textContent = ticket.client_name;
                document.getElementById('nsSub').textContent =
                    (ticket.loan_type_name ? ticket.loan_type_name + ' · ' : '') + 'Counter ' + (ticket.counter_assigned || '-');
                btnRecall.disabled = false;
                btnComplete.disabled = false;
                btnNoShow.disabled = false;
                btnTransfer.disabled = false;
                btnCallNext.disabled = true;
            } else {
                currentServingTicket = null;
                panel.classList.add('empty');
                document.getElementById('nsTicket').textContent = '---';
                document.getElementById('nsClient').textContent = 'No client being served';
                document.getElementById('nsSub').textContent = '';
                btnRecall.disabled = true;
                btnComplete.disabled = true;
                btnNoShow.disabled = true;
                btnTransfer.disabled = true;
                btnCallNext.disabled = false;
            }
        }

        async function callNext() {
            const btn = document.getElementById('btnCallNext');
            btn.disabled = true;
            try {
                const res = await SFI.post('/api/queue/call-next.php');
                if (res.success) {
                    SFI.toast(res.message, 'success');
                    await loadStats(); await loadQueue();
                } else {
                    SFI.toast(res.message, 'error');
                    btn.disabled = false;
                }
            } catch (e) {
                SFI.toast('Failed to call next. Please try again.', 'error');
                btn.disabled = false;
            }
        }

        async function recallTicket() {
            if (!currentServingTicket) return;
            try {
                const res = await SFI.post('/api/queue/recall.php');
                if (res.success) {
                    SFI.toast('Ticket recalled: ' + currentServingTicket.ticket_number, 'info');
                } else {
                    SFI.toast(res.message, 'error');
                }
            } catch (e) {
                SFI.toast('Failed to recall. Please try again.', 'error');
            }
        }

        async function completeTicket() {
            if (!currentServingTicket) return;
            const confirmed = await SFI.confirm('Mark as Served', 'Mark ' + currentServingTicket.ticket_number + ' as completed?');
            if (!confirmed) return;
            try {
                const res = await SFI.post('/api/queue/complete.php');
                if (res.success) {
                    SFI.toast(res.message, 'success');
                    await loadStats(); await loadQueue();
                } else {
                    SFI.toast(res.message, 'error');
                }
            } catch (e) {
                SFI.toast('Failed to complete. Please try again.', 'error');
            }
        }

        async function markNoShow() {
            if (!currentServingTicket) return;
            const confirmed = await SFI.confirm('Mark as No-Show', 'Are you sure you want to mark ' + currentServingTicket.ticket_number + ' as NO-SHOW?');
            if (!confirmed) return;
            try {
                const res = await SFI.post('/api/queue/no-show.php');
                if (res.success) {
                    SFI.toast(res.message, 'warning');
                    await loadStats(); await loadQueue();
                } else {
                    SFI.toast(res.message, 'error');
                }
            } catch (e) {
                SFI.toast('Failed to mark no-show. Please try again.', 'error');
            }
        }

        function showTransferModal() {
            if (!currentServingTicket) return;
            document.getElementById('transferModal').classList.add('show');
        }
        function hideTransferModal() {
            document.getElementById('transferModal').classList.remove('show');
        }
        async function doTransfer() {
            if (!currentServingTicket) return;
            const counter = document.getElementById('transferCounter').value;
            try {
                const res = await SFI.post('/api/queue/transfer.php', { counter: counter });
                if (res.success) {
                    hideTransferModal();
                    SFI.toast(res.message, 'success');
                    await loadStats(); await loadQueue();
                } else {
                    SFI.toast(res.message, 'error');
                }
            } catch (e) {
                SFI.toast('Failed to transfer. Please try again.', 'error');
            }
        }

        window.refreshAll = async function () { await loadStats(); await loadQueue(); };

        SFISocket.on('queue_updated', function () { loadStats(); loadQueue(); });
        SFISocket.on('announce_ticket', function () { loadStats(); loadQueue(); });

        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                currentFilter = this.dataset.filter;
                renderQueueTable();
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            loadStats();
            loadQueue();
            SFISocket.connect('<?= SOCKET_SERVER ?>');
            setInterval(function () { loadStats(); loadQueue(); }, 30000);
        });
    </script>
</body>
</html>
