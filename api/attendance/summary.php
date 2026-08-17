<?php
/**
 * SFI Queuing System - Attendance Summary API
 * GET /api/attendance/summary.php?month=2026-08
 * Returns summary stats (present, late, half days, total hours, absences) for the user.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
initAPI();

try {
    $db = Database::getConnection();
    $user = getSessionUser();
    $userId = (int)$user['id'];

    $month = get('month', date('Y-m'));
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        Response::error('Invalid month format.');
    }

    // Today's status
    $stmt = $db->prepare("SELECT * FROM employee_attendance WHERE user_id = :u AND attendance_date = :d LIMIT 1");
    $stmt->execute([':u' => $userId, ':d' => date('Y-m-d')]);
    $today = $stmt->fetch();

    // Month stats
    $stmt = $db->prepare("SELECT
                            COUNT(*) AS total_days,
                            SUM(status = 'present') AS present,
                            SUM(status = 'late') AS late,
                            SUM(status = 'half_day') AS half_day,
                            SUM(status = 'on_leave') AS on_leave,
                            SUM(time_in IS NOT NULL AND time_out IS NULL) AS active_now
                          FROM employee_attendance
                          WHERE user_id = :u AND DATE_FORMAT(attendance_date, '%Y-%m') = :m");
    $stmt->execute([':u' => $userId, ':m' => $month]);
    $stats = $stmt->fetch();

    // Total hours for the month
    $stmt = $db->prepare("SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND, time_in, time_out)), 0) AS secs
                          FROM employee_attendance
                          WHERE user_id = :u AND DATE_FORMAT(attendance_date, '%Y-%m') = :m AND time_in IS NOT NULL AND time_out IS NOT NULL");
    $stmt->execute([':u' => $userId, ':m' => $month]);
    $totalSecs = (int)$stmt->fetchColumn();

    // Absences = working days in month (Mon-Fri) minus days with attendance
    $daysInMonth = (int)date('t', strtotime($month . '-01'));
    $workDays = 0;
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dow = (int)date('N', strtotime($month . '-' . str_pad($d, 2, '0', STR_PAD_LEFT)));
        if ($dow <= 5) $workDays++; // Mon-Fri
    }
    $presentDays = (int)$stats['total_days'];
    $absences = max(0, $workDays - $presentDays);

    $data = [
        'today' => $today ? [
            'date'      => $today['attendance_date'],
            'time_in'   => $today['time_in'],
            'time_out'  => $today['time_out'],
            'status'    => $today['status'],
            'time_in_label'  => $today['time_in']  ? date('h:i A', strtotime($today['time_in']))  : '',
            'time_out_label' => $today['time_out'] ? date('h:i A', strtotime($today['time_out'])) : ''
        ] : null,
        'month' => $month,
        'present'   => (int)($stats['present'] ?? 0),
        'late'      => (int)($stats['late'] ?? 0),
        'half_day'  => (int)($stats['half_day'] ?? 0),
        'on_leave'  => (int)($stats['on_leave'] ?? 0),
        'absences'  => $absences,
        'total_hours' => round($totalSecs / 3600, 2),
        'work_days' => $workDays
    ];

    Response::success('Attendance summary loaded', $data);
} catch (Exception $e) {
    error_log('SFI Attendance Summary Error: ' . $e->getMessage());
    Response::error('Failed to load attendance summary.', [], 500);
}
