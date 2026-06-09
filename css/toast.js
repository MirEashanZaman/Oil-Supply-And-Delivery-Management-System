// Toast system for dynamic feedback
(function() {
    // Create toast container if not exists
    function getContainer() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    window.showToast = function(message, type = 'success') {
        const container = getContainer();
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        let icon = 'v';
        if (type === 'danger') icon = 'x';
        if (type === 'info') icon = 'i';
        if (type === 'warning') icon = '!';

        toast.innerHTML = `
            <div class="toast-icon">${icon}</div>
            <div class="toast-content">${message}</div>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;

        container.appendChild(toast);

        // Force reflow
        toast.offsetHeight;

        // Show
        setTimeout(() => toast.classList.add('show'), 10);

        // Auto hide after 4 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            toast.classList.add('hide');
            setTimeout(() => {
                toast.remove();
            }, 400);
        }, 4000);
    };
})();
