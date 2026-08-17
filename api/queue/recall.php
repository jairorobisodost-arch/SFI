<?php
/**
 * SFI Queuing System - Recall Ticket API
 * POST /api/queue/recall.php
 * Re-announces the current serving ticket without changing its status.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
initAPI();
requirePost();

try {
    $db = Database::getConnection();
    $counter = getAssignedCounter();

    // Find the currently serving ticket for this counter
    $stmt = $db->prepare("
        SELECT qt.*, lt.name AS loan_type_name
        FROM queue_tickets qt
        LEFT JOIN loan_types lt ON qt.loan_type_id = lt.id
        WHERE qt.queue_date = :date AND qt.status = 'serving' AND qt.counter_assigned = :counter
        ORDER BY qt.called_at DESC
        LIMIT 1
    ");
    $stmt->execute([':date' => today(), ':counter' => $counter]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        Response::error('No active ticket to recall.');
    }

    // Log activity
    logActivity(getUserId(), getUsername(), 'recall', 'Recalled ticket ' . $ticket['ticket_number']);

    // Emit Socket.IO event (no DB change needed)
    emitSocketEvent('ticket_recalled', [
        'ticket_number' => $ticket['ticket_number'],
        'client_name'   => $ticket['client_name'],
        'loan_type'     => $ticket['loan_type_name'],
        'counter'       => $counter,
        'prefix'        => $ticket['prefix']
    ]);

    Response::success('Ticket ' . $ticket['ticket_number'] . ' recalled', $ticket);

} catch (Exception $e) {
    error_log('SFI Recall Error: ' . $e->getMessage());
    Response::error('Failed to recall ticket.', [], 500);
}
