<?php
/**
 * SFI Queuing System - Monthly Attendance View API
 * GET /api/attendance/monthly.php?month=2026-08
 * Returns every day of the month with the employee's attendance status
 * (present / late / half_day / on_leave / weekend / absent).
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

    // Fetch all records for this month
    $stmt = $db->prepare("SELECT attendance_date, time_in, time_out, status, note
                          FROM employee_attendance
                          WHERE user_id = :u AND DATE_FORMAT(attendance_date, '%Y-%m') = :m
                          ORDER BY attendance_date");
    $stmt->execute([':u' => $userId, ':m' => $month]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $byDate = [];
    foreach ($records as $r) {
        $byDate[$r['attendance_date']] = $r;
    }

    // Build the month grid
    $daysInMonth = (int)date('t', strtotime($month . '-01'));
    $days = [];
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateStr = $month . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
        $dow = (int)date('N', strtotime($dateStr)); // 1=Mon ... 7=Sun
        $rec = $byDate[$dateStr] ?? null;

        $status = 'absent';
        $label = 'Absent';
        $timeIn = '';
        $timeOut = '';
        $note = '';

        if ($dow >= 6) {
            $status = 'weekend';
            $label = 'Weekend';
        } elseif ($rec) {
            $status = $rec['status'];
            $labelMap = ['present' => 'Present', 'late' => 'Late', 'half_day' => 'Half Day', 'on_leave' => 'On Leave'];
            $label = $labelMap[$rec['status']] ?? 'Present';
            $timeIn  = $rec['time_in']  ? date('h:i A', strtotime($rec['time_in']))  : '';
            $timeOut = $rec['time_out'] ? date('h:i A', strtotime($rec['time_out'])) : '';
            $note = $rec['note'] ?? '';
        }

        $days[] = [
            'day'      => $d,
            'date'     => $dateStr,
            'weekday'  => date('D', strtotime($dateStr)),
            'status'   => $status,
            'label'    => $label,
            'time_in'  => $timeIn,
            'time_out' => $timeOut,
            'note'     => $note
        ];
    }

    Response::success('Monthly attendance loaded', [
        'month' => $month,
        'days_in_month' => $daysInMonth,
        'days'  => $days
    ]);
} catch (Exception $e) {
    error_log('SFI Monthly Attendance Error: ' . $e->getMessage());
    Response::error('Failed to load monthly attendance.', [], 500);
}
