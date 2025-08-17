/**
 * UI Controller for RCE Application
 * Handles client-side interactions and PWA functionality
 */

class UIController {
    constructor() {
        this.currentReel = 0;
        this.reels = [];
        this.shortlist = [];
        this.isMoreMenuOpen = false;
        
        this.init();
    }

    /**
     * Initialize UI controller
     */
    init() {
        this.registerServiceWorker();
        this.setupEventListeners();
        this.initializeComponents();
    }

    /**
     * Register service worker for PWA functionality
     */
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/service-worker.js');
                console.log('Service Worker registered successfully:', registration);
                
                // Handle updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            this.showUpdateNotification();
                        }
                    });
                });
            } catch (error) {
                console.error('Service Worker registration failed:', error);
            }
        }
    }

    /**
     * Setup global event listeners
     */
    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initializePage();
        });

        // Handle form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('ajax-form')) {
                e.preventDefault();
                this.handleFormSubmission(e.target);
            }
        });

        // Handle keyboard navigation
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardNavigation(e);
        });

        // Handle clicks outside dropdowns
        document.addEventListener('click', (e) => {
            this.handleOutsideClicks(e);
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            this.handleResize();
        });
    }

    /**
     * Initialize page-specific components
     */
    initializePage() {
        const path = window.location.pathname;
        
        switch (true) {
            case path === '/' || path === '/index.php':
                this.initHomePage();
                break;
            case path.includes('/explore'):
                this.initExplorePage();
                break;
            case path.includes('/client/feed'):
                this.initClientFeed();
                break;
            case path.includes('/login'):
                this.initLoginPage();
                break;
            default:
                this.initGenericPage();
        }
    }

    /**
     * Initialize home page
     */
    initHomePage() {
        console.log('Initializing home page');
        
        // Add smooth scrolling to anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    }

    /**
     * Initialize explore page
     */
    async initExplorePage() {
        console.log('Initializing explore page');
        
        try {
            const feedData = await api.getPublicFeed();
            this.reels = feedData.reels || [];
            this.renderReels();
            this.setupReelControls();
        } catch (error) {
            console.error('Failed to load explore feed:', error);
            this.showError('Failed to load content. Please try again.');
        }
    }

    /**
     * Initialize client feed
     */
    async initClientFeed() {
        console.log('Initializing client feed');
        
        try {
            const feedData = await api.getClientFeed();
            this.reels = feedData.reels || [];
            this.shortlist = feedData.shortlist || [];
            
            this.renderReels();
            this.renderShortlist();
            this.setupReelControls();
            this.setupMoreMenu();
        } catch (error) {
            console.error('Failed to load client feed:', error);
            this.showError('Failed to load feed. Please try again.');
        }
    }

    /**
     * Initialize login page
     */
    initLoginPage() {
        console.log('Initializing login page');
        
        const loginForm = document.getElementById('loginForm') || document.getElementById('saLoginForm');
        if (loginForm) {
            this.setupLoginForm(loginForm);
        }
    }

    /**
     * Initialize generic page
     */
    initGenericPage() {
        console.log('Initializing generic page');
        this.initializeComponents();
    }

    /**
     * Initialize common components
     */
    initializeComponents() {
        // Initialize tooltips
        this.initTooltips();
        
        // Initialize modals
        this.initModals();
        
        // Initialize dropdowns
        this.initDropdowns();
    }

    /**
     * Setup login form
     */
    setupLoginForm(form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const errorContainer = form.querySelector('.error-message') || 
                                 document.getElementById('loginError') || 
                                 document.getElementById('saLoginError');
            
            try {
                loading.show(submitBtn);
                if (errorContainer) errorContainer.style.display = 'none';
                
                const formData = new FormData(form);
                const credentials = Object.fromEntries(formData.entries());
                
                // Determine portal type
                const portal = form.id === 'saLoginForm' ? 'saportal' : 'default';
                
                const response = await api.login(credentials, portal);
                
                if (response.success) {
                    // Redirect based on role
                    window.location.href = response.redirect_url || this.getDefaultRedirect(response.role);
                } else {
                    throw new Error(response.message || 'Login failed');
                }
            } catch (error) {
                console.error('Login error:', error);
                if (errorContainer) {
                    errorContainer.textContent = error.message || 'Login failed. Please try again.';
                    errorContainer.style.display = 'block';
                }
            } finally {
                loading.hide(submitBtn);
            }
        });
    }

    /**
     * Get default redirect URL based on role
     */
    getDefaultRedirect(role) {
        const redirects = {
            'super_admin': '/saportal/',
            'tenant_admin': '/admin/',
            'talent': '/talent/',
            'client': '/client/feed'
        };
        return redirects[role] || '/client/feed';
    }

    /**
     * Render reels
     */
    renderReels() {
        const reelsContainer = document.querySelector('.reels-container');
        if (!reelsContainer || !this.reels.length) return;

        const currentReelData = this.reels[this.currentReel];
        if (!currentReelData) return;

        reelsContainer.innerHTML = `
            <div class="reel-wrapper">
                ${this.renderReelMedia(currentReelData)}
                <div class="reel-overlay">
                    <div class="reel-title">${this.escapeHtml(currentReelData.title || 'Untitled')}</div>
                    <div class="reel-description">${this.escapeHtml(currentReelData.description || '')}</div>
                </div>
                <div class="reel-controls">
                    <button class="reel-control" id="prevReel" ${this.currentReel === 0 ? 'disabled' : ''}>‹</button>
                    <button class="reel-control" id="nextReel" ${this.currentReel >= this.reels.length - 1 ? 'disabled' : ''}>›</button>
                </div>
                <div class="more-menu" id="moreMenu">
                    More
                    <div class="more-dropdown" id="moreDropdown">
                        <a href="#" onclick="ui.shareReel(); return false;">Share</a>
                        <a href="#" onclick="ui.addToShortlist(${currentReelData.talent_id}); return false;">Add to Shortlist</a>
                        <a href="#" onclick="ui.viewProfile(${currentReelData.talent_id}); return false;">View Profile</a>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Render reel media (image or video)
     */
    renderReelMedia(reelData) {
        if (reelData.type === 'video') {
            return `
                <video class="reel-media" autoplay muted loop playsinline>
                    <source src="${this.escapeHtml(reelData.url)}" type="video/mp4">
                    Your browser does not support video playback.
                </video>
            `;
        } else {
            return `
                <img class="reel-media" src="${this.escapeHtml(reelData.url)}" alt="${this.escapeHtml(reelData.title || 'Reel image')}">
            `;
        }
    }

    /**
     * Setup reel controls
     */
    setupReelControls() {
        document.addEventListener('click', (e) => {
            if (e.target.id === 'prevReel') {
                this.previousReel();
            } else if (e.target.id === 'nextReel') {
                this.nextReel();
            } else if (e.target.id === 'moreMenu') {
                this.toggleMoreMenu();
            }
        });
    }

    /**
     * Setup more menu
     */
    setupMoreMenu() {
        // Already handled in setupReelControls
    }

    /**
     * Navigate to previous reel
     */
    previousReel() {
        if (this.currentReel > 0) {
            this.currentReel--;
            this.renderReels();
        }
    }

    /**
     * Navigate to next reel
     */
    nextReel() {
        if (this.currentReel < this.reels.length - 1) {
            this.currentReel++;
            this.renderReels();
        }
    }

    /**
     * Toggle more menu
     */
    toggleMoreMenu() {
        const dropdown = document.getElementById('moreDropdown');
        if (dropdown) {
            dropdown.classList.toggle('show');
            this.isMoreMenuOpen = dropdown.classList.contains('show');
        }
    }

    /**
     * Render shortlist
     */
    renderShortlist() {
        const shortlistContainer = document.querySelector('.shortlist-container');
        if (!shortlistContainer) return;

        if (!this.shortlist.length) {
            shortlistContainer.innerHTML = '<p class="text-muted">No talents shortlisted yet.</p>';
            return;
        }

        const shortlistHTML = this.shortlist.map(talent => `
            <div class="shortlist-item" onclick="ui.viewProfile(${talent.id})">
                <div class="shortlist-avatar" style="background-image: url('${this.escapeHtml(talent.avatar || '')}')"></div>
                <div class="shortlist-info">
                    <h4>${this.escapeHtml(talent.name)}</h4>
                    <p>${this.escapeHtml(talent.location || '')}</p>
                </div>
            </div>
        `).join('');

        shortlistContainer.innerHTML = shortlistHTML;
    }

    /**
     * Add talent to shortlist
     */
    async addToShortlist(talentId) {
        try {
            await api.addToShortlist(talentId);
            this.showSuccess('Added to shortlist');
            // Refresh shortlist
            const feedData = await api.getClientFeed();
            this.shortlist = feedData.shortlist || [];
            this.renderShortlist();
        } catch (error) {
            console.error('Failed to add to shortlist:', error);
            this.showError('Failed to add to shortlist');
        }
    }

    /**
     * Share current reel
     */
    shareReel() {
        const currentReelData = this.reels[this.currentReel];
        if (!currentReelData) return;

        if (navigator.share) {
            navigator.share({
                title: currentReelData.title || 'Check out this talent',
                text: currentReelData.description || '',
                url: window.location.href
            }).catch(console.error);
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(window.location.href).then(() => {
                this.showSuccess('Link copied to clipboard');
            }).catch(() => {
                this.showError('Failed to share');
            });
        }
        
        this.toggleMoreMenu();
    }

    /**
     * View talent profile
     */
    viewProfile(talentId) {
        window.location.href = `/talent/profile/${talentId}`;
    }

    /**
     * Handle keyboard navigation
     */
    handleKeyboardNavigation(e) {
        // Only handle on reel pages
        if (!document.querySelector('.reels-container')) return;

        switch (e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                this.previousReel();
                break;
            case 'ArrowRight':
                e.preventDefault();
                this.nextReel();
                break;
            case 'Escape':
                if (this.isMoreMenuOpen) {
                    this.toggleMoreMenu();
                }
                break;
        }
    }

    /**
     * Handle clicks outside dropdowns
     */
    handleOutsideClicks(e) {
        if (this.isMoreMenuOpen && !e.target.closest('.more-menu')) {
            this.toggleMoreMenu();
        }
    }

    /**
     * Handle window resize
     */
    handleResize() {
        // Adjust layout for mobile
        const sidebar = document.querySelector('.sidebar');
        if (sidebar && window.innerWidth <= 768) {
            sidebar.style.position = 'static';
            sidebar.style.width = '100%';
        } else if (sidebar) {
            sidebar.style.position = 'fixed';
            sidebar.style.width = '280px';
        }
    }

    /**
     * Handle form submission
     */
    async handleFormSubmission(form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        const errorContainer = form.querySelector('.error-message');
        
        try {
            loading.show(submitBtn);
            if (errorContainer) errorContainer.style.display = 'none';
            
            const formData = new FormData(form);
            const action = form.getAttribute('action') || form.dataset.action;
            const method = form.getAttribute('method') || 'POST';
            
            let response;
            if (method.toLowerCase() === 'post') {
                response = await http.post(action, formData);
            } else {
                response = await http.get(action);
            }
            
            if (response.success) {
                this.showSuccess(response.message || 'Operation completed successfully');
                
                // Handle redirect
                if (response.redirect_url) {
                    setTimeout(() => {
                        window.location.href = response.redirect_url;
                    }, 1000);
                }
            } else {
                throw new Error(response.message || 'Operation failed');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            if (errorContainer) {
                errorContainer.textContent = error.message || 'Operation failed. Please try again.';
                errorContainer.style.display = 'block';
            }
        } finally {
            loading.hide(submitBtn);
        }
    }

    /**
     * Initialize tooltips
     */
    initTooltips() {
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                this.showTooltip(e.target, e.target.dataset.tooltip);
            });
            
            element.addEventListener('mouseleave', () => {
                this.hideTooltip();
            });
        });
    }

    /**
     * Initialize modals
     */
    initModals() {
        document.querySelectorAll('[data-modal]').forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const modalId = trigger.dataset.modal;
                this.showModal(modalId);
            });
        });
        
        document.querySelectorAll('.modal-close').forEach(closeBtn => {
            closeBtn.addEventListener('click', () => {
                this.hideModal();
            });
        });
    }

    /**
     * Initialize dropdowns
     */
    initDropdowns() {
        document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                const dropdown = toggle.nextElementSibling;
                if (dropdown && dropdown.classList.contains('dropdown-menu')) {
                    dropdown.classList.toggle('show');
                }
            });
        });
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    /**
     * Show error message
     */
    showError(message) {
        this.showNotification(message, 'error');
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // Auto remove
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    /**
     * Show update notification for PWA
     */
    showUpdateNotification() {
        const notification = document.createElement('div');
        notification.className = 'update-notification';
        notification.innerHTML = `
            <p>A new version is available!</p>
            <button onclick="window.location.reload()">Update Now</button>
            <button onclick="this.parentNode.remove()">Later</button>
        `;
        
        document.body.appendChild(notification);
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize UI controller
const ui = new UIController();

// Export for global access
window.ui = ui;

// Additional utility functions
window.utils = {
    /**
     * Format date for display
     */
    formatDate(date) {
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }).format(new Date(date));
    },

    /**
     * Format time for display
     */
    formatTime(date) {
        return new Intl.DateTimeFormat('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date(date));
    },

    /**
     * Debounce function calls
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle function calls
     */
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
};
