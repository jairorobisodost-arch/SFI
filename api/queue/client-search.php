<?php
/**
 * SFI Queuing System - Client Autocomplete API (public, used by the kiosk)
 * GET /api/queue/client-search.php?q=
 * Searches the imported client masterlist by name or contact number.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
initAPI();

try {
    $q = trim(get('q'));
    if (mb_strlen($q) < 2) {
        Response::success('OK', ['clients' => []]);
    }

    $db = Database::getConnection();

    // Escape LIKE wildcards so user input is matched literally
    $like = '%' . addcslashes($q, '%_') . '%';

    $stmt = $db->prepare("
        SELECT c.id, c.full_name, c.contact_number, c.address, c.loan_status,
               lt.name AS loan_type_name
        FROM clients c
        LEFT JOIN loan_types lt ON lt.id = c.loan_type_id
        WHERE (c.full_name LIKE :l1 OR c.contact_number LIKE :l2) AND c.is_archived = 0
        ORDER BY c.full_name
        LIMIT 8
    ");
    $stmt->execute([':l1' => $like, ':l2' => $like]);

    Response::success('OK', ['clients' => $stmt->fetchAll()]);

} catch (Exception $e) {
    error_log('SFI Client Search Error: ' . $e->getMessage());
    Response::error('Search failed.', [], 500);
}
