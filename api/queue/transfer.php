<?php
/**
 * SFI Queuing System - Transfer Ticket API
 * POST /api/queue/transfer.php
 * Transfers the current serving ticket to another counter.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
initAPI();
requirePost();

try {
    $db = Database::getConnection();
    $counter = getAssignedCounter();
    $targetCounter = (int)post('counter');

    if ($targetCounter < 1 || $targetCounter > 10) {
        Response::error('Invalid target counter.');
    }

    if ($targetCounter === $counter) {
        Response::error('Cannot transfer to the same counter.');
    }

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
        Response::error('No active ticket to transfer.');
    }

    // Update counter assignment
    $stmt = $db->prepare("
        UPDATE queue_tickets 
        SET counter_assigned = :target
        WHERE id = :id
    ");
    $stmt->execute([':target' => $targetCounter, ':id' => $ticket['id']]);

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
    logActivity(getUserId(), getUsername(), 'transfer', 'Transferred ticket ' . $ticket['ticket_number'] . ' from Counter ' . $counter . ' to Counter ' . $targetCounter);

    // Emit Socket.IO event
    emitSocketEvent('ticket_transferred', [
        'ticket_number'  => $ticket['ticket_number'],
        'client_name'    => $ticket['client_name'],
        'from_counter'   => $counter,
        'to_counter'     => $targetCounter
    ]);

    Response::success('Ticket ' . $ticket['ticket_number'] . ' transferred to Counter ' . $targetCounter, $updated);

} catch (Exception $e) {
    error_log('SFI Transfer Error: ' . $e->getMessage());
    Response::error('Failed to transfer ticket.', [], 500);
}
