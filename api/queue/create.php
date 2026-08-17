<?php
/**
 * SFI Queuing System - Create Queue Ticket API
 * POST /api/queue/create.php
 *
 * Generates a unique ticket number using a transaction with row locking
 * to prevent race conditions from concurrent kiosk submissions.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

initAPI();
requirePost();

try {
    $client_name    = post('client_name');
    $contact_number = post('contact_number');
    $loan_type_id   = post('loan_type_id');

    // ---- Server-side Validation ----
    $errors = [];

    if (empty($client_name)) {
        $errors[] = 'Full name is required.';
    } elseif (strlen($client_name) < 2 || strlen($client_name) > 150) {
        $errors[] = 'Full name must be between 2 and 150 characters.';
    }

    if (empty($contact_number)) {
        $errors[] = 'Contact number is required.';
    } elseif (!preg_match('/^\d{11}$/', $contact_number)) {
        $errors[] = 'Contact number must be exactly 11 digits.';
    }

    if (empty($loan_type_id)) {
        $errors[] = 'Please select a loan type.';
    }

    if (!empty($errors)) {
        Response::error('Validation failed.', $errors);
    }

    $db = Database::getConnection();

    // Validate loan type exists and is active
    $stmt = $db->prepare("SELECT id, name, prefix FROM loan_types WHERE id = :id AND status = 'active' AND is_archived = 0 LIMIT 1");
    $stmt->execute([':id' => $loan_type_id]);
    $loanType = $stmt->fetch();

    if (!$loanType) {
        Response::error('Invalid or inactive loan type selected.');
    }

    // ---- Anti-duplicate: check if same name+contact created a ticket in last 60 seconds ----
    $stmt = $db->prepare("
        SELECT id, ticket_number FROM queue_tickets 
        WHERE client_name = :name AND contact_number = :contact AND queue_date = :date AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)
        LIMIT 1
    ");
    $stmt->execute([
        ':name'    => $client_name,
        ':contact' => $contact_number,
        ':date'    => today()
    ]);
    $duplicate = $stmt->fetch();
    if ($duplicate) {
        Response::error('A ticket was already created for this client recently. Please check your queue number: ' . $duplicate['ticket_number']);
    }

    // ---- Generate unique ticket number inside a transaction ----
    $db->beginTransaction();

    try {
        $ticketNumber = generateTicketNumber($db, $loanType['prefix']);

        $stmt = $db->prepare("
            INSERT INTO queue_tickets (ticket_number, queue_date, client_name, contact_number, loan_type_id, prefix, status)
            VALUES (:ticket_number, :queue_date, :client_name, :contact_number, :loan_type_id, :prefix, 'waiting')
        ");
        $stmt->execute([
            ':ticket_number'  => $ticketNumber,
            ':queue_date'     => today(),
            ':client_name'    => $client_name,
            ':contact_number' => $contact_number,
            ':loan_type_id'   => $loan_type_id,
            ':prefix'         => $loanType['prefix']
        ]);

        $ticketId = $db->lastInsertId();

        $db->commit();

        // Count how many waiting tickets are ahead
        $waitingAhead = getWaitingCountAhead($db, $ticketId);

        // Look up the client in the imported masterlist (by name + contact)
        // so the kiosk can show their loan status (approved / pending / etc.)
        $clientLookup = ['found' => false];
        $canonContact = normalizeContactNumber($contact_number);
        if ($canonContact !== '') {
            $stmt = $db->prepare("
                SELECT c.id, c.full_name, c.contact_number, c.loan_status, lt.name AS loan_type_name
                FROM clients c
                LEFT JOIN loan_types lt ON lt.id = c.loan_type_id
                WHERE c.contact_number LIKE :c AND c.is_archived = 0
                LIMIT 10
            ");
            $stmt->execute([':c' => '%' . $canonContact . '%']);
            $tokens = array_values(array_filter(preg_split('/\s+/', normalizeClientName($client_name))));
            foreach ($stmt as $row) {
                if (normalizeContactNumber($row['contact_number']) !== $canonContact) continue;
                $storedName = normalizeClientName($row['full_name']);
                $nameOk = true;
                foreach ($tokens as $t) {
                    if ($t !== '' && strpos($storedName, $t) === false) {
                        $nameOk = false;
                        break;
                    }
                }
                if ($nameOk) {
                    $clientLookup = [
                        'found'       => true,
                        'full_name'   => $row['full_name'],
                        'loan_status' => $row['loan_status'],
                        'loan_type'   => $row['loan_type_name']
                    ];
                    break;
                }
            }
        }

        // Emit Socket.IO event
        emitSocketEvent('new_ticket', [
            'ticket_number' => $ticketNumber,
            'client_name'   => $client_name,
            'loan_type'     => $loanType['name'],
            'prefix'        => $loanType['prefix']
        ]);

        Response::success('Ticket created successfully', [
            'ticket_id'      => $ticketId,
            'ticket_number'  => $ticketNumber,
            'client_name'    => $client_name,
            'loan_type'      => $loanType['name'],
            'loan_type_id'   => $loanType['id'],
            'prefix'         => $loanType['prefix'],
            'date'           => formatDate(today()),
            'time'           => date('g:i A'),
            'waiting_ahead'  => $waitingAhead,
            'client_lookup'  => $clientLookup
        ], 201);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    error_log('SFI Queue Create Error: ' . $e->getMessage());
    Response::error('Unable to generate queue number. Please try again.', [], 500);
} catch (Exception $e) {
    error_log('SFI Queue Create Error: ' . $e->getMessage());
    Response::error('A system error occurred. Please try again later.', [], 500);
}
