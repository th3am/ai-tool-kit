/**
 * EduAI Mobile — Alpine.js App Bootstrap
 *
 * - Registers global Alpine store (auth state, loading flag)
 * - Intercepts internal <a> navigation to show progress bar
 * - Intercepts form submits to show loading state on buttons
 * - Runs auth-guard redirect on page load
 */

document.addEventListener('alpine:init', () => {

    // ─── Global Store ────────────────────────────────────────────────────────
    Alpine.store('app', {
        loading: false,
        user: null,
        token: localStorage.getItem('auth_token') || null,

        setToken(t) {
            this.token = t;
            localStorage.setItem('auth_token', t);
        },

        clearToken() {
            this.token = null;
            this.user = null;
            localStorage.removeItem('auth_token');
        },

        isLoggedIn() {
            return !!this.token;
        },

        setUser(u) {
            this.user = u;
        },

        async logout() {
            try { await window.Api.post('/logout'); } catch (e) {}
            this.clearToken();
            window.location.href = '/login';
        },
    });

});

// ─── Progress Bar ─────────────────────────────────────────────────────────────

const ProgressBar = {
    el: null,
    timer: null,

    init() { this.el = document.getElementById('progress-bar'); },

    start() {
        if (!this.el) return;
        clearTimeout(this.timer);
        this.el.classList.remove('done');
        this.el.classList.add('loading');
    },

    done() {
        if (!this.el) return;
        this.el.classList.remove('loading');
        this.el.classList.add('done');
        this.timer = setTimeout(() => {
            if (this.el) this.el.classList.remove('done');
        }, 600);
    },
};

// ─── Navigation Intercept ──────────────────────────────────────────────────────

function isInternalLink(href) {
    if (!href) return false;
    if (href.startsWith('http') && !href.startsWith(window.location.origin)) return false;
    if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:')) return false;
    return true;
}

document.addEventListener('click', (e) => {
    const link = e.target.closest('a[href]');
    if (!link || link.target === '_blank') return;
    const href = link.getAttribute('href');
    if (!isInternalLink(href)) return;
    e.preventDefault();
    ProgressBar.start();
    setTimeout(() => { window.location.href = href; }, 50);
});

// ─── Auth Guard ────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    ProgressBar.init();
    ProgressBar.done();

    const path = window.location.pathname;
    const publicPaths = ['/login', '/register', '/otp'];
    const isPublic = publicPaths.some(p => path.startsWith(p));
    const token = localStorage.getItem('auth_token');

    if (!token && !isPublic) {
        ProgressBar.start();
        window.location.href = '/login';
        return;
    }

    if (token && isPublic) {
        ProgressBar.start();
        window.location.href = '/dashboard';
        return;
    }
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

window.toolLabel = function(type) {
    const map = {
        'quiz': 'Quiz Generator', 'presentation': 'Presentation',
        'mindmap': 'Mind Map', 'audio': 'Audio Narration',
        'video-animation': 'Animation', 'video-explainer': 'Video Explainer',
        'lecture': 'Video Lecture',
    };
    return map[type] || type;
};

window.statusBadge = function(status) {
    const map = {
        'queued': 'badge-gray', 'running': 'badge-blue',
        'succeeded': 'badge-green', 'failed': 'badge-red', 'cancelled': 'badge-gray',
    };
    return map[status] || 'badge-gray';
};

window.timeAgo = function(dateStr) {
    if (!dateStr) return '';
    const diff = (Date.now() - new Date(dateStr)) / 1000;
    if (diff < 60)    return 'Just now';
    if (diff < 3600)  return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
};

window.navigateTo = function(path) {
    ProgressBar.start();
    setTimeout(() => { window.location.href = path; }, 50);
};
