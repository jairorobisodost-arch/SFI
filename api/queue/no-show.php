<?php
/**
 * SFI Queuing System - No-Show Ticket API
 * POST /api/queue/no-show.php
 * Marks the current serving ticket as no-show.
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
        Response::error('No active ticket to mark as no-show.');
    }

    // Update status to no_show
    $stmt = $db->prepare("
        UPDATE queue_tickets 
        SET status = 'no_show', completed_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([':id' => $ticket['id']]);

    // Re-fetch
    $stmt = $db->prepare("
        SELECT qt.*, lt.name AS loan_type_name
        FROM queue_tickets qt
        LEFT JOIN loan_types lt ON qt.loan_type_id = lt.id
        WHERE qt.id = :id
    ");
    $stmt->execute([':id' => $ticket['id']]);
    $updated = $stmt->fetch();

    // Log activity
    logActivity(getUserId(), getUsername(), 'no_show', 'Marked ticket ' . $ticket['ticket_number'] . ' as no-show');

    // Emit Socket.IO event
    emitSocketEvent('ticket_no_show', [
        'ticket_number' => $ticket['ticket_number'],
        'client_name'   => $ticket['client_name'],
        'counter'       => $counter
    ]);

    Response::success('Ticket ' . $ticket['ticket_number'] . ' marked as no-show.', $updated);

} catch (Exception $e) {
    error_log('SFI No-Show Error: ' . $e->getMessage());
    Response::error('Failed to mark no-show.', [], 500);
}
