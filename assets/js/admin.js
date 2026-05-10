// ============================================================
// admin.js - Admin/Moderator panel functionality
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Remove post confirmation
    document.querySelectorAll('.remove-btn').forEach(btn => {
        btn.closest('form').addEventListener('submit', e => {
            e.preventDefault();
            Swal.fire({
                title: 'Remove post?',
                text: 'Remove "' + btn.dataset.name + '"? This hides it from public.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, remove',
                confirmButtonColor: '#dc2626'
            }).then(result => {
                if (result.isConfirmed) btn.closest('form').submit();
            });
        });
    });
    
    // Dismiss report confirmation
    document.querySelectorAll('.dismiss-btn').forEach(btn => {
        btn.closest('form').addEventListener('submit', e => {
            e.preventDefault();
            Swal.fire({
                title: 'Dismiss report?',
                text: 'Dismiss report for "' + btn.dataset.name + '"? No action will be taken.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, dismiss'
            }).then(result => {
                if (result.isConfirmed) btn.closest('form').submit();
            });
        });
    });
    
    // Ban user confirmation
    document.querySelectorAll('.ban-btn').forEach(btn => {
        btn.closest('form').addEventListener('submit', e => {
            e.preventDefault();
            Swal.fire({
                title: 'Ban user?',
                text: 'Ban ' + btn.dataset.name + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, ban',
                confirmButtonColor: '#dc2626'
            }).then(result => {
                if (result.isConfirmed) btn.closest('form').submit();
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
});