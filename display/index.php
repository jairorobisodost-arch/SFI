<?php
/**
 * SFI Queuing System - Live TV Display
 * URL: /display/
 * No login required. Optimized for full-screen TV/monitor.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
initPage();

// Get announcement message from settings
$announcementMsg = getSetting('announcement_message', 'Welcome to SFI Queuing System. Please proceed to the kiosk to get your queue number.');
// Voice + speed settings for the speech synthesis announcements
$announcementVoice = getSetting('announcement_voice', 'female');
$announcementSpeed = (float)getSetting('announcement_speed', '0.9');
if ($announcementSpeed < 0.5 || $announcementSpeed > 2) { $announcementSpeed = 0.9; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Display - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/display.css">
</head>
<body>
    <!-- Audio Init Overlay -->
    <div class="audio-overlay" id="audioOverlay">
        <button class="audio-overlay-btn" id="btnEnableSound">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
                <path d="M19.07 4.93a10 10 0 0 1 0 14.14"/>
                <path d="M15.54 8.46a5 5 0 0 1 0 7.07"/>
            </svg>
            CLICK TO ENABLE SOUND
        </button>
    </div>

    <!-- Connection Status -->
    <div class="display-connection">
        <span class="connection-status disconnected" id="connStatus">
            <span class="connection-dot"></span> OFFLINE
        </span>
    </div>

    <div class="display-wrapper">
        <!-- Main: Now Serving -->
        <div class="display-main" id="nowServingArea">
            <div class="display-label">NOW SERVING</div>
            <div class="display-ticket-number" id="displayTicketNumber">---</div>
            <div class="display-client-name" id="displayClientName">Waiting for next client</div>
            <div class="display-loan-type" id="displayLoanType"></div>
            <div class="display-counter" id="displayCounter"></div>
        </div>

        <!-- Bottom: Next In Line -->
        <div class="display-next-section">
            <div class="display-next-header">NEXT IN LINE</div>
            <div class="display-next-grid" id="nextInLineGrid">
                <div class="display-next-item empty"><div class="next-ticket">---</div><div class="next-type">Empty</div></div>
                <div class="display-next-item empty"><div class="next-ticket">---</div><div class="next-type">Empty</div></div>
                <div class="display-next-item empty"><div class="next-ticket">---</div><div class="next-type">Empty</div></div>
                <div class="display-next-item empty"><div class="next-ticket">---</div><div class="next-type">Empty</div></div>
                <div class="display-next-item empty"><div class="next-ticket">---</div><div class="next-type">Empty</div></div>
            </div>
        </div>

        <!-- Announcement Ticker -->
        <div class="display-ticker">
            <div class="display-ticker-text"><?= htmlspecialchars($announcementMsg) ?></div>
        </div>
    </div>

    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/socket.js?v=<?= filemtime(ROOT_PATH . '/assets/js/socket.js') ?>"></script>
    <script src="<?= BASE_URL ?>/assets/js/display.js?v=<?= filemtime(ROOT_PATH . '/assets/js/display.js') ?>"></script>
    <script>
        // Load voices for speech synthesis
        if (window.speechSynthesis) {
            window.speechSynthesis.onvoiceschanged = function() {
                window.speechSynthesis.getVoices();
            };
        }

        // Enable sound button
        document.getElementById('btnEnableSound').addEventListener('click', function() {
            Display.initAudio();
        });

        // Initialize display
        Display.init({
            baseUrl: '<?= BASE_URL ?>',
            socketServer: '<?= SOCKET_SERVER ?>',
            voice: '<?= htmlspecialchars($announcementVoice, ENT_QUOTES) ?>',
            speed: <?= json_encode($announcementSpeed) ?>
        });

        // Auto-hide cursor after 3 seconds of inactivity
        let cursorTimer;
        function hideCursor() {
            document.body.style.cursor = 'none';
        }
        function showCursor() {
            document.body.style.cursor = 'default';
            clearTimeout(cursorTimer);
            cursorTimer = setTimeout(hideCursor, 3000);
        }
        document.addEventListener('mousemove', showCursor);
        cursorTimer = setTimeout(hideCursor, 3000);
    </script>
</body>
</html>
