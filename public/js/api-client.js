/**
 * CERFlow ApiClient Helper
 * Provides standard JSON fetch with CSRF protection, error handling, and JSON parsing.
 */
const ApiClient = {
    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    },

    async request(url, options = {}) {
        const headers = {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': this.getCsrfToken(),
            ...(options.headers || {})
        };

        if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }

        const response = await fetch(url, { ...options, headers });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const errorMsg = data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : `Request failed with status ${response.status}`);
            const error = new Error(errorMsg);
            error.status = response.status;
            error.data = data;
            throw error;
        }

        return data;
    },

    get(url, headers = {}) {
        return this.request(url, { method: 'GET', headers });
    },

    post(url, body, headers = {}) {
        return this.request(url, { method: 'POST', body, headers });
    },

    put(url, body, headers = {}) {
        return this.request(url, { method: 'PUT', body, headers });
    },

    delete(url, headers = {}) {
        return this.request(url, { method: 'DELETE', headers });
    }
};

window.ApiClient = ApiClient;
