/**
 * FinanzApp Core JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {

    // --- Initialize Bootstrap Dropdowns ---
    const dropdownElements = document.querySelectorAll('.dropdown-toggle');
    dropdownElements.forEach(function(dropdown) {
        new bootstrap.Dropdown(dropdown);
    });

    // --- Sidebar Mobile Toggle ---
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileToggle = document.getElementById('mobileToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }

    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.remove('show');
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (sidebar && window.innerWidth < 992) {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnSidebarToggle = sidebarToggle && sidebarToggle.contains(event.target);
            const isClickOnMobileToggle = mobileToggle && mobileToggle.contains(event.target);
            
            if (!isClickInsideSidebar && !isClickOnSidebarToggle && !isClickOnMobileToggle) {
                sidebar.classList.remove('show');
            }
        }
    });

    // --- Toast / Flash Message Dismissal ---
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(function(toast) {
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            toast.style.animation = 'fadeOut 0.5s ease forwards';
            setTimeout(() => {
                const container = toast.parentElement;
                toast.remove();
                if(container && container.childElementCount === 0) {
                    container.remove();
                }
            }, 500);
        }, 5000);
    });

    // --- Form Unsaved Changes Warning (Optional Enhancement) ---
    // Could be implemented for large forms if needed.
    
    // --- Auto-format Amount Inputs globally where not specified inline ---
    const amountInputs = document.querySelectorAll('.currency-format');
    amountInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            let val = this.value.replace(/\D/g, '');
            if (val !== '') {
                val = parseInt(val);
                this.value = new Intl.NumberFormat('es-CO').format(val);
            }
        });
        
        // Format initial value if present
        if(input.value) {
            let val = input.value.replace(/\D/g, '');
            if (val !== '') {
                val = parseInt(val);
                input.value = new Intl.NumberFormat('es-CO').format(val);
            }
        }
    });

});

/**
 * Toggles visibility of a password input
 * @param {string} inputId The ID of the password input
 */
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    // Look for the icon inside the sibling/parent button
    const container = input.parentElement;
    const icon = container.querySelector('.password-toggle i');
    
    if (input.type === 'password') {
        input.type = 'text';
        if(icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    } else {
        input.type = 'password';
        if(icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
}