<?php
/**
 * SFI Queuing System - Loan Type Statistics API
 * GET /api/reports/loan-types.php
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
initAPI();

try {
    $db = Database::getConnection();
    $date = get('date', today());

    $stmt = $db->prepare("
        SELECT lt.name, lt.prefix,
            COUNT(qt.id) AS total,
            SUM(CASE WHEN qt.status = 'completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN qt.status = 'waiting' THEN 1 ELSE 0 END) AS waiting,
            SUM(CASE WHEN qt.status = 'no_show' THEN 1 ELSE 0 END) AS no_show
        FROM loan_types lt
        LEFT JOIN queue_tickets qt ON lt.id = qt.loan_type_id AND qt.queue_date = :date
        WHERE lt.status = 'active' AND lt.is_archived = 0
        GROUP BY lt.id, lt.name, lt.prefix
        ORDER BY total DESC
    ");
    $stmt->execute([':date' => $date]);
    $stats = $stmt->fetchAll();

    Response::success('Loan type statistics loaded', ['stats' => $stats]);

} catch (Exception $e) {
    error_log('SFI Loan Type Report Error: ' . $e->getMessage());
    Response::error('Failed to load loan type statistics.', [], 500);
}
