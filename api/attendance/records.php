<?php
/**
 * SFI Queuing System - Attendance Records API
 * GET /api/attendance/records.php?month=2026-08
 * Returns the employee's attendance records for a month (or all records if no month given).
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
initAPI();

try {
    $db = Database::getConnection();
    $user = getSessionUser();
    $userId = (int)$user['id'];

    $month = get('month'); // e.g. 2026-08
    $params = [':u' => $userId];
    $where = "user_id = :u";
    if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
        $where .= " AND DATE_FORMAT(attendance_date, '%Y-%m') = :m";
        $params[':m'] = $month;
    }

    $stmt = $db->prepare("SELECT id, attendance_date, time_in, time_out, status, note
                          FROM employee_attendance
                          WHERE $where
                          ORDER BY attendance_date DESC");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compute total hours for each record
    foreach ($records as &$r) {
        $r['total_hours'] = 0;
        if ($r['time_in'] && $r['time_out']) {
            $r['total_hours'] = round((strtotime($r['time_out']) - strtotime($r['time_in'])) / 3600, 2);
        }
        // Friendly time labels (e.g. 08:15 AM)
        $r['time_in_label']  = $r['time_in']  ? date('h:i A', strtotime($r['time_in']))  : '';
        $r['time_out_label'] = $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '';
    }
    unset($r);

    Response::success('Attendance records loaded', ['records' => $records]);
} catch (Exception $e) {
    error_log('SFI Attendance Records Error: ' . $e->getMessage());
    Response::error('Failed to load attendance records.', [], 500);
}
