<!-- Toast Container for Admin Notifications -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <div id="adminToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class='bx bx-check-circle me-2 text-success' id="adminToastIcon"></i>
            <strong class="me-auto" id="adminToastTitle">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="adminToastMessage">
            Message here
        </div>
    </div>
</div>

<script>
// Function to show admin toast notifications
function showAdminToast(title, message, type = 'success', duration = 3000) {
    const toastEl = document.getElementById('adminToast');
    const toastTitle = document.getElementById('adminToastTitle');
    const toastMessage = document.getElementById('adminToastMessage');
    const toastIcon = document.getElementById('adminToastIcon');
    const toastHeader = toastEl.querySelector('.toast-header');
    
    // Set title and message
    toastTitle.textContent = title;
    toastMessage.innerHTML = message.replace(/\n/g, '<br>');
    
    // Set icon and color based on type
    if (type === 'success') {
        toastIcon.className = 'bx bx-check-circle me-2 text-success';
        toastHeader.style.backgroundColor = '#d1e7dd';
        toastHeader.style.color = '#0f5132';
    } else if (type === 'error') {
        toastIcon.className = 'bx bx-error-circle me-2 text-danger';
        toastHeader.style.backgroundColor = '#f8d7da';
        toastHeader.style.color = '#842029';
    } else if (type === 'warning') {
        toastIcon.className = 'bx bx-error me-2 text-warning';
        toastHeader.style.backgroundColor = '#fff3cd';
        toastHeader.style.color = '#856404';
    } else {
        toastIcon.className = 'bx bx-info-circle me-2 text-info';
        toastHeader.style.backgroundColor = '#d1ecf1';
        toastHeader.style.color = '#0c5460';
    }
    
    // Show toast
    const toast = new bootstrap.Toast(toastEl, { delay: duration });
    toast.show();
}

if (!window.__adminAlertToToastInitialized) {
    window.__adminAlertToToastInitialized = true;

    window.showAppToast = function(message, type = 'info') {
        const toastType = type === 'error' ? 'error' : (type === 'warning' ? 'warning' : (type === 'success' ? 'success' : 'info'));
        const toastTitle = toastType === 'error' ? 'Error' : (toastType === 'warning' ? 'Warning' : (toastType === 'success' ? 'Success' : 'Notification'));
        showAdminToast(toastTitle, String(message), toastType, 3500);
    };

    window.alert = function(message) {
        window.showAppToast(message, 'warning');
    };
}
</script>
