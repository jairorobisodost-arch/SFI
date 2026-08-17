/**
 * SFI Queuing System - TV Display JavaScript
 * Handles: Audio (chime + speech synthesis), Socket.IO events, data refresh.
 */

const Display = {
    soundEnabled: false,
    audioInitialized: false,
    chimeAudio: null,
    baseUrl: '',
    socketServer: '',
    voicePreference: 'female',
    speechRate: 0.9,
    lastAnnounceKey: '',
    lastAnnounceAt: 0,
    speakTimer: null,

    /**
     * Initialize the display.
     */
    init(config) {
        this.baseUrl = config.baseUrl;
        this.socketServer = config.socketServer;
        if (config.voice) this.voicePreference = config.voice;
        if (config.speed) this.speechRate = config.speed;

        // Check localStorage for sound preference
        const savedSound = localStorage.getItem('sfi_sound_enabled');
        if (savedSound === '1') {
            this.initAudio();
        }

        // Pre-load speech voices (async in Chrome)
        this.ensureVoicesLoaded();

        // Connect Socket.IO
        SFISocket.connect(this.socketServer);
        this.bindSocketEvents();

        // Load initial data
        this.loadDisplayData();

        // Auto-refresh every 30 seconds as fallback
        setInterval(() => this.loadDisplayData(), 30000);
    },

    /**
     * Initialize audio (must be triggered by user gesture).
     */
    initAudio() {
        if (this.audioInitialized) return;

        // Create chime using AudioContext (no external file needed)
        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            this.audioInitialized = true;
            this.soundEnabled = true;
            localStorage.setItem('sfi_sound_enabled', '1');
        } catch (e) {
            console.warn('AudioContext not available');
            this.audioInitialized = true; // Still mark as initialized
            this.soundEnabled = false;
        }

        // Load speech voices (async in Chrome) so announcements pick the right one
        this.ensureVoicesLoaded();

        // Hide overlay
        const overlay = document.getElementById('audioOverlay');
        if (overlay) overlay.classList.add('hidden');
    },

    /**
     * Play the chime sound using Web Audio API.
     */
    playChime() {
        if (!this.soundEnabled || !this.audioContext) return;

        try {
            const ctx = this.audioContext;
            const now = ctx.currentTime;

            // Three-note chime: C5, E5, G5
            const notes = [523.25, 659.25, 783.99];
            notes.forEach((freq, i) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0.3, now + i * 0.2);
                gain.gain.exponentialRampToValueAtTime(0.01, now + i * 0.2 + 0.5);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(now + i * 0.2);
                osc.stop(now + i * 0.2 + 0.6);
            });
        } catch (e) {
            console.warn('Chime playback error:', e);
        }
    },

    /**
     * Cache the browser's speech voices (Chrome loads them async).
     */
    ensureVoicesLoaded() {
        if (!window.speechSynthesis) return;
        const load = () => {
            if (window.speechSynthesis.getVoices().length > 0) {
                this.voices = window.speechSynthesis.getVoices();
            }
        };
        load();
        window.speechSynthesis.onvoiceschanged = load;
    },

    /**
     * Pick a speech voice by gender preference using known voice names.
     */
    pickVoice(pref) {
        const voices = this.voices && this.voices.length ? this.voices : window.speechSynthesis.getVoices();
        if (!voices.length) return null;

        const femaleRe = /female|zira|hazel|samantha|victoria|karen|susan|google us english|google uk english female|aria|jenny|libby|sonia|natasha|tessa|heera|swara|lekha|veena/i;
        const maleRe = /male|david|mark|george|daniel|alex|google uk english male|guy|ryan|james|eric|christopher|brian|arvind|kiran|prabhat/i;
        const en = voices.filter(v => v.lang.toLowerCase().startsWith('en'));

        // 1) Exact gender match on English voices
        let found = en.find(v => (pref === 'male' ? maleRe : femaleRe).test(v.name));
        if (found) return found;
        // 2) Any English voice (better than the browser default)
        return en[0] || voices[0] || null;
    },

    /**
     * Speak the announcement using Speech Synthesis.
     */
    speakAnnouncement(ticketNumber, counter) {
        if (!this.soundEnabled) return;
        if (!window.speechSynthesis) return;

        // Cancel any ongoing speech AND any pending speak timer
        window.speechSynthesis.cancel();
        if (this.speakTimer) {
            clearTimeout(this.speakTimer);
            this.speakTimer = null;
        }

        // Format ticket number for speech: "PL-001" -> "P L zero zero one"
        const spoken = this.formatTicketForSpeech(ticketNumber);
        const text = 'Now serving ticket ' + spoken + ' at Counter ' + counter + '.';

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'en-US';
        utterance.rate = this.speechRate;
        utterance.pitch = 1.0;
        utterance.volume = 1.0;

        // Pick a voice based on the admin settings (female / male).
        // Voice names often DON'T contain the words "female"/"male"
        // (e.g. "Microsoft Zira Desktop" is female), so we match against
        // the known female/male voice names and cache the list because
        // Chrome loads voices asynchronously.
        const preferred = this.pickVoice(this.voicePreference);

        if (preferred) {
            utterance.voice = preferred;
        }

        // Small delay after chime before speaking (store timer so duplicates can cancel it)
        this.speakTimer = setTimeout(() => {
            this.speakTimer = null;
            window.speechSynthesis.speak(utterance);
        }, 800);
    },

    /**
     * Format ticket number for speech synthesis.
     * "PL-001" -> "P L zero zero one"
     */
    formatTicketForSpeech(ticketNumber) {
        if (!ticketNumber) return '';

        const parts = ticketNumber.split('-');
        const prefix = parts[0] || '';
        const number = parts[1] || '';

        // Spell out prefix letters
        let spoken = prefix.split('').join(' ');

        // Convert number to spoken form
        const digitWords = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine'];
        let numSpoken = '';
        for (let i = 0; i < number.length; i++) {
            const d = parseInt(number[i]);
            if (!isNaN(d)) {
                numSpoken += (numSpoken ? ' ' : '') + digitWords[d];
            }
        }

        return spoken + ' ' + numSpoken;
    },

    /**
     * Bind Socket.IO events.
     */
    bindSocketEvents() {
        // When a ticket is called or recalled - announce it
        SFISocket.on('announce_ticket', (data) => {
            this.announceTicket(data);
        });

        // When queue is updated - refresh display
        SFISocket.on('queue_updated', () => {
            this.loadDisplayData();
        });
    },

    /**
     * Announce a ticket (chime + speech + visual highlight).
     */
    announceTicket(data) {
        if (!data) return;

        // Dedup: if the SAME ticket was announced within the last 4 seconds,
        // ignore the duplicate so only ONE voice announcement plays.
        const key = (data.ticket_number || '') + '|' + (data.counter || data.counter_assigned || '');
        const now = Date.now();

        // Cross-tab lock: all open display tabs receive the same socket event.
        // Use a shared localStorage lock so only ONE tab speaks per announce,
        // even when the TV display and a test window are both open.
        try {
            const lockKey = 'sfi_announce_lock';
            const lock = JSON.parse(localStorage.getItem(lockKey) || 'null');
            if (lock && lock.key === key && (now - lock.at) < 4000) {
                console.log('SFI Display: announce locked by another tab, skipping', data.ticket_number);
                return;
            }
            localStorage.setItem(lockKey, JSON.stringify({ key: key, at: now }));
        } catch (e) { /* localStorage may be unavailable; fall back to in-tab dedup */ }

        if (key === this.lastAnnounceKey && (now - this.lastAnnounceAt) < 4000) {
            console.log('SFI Display: duplicate announce ignored for', data.ticket_number);
            return;
        }
        this.lastAnnounceKey = key;
        this.lastAnnounceAt = now;

        // Update the now-serving display
        this.updateNowServing(data);

        // Play chime
        this.playChime();

        // Speak announcement
        const counter = data.counter || data.counter_assigned || '-';
        this.speakAnnouncement(data.ticket_number, counter);

        // Visual highlight
        const el = document.getElementById('displayTicketNumber');
        if (el) {
            el.classList.add('highlight');
            setTimeout(() => el.classList.remove('highlight'), 2000);
        }
    },

    /**
     * Update the Now Serving section.
     */
    updateNowServing(data) {
        const container = document.getElementById('nowServingArea');
        if (!container || !data) return;

        const ticketNum = data.ticket_number || '---';
        const clientName = data.client_name || '';
        const loanType = data.loan_type || '';
        const counter = data.counter || data.counter_assigned || '-';

        document.getElementById('displayTicketNumber').textContent = ticketNum;
        document.getElementById('displayClientName').textContent = clientName;
        document.getElementById('displayLoanType').textContent = loanType;
        document.getElementById('displayCounter').textContent = counter !== '-' ? 'COUNTER ' + counter : '';
    },

    /**
     * Load display data from API.
     */
    async loadDisplayData() {
        try {
            // Fetch currently serving tickets
            const currentRes = await fetch(this.baseUrl + '/api/queue/current.php', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const currentData = await currentRes.json();

            if (currentData.success && currentData.data.tickets.length > 0) {
                // Show the most recently called serving ticket
                const ticket = currentData.data.tickets[0];
                document.getElementById('displayTicketNumber').textContent = ticket.ticket_number;
                document.getElementById('displayClientName').textContent = ticket.client_name;
                document.getElementById('displayLoanType').textContent = ticket.loan_type_name || '';
                document.getElementById('displayCounter').textContent = ticket.counter_assigned ? 'COUNTER ' + ticket.counter_assigned : '';
                document.getElementById('nowServingArea').classList.remove('display-empty');
            } else {
                document.getElementById('displayTicketNumber').textContent = '---';
                document.getElementById('displayClientName').textContent = 'Waiting for next client';
                document.getElementById('displayLoanType').textContent = '';
                document.getElementById('displayCounter').textContent = '';
                document.getElementById('nowServingArea').classList.add('display-empty');
            }

            // Fetch waiting tickets for "Next In Line"
            const waitingRes = await fetch(this.baseUrl + '/api/queue/waiting.php', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const waitingData = await waitingRes.json();

            if (waitingData.success) {
                this.updateNextInLine(waitingData.data.tickets.slice(0, 5));
            }

        } catch (e) {
            console.warn('Display data load error:', e);
        }
    },

    /**
     * Update the Next In Line section.
     */
    updateNextInLine(tickets) {
        const grid = document.getElementById('nextInLineGrid');
        if (!grid) return;

        let html = '';
        for (let i = 0; i < 5; i++) {
            if (tickets[i]) {
                html += '<div class="display-next-item">';
                html += '<div class="next-ticket">' + this.escapeHtml(tickets[i].ticket_number) + '</div>';
                html += '<div class="next-type">' + this.escapeHtml(tickets[i].loan_type_name || tickets[i].prefix) + '</div>';
                html += '</div>';
            } else {
                html += '<div class="display-next-item empty">';
                html += '<div class="next-ticket">---</div>';
                html += '<div class="next-type">Empty</div>';
                html += '</div>';
            }
        }
        grid.innerHTML = html;
    },

    /**
     * Escape HTML to prevent XSS.
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};
