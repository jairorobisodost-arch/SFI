<?php
/**
 * SFI Queuing System - Employee Attendance Report (DESIGN PROTOTYPE)
 * Records table lang dito. Consistent design gaya ng example dashboard.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - Design Prototype</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --sd-blue:  #1a4a9b;
            --sd-blue2: #123a7a;
            --hd-blue:  #0a3992;
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

        .main { flex: 1; margin-left: 250px; display: flex; flex-direction: column; min-height: 100vh; }
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

        .proto-ribbon {
            background: linear-gradient(90deg, #f59e0b, #f97316);
            color: #fff; border-radius: 10px; padding: 10px 18px; margin-bottom: 18px;
            font-size: .74rem; font-weight: 600; display: flex; align-items: center; gap: 8px;
        }
        .proto-ribbon svg { width: 16px; height: 16px; flex-shrink: 0; }

        /* Summary chips */
        .summary-row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
        .chip {
            background: #fff; border: 1px solid var(--border); border-radius: 999px;
            padding: 8px 16px; font-size: .74rem; font-weight: 600; color: var(--muted);
            display: flex; align-items: center; gap: 8px;
        }
        .chip strong { font-size: .92rem; color: var(--ink); }
        .chip .dot { width: 9px; height: 9px; border-radius: 50%; }
        .chip .dot.green { background: var(--green); }
        .chip .dot.amber { background: var(--amber); }
        .chip .dot.red { background: var(--red); }
        .chip .dot.blue { background: var(--acc-blue); }

        /* Records table card */
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
        .tabs { display: flex; gap: 6px; }
        .tab {
            border: 1px solid var(--border); background: #fff; color: var(--muted);
            border-radius: 999px; padding: 6px 16px; font-size: .72rem; font-weight: 600;
            font-family: inherit; cursor: pointer; transition: all .12s ease;
        }
        .tab.active { background: var(--sd-blue); border-color: var(--sd-blue); color: #fff; }

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
        .text-muted { color: var(--muted); }
        .text-center { text-align: center; }
        .hours-pill { background: var(--lilac-soft); color: #4a4f9c; border-radius: 999px; padding: 3px 12px; font-weight: 700; font-size: .72rem; }

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

    <!-- ===== Sidebar ===== -->
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
            <a href="employee_dashbord.php" class="sidebar-link">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg></span> Dashboard
            </a>
            <a href="daily.php" class="sidebar-link">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span> Daily Attendance
            </a>
            <a href="report.php" class="sidebar-link active">
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
            <div class="page-title-row">
                <div>
                    <h2>ATTENDANCE REPORT</h2>
                    <div class="title-sub"><?= date('F j, Y') ?> · Counter 1</div>
                </div>
            </div>

            <div class="proto-ribbon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                DESIGN PROTOTYPE — sample data lang ito. Hindi pa konektado sa database.
            </div>

            <!-- Summary chips -->
            <div class="summary-row">
                <span class="chip"><span class="dot green"></span> Present <strong>18</strong></span>
                <span class="chip"><span class="dot amber"></span> Late <strong>2</strong></span>
                <span class="chip"><span class="dot red"></span> Absences <strong>1</strong></span>
                <span class="chip"><span class="dot blue"></span> Total Hours <strong>142.5</strong></span>
            </div>

            <!-- Records table -->
            <div class="card">
                <div class="card-head">
                    <h3>Attendance Records</h3>
                    <div class="tools">
                        <div class="month-picker"><input type="month" value="2026-08"></div>
                        <div class="tabs">
                            <button class="tab active">Daily</button>
                            <button class="tab">Monthly</button>
                        </div>
                    </div>
                </div>
                <div class="table-wrapper">
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
                        <tbody>
                            <tr>
                                <td><strong>Aug 14, 2026</strong></td>
                                <td>08:02 AM</td>
                                <td>05:30 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.47 hrs</span></td>
                            </tr>
                            <tr>
                                <td><strong>Aug 13, 2026</strong></td>
                                <td>07:58 AM</td>
                                <td>05:15 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.28 hrs</span></td>
                            </tr>
                            <tr>
                                <td><strong>Aug 12, 2026</strong></td>
                                <td>08:22 AM</td>
                                <td>05:40 PM</td>
                                <td><span class="badge badge-late">Late</span></td>
                                <td><span class="hours-pill">9.30 hrs</span></td>
                            </tr>
                            <tr>
                                <td><strong>Aug 11, 2026</strong></td>
                                <td>07:55 AM</td>
                                <td>05:20 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.42 hrs</span></td>
                            </tr>
                            <tr>
                                <td><strong>Aug 8, 2026</strong></td>
                                <td>08:05 AM</td>
                                <td>05:25 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.33 hrs</span></td>
                            </tr>
                            <tr>
                                <td><strong>Aug 7, 2026</strong></td>
                                <td>09:10 AM</td>
                                <td>05:45 PM</td>
                                <td><span class="badge badge-late">Late</span></td>
                                <td><span class="hours-pill">8.58 hrs</span></td>
                            </tr>
                            <tr>
                                <td><strong>Aug 6, 2026</strong></td>
                                <td>08:00 AM</td>
                                <td>05:30 PM</td>
                                <td><span class="badge badge-present">Present</span></td>
                                <td><span class="hours-pill">9.50 hrs</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle (mobile)
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        document.getElementById('hamburgerBtn').addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
        });
        overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.style.display = 'none'; });
    </script>
</body>
</html>
