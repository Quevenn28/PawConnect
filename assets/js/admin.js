// ============================================================
// admin.js - Admin/Moderator panel functionality
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================================
    // SIDEBAR TOGGLE BUTTONS (Same as dashboard.js)
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
    
    // ============================================================
    // CONFIRMATION HANDLERS
    // ============================================================
    
    // Helper function to show alert messages
    function showAlert(message, isSuccess = true) {
        const alertDiv = document.createElement('div');
        alertDiv.className = isSuccess ? 'alert alert-success' : 'alert alert-error';
        alertDiv.textContent = message;
        const adminWrap = document.querySelector('.admin-wrap');
        if (adminWrap) {
            if (adminWrap.firstChild) {
                adminWrap.insertBefore(alertDiv, adminWrap.firstChild);
            } else {
                adminWrap.appendChild(alertDiv);
            }
            setTimeout(() => alertDiv.remove(), 5000);
        }
    }
    
    // Helper to safely parse JSON responses
    async function parseJsonResponse(response) {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse response as JSON:', text);
            throw new Error('Server returned invalid JSON: ' + text.substring(0, 100));
        }
    }
    
    // Remove post confirmation (AJAX - stays on dashboard)
    document.querySelectorAll('.remove-btn').forEach(btn => {
        btn.closest('form').addEventListener('submit', e => {
            e.preventDefault();
            const form = btn.closest('form');
            const petName = btn.dataset.name;
            
            Swal.fire({
                title: 'Remove post?',
                text: 'Remove "' + petName + '"? This hides it from public.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, remove',
                confirmButtonColor: '#dc2626'
            }).then(async result => {
                if (result.isConfirmed) {
                    const formData = new FormData(form);
                    formData.append('action', 'remove');
                    formData.append('report_id', form.dataset.reportId);
                    formData.append('pet_id', form.dataset.petId);
                    
                    try {
                        const response = await fetch('/controllers/admin/reports.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const res = await parseJsonResponse(response);
                        
                        if (res.success) {
                            showAlert('✅ ' + res.message, true);
                            // Refresh dashboard by reloading reports section
                            setTimeout(() => location.href = '?tab=reports', 800);
                        } else {
                            showAlert(res.error || 'Error occurred', false);
                        }
                    } catch (error) {
                        console.error('Remove error:', error);
                        showAlert('Error: ' + error.message, false);
                    }
                }
            });
        });
    });
    
    // Dismiss report confirmation (AJAX - stays on dashboard)
    document.querySelectorAll('.dismiss-btn').forEach(btn => {
        btn.closest('form').addEventListener('submit', e => {
            e.preventDefault();
            const form = btn.closest('form');
            const petName = btn.dataset.name;
            
            Swal.fire({
                title: 'Dismiss report?',
                text: 'Dismiss report for "' + petName + '"? No action will be taken.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, dismiss'
            }).then(async result => {
                if (result.isConfirmed) {
                    const formData = new FormData(form);
                    formData.append('action', 'dismiss');
                    formData.append('report_id', form.dataset.reportId);
                    formData.append('pet_id', form.dataset.petId);
                    
                    try {
                        const response = await fetch('/controllers/admin/reports.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const res = await parseJsonResponse(response);
                        
                        if (res.success) {
                            showAlert('✅ ' + res.message, true);
                            // Refresh dashboard by reloading reports section
                            setTimeout(() => location.href = '?tab=reports', 800);
                        } else {
                            showAlert(res.error || 'Error occurred', false);
                        }
                    } catch (error) {
                        console.error('Dismiss error:', error);
                        showAlert('Error: ' + error.message, false);
                    }
                }
            });
        });
    });
    
    // Ban user confirmation - FIXED to use AJAX via click handler
    document.querySelectorAll('.ban-btn').forEach(btn => {
        const form = btn.closest('form');
        
        // Prevent form submission
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        }
        
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const userName = btn.dataset.name;
            
            Swal.fire({
                title: 'Ban user?',
                text: 'Ban ' + userName + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, ban',
                confirmButtonColor: '#dc2626'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const formData = new FormData(form);
                    
                    try {
                        const response = await fetch('/controllers/admin/ban.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const res = await parseJsonResponse(response);
                        
                        if (res.success) {
                            showAlert(res.message, true);
                            setTimeout(() => location.reload(), 800);
                        } else {
                            showAlert(res.error || 'Error occurred', false);
                        }
                    } catch (error) {
                        console.error('Ban error:', error);
                        showAlert('Error: ' + error.message, false);
                    }
                }
            });
        });
    });
    
    // Hard delete pet confirmation
    document.querySelectorAll('.hard-del-btn').forEach(btn => {
        btn.closest('form').addEventListener('submit', e => {
            e.preventDefault();
            Swal.fire({
                title: 'Permanently delete?',
                text: 'Delete "' + btn.dataset.name + '" forever? This CANNOT be undone.',
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete forever',
                confirmButtonColor: '#dc2626'
            }).then(result => {
                if (result.isConfirmed) btn.closest('form').submit();
            });
        });
    });
    
    // Undo action confirmation
    document.querySelectorAll('.undo-form').forEach(form => {
        form.addEventListener('submit', e => {
            e.preventDefault();
            Swal.fire({
                title: 'Undo this action?',
                html: 'This will reverse: <strong>' + form.dataset.label + '</strong><br>The pet listing will be restored to available.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '↩️ Yes, Undo It',
                confirmButtonColor: '#f97316'
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        });
    });
    
    // ============================================================
    // BAN/UNBAN USER HANDLERS (for Users tab with modal)
    // ============================================================
    
    // Ban modal handlers
    document.querySelectorAll('.open-ban-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('banUserId').value = btn.dataset.id;
            document.getElementById('banUserName').textContent = btn.dataset.name;
            const modal = document.getElementById('banModal');
            if (modal) {
                modal.style.display = 'flex';
                if (isMod) {
                    const sel = document.getElementById('banDuration');
                    sel.value = '24h';
                    sel.disabled = true;
                    const note = document.getElementById('modBanNote');
                    if (note) note.style.display = 'block';
                }
            }
        });
    });
    
    // Close ban modal
    window.closeBanModal = function() {
        const modal = document.getElementById('banModal');
        if (modal) modal.style.display = 'none';
        const sel = document.getElementById('banDuration');
        if (sel) sel.disabled = false;
        const note = document.getElementById('modBanNote');
        if (note) note.style.display = 'none';
    };
    
    // Ban form AJAX submission (from modal)
    const banForm = document.getElementById('banForm');
    if (banForm) {
        banForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(banForm);
            
            try {
                const response = await fetch('/controllers/admin/ban.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await parseJsonResponse(response);
                
                if (result.success) {
                    showAlert(result.message, true);
                    closeBanModal();
                    setTimeout(() => location.reload(), 800);
                } else {
                    showAlert(result.error || 'Error occurred', false);
                }
            } catch (error) {
                console.error('Ban modal error:', error);
                showAlert('Error: ' + error.message, false);
            }
        });
    }
    
    // Unban form AJAX submission
    document.querySelectorAll('.unban-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const userName = form.dataset.name;
            Swal.fire({
                title: 'Unban ' + userName + '?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, unban',
                confirmButtonColor: '#16a34a'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const formData = new FormData(form);
                    
                    try {
                        const response = await fetch('/controllers/admin/ban.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const res = await parseJsonResponse(response);
                        
                        if (res.success) {
                            showAlert(res.message, true);
                            setTimeout(() => location.reload(), 800);
                        } else {
                            showAlert(res.error || 'Error occurred', false);
                        }
                    } catch (error) {
                        console.error('Unban error:', error);
                        showAlert('Error: ' + error.message, false);
                    }
                }
            });
        });
    });
    
    // ============================================================
    // DROPDOWN TOGGLES (Sort & filter dropdowns — all .sort-wrap)
    // ============================================================

    // Toggle clicked dropdown, close all others
    document.querySelectorAll('.sort-wrap .sort-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const wrap = btn.closest('.sort-wrap');
            const isOpen = wrap.classList.contains('open');
            // Close all
            document.querySelectorAll('.sort-wrap').forEach(w => w.classList.remove('open'));
            // Re-open if it wasn't already open
            if (!isOpen) wrap.classList.add('open');
        });
    });

    // Close all dropdowns when clicking anywhere else
    document.addEventListener('click', () => {
        document.querySelectorAll('.sort-wrap').forEach(w => w.classList.remove('open'));
    });
    
    // ============================================================
    // REPORT ACTION CONFIRMATIONS (Reports page)
    // ============================================================
    
    document.querySelectorAll('.report-action-form').forEach(f => {
        f.addEventListener('submit', e => {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: f.dataset.confirm,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: f.dataset.confirmBtn,
                confirmButtonColor: f.dataset.confirmColor,
                cancelButtonText: 'Cancel'
            }).then(r => { if (r.isConfirmed) f.submit(); });
        });
    });
});