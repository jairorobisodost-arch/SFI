<?php
/**
 * SFI Queuing System - Get Waiting Tickets API
 * GET /api/queue/waiting.php
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
initAPI();

try {
    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT qt.*, lt.name AS loan_type_name 
        FROM queue_tickets qt
        LEFT JOIN loan_types lt ON qt.loan_type_id = lt.id
        WHERE qt.queue_date = :date AND qt.status = 'waiting'
        ORDER BY qt.created_at ASC
    ");
    $stmt->execute([':date' => today()]);
    $tickets = $stmt->fetchAll();

    Response::success('Waiting tickets retrieved', ['tickets' => $tickets, 'count' => count($tickets)]);

} catch (Exception $e) {
    error_log('SFI Queue Waiting Error: ' . $e->getMessage());
    Response::error('Failed to load waiting queue.', [], 500);
}
