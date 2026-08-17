<?php
/**
 * SFI Queuing System - Cohere Chat Helper
 * Thin wrapper around the Cohere v2 Chat API using cURL (no SDK needed).
 */

/**
 * Call the Cohere Chat API and return the assistant's reply text.
 *
 * @param array  $messages  Conversation so far: [['role' => 'user'|'assistant', 'content' => '...'], ...]
 * @param string $systemPrompt Instructions the bot should follow.
 * @return string The assistant's reply text.
 * @throws Exception if the API call fails.
 */
function cohereChat(array $messages, $systemPrompt) {
    $url = 'https://api.cohere.com/v2/chat';

    // Build the messages array (system prompt first, then history)
    $payloadMessages = [];
    if ($systemPrompt !== '') {
        $payloadMessages[] = ['role' => 'system', 'content' => $systemPrompt];
    }
    foreach ($messages as $m) {
        $role = ($m['role'] === 'assistant') ? 'assistant' : 'user';
        $payloadMessages[] = ['role' => $role, 'content' => (string)$m['content']];
    }

    $payload = [
        'model'      => COHERE_MODEL,
        'messages'   => $payloadMessages,
        'max_tokens' => COHERE_MAX_TOKENS,
        'temperature'=> 0.3,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . COHERE_API_KEY,
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new Exception('Cohere connection error: ' . $curlErr);
    }

    $data = json_decode($response, true);

    if ($httpCode >= 400 || !$data) {
        $msg = $data['message'] ?? ('HTTP ' . $httpCode);
        if (is_array($msg)) $msg = json_encode($msg);
        throw new Exception('Cohere API error: ' . $msg);
    }

    // v2 response shape: message.content[].text
    $text = '';
    if (!empty($data['message']['content']) && is_array($data['message']['content'])) {
        foreach ($data['message']['content'] as $part) {
            if (isset($part['text'])) {
                $text .= $part['text'];
            }
        }
    }
    if ($text === '' && !empty($data['text'])) {
        $text = $data['text'];
    }

    return trim($text);
}
