<?php
/**
 * SFI Queuing System - Complete Ticket API
 * POST /api/queue/complete.php
 * Marks the current serving ticket as completed.
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
        Response::error('No active ticket to complete.');
    }

    // Update status to completed
    $stmt = $db->prepare("
        UPDATE queue_tickets 
        SET status = 'completed', completed_at = NOW()
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
    logActivity(getUserId(), getUsername(), 'complete', 'Completed ticket ' . $ticket['ticket_number']);

    // Emit Socket.IO event
    emitSocketEvent('ticket_completed', [
        'ticket_number' => $ticket['ticket_number'],
        'client_name'   => $ticket['client_name'],
        'counter'       => $counter
    ]);

    Response::success('Ticket ' . $ticket['ticket_number'] . ' marked as completed.', $updated);

} catch (Exception $e) {
    error_log('SFI Complete Error: ' . $e->getMessage());
    Response::error('Failed to complete ticket.', [], 500);
}
