@vite('resources/css/petugas-table-ux.css')

<style>
/* Dynamic CSS Variables for Theme Switching */
:root {
    --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
    --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    --shadow-color: rgba(0, 0, 0, 0.1);
}

.dark {
    --shadow-color: rgba(0, 0, 0, 0.3);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 🎯 Enhanced Table UX Initialization
    function initPetugasTableUX() {
        const tableContainer = document.querySelector('[data-resource="pendapatan-harian"]');
        if (!tableContainer) return;
        
        // Add our custom class
        tableContainer.classList.add('petugas-table-container');
        
        // 💫 Enhanced Row Interactions
        const rows = tableContainer.querySelectorAll('.fi-ta-row');
        
        rows.forEach((row, index) => {
            // Add staggered animation delay
            row.style.animationDelay = `${index * 0.05}s`;
            
            // 🎪 Advanced Hover Effects
            row.addEventListener('mouseenter', function(e) {
                // Add magnetic effect
                row.style.transform = 'scale(1.02) translateZ(0)';
                
                // Ripple effect
                const rect = row.getBoundingClientRect();
                const ripple = document.createElement('div');
                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, transparent 70%);
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                    z-index: 1;
                    left: ${e.clientX - rect.left - 25}px;
                    top: ${e.clientY - rect.top - 25}px;
                    width: 50px;
                    height: 50px;
                `;
                
                row.style.position = 'relative';
                row.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
            
            row.addEventListener('mouseleave', function() {
                row.style.transform = '';
            });
            
            // 🎵 Smooth Scroll on Row Click
            row.addEventListener('click', function(e) {
                if (e.target.closest('button')) return; // Don't trigger on button clicks
                
                row.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            });
        });
        
        // 💰 Enhanced Money Display
        const moneyCells = tableContainer.querySelectorAll('[data-column="nominal"]');
        moneyCells.forEach(cell => {
            // Add counting animation
            const value = cell.textContent;
            const numValue = parseFloat(value.replace(/[^\d]/g, ''));
            
            if (numValue > 0) {
                cell.style.background = 'var(--success-gradient)';
                cell.style.backgroundClip = 'text';
                cell.style.webkitBackgroundClip = 'text';
                cell.style.webkitTextFillColor = 'transparent';
            }
        });
        
        // 🎨 Badge Enhancement
        const badges = tableContainer.querySelectorAll('.fi-badge');
        badges.forEach(badge => {
            const shift = badge.textContent.trim();
            badge.setAttribute('data-shift', shift);
            
            // Add glow effect
            badge.addEventListener('mouseenter', function() {
                badge.style.boxShadow = '0 0 20px rgba(59, 130, 246, 0.5)';
            });
            
            badge.addEventListener('mouseleave', function() {
                badge.style.boxShadow = '';
            });
        });
        
        // 🌟 Intersection Observer for Row Animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '50px'
        };
        
        const rowObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = `fadeInUp 0.6s ease-out ${index * 0.1}s both`;
                }
            });
        }, observerOptions);
        
        rows.forEach(row => rowObserver.observe(row));
    }
    
    // 🔄 Re-initialize when table updates
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                setTimeout(initPetugasTableUX, 100);
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Initial setup
    initPetugasTableUX();
});

// 🎭 CSS Keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes pulse-glow {
        0%, 100% {
            box-shadow: 0 0 5px rgba(59, 130, 246, 0.3);
        }
        50% {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.6);
        }
    }
`;
document.head.appendChild(style);
</script>