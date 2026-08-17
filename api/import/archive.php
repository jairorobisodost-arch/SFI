<?php
/**
 * SFI Queuing System - Archive All Data API
 * POST /api/import/archive.php
 *
 * Moves all current (non-archived) records into the Archive. Used by the
 * "Archive All Old Data" button on the Import page.
 *
 * Two actions:
 *   action=counts  -> returns how many active records each table has
 *   action=archive -> archives one batch (default 500) for one table, returns
 *                     the number archived and how many are still active so the
 *                     frontend can show a real progress bar.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
requireRole('admin');
initAPI();
requirePost();

$allowedTables = ['clients', 'loan_types', 'counters', 'users'];

try {
    $action = post('action', 'archive');
    $db = Database::getConnection();
    $uid = (int)getUserId();

    if ($action === 'counts') {
        $tables = [];
        $archived = [];
        $total = 0;
        $archivedTotal = 0;
        foreach ($allowedTables as $t) {
            $sql = ($t === 'users')
                ? "SELECT COUNT(*) FROM users WHERE is_archived = 0 AND id <> $uid"
                : "SELECT COUNT(*) FROM `$t` WHERE is_archived = 0";
            $c = (int)$db->query($sql)->fetchColumn();
            $tables[$t] = $c;
            $total += $c;

            $sqlA = ($t === 'users')
                ? "SELECT COUNT(*) FROM users WHERE is_archived = 1 AND id <> $uid"
                : "SELECT COUNT(*) FROM `$t` WHERE is_archived = 1";
            $ca = (int)$db->query($sqlA)->fetchColumn();
            $archived[$t] = $ca;
            $archivedTotal += $ca;
        }
        Response::success('OK', ['tables' => $tables, 'total' => $total, 'archived' => $archived, 'archivedTotal' => $archivedTotal]);
    }

    // ---- Snapshot: write ONE dated .xlsx file with the data being archived ----
    // Called BEFORE archiving so the file captures exactly the data being moved.
    // If there is no active data but archived records exist, the file is built
    // from the archived records so the admin still gets a file.
    if ($action === 'snapshot') {
        require_once __DIR__ . '/../../includes/archive-export.php';

        $hasActive = (int)$db->query("SELECT (SELECT COUNT(*) FROM clients WHERE is_archived = 0)
            + (SELECT COUNT(*) FROM loan_types WHERE is_archived = 0)
            + (SELECT COUNT(*) FROM counters WHERE is_archived = 0)
            + (SELECT COUNT(*) FROM users WHERE is_archived = 0 AND id <> $uid)")->fetchColumn();

        $mode = ($hasActive > 0) ? 'active' : 'archived';
        $snap = writeArchiveSnapshot($mode);
        Response::success('Archive file created', [
            'file'   => $snap['file'],
            'path'   => $snap['path'],
            'mode'   => $mode,
            'counts' => $snap['counts'],
        ]);
    }

    // ---- Archive one batch for one table ----
    $table = post('table', 'clients');
    if (!in_array($table, $allowedTables, true)) {
        Response::error('Invalid table.');
    }
    $batch = min(2000, max(1, (int)post('batch', 500)));

    if ($table === 'users') {
        // Never archive the currently logged-in user, otherwise they would be locked out
        $archived = (int)$db->exec("UPDATE users SET is_archived = 1 WHERE is_archived = 0 AND id <> $uid LIMIT $batch");
        $remaining = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_archived = 0 AND id <> $uid")->fetchColumn();
    } else {
        $archived = (int)$db->exec("UPDATE `$table` SET is_archived = 1 WHERE is_archived = 0 LIMIT $batch");
        $remaining = (int)$db->query("SELECT COUNT(*) FROM `$table` WHERE is_archived = 0")->fetchColumn();
    }

    Response::success('Archive progress', [
        'table'     => $table,
        'archived'  => $archived,
        'remaining' => $remaining,
        'done'      => ($remaining === 0)
    ]);

} catch (Exception $e) {
    error_log('SFI Archive All Error: ' . $e->getMessage());
    Response::error('Failed to archive data.', [], 500);
}
