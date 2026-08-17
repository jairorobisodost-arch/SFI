<?php
/**
 * SFI Queuing System - Call Next Ticket API
 * POST /api/queue/call-next.php
 *
 * Finds the oldest waiting ticket and assigns it to the teller's counter.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
initAPI();
requirePost();

try {
    $db = Database::getConnection();
    $counter = getAssignedCounter();
    $userId = getUserId();

    // Check if teller already has an active (serving) ticket
    $stmt = $db->prepare("
        SELECT qt.*, lt.name AS loan_type_name
        FROM queue_tickets qt
        LEFT JOIN loan_types lt ON qt.loan_type_id = lt.id
        WHERE qt.queue_date = :date AND qt.status = 'serving' AND qt.counter_assigned = :counter
        ORDER BY qt.called_at DESC
        LIMIT 1
    ");
    $stmt->execute([':date' => today(), ':counter' => $counter]);
    $activeTicket = $stmt->fetch();

    if ($activeTicket) {
        Response::error('You already have an active ticket: ' . $activeTicket['ticket_number'] . '. Please complete or mark as no-show before calling the next client.');
    }

    // Find the oldest waiting ticket
    $stmt = $db->prepare("
        SELECT qt.*, lt.name AS loan_type_name
        FROM queue_tickets qt
        LEFT JOIN loan_types lt ON qt.loan_type_id = lt.id
        WHERE qt.queue_date = :date AND qt.status = 'waiting'
        ORDER BY qt.created_at ASC
        LIMIT 1
    ");
    $stmt->execute([':date' => today()]);
    $nextTicket = $stmt->fetch();

    if (!$nextTicket) {
        Response::error('No waiting clients in the queue.');
    }

    // Update the ticket: assign to counter, set status to serving, save called_at
    $stmt = $db->prepare("
        UPDATE queue_tickets 
        SET status = 'serving', counter_assigned = :counter, served_by = :user, called_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ':counter' => $counter,
        ':user'    => $userId,
        ':id'      => $nextTicket['id']
    ]);

    // Re-fetch the updated ticket
    $stmt = $db->prepare("
        SELECT qt.*, lt.name AS loan_type_name
        FROM queue_tickets qt
        LEFT JOIN loan_types lt ON qt.loan_type_id = lt.id
        WHERE qt.id = :id
    ");
    $stmt->execute([':id' => $nextTicket['id']]);
    $updatedTicket = $stmt->fetch();

    // Log activity
    logActivity($userId, getUsername(), 'call_next', 'Called ticket ' . $updatedTicket['ticket_number'] . ' to Counter ' . $counter);

    // Emit Socket.IO events
    emitSocketEvent('ticket_called', [
        'ticket_number'  => $updatedTicket['ticket_number'],
        'client_name'    => $updatedTicket['client_name'],
        'loan_type'      => $updatedTicket['loan_type_name'],
        'counter'        => $counter,
        'prefix'         => $updatedTicket['prefix']
    ]);

    Response::success('Ticket ' . $updatedTicket['ticket_number'] . ' called to Counter ' . $counter, $updatedTicket);

} catch (Exception $e) {
    error_log('SFI Call Next Error: ' . $e->getMessage());
    Response::error('Failed to call next ticket. Please try again.', [], 500);
}
