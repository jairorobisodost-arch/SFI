<?php
/**
 * SFI Queuing System - Queue Statistics API
 * GET /api/queue/statistics.php
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
initAPI();

try {
    $db = Database::getConnection();
    $date = get('date', today());

    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) AS waiting,
            SUM(CASE WHEN status = 'serving' THEN 1 ELSE 0 END) AS serving,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) AS no_show,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
            COUNT(*) AS total
        FROM queue_tickets
        WHERE queue_date = :date
    ");
    $stmt->execute([':date' => $date]);
    $stats = $stmt->fetch();

    // Average wait time (from created_at to called_at for served tickets)
    $stmt = $db->prepare("
        SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, called_at)) AS avg_wait
        FROM queue_tickets
        WHERE queue_date = :date AND called_at IS NOT NULL
    ");
    $stmt->execute([':date' => $date]);
    $waitRow = $stmt->fetch();
    $avgWaitSeconds = $waitRow ? (int)$waitRow['avg_wait'] : 0;

    // Average service time (from called_at to completed_at)
    $stmt = $db->prepare("
        SELECT AVG(TIMESTAMPDIFF(SECOND, called_at, completed_at)) AS avg_service
        FROM queue_tickets
        WHERE queue_date = :date AND completed_at IS NOT NULL AND called_at IS NOT NULL
    ");
    $stmt->execute([':date' => $date]);
    $svcRow = $stmt->fetch();
    $avgServiceSeconds = $svcRow ? (int)$svcRow['avg_service'] : 0;

    Response::success('Statistics loaded', [
        'waiting'        => (int)($stats['waiting'] ?? 0),
        'serving'        => (int)($stats['serving'] ?? 0),
        'completed'      => (int)($stats['completed'] ?? 0),
        'no_show'        => (int)($stats['no_show'] ?? 0),
        'cancelled'      => (int)($stats['cancelled'] ?? 0),
        'total'          => (int)($stats['total'] ?? 0),
        'avg_wait_time'  => timeDiff(date('Y-m-d H:i:s', strtotime('-' . $avgWaitSeconds . ' seconds')), date('Y-m-d H:i:s')),
        'avg_wait_secs'  => $avgWaitSeconds,
        'avg_service_time' => timeDiff(date('Y-m-d H:i:s', strtotime('-' . $avgServiceSeconds . ' seconds')), date('Y-m-d H:i:s')),
        'avg_service_secs' => $avgServiceSeconds
    ]);

} catch (Exception $e) {
    error_log('SFI Statistics Error: ' . $e->getMessage());
    Response::error('Failed to load statistics.', [], 500);
}
