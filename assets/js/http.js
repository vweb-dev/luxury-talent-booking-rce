/**
 * HTTP Client for RCE Application
 * Provides modern fetch-based HTTP utilities with error handling
 */

class HttpClient {
    constructor() {
        this.baseURL = '';
        this.defaultHeaders = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
    }

    /**
     * Make an HTTP request
     * @param {string} url - Request URL
     * @param {Object} options - Request options
     * @returns {Promise} Response data
     */
    async request(url, options = {}) {
        try {
            const config = {
                method: 'GET',
                headers: { ...this.defaultHeaders },
                ...options
            };

            // Handle FormData - don't set Content-Type header
            if (config.body instanceof FormData) {
                delete config.headers['Content-Type'];
            }

            // Convert body to JSON if it's an object and not FormData
            if (config.body && typeof config.body === 'object' && !(config.body instanceof FormData)) {
                config.body = JSON.stringify(config.body);
            }

            const response = await fetch(this.baseURL + url, config);
            
            // Handle different response types
            const contentType = response.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                data = await response.text();
            }

            if (!response.ok) {
                throw new HttpError(
                    data.message || `HTTP ${response.status}: ${response.statusText}`,
                    response.status,
                    data
                );
            }

            return data;
        } catch (error) {
            if (error instanceof HttpError) {
                throw error;
            }
            
            // Network or other errors
            throw new HttpError(
                error.message || 'Network request failed',
                0,
                { originalError: error }
            );
        }
    }

    /**
     * GET request
     * @param {string} url - Request URL
     * @param {Object} options - Request options
     * @returns {Promise} Response data
     */
    async get(url, options = {}) {
        return this.request(url, { ...options, method: 'GET' });
    }

    /**
     * POST request
     * @param {string} url - Request URL
     * @param {*} data - Request body data
     * @param {Object} options - Request options
     * @returns {Promise} Response data
     */
    async post(url, data = null, options = {}) {
        return this.request(url, {
            ...options,
            method: 'POST',
            body: data
        });
    }

    /**
     * PUT request
     * @param {string} url - Request URL
     * @param {*} data - Request body data
     * @param {Object} options - Request options
     * @returns {Promise} Response data
     */
    async put(url, data = null, options = {}) {
        return this.request(url, {
            ...options,
            method: 'PUT',
            body: data
        });
    }

    /**
     * DELETE request
     * @param {string} url - Request URL
     * @param {Object} options - Request options
     * @returns {Promise} Response data
     */
    async delete(url, options = {}) {
        return this.request(url, { ...options, method: 'DELETE' });
    }

    /**
     * Upload file with progress tracking
     * @param {string} url - Upload URL
     * @param {FormData} formData - Form data with file
     * @param {Function} onProgress - Progress callback
     * @returns {Promise} Response data
     */
    async upload(url, formData, onProgress = null) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            // Track upload progress
            if (onProgress && xhr.upload) {
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        onProgress(percentComplete);
                    }
                });
            }

            xhr.addEventListener('load', () => {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (xhr.status >= 200 && xhr.status < 300) {
                        resolve(response);
                    } else {
                        reject(new HttpError(
                            response.message || `HTTP ${xhr.status}`,
                            xhr.status,
                            response
                        ));
                    }
                } catch (error) {
                    reject(new HttpError('Invalid JSON response', xhr.status));
                }
            });

            xhr.addEventListener('error', () => {
                reject(new HttpError('Upload failed', 0));
            });

            xhr.open('POST', this.baseURL + url);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        });
    }
}

/**
 * Custom HTTP Error class
 */
class HttpError extends Error {
    constructor(message, status = 0, data = null) {
        super(message);
        this.name = 'HttpError';
        this.status = status;
        this.data = data;
    }
}

// Create global HTTP client instance
const http = new HttpClient();

// Legacy function for backward compatibility
async function httpRequest(url, options = {}) {
    return http.request(url, options);
}

// Utility functions for common operations
const api = {
    // Authentication
    async login(credentials, portal = 'default') {
        const formData = new FormData();
        Object.keys(credentials).forEach(key => {
            formData.append(key, credentials[key]);
        });
        if (portal !== 'default') {
            formData.append('portal', portal);
        }
        return http.post('/api/auth/login.php', formData);
    },

    async logout() {
        return http.post('/api/auth/logout.php');
    },

    // Public feed
    async getPublicFeed(page = 1, limit = 10) {
        return http.get(`/api/public/feed.php?page=${page}&limit=${limit}`);
    },

    // Client operations
    async getClientFeed(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        return http.get(`/api/client/feed.php?${params}`);
    },

    async addToShortlist(talentId) {
        return http.post('/api/client/shortlist.php', { talent_id: talentId });
    },

    async removeFromShortlist(talentId) {
        return http.delete(`/api/client/shortlist.php?talent_id=${talentId}`);
    },

    // Media operations
    async uploadMedia(file, type = 'photo', onProgress = null) {
        const formData = new FormData();
        formData.append('media', file);
        formData.append('type', type);
        return http.upload('/api/talent/media.php', formData, onProgress);
    },

    // Booking operations
    async createBooking(bookingData) {
        return http.post('/api/client/booking.php', bookingData);
    },

    async getBookings(status = 'all') {
        return http.get(`/api/client/bookings.php?status=${status}`);
    }
};

// Error handling utilities
const errorHandler = {
    /**
     * Display error message to user
     * @param {Error} error - Error object
     * @param {string} containerId - Container element ID
     */
    show(error, containerId = 'errorContainer') {
        const container = document.getElementById(containerId);
        if (!container) return;

        let message = 'An unexpected error occurred';
        
        if (error instanceof HttpError) {
            message = error.message;
        } else if (error.message) {
            message = error.message;
        }

        container.innerHTML = `
            <div class="error-message">
                ${this.escapeHtml(message)}
            </div>
        `;
        container.style.display = 'block';

        // Auto-hide after 5 seconds
        setTimeout(() => {
            this.hide(containerId);
        }, 5000);
    },

    /**
     * Hide error message
     * @param {string} containerId - Container element ID
     */
    hide(containerId = 'errorContainer') {
        const container = document.getElementById(containerId);
        if (container) {
            container.style.display = 'none';
            container.innerHTML = '';
        }
    },

    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Loading state utilities
const loading = {
    /**
     * Show loading state on element
     * @param {string|Element} element - Element or selector
     */
    show(element) {
        const el = typeof element === 'string' ? document.querySelector(element) : element;
        if (!el) return;

        el.disabled = true;
        el.classList.add('loading');
        
        if (el.tagName === 'BUTTON') {
            el.dataset.originalText = el.textContent;
            el.innerHTML = '<span class="loading"></span> Loading...';
        }
    },

    /**
     * Hide loading state on element
     * @param {string|Element} element - Element or selector
     */
    hide(element) {
        const el = typeof element === 'string' ? document.querySelector(element) : element;
        if (!el) return;

        el.disabled = false;
        el.classList.remove('loading');
        
        if (el.tagName === 'BUTTON' && el.dataset.originalText) {
            el.textContent = el.dataset.originalText;
            delete el.dataset.originalText;
        }
    }
};

// Export for module systems (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { HttpClient, HttpError, http, api, errorHandler, loading };
}
