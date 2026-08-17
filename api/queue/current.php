<?php
/**
 * SFI Queuing System - Get Currently Serving Tickets API
 * GET /api/queue/current.php
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
initAPI();

try {
    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT qt.*, lt.name AS loan_type_name 
        FROM queue_tickets qt
        LEFT JOIN loan_types lt ON qt.loan_type_id = lt.id
        WHERE qt.queue_date = :date AND qt.status = 'serving'
        ORDER BY qt.called_at DESC
    ");
    $stmt->execute([':date' => today()]);
    $tickets = $stmt->fetchAll();

    Response::success('Currently serving tickets', ['tickets' => $tickets]);

} catch (Exception $e) {
    error_log('SFI Queue Current Error: ' . $e->getMessage());
    Response::error('Failed to load current queue.', [], 500);
}
