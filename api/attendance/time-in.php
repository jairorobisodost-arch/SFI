<?php
/**
 * SFI Queuing System - Attendance Time In API
 * POST /api/attendance/time-in.php
 * Records the employee's time-in for today (or a given date).
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

    // Check if there is already a record for this user/date
    $stmt = $db->prepare("SELECT * FROM employee_attendance WHERE user_id = :u AND attendance_date = :d LIMIT 1");
    $stmt->execute([':u' => $userId, ':d' => $date]);
    $existing = $stmt->fetch();

    if ($existing && $existing['time_in']) {
        Response::error('You have already timed in for this day.', ['time_in' => $existing['time_in']]);
    }

    // Determine status: late if time-in is after 08:00
    $timePart = date('H:i:s', strtotime($now));
    $status = ($timePart > '08:00:00') ? 'late' : 'present';

    if ($existing) {
        // Update the record's time_in (was created without a time_in)
        $stmt = $db->prepare("UPDATE employee_attendance SET time_in = :t, status = :s WHERE id = :id");
        $stmt->execute([':t' => $now, ':s' => $status, ':id' => $existing['id']]);
        $attId = (int)$existing['id'];
    } else {
        $stmt = $db->prepare("INSERT INTO employee_attendance (user_id, attendance_date, time_in, status)
                              VALUES (:u, :d, :t, :s)");
        $stmt->execute([':u' => $userId, ':d' => $date, ':t' => $now, ':s' => $status]);
        $attId = (int)$db->lastInsertId();
    }

    logActivity($userId, $user['username'], 'attendance_time_in', 'Timed in on ' . $date . ' at ' . $now);

    Response::success('Time in recorded successfully.', [
        'id'        => $attId,
        'date'      => $date,
        'time_in'   => $now,
        'status'    => $status
    ]);
} catch (Exception $e) {
    error_log('SFI Time In Error: ' . $e->getMessage());
    Response::error('Failed to record time in.', [], 500);
}
