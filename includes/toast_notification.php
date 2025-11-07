<style>
.toast-notification-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 320px;
    width: 100%;
    pointer-events: none;
}

.toast-notification {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(124, 58, 237, 0.15);
    margin-bottom: 10px;
    overflow: hidden;
    animation: slideInRight 0.3s ease-out;
    pointer-events: auto;
    position: relative;
}

.toast-notification::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #7c3aed 0%, #a78bfa 100%);
    z-index: 10;
}

@keyframes slideInRight {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}

.toast-notification.hiding {
    animation: slideOutRight 0.3s ease-in forwards;
}

.toast-notification-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    border-bottom: 1px solid #e9ecef;
}

.toast-notification-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 13px;
    margin: 0;
}

.toast-notification-icon {
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toast-notification-icon i {
    font-size: 16px;
}

.toast-notification-close {
    background: none;
    border: none;
    color: #6c757d;
    font-size: 18px;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s;
}

.toast-notification-close:hover {
    background: #f8f9fa;
    color: #212529;
}

.toast-notification-body {
    padding: 10px 12px;
    color: #495057;
    font-size: 13px;
    line-height: 1.4;
}

.toast-notification-progress {
    height: 4px;
    background: #e9ecef;
    overflow: hidden;
    position: relative;
}

.toast-notification-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
    transition: width 0.3s ease;
    position: relative;
    overflow: hidden;
}

.toast-notification-progress-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.3),
        transparent
    );
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

.toast-notification.toast-success .toast-notification-icon {
    color: #28a745;
}

.toast-notification.toast-error .toast-notification-icon {
    color: #dc3545;
}

.toast-notification.toast-warning .toast-notification-icon {
    color: #ffc107;
}

.toast-notification.toast-info .toast-notification-icon {
    color: #17a2b8;
}

.toast-notification.toast-loading .toast-notification-icon {
    color: #007bff;
}

.toast-notification.toast-loading .toast-notification-icon i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.toast-notification.toast-success .toast-notification-progress-bar {
    background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
}

.toast-notification.toast-error .toast-notification-progress-bar {
    background: linear-gradient(90deg, #dc3545 0%, #e55353 100%);
}

.toast-notification.toast-warning .toast-notification-progress-bar {
    background: linear-gradient(90deg, #ffc107 0%, #ffcd39 100%);
}

.toast-notification.toast-info .toast-notification-progress-bar {
    background: linear-gradient(90deg, #17a2b8 0%, #20c9e0 100%);
}

.toast-notification.toast-loading .toast-notification-progress-bar {
    background: linear-gradient(90deg, #007bff 0%, #0d6efd 100%);
}
</style>

<div class="toast-notification-container" id="toastContainer"></div>

<script>
class ToastNotification {
    constructor() {
        this.container = document.getElementById('toastContainer');
        this.toasts = [];
    }

    show(title, message, type = 'info', options = {}) {
        const {
            duration = 5000,
            showProgress = true,
            autoClose = true,
            onClose = null
        } = options;

        const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        
        const iconMap = {
            success: 'bi-check-circle-fill',
            error: 'bi-x-circle-fill',
            warning: 'bi-exclamation-triangle-fill',
            info: 'bi-info-circle-fill',
            loading: 'bi-arrow-repeat'
        };

        const icon = iconMap[type] || iconMap.info;

        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast-notification toast-${type}`;
        toast.innerHTML = `
            <div class="toast-notification-header">
                <div class="toast-notification-title">
                    <div class="toast-notification-icon">
                        <i class="bi ${icon}"></i>
                    </div>
                    <span>${title}</span>
                </div>
                <button class="toast-notification-close" onclick="window.toastNotificationSystem.close('${toastId}')">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="toast-notification-body">
                ${message}
            </div>
            ${showProgress ? `
                <div class="toast-notification-progress">
                    <div class="toast-notification-progress-bar" style="width: 100%"></div>
                </div>
            ` : ''}
        `;

        this.container.appendChild(toast);
        this.toasts.push({ id: toastId, element: toast, onClose });

        if (showProgress && autoClose && duration > 0) {
            const progressBar = toast.querySelector('.toast-notification-progress-bar');
            if (progressBar) {
                progressBar.style.transition = `width ${duration}ms linear`;
                setTimeout(() => {
                    progressBar.style.width = '0%';
                }, 50);
            }
        }

        if (autoClose && duration > 0) {
            setTimeout(() => {
                this.close(toastId);
            }, duration);
        }

        return toastId;
    }

    close(toastId) {
        const toastData = this.toasts.find(t => t.id === toastId);
        if (!toastData) return;

        const toast = toastData.element;
        toast.classList.add('hiding');

        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
            this.toasts = this.toasts.filter(t => t.id !== toastId);
            
            if (toastData.onClose) {
                toastData.onClose();
            }
        }, 300);
    }

    update(toastId, title, message, type) {
        const toastData = this.toasts.find(t => t.id === toastId);
        if (!toastData) return;

        const toast = toastData.element;
        
        const iconMap = {
            success: 'bi-check-circle-fill',
            error: 'bi-x-circle-fill',
            warning: 'bi-exclamation-triangle-fill',
            info: 'bi-info-circle-fill',
            loading: 'bi-arrow-repeat'
        };

        toast.className = `toast-notification toast-${type}`;
        
        const titleElement = toast.querySelector('.toast-notification-title span');
        const iconElement = toast.querySelector('.toast-notification-icon i');
        const messageElement = toast.querySelector('.toast-notification-body');
        
        if (titleElement) titleElement.textContent = title;
        if (iconElement) iconElement.className = `bi ${iconMap[type] || iconMap.info}`;
        if (messageElement) messageElement.textContent = message;
    }

    loading(processName) {
        return this.show(
            processName,
            'Pågår...',
            'loading',
            { autoClose: false, showProgress: true }
        );
    }

    success(toastId, processName, message = null) {
        this.update(
            toastId,
            processName,
            message || 'Vellykket',
            'success'
        );
        
        const toast = this.toasts.find(t => t.id === toastId);
        if (toast) {
            const progressBar = toast.element.querySelector('.toast-notification-progress-bar');
            if (progressBar) {
                progressBar.style.transition = 'width 3000ms linear';
                progressBar.style.width = '0%';
            }
        }
        
        setTimeout(() => {
            this.close(toastId);
        }, 3000);
    }

    error(toastId, processName, errorMessage) {
        this.update(
            toastId,
            processName,
            'Mislykket: ' + errorMessage,
            'error'
        );
        
        const toast = this.toasts.find(t => t.id === toastId);
        if (toast) {
            const progressBar = toast.element.querySelector('.toast-notification-progress-bar');
            if (progressBar) {
                progressBar.style.transition = 'width 5000ms linear';
                progressBar.style.width = '0%';
            }
        }
        
        setTimeout(() => {
            this.close(toastId);
        }, 5000);
    }

    quickSuccess(title, message) {
        return this.show(title, message || 'Vellykket', 'success', { duration: 3000 });
    }

    quickError(title, message) {
        return this.show(title, message || 'En feil oppstod', 'error', { duration: 5000 });
    }

    quickWarning(title, message) {
        return this.show(title, message || 'Advarsel', 'warning', { duration: 4000 });
    }

    quickInfo(title, message) {
        return this.show(title, message || 'Informasjon', 'info', { duration: 4000 });
    }
}

window.toastNotificationSystem = new ToastNotification();
window.toast = window.toastNotificationSystem;
</script>
