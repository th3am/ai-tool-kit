/**
 * EduAI Mobile — Centralized API Client
 *
 * Usage: window.Api.get('/user'), window.Api.post('/login', {email, password})
 *
 * - Reads API base URL from <meta name="api-base-url"> injected by the layout.
 * - Reads Bearer token from Alpine $store.app.token (falls back to localStorage).
 * - Handles 401 → clears auth state → redirects to /login.
 * - All JSON responses returned as plain objects. Throws on non-2xx.
 */

const Api = (() => {
    function getBaseUrl() {
        const meta = document.querySelector('meta[name="api-base-url"]');
        return meta ? meta.content.replace(/\/$/, '') : '/api/v1';
    }

    function getToken() {
        // Try Alpine store first (reactive), then localStorage
        try {
            if (window.Alpine && Alpine.store('app')) {
                return Alpine.store('app').token;
            }
        } catch (e) { /* Alpine not ready yet */ }
        return localStorage.getItem('auth_token');
    }

    function absoluteUrl(pathOrUrl) {
        if (/^https?:\/\//i.test(pathOrUrl)) return pathOrUrl;
        return getBaseUrl() + (pathOrUrl.startsWith('/') ? pathOrUrl : '/' + pathOrUrl);
    }

    function handle401() {
        localStorage.removeItem('auth_token');
        try {
            if (window.Alpine && Alpine.store('app')) {
                Alpine.store('app').clearToken();
                Alpine.store('app').user = null;
            }
        } catch (e) { /* Alpine not ready */ }
        // Only redirect if not already on auth pages
        if (!window.location.pathname.startsWith('/login') &&
            !window.location.pathname.startsWith('/register') &&
            !window.location.pathname.startsWith('/otp')) {
            window.location.href = '/login';
        }
    }

    async function request(method, path, data = null, isFormData = false) {
        const url = absoluteUrl(path);
        const token = getToken();

        const headers = { 'Accept': 'application/json' };
        if (token) headers['Authorization'] = `Bearer ${token}`;
        if (!isFormData && data !== null) headers['Content-Type'] = 'application/json';

        const opts = { method, headers };
        if (data !== null) {
            opts.body = isFormData ? data : JSON.stringify(data);
        }

        let response;
        try {
            response = await fetch(url, opts);
        } catch (networkError) {
            throw { message: 'Network error — is the API server running?', status: 0 };
        }

        if (response.status === 401) {
            handle401();
            throw { message: 'Unauthenticated. Please login again.', status: 401 };
        }

        // Try to parse JSON body
        let body;
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            body = await response.json();
        } else {
            body = await response.text();
        }

        if (!response.ok) {
            // Laravel validation errors (422)
            if (response.status === 422 && body?.errors) {
                const firstError = Object.values(body.errors)[0]?.[0] || 'Validation failed';
                throw { message: firstError, status: 422, errors: body.errors };
            }
            throw { message: body?.message || `Request failed (${response.status})`, status: response.status };
        }

        return body;
    }

    async function createSession(title) {
        const response = await request('POST', '/sessions', { title });
        const rawId = response?.id
            ?? response?.data?.id
            ?? response?.session?.id
            ?? response?.data?.session?.id
            ?? response?.session_id;
        const id = rawId === null || rawId === undefined ? '' : String(rawId).trim();

        if (id === '') {
            throw { message: 'Could not create a valid chat session.', status: 422 };
        }

        return id;
    }

    function filenameFromDisposition(disposition) {
        if (!disposition) return null;

        const utfMatch = disposition.match(/filename\*=UTF-8''([^;]+)/i);
        if (utfMatch?.[1]) return decodeURIComponent(utfMatch[1].replace(/"/g, ''));

        const match = disposition.match(/filename="?([^"]+)"?/i);
        return match?.[1] || null;
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    async function nativeBridgeCall(method, params = {}) {
        const response = await fetch('/_native/api/call', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ method, params }),
        });

        if (!response.ok) throw new Error('Native bridge is unavailable.');

        const result = await response.json();
        if (result.status === 'error') throw new Error(result.message || 'Native bridge failed.');

        return result.data;
    }

    function withTokenQuery(url) {
        const token = getToken();
        if (!token) return url;

        const parsed = new URL(url, window.location.origin);
        if (!parsed.searchParams.has('token')) {
            parsed.searchParams.set('token', token);
        }
        return parsed.toString();
    }

    async function download(pathOrUrl, filename = 'download') {
        const downloadUrl = withTokenQuery(absoluteUrl(pathOrUrl));

        window.location.assign(downloadUrl);

        return { filename, opened: true };
    }

    async function downloadViaBlob(pathOrUrl, filename = 'download') {
        const token = getToken();
        const headers = { 'Accept': 'application/octet-stream' };
        if (token) headers['Authorization'] = `Bearer ${token}`;

        const response = await fetch(absoluteUrl(pathOrUrl), { method: 'GET', headers });
        if (!response.ok) throw { message: `Download failed (${response.status})`, status: response.status };

        const blob = await response.blob();
        const objectUrl = URL.createObjectURL(blob);
        const finalName = filenameFromDisposition(response.headers.get('content-disposition')) || filename;
        const anchor = document.createElement('a');
        anchor.href = objectUrl;
        anchor.download = finalName;
        anchor.style.display = 'none';
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();

        setTimeout(() => URL.revokeObjectURL(objectUrl), 60000);

        return { filename: finalName, size: blob.size };
    }

    return {
        get:      (path)             => request('GET',    path),
        post:     (path, data)       => request('POST',   path, data),
        put:      (path, data)       => request('PUT',    path, data),
        delete:   (path)             => request('DELETE', path),
        postForm: (path, formData)   => request('POST',   path, formData, true),
        absoluteUrl,
        createSession,
        download,
        downloadViaBlob,
    };
})();

window.Api = Api;
