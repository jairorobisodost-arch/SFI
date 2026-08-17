<?php
/**
 * SFI Queuing System - Get Today's Queue API
 * GET /api/queue/today.php
 * Returns all tickets for today + the teller's currently serving ticket.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
initAPI();

try {
    $db = Database::getConnection();
    $counter = getAssignedCounter();

    // All today's tickets
    $stmt = $db->prepare("
        SELECT qt.*, lt.name AS loan_type_name
        FROM queue_tickets qt
        LEFT JOIN loan_types lt ON qt.loan_type_id = lt.id
        WHERE qt.queue_date = :date
        ORDER BY qt.created_at DESC
        LIMIT 200
    ");
    $stmt->execute([':date' => today()]);
    $tickets = $stmt->fetchAll();

    // Find this teller's currently serving ticket
    $stmt = $db->prepare("
        SELECT qt.*, lt.name AS loan_type_name
        FROM queue_tickets qt
        LEFT JOIN loan_types lt ON qt.loan_type_id = lt.id
        WHERE qt.queue_date = :date AND qt.status = 'serving' AND qt.counter_assigned = :counter
        ORDER BY qt.called_at DESC
        LIMIT 1
    ");
    $stmt->execute([':date' => today(), ':counter' => $counter]);
    $servingTicket = $stmt->fetch();

    Response::success('Today\'s queue loaded', [
        'tickets'        => $tickets,
        'serving_ticket' => $servingTicket ?: null,
        'total'          => count($tickets)
    ]);

} catch (Exception $e) {
    error_log('SFI Queue Today Error: ' . $e->getMessage());
    Response::error('Failed to load today\'s queue.', [], 500);
}
