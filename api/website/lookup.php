<?php
/**
 * SFI Queuing System - Public Client Loan Lookup API
 * POST /api/website/lookup.php
 *
 * DISABLED: replaced by the OTP-protected flow (send-otp.php + verify-otp.php).
 * This endpoint now requires a verified OTP session, so loan info can no
 * longer be fetched with just a name + number.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
initAPI();
requirePost();

// ---- OTP gate: a valid OTP must have been verified in this session -------
if (empty($_SESSION['otp_verified']) || empty($_SESSION['otp_pending_number'])) {
    Response::error('Verification required. Please use the Check Your Loan form to verify your number first.', [], 403);
}

try {
    $name   = post('name');
    $number = post('number');

    // ---- Validation ----
    if (mb_strlen(trim($name)) < 2) {
        Response::error('Please enter your full name.');
    }
    $digits = preg_replace('/\D+/', '', $number);
    if (strlen($digits) < 10) {
        Response::error('Please enter a valid contact number.');
    }

    // ---- Rate limiting (10 attempts per 15 minutes per browser/IP) ----
    $ip = getClientIP();
    $key = 'lookup_attempts_' . md5($ip);
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first' => time()];
    }
    if (time() - $_SESSION[$key]['first'] > 900) {
        $_SESSION[$key] = ['count' => 0, 'first' => time()];
    }
    if ($_SESSION[$key]['count'] >= 10) {
        Response::error('Too many attempts. Please try again after 15 minutes.', [], 429);
    }

    $db = Database::getConnection();

    // ---- Find the client: number (canonical, acts as the access code) ----
    $canon = normalizeContactNumber($digits);
    $stmt = $db->prepare("
        SELECT c.id, c.full_name, c.contact_number, c.address, c.loan_status, c.raw_data,
               lt.name AS loan_type_name
        FROM clients c
        LEFT JOIN loan_types lt ON lt.id = c.loan_type_id
        WHERE c.contact_number LIKE :c AND c.is_archived = 0
        LIMIT 10
    ");
    $stmt->execute([':c' => '%' . $canon . '%']);

    $match = null;
    $tokens = array_values(array_filter(preg_split('/\s+/', normalizeClientName($name))));
    foreach ($stmt as $row) {
        if (normalizeContactNumber($row['contact_number']) !== $canon) continue;
        $storedName = normalizeClientName($row['full_name']);
        $nameOk = true;
        foreach ($tokens as $t) {
            if ($t !== '' && strpos($storedName, $t) === false) {
                $nameOk = false;
                break;
            }
        }
        if ($nameOk) {
            $match = $row;
            break;
        }
    }

    // ---- Generic failure so names can't be probed ----
    if (!$match) {
        $_SESSION[$key]['count']++;
        Response::error('No matching record found. Please double-check your full name and contact number.', [], 404);
    }

    $_SESSION[$key] = ['count' => 0, 'first' => time()];

    // ---- Build the response data ----
    $raw = ($match['raw_data'] !== null) ? json_decode($match['raw_data'], true) : [];
    if (!is_array($raw)) $raw = [];

    $statusLabels = [
        'pending'  => 'Pending / Under Review',
        'approved' => 'Approved',
        'released' => 'Released',
        'active'   => 'Active / Repaying',
        'closed'   => 'Closed'
    ];

    Response::success('Record found.', [
        'client' => [
            'full_name'      => $match['full_name'],
            'contact_number' => $match['contact_number'],
            'address'        => $match['address'],
            'loan_type'      => $match['loan_type_name'],
            'loan_status'    => $match['loan_status'],
            'loan_status_label' => isset($statusLabels[$match['loan_status']])
                ? $statusLabels[$match['loan_status']]
                : null
        ],
        'loan' => [
            'loan_reference'     => isset($raw['LoanReference']) ? $raw['LoanReference'] : '',
            'loan_amount'        => isset($raw['LoanAmountDisbursed']) ? $raw['LoanAmountDisbursed'] : '',
            'principal_balance'  => isset($raw['PrincipalBalance']) ? $raw['PrincipalBalance'] : '',
            'interest_rate'      => isset($raw['InterestRate']) ? $raw['InterestRate'] : '',
            'date_granted'       => isset($raw['DateGranted']) ? excelSerialToDate($raw['DateGranted']) : '',
            'date_due'           => isset($raw['DateDue']) ? excelSerialToDate($raw['DateDue']) : '',
            'contract_type'      => isset($raw['ContractType']) ? $raw['ContractType'] : '',
            'contract_phase'     => isset($raw['ContractPhase']) ? $raw['ContractPhase'] : ''
        ],
        'raw' => $raw
    ]);

} catch (Exception $e) {
    error_log('SFI Website Lookup Error: ' . $e->getMessage());
    Response::error('A system error occurred. Please try again later.', [], 500);
}
