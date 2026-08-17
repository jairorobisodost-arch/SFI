<?php
/**
 * SFI Queuing System - Employee Dashboard (DESIGN PROTOTYPE)
 * Static design lang muna — walang backend, walang login.
 * Design na gaya ng "dashoard 1.jpg" example:
 * - Dark blue sidebar (rgb 26,74,155)
 * - Blue gradient header (rgb 10,57,146)
 * Sections:
 *   Dashboard       -> Total Hours stat cards + Bar Graph + History Logs
 *   Daily Attendance -> Malaking TIME IN button sa gitna
 *   Attendance Report -> 5-column records table
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Design Prototype</title>
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

        /* ---------- Dashboard: stat cards ---------- */
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

        /* ---------- Dashboard: bar graph ---------- */
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
        .month-picker input[type="month"] {
            border: 1px solid var(--border); border-radius: 9px;
            padding: 7px 10px; font-family: inherit; font-size: .78rem; color: var(--ink);
        }
        .chart-wrap { padding: 24px 26px 18px; }
        .bar-chart { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; height: 200px; }
        .bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 8px; height: 100%; justify-content: flex-end; }
        .bar-track {
            width: 100%; max-width: 44px; height: 100%;
            display: flex; align-items: flex-end;
            background: #f1f5f9; border-radius: 8px; overflow: hidden;
        }
        .bar-fill {
            width: 100%; border-radius: 8px;
            background: linear-gradient(180deg, var(--acc-blue), var(--sd-blue));
            transition: height .4s ease;
        }
        .bar-col .b-val { font-size: .68rem; font-weight: 700; color: var(--ink); }
        .bar-col .b-label { font-size: .66rem; color: var(--muted); font-weight: 600; text-transform: uppercase; }
        .chart-legend { display: flex; gap: 16px; justify-content: center; padding: 0 26px 18px; flex-wrap: wrap; }
        .chart-legend span { font-size: .68rem; color: var(--muted); display: flex; align-items: center; gap: 6px; }
        .chart-legend .sw { width: 10px; height: 10px; border-radius: 3px; display: inline-block; }

        /* ---------- Tables ---------- */
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .8rem; }
        thead th {
            text-align: left; padding: 13px 22px;
            background: var(--lilac-soft);
            color: #333a7a;
            font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .8px;
            border-bottom: 1px solid #cdd1f0;
        }
        tbody td { padding: 13px 22px; border-bottom: 1px solid #f1f5f9; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8fafc; }
        .badge {
            display: inline-block; padding: 4px 12px; border-radius: 999px;
            font-size: .64rem; font-weight: 700; text-transform: capitalize;
        }
        .badge-present  { background: rgba(16,185,129,.14); color: #047857; }
        .badge-late     { background: rgba(245,158,11,.16); color: #b45309; }
        .badge-half_day { background: rgba(139,92,246,.14); color: #6d28d9; }
        .badge-on_leave { background: rgba(100,116,139,.14); color: #475569; }
        .badge-idle     { background: rgba(226,232,240,.6); color: #64748b; }
        .text-muted { color: var(--muted); }
        .hours-pill { background: var(--lilac-soft); color: #4a4f9c; border-radius: 999px; padding: 3px 12px; font-weight: 700; font-size: .72rem; }

        /* Section label */
        .section-label {
            font-size: .72rem; font-weight: 700; color: var(--hd-blue);
            text-transform: uppercase; letter-spacing: 1.5px;
            margin: 6px 0 12px; display: flex; align-items: center; gap: 8px;
        }
        .section-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }

        /* ---------- History Logs (table + pagination) ---------- */
        .log-table-wrap { overflow-x: auto; }
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

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .hamburger { display: block; }
            .content { padding: 16px; }
            .conn-pill { display: none; }
        }
    </style>
</head>
<body>

    <!-- ===== Sidebar (dark blue, gaya ng example) ===== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark"><img src="../background/logo.png" alt="SFI Logo" onerror="this.style.display='none'"></div>
            <div>
                <h2>SFI QUEUING</h2>
                <p>Employee Portal</p>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">Menu</div>
            <a href="employee_dashbord.php" class="sidebar-link active">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg></span> Dashboard
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
                <h1>Employee Dashboard</h1>
                <p>Smart Loan Queue Management System</p>
            </div>
            <div class="topbar-right">
                <span class="conn-pill"><span class="dot"></span> CONNECTED</span>
                <div class="user-chip">
                    <div class="user-avatar">EP</div>
                    <div>
                        <div class="u-name">Employee One</div>
                        <div class="u-role">Employee</div>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            <!-- Page title -->
            <div class="page-title-row">
                <div>
                    <h2>EMPLOYEE ATTENDANCE</h2>
                    <div class="title-sub"><?= date('F j, Y') ?> · Counter 1</div>
                </div>
            </div>

            <!-- Prototype notice -->
            <div class="proto-ribbon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                DESIGN PROTOTYPE — sample data lang ito. Hindi pa konektado sa database.
            </div>

            <!-- ================================================================ -->
            <!-- ===== SECTION 1: DASHBOARD (Total Hours + Bar Graph + Logs) ==== -->
            <!-- ================================================================ -->
            <div class="section-label" id="dashboard">Dashboard</div>

            <!-- Stat cards: Total Hours prominent -->
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="sc-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="sc-label">Total Hours</div>
                        <div class="sc-value">142.5</div>
                        <div class="sc-sub">ngayong buwan</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="sc-icon green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div>
                        <div class="sc-label">Present</div>
                        <div class="sc-value">18</div>
                        <div class="sc-sub">araw ngayong buwan</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="sc-icon amber">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="sc-label">Late</div>
                        <div class="sc-value">2</div>
                        <div class="sc-sub">beses ngayong buwan</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="sc-icon red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    </div>
                    <div>
                        <div class="sc-label">Absences</div>
                        <div class="sc-value">1</div>
                        <div class="sc-sub">araw ngayong buwan</div>
                    </div>
                </div>
            </div>

            <!-- Bar graph: hours per week -->
            <div class="card">
                <div class="card-head">
                    <h3>Weekly Hours</h3>
                    <div class="tools">
                        <div class="month-picker"><input type="month" value="2026-08"></div>
                    </div>
                </div>
                <div class="chart-wrap">
                    <div class="bar-chart">
                        <div class="bar-col"><div class="b-val">9.4h</div><div class="bar-track"><div class="bar-fill" style="height:94%"></div></div><div class="b-label">Mon</div></div>
                        <div class="bar-col"><div class="b-val">9.3h</div><div class="bar-track"><div class="bar-fill" style="height:93%"></div></div><div class="b-label">Tue</div></div>
                        <div class="bar-col"><div class="b-val">8.6h</div><div class="bar-track"><div class="bar-fill" style="height:86%"></div></div><div class="b-label">Wed</div></div>
                        <div class="bar-col"><div class="b-val">9.4h</div><div class="bar-track"><div class="bar-fill" style="height:94%"></div></div><div class="b-label">Thu</div></div>
                        <div class="bar-col"><div class="b-val">7.2h</div><div class="bar-track"><div class="bar-fill" style="height:72%"></div></div><div class="b-label">Fri</div></div>
                        <div class="bar-col"><div class="b-val">-</div><div class="bar-track"><div class="bar-fill" style="height:0%"></div></div><div class="b-label">Sat</div></div>
                        <div class="bar-col"><div class="b-val">-</div><div class="bar-track"><div class="bar-fill" style="height:0%"></div></div><div class="b-label">Sun</div></div>
                    </div>
                </div>
                <div class="chart-legend">
                    <span><span class="sw" style="background:linear-gradient(180deg,var(--acc-blue),var(--sd-blue));"></span> Oras ng trabaho</span>
                    <span>Total: 43.9 hrs / linggo</span>
                </div>
            </div>

            <!-- History Logs (table + pagination) -->
            <div class="card" style="margin-top:18px;">
                <div class="card-head">
                    <h3>History Logs</h3>
                    <div class="tools">
                        <div class="month-picker"><input type="month" value="2026-08"></div>
                    </div>
                </div>
                <div class="log-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                                <th>Total Hours</th>
                            </tr>
                        </thead>
                        <tbody id="logBody">
                            <tr data-log>
                                <td><strong>Aug 14, 2026</strong></td>
                                <td>08:02 AM</td>
                                <td>05:30 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.47 hrs</span></td>
                            </tr>
                            <tr data-log>
                                <td><strong>Aug 13, 2026</strong></td>
                                <td>07:58 AM</td>
                                <td>05:15 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.28 hrs</span></td>
                            </tr>
                            <tr data-log>
                                <td><strong>Aug 12, 2026</strong></td>
                                <td>08:22 AM</td>
                                <td>05:40 PM</td>
                                <td><span class="badge badge-late">Late</span></td>
                                <td><span class="hours-pill">9.30 hrs</span></td>
                            </tr>
                            <tr data-log>
                                <td><strong>Aug 11, 2026</strong></td>
                                <td>07:55 AM</td>
                                <td>05:20 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.42 hrs</span></td>
                            </tr>
                            <tr data-log>
                                <td><strong>Aug 8, 2026</strong></td>
                                <td>08:05 AM</td>
                                <td>05:25 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.33 hrs</span></td>
                            </tr>
                            <tr data-log>
                                <td><strong>Aug 7, 2026</strong></td>
                                <td>09:10 AM</td>
                                <td>05:45 PM</td>
                                <td><span class="badge badge-late">Late</span></td>
                                <td><span class="hours-pill">8.58 hrs</span></td>
                            </tr>
                            <tr data-log>
                                <td><strong>Aug 6, 2026</strong></td>
                                <td>08:00 AM</td>
                                <td>05:30 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.50 hrs</span></td>
                            </tr>
                            <tr data-log>
                                <td><strong>Aug 5, 2026</strong></td>
                                <td>07:52 AM</td>
                                <td>05:10 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.30 hrs</span></td>
                            </tr>
                            <tr data-log>
                                <td><strong>Aug 4, 2026</strong></td>
                                <td>08:18 AM</td>
                                <td>05:35 PM</td>
                                <td><span class="badge badge-late">Late</span></td>
                                <td><span class="hours-pill">9.28 hrs</span></td>
                            </tr>
                            <tr data-log>
                                <td><strong>Aug 1, 2026</strong></td>
                                <td>08:03 AM</td>
                                <td>05:28 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.42 hrs</span></td>
                            </tr>
                            <tr data-log>
                                <td><strong>Jul 31, 2026</strong></td>
                                <td>07:58 AM</td>
                                <td>05:20 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.37 hrs</span></td>
                            </tr>
                            <tr data-log>
                                <td><strong>Jul 30, 2026</strong></td>
                                <td>08:25 AM</td>
                                <td>05:50 PM</td>
                                <td><span class="badge badge-late">Late</span></td>
                                <td><span class="hours-pill">9.42 hrs</span></td>
                            </tr>
                            <tr data-log>
                                <td><strong>Jul 29, 2026</strong></td>
                                <td>08:01 AM</td>
                                <td>05:30 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.48 hrs</span></td>
                            </tr>
                            <tr data-log>
                                <td><strong>Jul 28, 2026</strong></td>
                                <td>07:55 AM</td>
                                <td>05:15 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.33 hrs</span></td>
                            </tr>
                            <tr data-log>
                                <td><strong>Jul 25, 2026</strong></td>
                                <td>08:10 AM</td>
                                <td>05:40 PM</td>
                                <td><span class="badge badge-late">Late</span></td>
                                <td><span class="hours-pill">9.50 hrs</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="pagination">
                    <button class="page-btn" id="logPrev" onclick="logPage(-1)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        Prev
                    </button>
                    <div class="page-nums" id="logPageNums"></div>
                    <button class="page-btn" id="logNext" onclick="logPage(1)">
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
        // History Logs pagination (prototype)
        // ============================================
        const LOG_PER_PAGE = 3;
        let logPageNum = 1;
        const logRows = Array.from(document.querySelectorAll('#logBody tr[data-log]'));
        const logTotalPages = Math.max(1, Math.ceil(logRows.length / LOG_PER_PAGE));

        function renderLogPagination() {
            const numsEl = document.getElementById('logPageNums');
            let html = '';
            for (let i = 1; i <= logTotalPages; i++) {
                html += '<button class="page-num' + (i === logPageNum ? ' active' : '') + '" onclick="logGo(' + i + ')">' + i + '</button>';
            }
            numsEl.innerHTML = html;
            document.getElementById('logPrev').disabled = logPageNum <= 1;
            document.getElementById('logNext').disabled = logPageNum >= logTotalPages;
        }
        function renderLogRows() {
            const start = (logPageNum - 1) * LOG_PER_PAGE;
            const end = start + LOG_PER_PAGE;
            logRows.forEach((row, idx) => {
                row.style.display = (idx >= start && idx < end) ? '' : 'none';
            });
        }
        function logPage(dir) {
            const next = logPageNum + dir;
            if (next < 1 || next > logTotalPages) return;
            logPageNum = next;
            renderLogRows();
            renderLogPagination();
        }
        function logGo(page) {
            logPageNum = page;
            renderLogRows();
            renderLogPagination();
        }
        // Init
        renderLogRows();
        renderLogPagination();

    </script>
</body>
</html>
