/**
 * SFI QueUING SYSTEM - Socket.IO Real-Time Server
 *
 * Handles real-time communication between:
 * - Client Kiosk (new ticket creation)
 * - Staff Dashboard (call/recall/complete/no-show/transfer)
 * - TV Display (announcements + queue updates)
 *
 * PHP backend communicates via HTTP POST to /emit endpoint.
 */

require('dotenv').config();

const express = require('express');
const http = require('http');
const cors = require('cors');
const { Server } = require('socket.io');

const app = express();
const server = http.createServer(app);

const PORT = process.env.PORT || 4000;
const CORS_ORIGIN = process.env.CORS_ORIGIN || '*';

// Optional shared secret for the /emit HTTP endpoint.
// If EMIT_TOKEN is set in .env, PHP must send it as "Authorization: Bearer <token>".
// If empty, the check is disabled (backward compatible with existing setups).
const EMIT_TOKEN = process.env.EMIT_TOKEN || '';

// ---- Announce dedup: only broadcast one announce_ticket per ticket per 4s ----
// The PHP backend emits via HTTP /emit AND clients may also emit via socket,
// which can cause duplicate announcements. Track the last announced ticket
// (number + counter) so duplicates are dropped server-side.
const lastAnnounced = { key: '', at: 0 };
const ANNOUNCE_DEDUP_MS = 4000;

function shouldAnnounce(key) {
    const now = Date.now();
    if (lastAnnounced.key === key && (now - lastAnnounced.at) < ANNOUNCE_DEDUP_MS) {
        console.log('[ANNOUNCE] deduped (already announced recently):', key);
        return false;
    }
    lastAnnounced.key = key;
    lastAnnounced.at = now;
    return true;
}

function buildAnnounceKey(data) {
    const t = (data && data.ticket_number) || '';
    const ctr = (data && (data.counter || data.counter_assigned)) || '';
    return t + '|' + ctr;
}

// Middleware
app.use(cors({ origin: CORS_ORIGIN }));
app.use(express.json());

// Health check
app.get('/', (req, res) => {
    res.json({
        status: 'online',
        service: 'SFI Queuing System - Real-Time Server',
        version: '1.0.0',
        timestamp: new Date().toISOString()
    });
});

// HTTP endpoint for PHP to emit events
// PHP calls: POST http://localhost:4000/emit with { event: "new_ticket", data: {...} }
app.post('/emit', (req, res) => {
    try {
        // Optional bearer-token check (backward compatible: empty token = no check)
        if (EMIT_TOKEN !== '') {
            const auth = req.headers.authorization || '';
            if (auth !== 'Bearer ' + EMIT_TOKEN) {
                return res.status(401).json({ success: false, message: 'Unauthorized' });
            }
        }

        const { event, data } = req.body;

        if (!event) {
            return res.status(400).json({ success: false, message: 'Event name required' });
        }

        // Broadcast the event to all connected clients
        io.emit(event, data || {});

        // Also emit queue_updated for most events (kiosk/dashboard/TV listen for this)
        const queueEvents = [
            'new_ticket', 'ticket_called', 'ticket_completed',
            'ticket_no_show', 'ticket_transferred', 'queue_reset'
        ];
        if (queueEvents.includes(event)) {
            io.emit('queue_updated', { trigger: event, data: data });
        }

        // For call/recall, also emit announce_ticket (TV display listens for this)
        const announceEvents = ['ticket_called', 'ticket_recalled'];
        if (announceEvents.includes(event) && shouldAnnounce(buildAnnounceKey(data))) {
            io.emit('announce_ticket', data || {});
        }

        console.log(`[EMIT] ${event}`, data ? JSON.stringify(data).substring(0, 100) : '');
        res.json({ success: true, event: event });

    } catch (err) {
        console.error('[EMIT ERROR]', err.message);
        res.status(500).json({ success: false, message: 'Emit failed' });
    }
});

// Socket.IO Server
const io = new Server(server, {
    cors: {
        origin: CORS_ORIGIN,
        methods: ['GET', 'POST']
    },
    pingTimeout: 60000,
    pingInterval: 25000
});

// Track connected clients
let clientCount = 0;

io.on('connection', (socket) => {
    clientCount++;
    console.log(`[CONNECT] ${socket.id} | Total clients: ${clientCount}`);

    // Client can emit events directly (optional, PHP can also do it via HTTP)
    socket.on('new_ticket', (data) => {
        console.log('[EVENT] new_ticket from', socket.id);
        io.emit('new_ticket', data);
        io.emit('queue_updated', { trigger: 'new_ticket', data: data });
    });

    socket.on('ticket_called', (data) => {
        console.log('[EVENT] ticket_called from', socket.id);
        io.emit('ticket_called', data);
        if (shouldAnnounce(buildAnnounceKey(data))) {
            io.emit('announce_ticket', data);
        }
        io.emit('queue_updated', { trigger: 'ticket_called', data: data });
    });

    socket.on('ticket_recalled', (data) => {
        console.log('[EVENT] ticket_recalled from', socket.id);
        io.emit('ticket_recalled', data);
        if (shouldAnnounce(buildAnnounceKey(data))) {
            io.emit('announce_ticket', data);
        }
    });

    socket.on('ticket_completed', (data) => {
        console.log('[EVENT] ticket_completed from', socket.id);
        io.emit('ticket_completed', data);
        io.emit('queue_updated', { trigger: 'ticket_completed', data: data });
    });

    socket.on('ticket_no_show', (data) => {
        console.log('[EVENT] ticket_no_show from', socket.id);
        io.emit('ticket_no_show', data);
        io.emit('queue_updated', { trigger: 'ticket_no_show', data: data });
    });

    socket.on('ticket_transferred', (data) => {
        console.log('[EVENT] ticket_transferred from', socket.id);
        io.emit('ticket_transferred', data);
        io.emit('queue_updated', { trigger: 'ticket_transferred', data: data });
    });

    socket.on('queue_reset', (data) => {
        console.log('[EVENT] queue_reset from', socket.id);
        io.emit('queue_reset', data);
        io.emit('queue_updated', { trigger: 'queue_reset', data: data });
    });

    socket.on('disconnect', (reason) => {
        clientCount--;
        console.log(`[DISCONNECT] ${socket.id} (${reason}) | Total clients: ${clientCount}`);
    });

    socket.on('error', (err) => {
        console.error(`[SOCKET ERROR] ${socket.id}:`, err.message);
    });
});

// Graceful shutdown
process.on('SIGTERM', () => {
    console.log('[SHUTDOWN] SIGTERM received. Closing server...');
    server.close(() => {
        console.log('[SHUTDOWN] Server closed.');
        process.exit(0);
    });
});

process.on('SIGINT', () => {
    console.log('[SHUTDOWN] SIGINT received. Closing server...');
    server.close(() => {
        console.log('[SHUTDOWN] Server closed.');
        process.exit(0);
    });
});

// Start server
server.listen(PORT, () => {
    console.log('='.repeat(50));
    console.log('  SFI Queuing System - Real-Time Server');
    console.log('  Running on port ' + PORT);
    console.log('  CORS Origin: ' + CORS_ORIGIN);
    console.log('  Emit auth: ' + (EMIT_TOKEN !== '' ? 'token required' : 'DISABLED'));
    console.log('  Environment: ' + (process.env.NODE_ENV || 'development'));
    console.log('='.repeat(50));
});
