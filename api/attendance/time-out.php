<?php
/**
 * SFI Queuing System - Attendance Time Out API
 * POST /api/attendance/time-out.php
 * Records the employee's time-out for today and computes total hours.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
initAPI();
requirePost();

try {
    $db = Database::getConnection();
    $user = getSessionUser();
    $userId = (int)$user['id'];

    $date = post('date', date('Y-m-d'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        Response::error('Invalid date format.');
    }

    $now = date('Y-m-d H:i:s');

    // Find today's record
    $stmt = $db->prepare("SELECT * FROM employee_attendance WHERE user_id = :u AND attendance_date = :d LIMIT 1");
    $stmt->execute([':u' => $userId, ':d' => $date]);
    $rec = $stmt->fetch();

    if (!$rec || !$rec['time_in']) {
        Response::error('You must time in first before timing out.');
    }
    if ($rec['time_out']) {
        Response::error('You have already timed out for this day.', ['time_out' => $rec['time_out']]);
    }

    // Update time_out
    $stmt = $db->prepare("UPDATE employee_attendance SET time_out = :t WHERE id = :id");
    $stmt->execute([':t' => $now, ':id' => $rec['id']]);

    $totalHours = round((strtotime($now) - strtotime($rec['time_in'])) / 3600, 2);
    if ($totalHours < 0) $totalHours = 0;

    logActivity($userId, $user['username'], 'attendance_time_out', 'Timed out on ' . $date . ' at ' . $now);

    Response::success('Time out recorded successfully.', [
        'id'          => (int)$rec['id'],
        'date'        => $date,
        'time_in'     => $rec['time_in'],
        'time_out'    => $now,
        'total_hours' => $totalHours
    ]);
} catch (Exception $e) {
    error_log('SFI Time Out Error: ' . $e->getMessage());
    Response::error('Failed to record time out.', [], 500);
}
