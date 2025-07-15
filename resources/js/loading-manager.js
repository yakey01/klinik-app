/**
 * Dokterku Loading Manager
 * Handles loading states, progress indicators, and user feedback
 */

class LoadingManager {
    constructor() {
        this.loadingStates = new Map();
        this.progressBars = new Map();
        this.init();
    }

    init() {
        // Initialize loading states on page load
        this.setupPageLoadingOverlay();
        this.setupFormLoadingStates();
        this.setupTableLoadingStates();
        this.setupAjaxLoadingStates();
        this.setupButtonLoadingStates();
    }

    /**
     * Page Loading Overlay
     */
    setupPageLoadingOverlay() {
        // Show loading overlay on page navigation
        document.addEventListener('DOMContentLoaded', () => {
            this.hidePageLoading();
        });

        // Show loading on form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('loading-form')) {
                this.showPageLoading('Processing...', 'Please wait while we save your changes');
            }
        });
    }

    showPageLoading(title = 'Loading...', subtitle = 'Please wait') {
        const overlay = document.createElement('div');
        overlay.className = 'page-loading-overlay';
        overlay.id = 'page-loading-overlay';
        
        overlay.innerHTML = `
            <div class="page-loading-content">
                <div class="page-loading-spinner"></div>
                <div class="page-loading-text">${title}</div>
                <div class="page-loading-subtext">${subtitle}</div>
            </div>
        `;
        
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';
    }

    hidePageLoading() {
        const overlay = document.getElementById('page-loading-overlay');
        if (overlay) {
            overlay.remove();
            document.body.style.overflow = '';
        }
    }

    /**
     * Form Loading States
     */
    setupFormLoadingStates() {
        // Handle form loading states
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.classList.contains('loading-form')) {
                this.setFormLoading(form, true);
                
                // Auto-hide loading after 30 seconds (timeout)
                setTimeout(() => {
                    this.setFormLoading(form, false);
                }, 30000);
            }
        });
    }

    setFormLoading(form, isLoading) {
        const formId = form.id || 'form-' + Date.now();
        form.id = formId;

        if (isLoading) {
            form.classList.add('form-loading');
            
            // Disable all form controls
            const controls = form.querySelectorAll('input, textarea, select, button');
            controls.forEach(control => {
                control.classList.add('form-field-loading');
                control.disabled = true;
            });

            // Show loading spinner on submit button
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                this.setButtonLoading(submitBtn, true, 'Processing...');
            }

            this.loadingStates.set(formId, true);
        } else {
            form.classList.remove('form-loading');
            
            // Re-enable all form controls
            const controls = form.querySelectorAll('input, textarea, select, button');
            controls.forEach(control => {
                control.classList.remove('form-field-loading');
                control.disabled = false;
            });

            // Hide loading spinner on submit button
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                this.setButtonLoading(submitBtn, false);
            }

            this.loadingStates.delete(formId);
        }
    }

    /**
     * Button Loading States
     */
    setupButtonLoadingStates() {
        // Handle button loading states
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('loading-btn')) {
                this.setButtonLoading(e.target, true);
                
                // Auto-hide loading after 10 seconds
                setTimeout(() => {
                    this.setButtonLoading(e.target, false);
                }, 10000);
            }
        });
    }

    setButtonLoading(button, isLoading, text = null) {
        const buttonId = button.id || 'btn-' + Date.now();
        button.id = buttonId;

        if (isLoading) {
            // Store original text
            if (!button.dataset.originalText) {
                button.dataset.originalText = button.textContent;
            }

            button.classList.add('btn-loading-with-text');
            button.disabled = true;
            
            // Add spinner and text
            const spinner = '<span class="loading-spinner loading-spinner-sm"></span>';
            const loadingText = text || button.dataset.originalText || 'Loading...';
            button.innerHTML = spinner + loadingText;

            this.loadingStates.set(buttonId, true);
        } else {
            button.classList.remove('btn-loading-with-text');
            button.disabled = false;
            
            // Restore original text
            if (button.dataset.originalText) {
                button.textContent = button.dataset.originalText;
            }

            this.loadingStates.delete(buttonId);
        }
    }

    /**
     * Table Loading States
     */
    setupTableLoadingStates() {
        // Handle table loading states
        const tables = document.querySelectorAll('.loading-table');
        tables.forEach(table => {
            this.setupTableLoadingListeners(table);
        });
    }

    setupTableLoadingListeners(table) {
        // Show loading on pagination links
        const paginationLinks = table.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', () => {
                this.setTableLoading(table, true);
            });
        });

        // Show loading on sort links
        const sortLinks = table.querySelectorAll('th a');
        sortLinks.forEach(link => {
            link.addEventListener('click', () => {
                this.setTableLoading(table, true);
            });
        });
    }

    setTableLoading(table, isLoading) {
        const tableId = table.id || 'table-' + Date.now();
        table.id = tableId;

        if (isLoading) {
            table.classList.add('table-loading');
            
            // Add loading overlay
            const overlay = document.createElement('div');
            overlay.className = 'table-loading-overlay';
            overlay.innerHTML = '<div class="loading-spinner"></div>';
            table.appendChild(overlay);

            // Add loading state to rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.classList.add('table-row-loading');
            });

            this.loadingStates.set(tableId, true);
        } else {
            table.classList.remove('table-loading');
            
            // Remove loading overlay
            const overlay = table.querySelector('.table-loading-overlay');
            if (overlay) {
                overlay.remove();
            }

            // Remove loading state from rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.classList.remove('table-row-loading');
            });

            this.loadingStates.delete(tableId);
        }
    }

    /**
     * Progress Bar Management
     */
    createProgressBar(container, options = {}) {
        const progressId = 'progress-' + Date.now();
        const config = {
            animated: options.animated || false,
            variant: options.variant || 'primary',
            showPercentage: options.showPercentage || false,
            ...options
        };

        const progressBar = document.createElement('div');
        progressBar.className = `progress-bar progress-bar-${config.variant}`;
        if (config.animated) {
            progressBar.classList.add('progress-bar-animated');
        }

        const fill = document.createElement('div');
        fill.className = 'progress-bar-fill';
        fill.style.width = '0%';

        progressBar.appendChild(fill);

        if (config.showPercentage) {
            const percentage = document.createElement('div');
            percentage.className = 'progress-percentage';
            percentage.textContent = '0%';
            progressBar.appendChild(percentage);
        }

        container.appendChild(progressBar);
        
        this.progressBars.set(progressId, {
            element: progressBar,
            fill: fill,
            percentage: progressBar.querySelector('.progress-percentage'),
            config: config
        });

        return progressId;
    }

    updateProgress(progressId, value) {
        const progress = this.progressBars.get(progressId);
        if (!progress) return;

        const percentage = Math.max(0, Math.min(100, value));
        progress.fill.style.width = percentage + '%';
        
        if (progress.percentage) {
            progress.percentage.textContent = percentage + '%';
        }

        // Auto-remove when complete
        if (percentage >= 100) {
            setTimeout(() => {
                this.removeProgress(progressId);
            }, 1000);
        }
    }

    removeProgress(progressId) {
        const progress = this.progressBars.get(progressId);
        if (progress) {
            progress.element.remove();
            this.progressBars.delete(progressId);
        }
    }

    /**
     * AJAX Loading States
     */
    setupAjaxLoadingStates() {
        // Handle AJAX requests
        if (window.jQuery) {
            $(document).ajaxStart(() => {
                this.showGlobalLoading();
            });

            $(document).ajaxStop(() => {
                this.hideGlobalLoading();
            });
        }

        // Handle fetch requests
        const originalFetch = window.fetch;
        window.fetch = (...args) => {
            this.showGlobalLoading();
            return originalFetch(...args).finally(() => {
                this.hideGlobalLoading();
            });
        };
    }

    showGlobalLoading() {
        const loader = document.getElementById('global-loader');
        if (!loader) {
            const globalLoader = document.createElement('div');
            globalLoader.id = 'global-loader';
            globalLoader.className = 'global-loading-indicator';
            globalLoader.innerHTML = '<div class="loading-spinner"></div>';
            document.body.appendChild(globalLoader);
        }
    }

    hideGlobalLoading() {
        const loader = document.getElementById('global-loader');
        if (loader) {
            loader.remove();
        }
    }

    /**
     * Skeleton Loading
     */
    createSkeleton(container, type = 'text', count = 1) {
        for (let i = 0; i < count; i++) {
            const skeleton = document.createElement('div');
            skeleton.className = `skeleton skeleton-${type}`;
            container.appendChild(skeleton);
        }
    }

    createSkeletonCard(container) {
        const card = document.createElement('div');
        card.className = 'card-loading';
        
        card.innerHTML = `
            <div class="card-loading-header">
                <div class="skeleton skeleton-avatar card-loading-avatar"></div>
                <div>
                    <div class="skeleton skeleton-text card-loading-title"></div>
                    <div class="skeleton skeleton-text card-loading-subtitle"></div>
                </div>
            </div>
            <div class="skeleton skeleton-text card-loading-content"></div>
            <div class="skeleton skeleton-text card-loading-content"></div>
            <div class="skeleton skeleton-text card-loading-content"></div>
        `;
        
        container.appendChild(card);
    }

    /**
     * Utility Methods
     */
    isLoading(id) {
        return this.loadingStates.has(id);
    }

    clearAllLoading() {
        this.loadingStates.clear();
        this.progressBars.clear();
        this.hidePageLoading();
        this.hideGlobalLoading();
    }

    /**
     * Modal Loading
     */
    setModalLoading(modal, isLoading) {
        const modalContent = modal.querySelector('.modal-content, .fi-modal-content');
        if (!modalContent) return;

        if (isLoading) {
            modalContent.classList.add('modal-loading');
            modalContent.innerHTML = '<div class="modal-loading-spinner"></div>';
        } else {
            modalContent.classList.remove('modal-loading');
        }
    }

    /**
     * Widget Loading
     */
    setWidgetLoading(widget, isLoading, message = 'Loading...') {
        if (isLoading) {
            widget.classList.add('widget-loading');
            widget.innerHTML = `
                <div class="widget-loading-icon">
                    <div class="loading-spinner loading-spinner-lg"></div>
                </div>
                <div class="widget-loading-text">${message}</div>
            `;
        } else {
            widget.classList.remove('widget-loading');
        }
    }

    /**
     * Chart Loading
     */
    setChartLoading(chartContainer, isLoading) {
        if (isLoading) {
            chartContainer.classList.add('chart-loading');
            chartContainer.innerHTML = '<div class="loading-spinner loading-spinner-lg"></div>';
        } else {
            chartContainer.classList.remove('chart-loading');
        }
    }
}

// Initialize Loading Manager
window.loadingManager = new LoadingManager();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LoadingManager;
}

// CSS for Global Loading Indicator
const globalLoadingStyles = `
    .global-loading-indicator {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(
            90deg,
            var(--primary),
            var(--secondary),
            var(--accent)
        );
        background-size: 200% 100%;
        animation: progress-bar-stripes 2s linear infinite;
        z-index: 10000;
    }
`;

// Inject global loading styles
const style = document.createElement('style');
style.textContent = globalLoadingStyles;
document.head.appendChild(style);