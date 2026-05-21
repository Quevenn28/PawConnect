// ============================================================
// backup.js - Backup & Restore confirmations
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Create backup confirmation
    var backupForms = document.querySelectorAll('.backup-create-form');
    backupForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Create Database Backup?',
                text: 'This may take a few seconds while the backup is created.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Yes, create backup',
                confirmButtonColor: '#2563eb',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });
    
    // Restore from existing backup confirmation
    var restoreForms = document.querySelectorAll('.restore-form');
    restoreForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var filename = form.dataset.file;
            Swal.fire({
                title: 'Restore Database?',
                text: 'Restore from "' + filename + '"? This will overwrite your current database and CANNOT be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, restore it',
                confirmButtonColor: '#dc2626',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });
    
    // Delete backup confirmation
    var deleteForms = document.querySelectorAll('.delete-backup-form');
    deleteForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var filename = form.dataset.file;
            Swal.fire({
                title: 'Delete Backup?',
                text: 'Delete "' + filename + '"? This cannot be undone.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                confirmButtonColor: '#dc2626',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });
    
    // Upload and restore confirmation
    var uploadForms = document.querySelectorAll('.upload-restore-form');
    uploadForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Restore from Upload?',
                text: 'This will overwrite your current database with the uploaded file. This action CANNOT be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, restore',
                confirmButtonColor: '#dc2626',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });
});