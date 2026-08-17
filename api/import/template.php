<?php
/**
 * SFI Queuing System - Import Template Download
 * GET /api/import/template.php
 * Downloads an Excel-compatible CSV template with the correct headers.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
requireRole('admin');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sfi-import-template.csv"');

// UTF-8 BOM so Excel opens it correctly
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['Full Name', 'Contact Number', 'Address', 'Transaction Type', 'Remarks']);
fputcsv($out, ['Juan Dela Cruz', '09171234567', 'Brgy. San Isidro', 'Payment', '']);
fputcsv($out, ['Maria Santos', '09281234567', 'Brgy. Poblacion', 'Release', 'Sample remarks']);
fputcsv($out, ['Jose Reyes', '09391234567', '', 'Customer Services', '']);
fclose($out);
exit;
