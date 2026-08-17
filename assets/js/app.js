/**
 * SFI Queuing System - Shared Application JavaScript
 * Provides: API helper, toast notifications, modals, connection status, utilities.
 */

const SFI = {
    /** Base URL for API calls (set by each page) */
    baseUrl: '',
    socketServer: 'http://localhost:4000',

    /**
     * Initialize the SFI app.
     */
    init(config) {
        this.baseUrl = config.baseUrl || '';
        this.socketServer = config.socketServer || 'http://localhost:4000';
    },

    /**
     * Make an API request.
     * @param {string} endpoint - API endpoint path (e.g., '/api/queue/waiting')
     * @param {object} options - fetch options
     * @returns {Promise<object>} JSON response
     */
    async api(endpoint, options = {}) {
        const url = this.baseUrl + endpoint;
        const defaults = {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        };

        if (options.body && !(options.body instanceof FormData)) {
            defaults.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }

        const config = { ...defaults, ...options };
        if (options.headers) {
            config.headers = { ...defaults.headers, ...options.headers };
        }

        try {
            const response = await fetch(url, config);

            if (response.status === 401) {
                // Session expired - redirect to login
                this.toast('Session expired. Redirecting to login...', 'warning');
                setTimeout(() => {
                    window.location.href = this.baseUrl + '/login/';
                }, 1500);
                throw new Error('Session expired');
            }

            const data = await response.json();
            return data;
        } catch (err) {
            if (err.message !== 'Session expired') {
                console.error('API Error:', err);
                this.toast('Network error. Please check your connection.', 'error');
            }
            throw err;
        }
    },

    /**
     * POST request helper.
     */
    async post(endpoint, data = {}) {
        const formData = new FormData();
        if (typeof data === 'object') {
            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }
        }
        return this.api(endpoint, { method: 'POST', body: formData });
    },

    /**
     * GET request helper.
     */
    async get(endpoint) {
        return this.api(endpoint, { method: 'GET' });
    },

    // ======================
    // Toast Notifications
    // ======================
    toastContainer: null,

    /**
     * Show a toast notification.
     */
    toast(message, type = 'info', duration = 4000) {
        if (!this.toastContainer) {
            this.toastContainer = document.createElement('div');
            this.toastContainer.className = 'toast-container';
            document.body.appendChild(this.toastContainer);
        }

        const icons = {
            success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0E9F6E" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
            error: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#E02424" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
            warning: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
        };

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || icons.info}</span>
            <span class="toast-message">${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;

        this.toastContainer.appendChild(toast);

        // Auto remove
        setTimeout(() => {
            toast.style.animation = 'toastSlideOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, duration);

        return toast;
    },

    // ======================
    // Modal Manager
    // ======================

    /**
     * Show a modal by ID.
     */
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },

    /**
     * Hide a modal by ID.
     */
    hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    /**
     * Show a confirmation dialog.
     * @returns {Promise<boolean>}
     */
    confirm(title, message) {
        return new Promise((resolve) => {
            // Remove existing confirm modal
            const existing = document.getElementById('sfi-confirm-modal');
            if (existing) existing.remove();

            const modal = document.createElement('div');
            modal.id = 'sfi-confirm-modal';
            modal.className = 'modal-overlay active';
            modal.innerHTML = `
                <div class="modal modal-sm">
                    <div class="modal-header">
                        <h3>${title}</h3>
                        <button class="modal-close" id="confirmCancel">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p style="color: var(--gray-text); font-size: 0.9rem;">${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" id="confirmNo">Cancel</button>
                        <button class="btn btn-danger" id="confirmYes">Confirm</button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden';

            const cleanup = () => {
                modal.remove();
                document.body.style.overflow = '';
            };

            document.getElementById('confirmYes').onclick = () => { cleanup(); resolve(true); };
            document.getElementById('confirmNo').onclick = () => { cleanup(); resolve(false); };
            document.getElementById('confirmCancel').onclick = () => { cleanup(); resolve(false); };
            modal.onclick = (e) => { if (e.target === modal) { cleanup(); resolve(false); } };
        });
    },

    // ======================
    // Sidebar Toggle
    // ======================
    initSidebar() {
        const hamburger = document.querySelector('.hamburger');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');

        if (hamburger && sidebar) {
            hamburger.addEventListener('click', () => {
                sidebar.classList.toggle('open');
                if (overlay) overlay.classList.toggle('active');
            });
        }
        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            });
        }
    },

    // ======================
    // Logout
    // ======================
    initLogout() {
        const logoutBtns = document.querySelectorAll('[data-logout]');
        logoutBtns.forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const confirmed = await this.confirm('Logout', 'Are you sure you want to logout?');
                if (confirmed) {
                    try {
                        await this.post('/api/auth/logout.php');
                    } catch (e) { /* ignore */ }
                    window.location.href = this.baseUrl + '/login/';
                }
            });
        });
    },

    // ======================
    // Utilities
    // ======================

    /**
     * Format time from a date string.
     */
    formatTime(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    },

    /**
     * Format date.
     */
    formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    },

    /**
     * Set button loading state.
     */
    setButtonLoading(btn, loading, originalText) {
        if (loading) {
            btn.disabled = true;
            btn.classList.add('btn-loading');
        } else {
            btn.disabled = false;
            btn.classList.remove('btn-loading');
            if (originalText) btn.textContent = originalText;
        }
    },

    /**
     * Escape HTML.
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    /**
     * Get initials from a full name.
     */
    getInitials(name) {
        if (!name) return '?';
        return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
    },

    /**
     * Initialize the dark/light theme toggle.
     * The saved theme is applied early by an inline script in layout.php;
     * this wires up the toggle button and persists the choice.
     */
    initTheme() {
        const btn = document.getElementById('themeToggle');
        if (!btn) return;

        const apply = (theme) => {
            document.documentElement.setAttribute('data-theme', theme);
            try { localStorage.setItem('sfi_theme', theme); } catch (e) {}
        };

        btn.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            apply(current === 'dark' ? 'light' : 'dark');
        });
    },

    /**
     * Initialize the topbar user menu dropdown.
     */
    initUserMenu() {
        const wrap = document.getElementById('userMenuWrap');
        const btn = document.getElementById('userMenuBtn');
        if (!wrap || !btn) return;

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = wrap.classList.toggle('open');
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!wrap.contains(e.target)) {
                wrap.classList.remove('open');
                btn.setAttribute('aria-expanded', 'false');
            }
        });

        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                wrap.classList.remove('open');
                btn.setAttribute('aria-expanded', 'false');
            }
        });
    }
};

// Auto-init sidebar, logout, theme and user menu on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    SFI.initSidebar();
    SFI.initLogout();
    SFI.initTheme();
    SFI.initUserMenu();
});
