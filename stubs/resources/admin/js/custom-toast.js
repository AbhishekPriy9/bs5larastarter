/**
 * Displays a Bootstrap 5 toast notification in the top-right corner.
 * 
 * @param {string} message The message to display in the toast.
 * @param {string} type The type of toast (success, error, warning, info).
 */
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    
    if (!toastContainer) {
        // Create the container if it doesn't exist in the DOM.
        const container = document.createElement('div');
        container.id = 'toastContainer';
        document.body.appendChild(container);
        // Recurse once to find the newly created container.
        return showToast(message, type);
    }

    toastContainer.className = 'bs-toast-container position-fixed top-0 end-0 p-3';

    // Set a very high z-index to ensure toasts appear above all other content.
    toastContainer.style.zIndex = '9999';

    // Map type to Bootstrap background color class
    const typeMap = {
        success: 'bg-success',
        error: 'bg-danger',
        warning: 'bg-warning',
        info: 'bg-info'
    };
    const bgColorClass = typeMap[type] || 'bg-secondary';
    const toastId = 'toast-' + Date.now();

    const toastHTML = `
        <div id="${toastId}" class="bs-toast toast fade ${bgColorClass}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
            <div class="toast-header">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">${type.charAt(0).toUpperCase() + type.slice(1)}</div>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);

    // Remove the toast from the DOM after it's hidden to prevent clutter
    toastElement.addEventListener('hidden.bs.toast', function () {
        toastElement.remove();
    });

    toast.show();
}