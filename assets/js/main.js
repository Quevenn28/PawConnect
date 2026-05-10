// ============================================================
// main.js - Global functions for collapsible sidebar
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Get sidebar wrapper
    const sidebar = document.querySelector('.sidebar-wrapper');
    
    // Only proceed if sidebar exists on this page
    if (!sidebar) return;
    
    // Create overlay for mobile
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    // Function to toggle sidebar (for mobile overlay click)
    function toggleSidebar() {
        if (sidebar) {
            sidebar.classList.toggle('collapsed');
            
            // For mobile: manage overlay
            if (window.innerWidth <= 768) {
                if (!sidebar.classList.contains('collapsed')) {
                    overlay.classList.add('active');
                } else {
                    overlay.classList.remove('active');
                }
            }
        }
    }
    
    // Close sidebar when clicking overlay (mobile only)
    overlay.addEventListener('click', function() {
        if (window.innerWidth <= 768 && sidebar && !sidebar.classList.contains('collapsed')) {
            toggleSidebar();
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && sidebar) {
            sidebar.classList.remove('collapsed');
            overlay.classList.remove('active');
        } else if (window.innerWidth <= 768 && sidebar) {
            // On mobile, sidebar should be collapsed by default
            if (!sidebar.classList.contains('collapsed')) {
                sidebar.classList.add('collapsed');
                overlay.classList.remove('active');
            }
        }
    });
    
    // Initialize: on mobile, sidebar starts collapsed
    if (window.innerWidth <= 768 && sidebar) {
        sidebar.classList.add('collapsed');
    }
});