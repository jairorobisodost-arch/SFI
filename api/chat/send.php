<?php
/**
 * SFI Queuing System - Chatbot API
 * POST /api/chat/send.php
 *
 * Public endpoint used by the website chat widget. Calls Cohere with a system
 * prompt containing the loan office's information and returns the reply.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/cohere.php';
initAPI();
requirePost();

// ---- Rate limiting (protect the free monthly credits) ----
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Per-session cooldown: minimum 1.5s between messages
$last = $_SESSION['chat_last_time'] ?? 0;
if ((microtime(true) - $last) < 1.5) {
    Response::error('Masyadong mabilis ang pag-type mo. Sandali lang.', [], 429);
}

// Per-session cap: max 15 messages per browser session
$sessionCount = (int)($_SESSION['chat_count'] ?? 0);
if ($sessionCount >= 15) {
    Response::error('Naabot mo na ang limit ng chat session na ito. I-refresh ang page para magsimula ulit.', [], 429);
}

// Global daily cap (trial key: ~1,000 calls/month, keep it conservative)
$usageFile = ROOT_PATH . '/data/cohere_usage.json';
$usage = ['date' => date('Y-m-d'), 'count' => 0];
if (is_file($usageFile)) {
    $saved = @json_decode((string)@file_get_contents($usageFile), true);
    if (is_array($saved) && ($saved['date'] ?? '') === date('Y-m-d')) {
        $usage = $saved;
    }
}
if ($usage['count'] >= 200) {
    Response::error('Naabot na ang daily limit ng chatbot. Balikan mo bukas o makipag-ugnayan sa opisina.', [], 429);
}

$message = trim(post('message'));
if ($message === '') {
    Response::error('Please enter a message.');
}
if (mb_strlen($message) > 1000) {
    Response::error('Message is too long.');
}

// ---- System prompt: the bot's knowledge about the loan office ----
$systemPrompt = <<<PROMPT
Ikaw ay si "SFI Assistant" — ang virtual assistant ng SFI (isang lending/loan office sa CALAPAN CITY, ORIENTAL MINDORO). Ang trabaho mo ay tumulong sa mga kliyente at bisita ng website.

MGA SERBISYO NG OPISINA (queue via kiosk):
- Payment (PY) - pagbabayad ng loan
- Release (RL) - paglabas/pag-release ng loan
- Customer Services (CS) - customer service at inquiries
- New Loan Application (AP) - aplikasyon ng bagong loan

MGA LOAN PRODUCT:
- Productive Loan (PL)
- Angat Negosyo Loan (AN)
- Educational Loan (EL)

PAANO GAMITIN ANG SYSTEM:
- Para kumuha ng queue number: pumunta sa kiosk sa opisina at ilagay ang pangalan at contact number, piliin ang transaction, at makakakuha ng queue number.
- Para i-check ang loan status/impormasyon: gamitin ang "Check Your Loan" sa website — ilagay ang buong pangalan at contact number, may OTP verification para sa seguridad. HUWAG ibigay ang personal na loan details ng sinuman dito sa chat — i-direct sila sa Check Your Loan feature.
- Ang ticket number ay may prefix tulad ng PY, RL, CS, PL, AN, EL, AP.

MGA ALITUNTUNIN:
- Sumagot sa natural na Filipino/Taglish (halo-halong Tagalog at English) na parang kausap mong kaibigan — maging maikli, direkta, at conversational. Huwag masyadong pormal o parang robot.
- Kapag binati ka (hal. "kamusta", "hi", "hello", "good morning"), sumagot ka ng pabalik na natural (hal. "Okay naman ako! Ikaw, kamusta? May matutulong ba ako sa'yo?") bago magtanong kung ano ang kailangan.
- Maikli at direkta sa punto ang mga sagot (1-3 pangungusap kung posible). Huwag mag-lecture o magbigay ng mahabang paliwanag — kung simple ang tanong, simple lang din ang sagot (hal. "Ano ang mga loan nyo?" → ilista lang ang 3 products).
- Kung hindi mo alam ang sagot, sabihin na hindi mo alam at i-refer ang kliyente sa opisina o sa tawag.
- Huwag gumawa ng mga presyo, interest rate, o oras ng opisina — sabihin na itanong ito sa opisina.
- Huwag magbigay ng sensitibong impormasyon (loan balances, account details) sa chat — privacy ito.
PROMPT;

// ---- Keep a short conversation history in the session ----
if (empty($_SESSION['chat_history']) || !is_array($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}
$history = array_slice($_SESSION['chat_history'], -6); // last 3 exchanges
$history[] = ['role' => 'user', 'content' => $message];

try {
    $reply = cohereChat($history, $systemPrompt);

    if ($reply === '') {
        throw new Exception('Empty reply from Cohere.');
    }

    // Save usage + history
    $usage['count']++;
    @file_put_contents($usageFile, json_encode($usage), LOCK_EX);

    $_SESSION['chat_history'] = $history;
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION['chat_last_time'] = microtime(true);
    $_SESSION['chat_count'] = $sessionCount + 1;

    Response::success('OK', ['reply' => $reply]);

} catch (Exception $e) {
    error_log('SFI Chatbot Error: ' . $e->getMessage());
    Response::error('Pasensya na, may problema sa chatbot. Subukan mo ulit sa ilang sandali.', [], 500);
}
