<?php
/**
 * SFI Queuing System - Update Client Loan Info API
 * POST /api/import/update.php
 * Updates a client's loan type (product) and loan status.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
requireRole('admin');
initAPI();
requirePost();

try {
    $id = (int)post('id');
    if ($id < 1) {
        Response::error('Invalid client ID.');
    }

    $loanStatusRaw = post('loan_status');
    $loanTypeRaw   = post('loan_type_id');

    $allowedStatuses = ['', 'pending', 'approved', 'released', 'active', 'closed'];
    if (!in_array($loanStatusRaw, $allowedStatuses, true)) {
        Response::error('Invalid loan status.');
    }

    $loanTypeId = null;
    if ($loanTypeRaw !== '') {
        if (!is_numeric($loanTypeRaw)) {
            Response::error('Invalid loan type.');
        }
        $loanTypeId = (int)$loanTypeRaw;
    }

    $db = Database::getConnection();

    // Verify the client exists
    $stmt = $db->prepare("SELECT id, full_name FROM clients WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $client = $stmt->fetch();
    if (!$client) {
        Response::error('Client not found.');
    }

    $stmt = $db->prepare("UPDATE clients SET loan_status = :s, loan_type_id = :lt WHERE id = :id");
    $stmt->execute([
        ':s'  => $loanStatusRaw === '' ? null : $loanStatusRaw,
        ':lt' => $loanTypeId,
        ':id' => $id
    ]);

    logActivity(getUserId(), getUsername(), 'update_client_loan',
        'Updated loan info for client: ' . $client['full_name']);

    Response::success('Client loan information updated.');

} catch (Exception $e) {
    error_log('SFI Client Update Error: ' . $e->getMessage());
    Response::error('Failed to update client loan information.', [], 500);
}
