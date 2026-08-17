<?php
/**
 * SFI Queuing System - Daily Report API
 * GET /api/reports/daily.php
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
initAPI();

try {
    $db = Database::getConnection();
    $date = get('date', today());

    // Daily summary
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) AS waiting,
            SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) AS no_show,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
            SUM(CASE WHEN status = 'serving' THEN 1 ELSE 0 END) AS serving,
            AVG(CASE WHEN called_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, created_at, called_at) ELSE NULL END) AS avg_wait,
            AVG(CASE WHEN completed_at IS NOT NULL AND called_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, called_at, completed_at) ELSE NULL END) AS avg_service
        FROM queue_tickets
        WHERE queue_date = :date
    ");
    $stmt->execute([':date' => $date]);
    $summary = $stmt->fetch();

    Response::success('Daily report loaded', [
        'date'         => $date,
        'total'        => (int)$summary['total'],
        'completed'    => (int)$summary['completed'],
        'waiting'      => (int)$summary['waiting'],
        'no_show'      => (int)$summary['no_show'],
        'cancelled'    => (int)$summary['cancelled'],
        'serving'      => (int)$summary['serving'],
        'avg_wait'     => (int)($summary['avg_wait'] ?? 0),
        'avg_service'  => (int)($summary['avg_service'] ?? 0)
    ]);

} catch (Exception $e) {
    error_log('SFI Daily Report Error: ' . $e->getMessage());
    Response::error('Failed to load daily report.', [], 500);
}
