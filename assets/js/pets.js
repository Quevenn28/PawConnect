// ============================================================
// pets.js - Pet listing and detail page functionality
// ============================================================

// Image preview for create/edit forms
function previewPhoto(input) {
    const file = input.files[0];
    const fileNameSpan = document.getElementById('fileName');
    const previewDiv = document.getElementById('photoPreview');
    const previewImg = document.getElementById('previewImg');
    
    if (fileNameSpan) {
        fileNameSpan.textContent = file ? file.name : 'No file chosen';
    }
    
    if (file && previewImg) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            if (previewDiv) {
                previewDiv.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);
    }
}

// For edit page - same function but handles existing preview
function previewPhotoEdit(input) {
    const file = input.files[0];
    const fileNameSpan = document.getElementById('fileName');
    
    if (fileNameSpan) {
        fileNameSpan.textContent = file ? file.name : 'No file chosen';
    }
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let img = document.getElementById('previewImg');
            if (!img) {
                const preview = document.createElement('div');
                preview.className = 'photo-preview';
                preview.style.marginTop = '10px';
                img = document.createElement('img');
                img.id = 'previewImg';
                img.style.maxWidth = '200px';
                img.style.borderRadius = '8px';
                preview.appendChild(img);
                document.querySelector('.form-group').appendChild(preview);
            }
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

// Report form functions for show.php
function openReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Re-enable scrolling
    }
}

// ============================================================
// SIDEBAR TOGGLE BUTTONS (SAME LOGIC AS DASHBOARD)
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    // Reset body overflow on page load (in case modal was open before)
    document.body.style.overflow = 'auto';
    
    // Setup report modal - close when clicking outside
    const reportModal = document.getElementById('reportModal');
    if (reportModal) {
        reportModal.addEventListener('click', function(e) {
            if (e.target === reportModal) {
                closeReportModal();
            }
        });
    }
    
    // Auto-dismiss success alerts after 4 seconds (MUST run first, before sidebar check)
    document.querySelectorAll('.alert-success.auto-dismiss').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.3s ease-out';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 4000);
    });
    
    // For create page - check if previewPhoto function is needed
    const photoInput = document.getElementById('photoInput');
    if (photoInput && window.location.pathname.includes('create.php')) {
        photoInput.setAttribute('onchange', 'previewPhoto(this)');
    }
    
    // For edit page - check if previewPhotoEdit is needed
    if (photoInput && window.location.pathname.includes('edit.php')) {
        photoInput.setAttribute('onchange', 'previewPhotoEdit(this)');
    }
    
    // ============================================================
    // SIDEBAR TOGGLE (EXACT SAME AS DASHBOARD)
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
    // IMAGE MODAL FOR PET PHOTO (show.php)
    // ============================================================
    
    const petPhoto = document.getElementById('petPhoto');
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const closeModal = document.querySelector('#imageModal .close-modal');
    
    if (petPhoto && modal && modalImg) {
        petPhoto.addEventListener('click', function() {
            modal.style.display = 'flex';
            modalImg.src = this.src;
        });
    }
    
    if (closeModal && modal) {
        closeModal.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }
    
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
});