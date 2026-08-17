/**
 * SFI Queuing System - Socket.IO Client Wrapper
 * Manages real-time connection, events, and auto-reconnection.
 */

const SFISocket = {
    socket: null,
    connected: false,
    listeners: {},
    reconnectTimer: null,

    /**
     * Connect to the Socket.IO server.
     * @param {string} serverUrl - Socket.IO server URL
     * @param {object} options - Connection options
     */
    connect(serverUrl, options = {}) {
        if (typeof io === 'undefined') {
            console.warn('Socket.IO library not loaded. Real-time features disabled.');
            this.updateStatus(false);
            return;
        }

        try {
            this.socket = io(serverUrl, {
                reconnection: true,
                reconnectionDelay: 2000,
                reconnectionDelayMax: 10000,
                reconnectionAttempts: Infinity,
                timeout: 10000,
                ...options
            });

            this.socket.on('connect', () => {
                console.log('Socket connected:', this.socket.id);
                this.connected = true;
                this.updateStatus(true);

                // Trigger sync on reconnect
                if (this.listeners['reconnected']) {
                    this.listeners['reconnected'].forEach(cb => cb());
                }
            });

            this.socket.on('disconnect', (reason) => {
                console.log('Socket disconnected:', reason);
                this.connected = false;
                this.updateStatus(false);
            });

            this.socket.on('connect_error', (err) => {
                console.warn('Socket connection error:', err.message);
                this.connected = false;
                this.updateStatus(false);
            });

            // Listen for standard events
            this._bindStandardEvents();

        } catch (err) {
            console.error('Socket init failed:', err);
            this.updateStatus(false);
        }
    },

    /**
     * Bind standard queue events.
     */
    _bindStandardEvents() {
        const events = [
            'queue_updated',
            'announce_ticket',
            'new_ticket',
            'ticket_called',
            'ticket_recalled',
            'ticket_completed',
            'ticket_no_show',
            'ticket_transferred',
            'queue_reset'
        ];

        events.forEach(event => {
            this.socket.on(event, (data) => {
                if (this.listeners[event]) {
                    this.listeners[event].forEach(cb => cb(data));
                }
            });
        });
    },

    /**
     * Register an event listener.
     * @param {string} event - Event name
     * @param {function} callback - Callback function
     */
    on(event, callback) {
        if (!this.listeners[event]) {
            this.listeners[event] = [];
        }
        this.listeners[event].push(callback);
    },

    /**
     * Remove all listeners for an event.
     */
    off(event) {
        delete this.listeners[event];
    },

    /**
     * Emit an event to the server.
     */
    emit(event, data = {}) {
        if (this.socket && this.connected) {
            this.socket.emit(event, data);
        } else {
            console.warn('Socket not connected. Cannot emit:', event);
        }
    },

    /**
     * Update the connection status indicator in the DOM.
     */
    updateStatus(isConnected) {
        const indicators = document.querySelectorAll('.connection-status');
        indicators.forEach(el => {
            if (isConnected) {
                el.className = 'connection-status connected';
                el.innerHTML = '<span class="connection-dot"></span> CONNECTED';
            } else {
                el.className = 'connection-status disconnected';
                el.innerHTML = '<span class="connection-dot"></span> OFFLINE';
            }
        });
    },

    /**
     * Disconnect the socket.
     */
    disconnect() {
        if (this.socket) {
            this.socket.disconnect();
            this.socket = null;
            this.connected = false;
        }
    }
};
