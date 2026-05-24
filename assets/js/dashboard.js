// Dashboard section handling with URL parameters
document.addEventListener('DOMContentLoaded', function() {
    const dashboardLinks = document.querySelectorAll('.sidebar-link');
    const dashboardSections = document.querySelectorAll('.dashboard-section');
    
    if (dashboardLinks.length && dashboardSections.length) {
        
        // Function to show a specific section
        function showSection(sectionId) {
            dashboardLinks.forEach(link => {
                if (link.dataset.section === sectionId) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
            
            dashboardSections.forEach(section => {
                if (section.id === sectionId) {
                    section.classList.add('active');
                } else {
                    section.classList.remove('active');
                }
            });
        }
        
        // Check URL parameter on page load
        const urlParams = new URLSearchParams(window.location.search);
        const sectionParam = urlParams.get('section');
        
        if (sectionParam && document.getElementById(sectionParam)) {
            showSection(sectionParam);
        }
        
        // Handle hash-based navigation (for notification links)
        if (window.location.hash) {
            const hashSection = window.location.hash.substring(1);
            if (document.getElementById(hashSection)) {
                showSection(hashSection);
                window.history.replaceState({ section: hashSection }, '', `${window.location.pathname}?section=${hashSection}`);
            }
        }
        
        // Handle sidebar clicks
        dashboardLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = link.dataset.section;
                showSection(section);
                
                // Update URL without page reload
                const newUrl = `${window.location.pathname}?section=${section}`;
                window.history.pushState({ section: section }, '', newUrl);
            });
        });
        
        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(event) {
            const urlParams = new URLSearchParams(window.location.search);
            const sectionParam = urlParams.get('section');
            if (sectionParam && document.getElementById(sectionParam)) {
                showSection(sectionParam);
            }
        });
    }
    
    // ============================================================
    // SIDEBAR TOGGLE BUTTONS
    // ============================================================
    
    const sidebar = document.querySelector('.sidebar-wrapper');
    const closeBtn = document.getElementById('closeSidebarBtn');
    const openBtn = document.getElementById('openSidebarBtn');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (sidebar) {
                sidebar.classList.add('collapsed');
                // On mobile, also hide overlay
                if (window.innerWidth <= 768) {
                    const overlay = document.querySelector('.sidebar-overlay');
                    if (overlay) overlay.classList.remove('active');
                }
            }
        });
    }
    
    if (openBtn) {
        openBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (sidebar) {
                sidebar.classList.remove('collapsed');
                // On mobile, show overlay
                if (window.innerWidth <= 768) {
                    const overlay = document.querySelector('.sidebar-overlay');
                    if (overlay) overlay.classList.add('active');
                }
            }
        });
    }
});

// Handle SweetAlert confirmations for pet actions
document.addEventListener('DOMContentLoaded', function() {
    // Mark as adopted confirmation
    document.querySelectorAll('.mark-adopted-form').forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const petName = form.dataset.name;
            Swal.fire({
                title: 'Mark adopted?',
                text: `Mark ${petName} as adopted? This cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, mark adopted',
                confirmButtonColor: '#16a34a',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        });
    });
    
    // Delete pet listing confirmation
    document.querySelectorAll('.delete-pet-form').forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const petName = form.dataset.name;
            Swal.fire({
                title: 'Remove listing?',
                text: `Remove ${petName}? This can't be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, remove it',
                confirmButtonColor: '#dc2626',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        });
    });
    
    // Delete request confirmation
    document.querySelectorAll('.del-req-form').forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const petName = form.dataset.name;
            Swal.fire({
                title: 'Remove request?',
                text: `Remove your request for ${petName}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, remove',
                confirmButtonColor: '#f97316',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        });
    });
});