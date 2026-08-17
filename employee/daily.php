<?php
/**
 * SFI Queuing System - Employee Daily Attendance (DESIGN PROTOTYPE)
 * TIME IN button lang dito. Consistent design gaya ng example dashboard.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Attendance - Design Prototype</title>
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

        /* Daily card - button sa gitna */
        .daily-card {
            background: #fff; border-radius: 18px; border: 1px solid var(--border);
            box-shadow: 0 4px 16px rgba(4,38,152,.08);
            padding: 30px 30px 24px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center; gap: 4px;
        }
        .daily-card .dc-date { font-size: .8rem; color: var(--muted); letter-spacing: .5px; text-transform: uppercase; }
        .daily-card .dc-clock { font-size: 3rem; font-weight: 800; color: var(--hd-blue); font-variant-numeric: tabular-nums; line-height: 1.1; }
        .daily-card .dc-status { font-size: .86rem; margin-top: 6px; min-height: 26px; }
        .daily-card .dc-times { font-size: .8rem; color: var(--muted); margin-top: 4px; min-height: 22px; }

        .big-punch {
            width: 210px; height: 210px; border-radius: 50%;
            border: none; cursor: pointer;
            font-family: inherit; font-weight: 800; color: #fff;
            font-size: 1.3rem; letter-spacing: 1.5px;
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;
            margin: 22px 0 14px;
            transition: transform .15s ease, box-shadow .15s ease;
            box-shadow: 0 16px 40px rgba(4,38,152,.25);
        }
        .big-punch .bp-icon { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; }
        .big-punch .bp-icon svg { width: 48px; height: 48px; }
        .big-punch:hover:not(:disabled) { transform: translateY(-4px) scale(1.02); }
        .big-punch:active:not(:disabled) { transform: scale(.97); }
        .big-punch.in  { background: radial-gradient(circle at 30% 25%, #34d399, #10b981 55%, #059669); }
        .big-punch.out { background: radial-gradient(circle at 30% 25%, #f87171, #ef4444 55%, #dc2626); }
        .big-punch.done { background: radial-gradient(circle at 30% 25%, #94a3b8, #64748b 55%, #475569); }

        .badge {
            display: inline-block; padding: 5px 14px; border-radius: 999px;
            font-size: .7rem; font-weight: 700; text-transform: capitalize;
        }
        .badge-present  { background: rgba(16,185,129,.14); color: #047857; }
        .badge-late     { background: rgba(245,158,11,.16); color: #b45309; }
        .badge-idle     { background: rgba(226,232,240,.6); color: #64748b; }

        .daily-hint { font-size: .74rem; color: var(--muted); max-width: 380px; line-height: 1.7; }
        .info-tiles {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
            width: 100%; max-width: 560px; margin: 12px 0 4px;
        }
        @media (max-width: 480px) { .info-tiles { grid-template-columns: 1fr; } }
        .tile {
            background: #f8fafc; border: 1px solid var(--border); border-radius: 14px;
            padding: 14px 16px; text-align: center;
        }
        .tile .t-label { font-size: .62rem; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; }
        .tile .t-value { font-size: 1.05rem; font-weight: 700; color: var(--hd-blue); margin-top: 3px; font-variant-numeric: tabular-nums; }
        .tile .t-value.green { color: #047857; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .hamburger { display: block; }
            .content { padding: 16px; }
            .conn-pill { display: none; }
            .daily-card .dc-clock { font-size: 2.2rem; }
            .big-punch { width: 180px; height: 180px; font-size: 1.1rem; }
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
            <a href="daily.php" class="sidebar-link active">
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
            <div class="page-title-row">
                <div>
                    <h2>DAILY ATTENDANCE</h2>
                    <div class="title-sub"><?= date('F j, Y') ?> · Counter 1</div>
                </div>
            </div>

            <div class="proto-ribbon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                DESIGN PROTOTYPE — sample data lang ito. Hindi pa konektado sa database.
            </div>

            <!-- Daily attendance: TIME IN button sa gitna -->
            <div class="daily-card">
                <div class="dc-date">Monday, August 17, 2026</div>
                <div class="dc-clock" id="clockDigits">--:--:--</div>
                <div class="dc-status"><span class="badge badge-idle">Not yet timed in</span></div>
                <div class="dc-times"></div>

                <button class="big-punch in" onclick="previewState('in')">
                    <span class="bp-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </span>
                    <span>TIME IN</span>
                </button>

                <div class="daily-hint">Pindutin ang button upang i-record ang iyong pagdating sa trabaho. <em>(Sample — i-click para makita ang TIME OUT state)</em></div>

                <div class="info-tiles">
                    <div class="tile">
                        <div class="t-label">Time In</div>
                        <div class="t-value">--:--</div>
                    </div>
                    <div class="tile">
                        <div class="t-label">Time Out</div>
                        <div class="t-value">--:--</div>
                    </div>
                    <div class="tile">
                        <div class="t-label">Hours Ngayon</div>
                        <div class="t-value">0.00</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
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

        // Preview states
        function previewState(state) {
            const btn = document.querySelector('.big-punch');
            const hint = document.querySelector('.daily-hint');
            const statusEl = document.querySelector('.dc-status');
            const timesEl = document.querySelector('.dc-times');
            const tiles = document.querySelectorAll('.tile .t-value');

            const OUT_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>';
            const IN_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
            const DONE_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';

            if (state === 'in') {
                btn.className = 'big-punch out';
                btn.innerHTML = '<span class="bp-icon">' + OUT_ICON + '</span><span>TIME OUT</span>';
                hint.innerHTML = 'Pindutin ang button upang i-record ang iyong pag-uwi. <em>(i-click ulit para sa DONE state)</em>';
                statusEl.innerHTML = '<span class="badge badge-present">Present</span>';
                timesEl.innerHTML = 'Time In: <strong>08:01 AM</strong>';
                tiles[0].textContent = '08:01 AM';
                tiles[0].classList.add('green');
                tiles[1].textContent = '--:--';
                tiles[2].textContent = 'Ongoing';
            } else if (state === 'out') {
                btn.className = 'big-punch done';
                btn.disabled = true;
                btn.innerHTML = '<span class="bp-icon">' + DONE_ICON + '</span><span>DONE</span>';
                hint.innerHTML = 'Kumpleto na ang iyong attendance para sa araw na ito. Salamat!';
                statusEl.innerHTML = '<span class="badge badge-present">Present</span>';
                timesEl.innerHTML = 'Time In: <strong>08:01 AM</strong> · Time Out: <strong>05:30 PM</strong>';
                tiles[0].textContent = '08:01 AM';
                tiles[0].classList.add('green');
                tiles[1].textContent = '05:30 PM';
                tiles[1].classList.add('green');
                tiles[2].textContent = '9.48 hrs';
                tiles[2].classList.add('green');
            } else {
                btn.className = 'big-punch in';
                btn.disabled = false;
                btn.innerHTML = '<span class="bp-icon">' + IN_ICON + '</span><span>TIME IN</span>';
                hint.innerHTML = 'Pindutin ang button upang i-record ang iyong pagdating sa trabaho. <em>(Sample — i-click para makita ang TIME OUT state)</em>';
                statusEl.innerHTML = '<span class="badge badge-idle">Not yet timed in</span>';
                timesEl.textContent = '';
                tiles.forEach(t => { t.textContent = '--:--'; t.classList.remove('green'); });
                tiles[2].textContent = '0.00';
            }
        }
    </script>
</body>
</html>
