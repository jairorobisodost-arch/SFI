<?php
/**
 * SFI Queuing System - Verify OTP API
 * POST /api/website/verify-otp.php
 *
 * Verifies the 6-digit OTP sent to the client's number. On success, returns the
 * client's loan information (from the 3-file import: Client Information,
 * Client Loan Report, and Client VS And CBU Report) matched via client_reference.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/sms.php';
initAPI();
requirePost();

try {
    $name   = post('name');
    $number = post('number');
    $otp    = post('otp');

    if (mb_strlen(trim($name)) < 2) {
        Response::error('Please enter your full name.');
    }
    $digits = preg_replace('/\D+/', '', $number);
    if (strlen($digits) < 10) {
        Response::error('Please enter a valid contact number.');
    }
    if ($otp === '') {
        Response::error('Please enter the verification code.');
    }

    $db = Database::getConnection();

    // ---- Resolve the canonical number (same normalization as send-otp) ----
    $canon = normalizeContactNumber($digits);
    $stmt = $db->prepare("
        SELECT c.full_name, c.contact_number
        FROM clients c
        WHERE c.contact_number LIKE :c AND c.is_archived = 0
        LIMIT 10
    ");
    $stmt->execute([':c' => '%' . $canon . '%']);

    $storedContact = null;
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
            $storedContact = $row['contact_number'];
            break;
        }
    }

    if ($storedContact === null) {
        Response::error('No matching record found. Please double-check your full name and contact number.', [], 404);
    }

    // ---- Verify the OTP ----
    $verify = verifyOTP($storedContact, $otp);
    if (!$verify['ok']) {
        Response::error($verify['message'], [], 400);
    }

    // Mark this session as verified for this number (defense in depth)
    $_SESSION['otp_verified'] = true;
    $_SESSION['otp_verified_number'] = $storedContact;
    $_SESSION['otp_pending_number'] = $storedContact;

    // ---- OTP verified: fetch full client record ----
    $stmt = $db->prepare("
        SELECT c.id, c.client_reference, c.full_name, c.contact_number, c.address,
               c.gender, c.civil_status, c.birthday, c.branch, c.ao, c.center_name,
               c.loan_status, c.raw_data,
               lt.name AS loan_type_name
        FROM clients c
        LEFT JOIN loan_types lt ON lt.id = c.loan_type_id
        WHERE c.contact_number = :c AND c.is_archived = 0
        LIMIT 1
    ");
    $stmt->execute([':c' => $storedContact]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        Response::error('No matching record found.', [], 404);
    }

    // ---- Load loans + CBU matched by client_reference ----
    $loans = [];
    $cbu = null;
    if (!empty($match['client_reference'])) {
        $ref = $match['client_reference'];
        $stmt = $db->prepare("
            SELECT loan_category, loan_product, cycle_no, date_release, date_matured,
                   principal, interest, principal_balance, interest_balance,
                   advances, total_arrears
            FROM client_loans
            WHERE client_reference = :ref AND is_archived = 0
            ORDER BY COALESCE(date_release, created_at) DESC
        ");
        $stmt->execute([':ref' => $ref]);
        $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("
            SELECT vs_deposits, vs_withdrawals, vs_adjust_credit, vs_adjust_debit, vs_balance,
                   cbu_deposits, cbu_withdrawals, cbu_transfer_to_vs,
                   cbu_adjust_credit, cbu_adjust_debit, cbu_balance
            FROM client_cbu
            WHERE client_reference = :ref AND is_archived = 0
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([':ref' => $ref]);
        $cbu = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ---- Build the response data ----
    $statusLabels = [
        'pending'  => 'Pending / Under Review',
        'approved' => 'Approved',
        'released' => 'Released',
        'active'   => 'Active / Repaying',
        'closed'   => 'Closed'
    ];

    // The primary loan is the most recent one (for the summary card)
    $primaryLoan = isset($loans[0]) ? $loans[0] : null;
    $totalArrears = 0;
    foreach ($loans as $l) {
        $totalArrears += (float)$l['total_arrears'];
    }

    Response::success('Verification successful.', [
        'client' => [
            'full_name'      => $match['full_name'],
            'contact_number' => $match['contact_number'],
            'address'        => $match['address'],
            'gender'         => $match['gender'],
            'civil_status'   => $match['civil_status'],
            'birthday'       => $match['birthday'] ? date('F j, Y', strtotime($match['birthday'])) : '',
            'branch'         => $match['branch'],
            'ao'             => $match['ao'],
            'center_name'    => $match['center_name'],
            'client_reference' => $match['client_reference'],
            'loan_type'      => $match['loan_type_name'],
            'loan_status'    => $match['loan_status'],
            'loan_status_label' => isset($statusLabels[$match['loan_status']])
                ? $statusLabels[$match['loan_status']]
                : null
        ],
        'loan' => [
            'loan_reference'     => $match['client_reference'],
            'loan_product'       => $primaryLoan ? $primaryLoan['loan_product'] : '',
            'loan_category'      => $primaryLoan ? $primaryLoan['loan_category'] : '',
            'cycle_no'           => $primaryLoan ? $primaryLoan['cycle_no'] : '',
            'loan_amount'        => $primaryLoan ? $primaryLoan['principal'] : '',
            'principal_balance'  => $primaryLoan ? $primaryLoan['principal_balance'] : '',
            'interest'           => $primaryLoan ? $primaryLoan['interest'] : '',
            'interest_balance'   => $primaryLoan ? $primaryLoan['interest_balance'] : '',
            'date_granted'       => $primaryLoan && $primaryLoan['date_release']
                ? date('F j, Y', strtotime($primaryLoan['date_release'])) : '',
            'date_due'           => $primaryLoan && $primaryLoan['date_matured']
                ? date('F j, Y', strtotime($primaryLoan['date_matured'])) : '',
            'advances'           => $primaryLoan ? $primaryLoan['advances'] : '',
            'total_arrears'      => $primaryLoan ? $primaryLoan['total_arrears'] : '',
            'total_arrears_all'  => number_format($totalArrears, 2, '.', '')
        ],
        'loans' => $loans,
        'cbu' => $cbu,
        'raw' => json_decode((string)($match['raw_data'] ?? ''), true) ?: []
    ]);

} catch (Exception $e) {
    error_log('SFI Verify OTP Error: ' . $e->getMessage());
    Response::error('A system error occurred. Please try again later.', [], 500);
}
