<?php
/**
 * SFI Queuing System - HR Attendance Monitoring Dashboard (DESIGN PROTOTYPE)
 * Static design lang muna — sample data, hindi pa konektado sa database.
 * Same design gaya ng employee_dashbord.php:
 * - Dark blue sidebar (rgb 26,74,155)
 * - Blue gradient header (rgb 10,57,146)
 * Dito makikita ng HR ang TIME IN / TIME OUT ng LAHAT ng employees.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Monitoring - Design Prototype</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --sd-blue:  #1a4a9b;   /* sidebar - gaya ng example rgb(26,74,155) */
            --sd-blue2: #123a7a;
            --hd-blue:  #0a3992;   /* header - gaya ng example rgb(10,57,146) */
            --acc-blue: #1E5AE8;
            --lilac:    #8B8FD5;
            --lilac-soft: #DCDFF8;
            --ink:      #1e293b;
            --muted:    #64748b;
            --border:   #e2e8f0;
            --bg:       #f1f5f9;
            --green:    #10b981;
            --red:      #ef4444;
            --amber:    #f59e0b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
        }

        /* ---------- Sidebar (dark blue, gaya ng example) ---------- */
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
        .brand-mark { width: 44px; height: 44px; background: rgba(255,255,255,.15); border-radius: 10px; padding: 4px; }
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

        /* ---------- Header (blue, gaya ng example) ---------- */
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
        .conn-pill .dot { width: 8px; height: 8px; border-radius: 50%; background: #4ade80; }
        .user-chip {
            display: flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,.12); padding: 6px 14px 6px 6px; border-radius: 999px;
        }
        .user-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--lilac); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .75rem; overflow: hidden;
        }
        .user-chip .u-name { font-size: .75rem; font-weight: 600; line-height: 1.1; }
        .user-chip .u-role { font-size: .62rem; color: rgba(255,255,255,.7); }

        /* ---------- Content ---------- */
        .content { padding: 26px 30px; flex: 1; display: flex; flex-direction: column; }

        .page-title-row {
            display: flex; align-items: flex-end; justify-content: space-between;
            gap: 16px; flex-wrap: wrap; margin-bottom: 18px;
        }
        .page-title-row h2 {
            font-size: 1.35rem; font-weight: 800;
            color: var(--hd-blue);
            letter-spacing: .3px;
        }
        .page-title-row .title-sub { font-size: .74rem; color: var(--muted); margin-top: 3px; }

        /* Prototype ribbon */
        .proto-ribbon {
            background: linear-gradient(90deg, #f59e0b, #f97316);
            color: #fff; border-radius: 10px; padding: 10px 18px; margin-bottom: 18px;
            font-size: .74rem; font-weight: 600; display: flex; align-items: center; gap: 8px;
        }
        .proto-ribbon svg { width: 16px; height: 16px; flex-shrink: 0; }

        /* ---------- Stat cards ---------- */
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 18px; }
        @media (max-width: 900px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 480px) { .stat-grid { grid-template-columns: 1fr; } }
        .stat-card {
            background: #fff; border: 1px solid var(--border); border-radius: 16px;
            padding: 16px 18px; box-shadow: 0 2px 10px rgba(4,38,152,.06);
            display: flex; align-items: center; gap: 14px;
        }
        .stat-card .sc-icon {
            width: 46px; height: 46px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .stat-card .sc-icon svg { width: 22px; height: 22px; }
        .stat-card .sc-icon.blue  { background: rgba(30,90,232,.12); color: var(--acc-blue); }
        .stat-card .sc-icon.green { background: rgba(16,185,129,.12); color: #047857; }
        .stat-card .sc-icon.amber { background: rgba(245,158,11,.14); color: #b45309; }
        .stat-card .sc-icon.red   { background: rgba(239,68,68,.12); color: #b91c1c; }
        .stat-card .sc-label { font-size: .66rem; color: var(--muted); text-transform: uppercase; letter-spacing: .8px; }
        .stat-card .sc-value { font-size: 1.25rem; font-weight: 800; color: var(--ink); line-height: 1.2; }
        .stat-card .sc-sub { font-size: .64rem; color: var(--muted); }

        /* ---------- Cards & tables ---------- */
        .card {
            background: #fff; border-radius: 16px; border: 1px solid var(--border);
            box-shadow: 0 2px 10px rgba(4,38,152,.06); overflow: hidden;
        }
        .card-head {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 16px 22px; border-bottom: 1px solid var(--border); flex-wrap: wrap;
        }
        .card-head h3 { font-size: .95rem; font-weight: 700; color: var(--hd-blue); }
        .card-head .tools { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        /* Search box */
        .search-box {
            position: relative; display: flex; align-items: center;
        }
        .search-box svg {
            position: absolute; left: 12px; width: 15px; height: 15px;
            color: var(--muted); pointer-events: none;
        }
        .search-box input {
            border: 1px solid var(--border); border-radius: 10px;
            padding: 9px 14px 9px 36px; font-family: inherit; font-size: .78rem;
            color: var(--ink); width: 240px; max-width: 100%;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .search-box input:focus {
            outline: none; border-color: var(--acc-blue);
            box-shadow: 0 0 0 3px rgba(30,90,232,.12);
        }

        /* Filter tabs */
        .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
        .filter-tab {
            border: 1px solid var(--border); background: #fff; color: var(--muted);
            border-radius: 999px; padding: 7px 14px; font-size: .7rem; font-weight: 600;
            font-family: inherit; cursor: pointer; transition: all .12s ease;
        }
        .filter-tab:hover { border-color: var(--acc-blue); color: var(--acc-blue); }
        .filter-tab.active { background: var(--sd-blue); border-color: var(--sd-blue); color: #fff; }

        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .8rem; }
        thead th {
            text-align: left; padding: 13px 22px;
            background: var(--lilac-soft);
            color: #333a7a;
            font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .8px;
            border-bottom: 1px solid #cdd1f0; white-space: nowrap;
        }
        tbody td { padding: 13px 22px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8fafc; }

        /* Employee cell */
        .emp-cell { display: flex; align-items: center; gap: 12px; }
        .emp-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: var(--lilac); color: #fff; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .72rem;
        }
        .emp-avatar.on-duty { background: linear-gradient(135deg, #34d399, #059669); }
        .emp-avatar.late { background: linear-gradient(135deg, #fbbf24, #d97706); }
        .emp-avatar.idle { background: var(--lilac); }
        .emp-cell .e-name { font-weight: 600; font-size: .8rem; line-height: 1.2; }
        .emp-cell .e-meta { font-size: .66rem; color: var(--muted); }

        .text-muted { color: var(--muted); }
        .hours-pill { background: var(--lilac-soft); color: #4a4f9c; border-radius: 999px; padding: 3px 12px; font-weight: 700; font-size: .72rem; }

        /* Badges */
        .badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 999px;
            font-size: .64rem; font-weight: 700; text-transform: capitalize; white-space: nowrap;
        }
        .badge-present  { background: rgba(16,185,129,.14); color: #047857; }
        .badge-late     { background: rgba(245,158,11,.16); color: #b45309; }
        .badge-half_day { background: rgba(139,92,246,.14); color: #6d28d9; }
        .badge-on_leave { background: rgba(100,116,139,.14); color: #475569; }
        .badge-idle     { background: rgba(226,232,240,.6); color: #64748b; }
        .badge-on_duty  { background: rgba(16,185,129,.16); color: #047857; }

        /* Live pulsing dot */
        .live-dot {
            width: 7px; height: 7px; border-radius: 50%; background: #10b981;
            animation: pulse 1.2s ease-in-out infinite; flex-shrink: 0;
        }
        .live-dot.amber { background: #f59e0b; }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(16,185,129,.45); }
            50%      { box-shadow: 0 0 0 5px rgba(16,185,129,0); }
        }

        /* Section label */
        .section-label {
            font-size: .72rem; font-weight: 700; color: var(--hd-blue);
            text-transform: uppercase; letter-spacing: 1.5px;
            margin: 6px 0 12px; display: flex; align-items: center; gap: 8px;
        }
        .section-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }

        /* Pagination */
        .pagination {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 14px 22px; border-top: 1px solid var(--border);
        }
        .page-btn {
            border: 1px solid var(--border); background: #fff; color: var(--ink);
            border-radius: 9px; padding: 7px 14px; font-size: .72rem; font-weight: 600;
            font-family: inherit; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
            transition: all .12s ease;
        }
        .page-btn:hover:not(:disabled) { border-color: var(--acc-blue); color: var(--acc-blue); }
        .page-btn:disabled { opacity: .4; cursor: not-allowed; }
        .page-btn svg { width: 14px; height: 14px; }
        .page-nums { display: flex; gap: 6px; }
        .page-num {
            width: 32px; height: 32px; border-radius: 9px; border: 1px solid var(--border);
            background: #fff; color: var(--ink); font-size: .74rem; font-weight: 600;
            font-family: inherit; cursor: pointer; transition: all .12s ease;
        }
        .page-num.active { background: var(--sd-blue); border-color: var(--sd-blue); color: #fff; }
        .page-num:hover:not(.active) { border-color: var(--acc-blue); color: var(--acc-blue); }
        .page-info { font-size: .7rem; color: var(--muted); }

        .empty-row td { text-align: center; color: var(--muted); padding: 40px; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .hamburger { display: block; }
            .content { padding: 16px; }
            .conn-pill { display: none; }
            .search-box input { width: 100%; }
            .card-head .tools { width: 100%; }
            .search-box { flex: 1; }
        }
    </style>
</head>
<body>

    <!-- ===== Sidebar (dark blue, gaya ng employee dashboard) ===== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark"><img src="../background/logo.png" alt="SFI Logo" onerror="this.style.display='none'"></div>
            <div>
                <h2>SFI QUEUING</h2>
                <p>HR Portal</p>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">Menu</div>
            <a href="hr_monitoring.php" class="sidebar-link active">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg></span> HR Monitoring
            </a>
            <a href="employee_dashbord.php" class="sidebar-link">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg></span> Employee Dashboard
            </a>
            <a href="daily.php" class="sidebar-link">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span> Daily Attendance
            </a>
            <a href="report.php" class="sidebar-link">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span> Attendance Report
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="#" class="sidebar-link">
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
                <h1>HR Monitoring</h1>
                <p>Smart Loan Queue Management System</p>
            </div>
            <div class="topbar-right">
                <span class="conn-pill"><span class="dot"></span> CONNECTED</span>
                <div class="user-chip">
                    <div class="user-avatar">HR</div>
                    <div>
                        <div class="u-name">HR Officer</div>
                        <div class="u-role">Human Resources</div>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            <!-- Page title -->
            <div class="page-title-row">
                <div>
                    <h2>HR ATTENDANCE MONITORING</h2>
                    <div class="title-sub"><?= date('F j, Y') ?> · Live time-in / time-out ng lahat ng empleyado</div>
                </div>
                <div class="card" style="box-shadow:none;padding:10px 18px;display:flex;align-items:center;gap:12px;">
                    <span class="live-dot"></span>
                    <div>
                        <div class="sc-label" style="font-size:.6rem;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;">Live Clock</div>
                        <div style="font-weight:800;font-size:1.05rem;color:var(--hd-blue);font-variant-numeric:tabular-nums;" id="clockDigits">--:--:--</div>
                    </div>
                </div>
            </div>

            <!-- Prototype notice -->
            <div class="proto-ribbon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                DESIGN PROTOTYPE — sample data lang ito. Hindi pa konektado sa database.
            </div>

            <!-- ================================================================ -->
            <!-- ===== SECTION 1: STAT CARDS =================================== -->
            <!-- ================================================================ -->
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="sc-icon green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="sc-label">On Duty Now</div>
                        <div class="sc-value" id="statOnDuty">2</div>
                        <div class="sc-sub">kasalukuyang nagtatrabaho</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="sc-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div>
                        <div class="sc-label">Timed In Today</div>
                        <div class="sc-value" id="statTimedIn">7</div>
                        <div class="sc-sub">sa 10 empleyado</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="sc-icon amber">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="sc-label">Late Today</div>
                        <div class="sc-value" id="statLate">2</div>
                        <div class="sc-sub">pagdating pagkatapos ng 08:00 AM</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="sc-icon red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    </div>
                    <div>
                        <div class="sc-label">Not Yet Timed In</div>
                        <div class="sc-value" id="statNotIn">2</div>
                        <div class="sc-sub">hindi pa nag-time in</div>
                    </div>
                </div>
            </div>

            <!-- ================================================================ -->
            <!-- ===== SECTION 2: TODAY'S ATTENDANCE TABLE ====================== -->
            <!-- ================================================================ -->
            <div class="section-label">Today's Attendance</div>

            <div class="card">
                <div class="card-head">
                    <h3>Employee Time In / Time Out — Ngayong Araw</h3>
                    <div class="tools">
                        <div class="search-box">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" id="searchInput" placeholder="Search employee...">
                        </div>
                    </div>
                </div>

                <!-- Filter tabs -->
                <div style="padding: 14px 22px 0;">
                    <div class="filter-tabs" id="filterTabs">
                        <button class="filter-tab active" data-filter="all">All</button>
                        <button class="filter-tab" data-filter="on_duty">On Duty</button>
                        <button class="filter-tab" data-filter="present">Present</button>
                        <button class="filter-tab" data-filter="late">Late</button>
                        <button class="filter-tab" data-filter="not_in">Not Yet Timed In</button>
                        <button class="filter-tab" data-filter="on_leave">On Leave</button>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Counter / Position</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                                <th>Total Hours</th>
                            </tr>
                        </thead>
                        <tbody id="attBody">
                            <!-- ===== SAMPLE DATA (prototype) ===== -->
                            <tr data-att data-status="on_duty">
                                <td>
                                    <div class="emp-cell">
                                        <div class="emp-avatar on-duty">CR</div>
                                        <div>
                                            <div class="e-name">Carlo Reyes</div>
                                            <div class="e-meta">@creyes · Counter 3</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Counter 3</td>
                                <td>08:12 AM</td>
                                <td><span class="badge badge-on_duty"><span class="live-dot"></span> On Duty</span></td>
                                <td><span class="badge badge-on_duty"><span class="live-dot"></span> Working</span></td>
                                <td><span class="hours-pill">Ongoing</span></td>
                            </tr>
                            <tr data-att data-status="on_duty">
                                <td>
                                    <div class="emp-cell">
                                        <div class="emp-avatar on-duty">BT</div>
                                        <div>
                                            <div class="e-name">Bea Torres</div>
                                            <div class="e-meta">@btorres · Counter 4</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Counter 4</td>
                                <td>07:55 AM</td>
                                <td><span class="badge badge-on_duty"><span class="live-dot"></span> On Duty</span></td>
                                <td><span class="badge badge-on_duty"><span class="live-dot"></span> Working</span></td>
                                <td><span class="hours-pill">Ongoing</span></td>
                            </tr>
                            <tr data-att data-status="present">
                                <td>
                                    <div class="emp-cell">
                                        <div class="emp-avatar">MS</div>
                                        <div>
                                            <div class="e-name">Maria Santos</div>
                                            <div class="e-meta">@msantos · Counter 1</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Counter 1</td>
                                <td>08:01 AM</td>
                                <td>05:30 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.48 hrs</span></td>
                            </tr>
                            <tr data-att data-status="present">
                                <td>
                                    <div class="emp-cell">
                                        <div class="emp-avatar">AC</div>
                                        <div>
                                            <div class="e-name">Ana Cruz</div>
                                            <div class="e-meta">@acruz · Counter 3</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Counter 3</td>
                                <td>08:00 AM</td>
                                <td>05:30 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.50 hrs</span></td>
                            </tr>
                            <tr data-att data-status="present">
                                <td>
                                    <div class="emp-cell">
                                        <div class="emp-avatar">JD</div>
                                        <div>
                                            <div class="e-name">Juan Dela Cruz</div>
                                            <div class="e-meta">@jdelacruz · Counter 4</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Counter 4</td>
                                <td>07:58 AM</td>
                                <td>05:15 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.28 hrs</span></td>
                            </tr>
                            <tr data-att data-status="present">
                                <td>
                                    <div class="emp-cell">
                                        <div class="emp-avatar">LM</div>
                                        <div>
                                            <div class="e-name">Liza Morales</div>
                                            <div class="e-meta">@lmorales · Counter 2</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Counter 2</td>
                                <td>08:05 AM</td>
                                <td>05:25 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.33 hrs</span></td>
                            </tr>
                            <tr data-att data-status="late">
                                <td>
                                    <div class="emp-cell">
                                        <div class="emp-avatar late">JR</div>
                                        <div>
                                            <div class="e-name">Jose Reyes</div>
                                            <div class="e-meta">@jreyes · Counter 2</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Counter 2</td>
                                <td>08:22 AM</td>
                                <td>05:40 PM</td>
                                <td><span class="badge badge-late">Late</span></td>
                                <td><span class="hours-pill">9.30 hrs</span></td>
                            </tr>
                            <tr data-att data-status="late">
                                <td>
                                    <div class="emp-cell">
                                        <div class="emp-avatar late">PS</div>
                                        <div>
                                            <div class="e-name">Pedro Santos</div>
                                            <div class="e-meta">@psantos · Counter 1</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Counter 1</td>
                                <td>09:10 AM</td>
                                <td>05:45 PM</td>
                                <td><span class="badge badge-late">Late</span></td>
                                <td><span class="hours-pill">8.58 hrs</span></td>
                            </tr>
                            <tr data-att data-status="on_leave">
                                <td>
                                    <div class="emp-cell">
                                        <div class="emp-avatar idle">NG</div>
                                        <div>
                                            <div class="e-name">Nina Garcia</div>
                                            <div class="e-meta">@ngarcia · Counter 4</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Counter 4</td>
                                <td class="text-muted">—</td>
                                <td class="text-muted">—</td>
                                <td><span class="badge badge-on_leave">On Leave</span></td>
                                <td class="text-muted">—</td>
                            </tr>
                            <tr data-att data-status="not_in">
                                <td>
                                    <div class="emp-cell">
                                        <div class="emp-avatar idle">MV</div>
                                        <div>
                                            <div class="e-name">Mark Villanueva</div>
                                            <div class="e-meta">@mvillanueva · Counter 1</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Counter 1</td>
                                <td class="text-muted">—</td>
                                <td class="text-muted">—</td>
                                <td><span class="badge badge-idle">Not Yet Timed In</span></td>
                                <td class="text-muted">—</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="pagination">
                    <button class="page-btn" id="attPrev" onclick="attPage(-1)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        Prev
                    </button>
                    <div class="page-nums" id="attPageNums"></div>
                    <button class="page-btn" id="attNext" onclick="attPage(1)">
                        Next
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
            </div>

            <!-- ================================================================ -->
        </div>
    </div>

    <script>
        // ============================================
        // DESIGN PROTOTYPE — preview lang ng states
        // ============================================

        // Live clock
        function tickClock() {
            const now = new Date();
            document.getElementById('clockDigits').textContent =
                now.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
        tickClock();
        setInterval(tickClock, 1000);

        // Sidebar toggle (mobile)
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        document.getElementById('hamburgerBtn').addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
        });
        overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.style.display = 'none'; });

        // ============================================
        // Sample data (prototype) — galing ito sa employee_attendance table sa susunod
        // ============================================
        const STATUS_LABEL = {
            on_duty: 'Working',
            present: 'Present',
            late: 'Late',
            on_leave: 'On Leave',
            not_in: 'Not Yet Timed In'
        };

        let currentFilter = 'all';
        let searchTerm = '';
        const allRows = Array.from(document.querySelectorAll('#attBody tr[data-att]'));

        // ============================================
        // Stat cards update (prototype)
        // ============================================
        function updateStats() {
            const count = s => allRows.filter(r => r.dataset.status === s).length;
            document.getElementById('statOnDuty').textContent = count('on_duty');
            document.getElementById('statLate').textContent = count('late');
            document.getElementById('statNotIn').textContent = count('not_in') + count('on_leave');
            document.getElementById('statTimedIn').textContent = allRows.length - count('not_in') - count('on_leave');
        }

        // ============================================
        // Filter + Search + Pagination
        // ============================================
        const ATT_PER_PAGE = 5;
        let attPageNum = 1;

        function getVisibleRows() {
            return allRows.filter(r => {
                const okStatus = currentFilter === 'all' || r.dataset.status === currentFilter;
                const name = (r.querySelector('.e-name') || {}).textContent || '';
                const meta = (r.querySelector('.e-meta') || {}).textContent || '';
                const okSearch = !searchTerm || (name + ' ' + meta).toLowerCase().includes(searchTerm);
                return okStatus && okSearch;
            });
        }

        function renderAttRows() {
            const visible = getVisibleRows();
            const totalPages = Math.max(1, Math.ceil(visible.length / ATT_PER_PAGE));
            if (attPageNum > totalPages) attPageNum = totalPages;

            const start = (attPageNum - 1) * ATT_PER_PAGE;
            const end = start + ATT_PER_PAGE;
            visible.forEach((row, idx) => {
                row.style.display = (idx >= start && idx < end) ? '' : 'none';
            });

            const numsEl = document.getElementById('attPageNums');
            if (visible.length === 0) {
                numsEl.innerHTML = '';
                document.getElementById('attPrev').disabled = true;
                document.getElementById('attNext').disabled = true;
                return;
            }
            let html = '';
            for (let i = 1; i <= totalPages; i++) {
                html += '<button class="page-num' + (i === attPageNum ? ' active' : '') + '" onclick="attGo(' + i + ')">' + i + '</button>';
            }
            numsEl.innerHTML = html;
            document.getElementById('attPrev').disabled = attPageNum <= 1;
            document.getElementById('attNext').disabled = attPageNum >= totalPages;
        }

        function attPage(dir) {
            const visible = getVisibleRows();
            const totalPages = Math.max(1, Math.ceil(visible.length / ATT_PER_PAGE));
            const next = attPageNum + dir;
            if (next < 1 || next > totalPages) return;
            attPageNum = next;
            renderAttRows();
        }
        function attGo(page) {
            attPageNum = page;
            renderAttRows();
        }

        // Filter tabs
        document.querySelectorAll('#filterTabs .filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('#filterTabs .filter-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                currentFilter = this.dataset.filter;
                attPageNum = 1;
                renderAttRows();
            });
        });

        // Search
        document.getElementById('searchInput').addEventListener('input', function() {
            searchTerm = this.value.trim().toLowerCase();
            attPageNum = 1;
            renderAttRows();
        });

        // Init
        updateStats();
        renderAttRows();
    </script>
</body>
</html>
