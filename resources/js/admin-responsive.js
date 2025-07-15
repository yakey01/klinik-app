/**
 * Admin Responsive Design System
 * Provides JavaScript utilities for responsive behavior
 */

class AdminResponsive {
    constructor() {
        this.breakpoints = {
            sm: 640,
            md: 768,
            lg: 1024,
            xl: 1280,
            '2xl': 1536
        };
        
        this.currentBreakpoint = this.getCurrentBreakpoint();
        this.init();
    }
    
    init() {
        this.setupResizeListener();
        this.setupMenuToggle();
        this.setupCollapsibleCards();
        this.setupResponsiveTables();
        this.setupStickyHeaders();
        this.setupAutoHideElements();
    }
    
    getCurrentBreakpoint() {
        const width = window.innerWidth;
        
        if (width >= this.breakpoints['2xl']) return '2xl';
        if (width >= this.breakpoints.xl) return 'xl';
        if (width >= this.breakpoints.lg) return 'lg';
        if (width >= this.breakpoints.md) return 'md';
        if (width >= this.breakpoints.sm) return 'sm';
        return 'xs';
    }
    
    setupResizeListener() {
        let resizeTimer;
        
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                const newBreakpoint = this.getCurrentBreakpoint();
                
                if (newBreakpoint !== this.currentBreakpoint) {
                    this.currentBreakpoint = newBreakpoint;
                    this.onBreakpointChange(newBreakpoint);
                }
            }, 150);
        });
    }
    
    onBreakpointChange(breakpoint) {
        // Emit custom event for breakpoint change
        window.dispatchEvent(new CustomEvent('admin:breakpoint-change', {
            detail: { breakpoint }
        }));
        
        // Update responsive grids
        this.updateResponsiveGrids();
        
        // Update responsive tables
        this.updateResponsiveTables();
        
        // Update navigation
        this.updateNavigation();
    }
    
    setupMenuToggle() {
        const menuToggle = document.getElementById('admin-menu-toggle');
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('admin-sidebar-overlay');
        
        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('admin-sidebar-open');
                if (overlay) {
                    overlay.classList.toggle('hidden');
                }
            });
        }
        
        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('admin-sidebar-open');
                overlay.classList.add('hidden');
            });
        }
    }
    
    setupCollapsibleCards() {
        const collapsibleCards = document.querySelectorAll('[data-admin-collapsible]');
        
        collapsibleCards.forEach(card => {
            const toggle = card.querySelector('[data-admin-collapse-toggle]');
            const content = card.querySelector('[data-admin-collapse-content]');
            
            if (toggle && content) {
                toggle.addEventListener('click', () => {
                    const isExpanded = content.style.display !== 'none';
                    
                    if (isExpanded) {
                        content.style.display = 'none';
                        toggle.querySelector('svg')?.classList.add('rotate-180');
                    } else {
                        content.style.display = 'block';
                        toggle.querySelector('svg')?.classList.remove('rotate-180');
                    }
                });
            }
        });
    }
    
    setupResponsiveTables() {
        const tables = document.querySelectorAll('.admin-table-responsive');
        
        tables.forEach(table => {
            this.makeTableResponsive(table);
        });
    }
    
    makeTableResponsive(table) {
        const wrapper = document.createElement('div');
        wrapper.className = 'admin-table-wrapper overflow-x-auto';
        
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
        
        // Add mobile-specific styling
        if (this.currentBreakpoint === 'xs' || this.currentBreakpoint === 'sm') {
            this.addMobileTableStyling(table);
        }
    }
    
    addMobileTableStyling(table) {
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                const header = table.querySelector(`th:nth-child(${index + 1})`);
                if (header) {
                    cell.setAttribute('data-label', header.textContent.trim());
                }
            });
        });
    }
    
    updateResponsiveGrids() {
        const grids = document.querySelectorAll('[data-admin-responsive-grid]');
        
        grids.forEach(grid => {
            const config = JSON.parse(grid.getAttribute('data-admin-responsive-grid'));
            const currentCols = config[this.currentBreakpoint] || config.default || 1;
            
            // Update grid columns
            grid.className = grid.className.replace(/grid-cols-\d+/g, '');
            grid.classList.add(`grid-cols-${currentCols}`);
        });
    }
    
    updateResponsiveTables() {
        const tables = document.querySelectorAll('.admin-table-responsive');
        
        tables.forEach(table => {
            if (this.currentBreakpoint === 'xs' || this.currentBreakpoint === 'sm') {
                table.classList.add('admin-table-mobile');
            } else {
                table.classList.remove('admin-table-mobile');
            }
        });
    }
    
    updateNavigation() {
        const nav = document.getElementById('admin-navigation');
        
        if (nav) {
            if (this.currentBreakpoint === 'xs' || this.currentBreakpoint === 'sm') {
                nav.classList.add('admin-nav-mobile');
            } else {
                nav.classList.remove('admin-nav-mobile');
            }
        }
    }
    
    setupStickyHeaders() {
        const stickyHeaders = document.querySelectorAll('[data-admin-sticky]');
        
        stickyHeaders.forEach(header => {
            const observer = new IntersectionObserver(
                ([entry]) => {
                    if (entry.isIntersecting) {
                        header.classList.remove('admin-sticky-active');
                    } else {
                        header.classList.add('admin-sticky-active');
                    }
                },
                { threshold: 0 }
            );
            
            observer.observe(header);
        });
    }
    
    setupAutoHideElements() {
        const autoHideElements = document.querySelectorAll('[data-admin-auto-hide]');
        
        autoHideElements.forEach(element => {
            const breakpoint = element.getAttribute('data-admin-auto-hide');
            
            if (this.shouldHideAtBreakpoint(breakpoint)) {
                element.classList.add('hidden');
            } else {
                element.classList.remove('hidden');
            }
        });
    }
    
    shouldHideAtBreakpoint(breakpoint) {
        const breakpointOrder = ['xs', 'sm', 'md', 'lg', 'xl', '2xl'];
        const currentIndex = breakpointOrder.indexOf(this.currentBreakpoint);
        const hideIndex = breakpointOrder.indexOf(breakpoint);
        
        return currentIndex <= hideIndex;
    }
    
    // Utility methods
    isMobile() {
        return this.currentBreakpoint === 'xs' || this.currentBreakpoint === 'sm';
    }
    
    isTablet() {
        return this.currentBreakpoint === 'md';
    }
    
    isDesktop() {
        return ['lg', 'xl', '2xl'].includes(this.currentBreakpoint);
    }
    
    getBreakpointValue(breakpoint) {
        return this.breakpoints[breakpoint] || 0;
    }
    
    // Animation utilities
    fadeIn(element, duration = 300) {
        element.style.opacity = '0';
        element.style.display = 'block';
        
        let opacity = 0;
        const timer = setInterval(() => {
            opacity += 50 / duration;
            element.style.opacity = opacity;
            
            if (opacity >= 1) {
                clearInterval(timer);
                element.style.opacity = '1';
            }
        }, 50);
    }
    
    fadeOut(element, duration = 300) {
        let opacity = 1;
        const timer = setInterval(() => {
            opacity -= 50 / duration;
            element.style.opacity = opacity;
            
            if (opacity <= 0) {
                clearInterval(timer);
                element.style.display = 'none';
                element.style.opacity = '0';
            }
        }, 50);
    }
    
    slideToggle(element, duration = 300) {
        if (element.style.display === 'none') {
            this.slideDown(element, duration);
        } else {
            this.slideUp(element, duration);
        }
    }
    
    slideDown(element, duration = 300) {
        element.style.display = 'block';
        element.style.height = '0';
        element.style.overflow = 'hidden';
        
        const height = element.scrollHeight;
        let currentHeight = 0;
        const increment = height / (duration / 16);
        
        const timer = setInterval(() => {
            currentHeight += increment;
            element.style.height = currentHeight + 'px';
            
            if (currentHeight >= height) {
                clearInterval(timer);
                element.style.height = 'auto';
                element.style.overflow = 'visible';
            }
        }, 16);
    }
    
    slideUp(element, duration = 300) {
        const height = element.scrollHeight;
        let currentHeight = height;
        const decrement = height / (duration / 16);
        
        element.style.height = height + 'px';
        element.style.overflow = 'hidden';
        
        const timer = setInterval(() => {
            currentHeight -= decrement;
            element.style.height = currentHeight + 'px';
            
            if (currentHeight <= 0) {
                clearInterval(timer);
                element.style.display = 'none';
                element.style.height = 'auto';
                element.style.overflow = 'visible';
            }
        }, 16);
    }
}

// Initialize responsive system
document.addEventListener('DOMContentLoaded', () => {
    window.AdminResponsive = new AdminResponsive();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminResponsive;
}