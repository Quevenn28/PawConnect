// ============================================================
// backup.js - Backup & Restore confirmations
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Restore from existing backup confirmation
    document.querySelectorAll('.restore-form').forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const filename = form.dataset.file;
            Swal.fire({
                title: 'Restore Database?',
                text: `Restore from "${filename}"? This will overwrite your current database and CANNOT be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, restore it',
                confirmButtonColor: '#dc2626',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        });
    });
    
    // Delete backup confirmation
    document.querySelectorAll('.delete-backup-form').forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const filename = form.dataset.file;
            Swal.fire({
                title: 'Delete Backup?',
                text: `Delete "${filename}"? This cannot be undone.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                confirmButtonColor: '#dc2626',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        });
    });
    
    // Upload and restore confirmation
    document.querySelectorAll('.upload-restore-form').forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            Swal.fire({
                title: 'Restore from Upload?',
                text: 'This will overwrite your current database with the uploaded file. This action CANNOT be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, restore',
                confirmButtonColor: '#dc2626',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        });
    });
});