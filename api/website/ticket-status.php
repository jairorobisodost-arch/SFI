<?php
/**
 * SFI Queuing System - Public Ticket Status API
 * GET /api/website/ticket-status.php?ticket=PL-001
 *
 * Public endpoint used by the QR code on printed tickets.
 * Returns the current status of a queue ticket without requiring login.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
initAPI();

try {
    $ticketNumber = trim(get('ticket'));
    if ($ticketNumber === '') {
        Response::error('Ticket number is required.', [], 400);
    }
    if (!preg_match('/^[A-Z0-9-]{2,20}$/i', $ticketNumber)) {
        Response::error('Invalid ticket number format.', [], 400);
    }

    $db = Database::getConnection();

    // Look up the most recent ticket with this number (today's queue by default)
    $stmt = $db->prepare("
        SELECT qt.id, qt.ticket_number, qt.queue_date, qt.client_name, qt.contact_number,
               qt.status, qt.counter_assigned, qt.called_at, qt.completed_at, qt.created_at,
               lt.name AS loan_type_name,
               c.counter_number AS counter_number, c.name AS counter_name
        FROM queue_tickets qt
        LEFT JOIN loan_types lt ON lt.id = qt.loan_type_id
        LEFT JOIN counters c ON c.id = qt.counter_assigned
        WHERE qt.ticket_number = :ticket
        ORDER BY qt.queue_date DESC, qt.id DESC
        LIMIT 1
    ");
    $stmt->execute([':ticket' => strtoupper($ticketNumber)]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        Response::error('Ticket not found. Please check the number on your ticket.', [], 404);
    }

    // How many waiting tickets are ahead of this one (same day only)
    $waitingAhead = 0;
    if ($ticket['status'] === 'waiting') {
        $stmt = $db->prepare("
            SELECT COUNT(*) AS cnt
            FROM queue_tickets
            WHERE queue_date = :date AND status = 'waiting' AND id < :id
        ");
        $stmt->execute([':date' => $ticket['queue_date'], ':id' => $ticket['id']]);
        $waitingAhead = (int)($stmt->fetch()['cnt'] ?? 0);
    }

    // Current ticket being served right now (same day, same loan type if possible)
    $nowServing = null;
    $stmt = $db->prepare("
        SELECT ticket_number, counter_number, c.name AS counter_name
        FROM queue_tickets qt
        LEFT JOIN counters c ON c.id = qt.counter_assigned
        WHERE qt.queue_date = :date AND qt.status = 'serving'
        ORDER BY qt.called_at DESC
        LIMIT 1
    ");
    $stmt->execute([':date' => $ticket['queue_date']]);
    $nowServing = $stmt->fetch() ?: null;

    Response::success('Ticket status retrieved', [
        'ticket' => [
            'ticket_number'   => $ticket['ticket_number'],
            'queue_date'      => $ticket['queue_date'],
            'client_name'     => $ticket['client_name'],
            'loan_type'       => $ticket['loan_type_name'] ?? 'General',
            'status'          => $ticket['status'],
            'counter_number'  => $ticket['counter_number'],
            'counter_name'    => $ticket['counter_name'],
            'called_at'       => $ticket['called_at'],
            'completed_at'    => $ticket['completed_at'],
            'created_at'      => $ticket['created_at'],
            'waiting_ahead'   => $waitingAhead,
        ],
        'now_serving' => $nowServing
    ]);

} catch (Exception $e) {
    error_log('SFI Ticket Status Error: ' . $e->getMessage());
    Response::error('Failed to load ticket status. Please try again.', [], 500);
}
