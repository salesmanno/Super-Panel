<style>
.toast-notification-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 360px;
    width: 100%;
    pointer-events: none;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.toast-notification {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    animation: slideInRight 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    pointer-events: auto;
    position: relative;
    transition: all 0.3s ease;
}

.toast-notification:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.16);
    transform: translateY(-2px);
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
        transform: translateX(0) scale(1);
        opacity: 1;
    }
    to {
        transform: translateX(400px) scale(0.9);
        opacity: 0;
    }
}

.toast-notification.hiding {
    animation: slideOutRight 0.25s ease-in forwards;
}

.toast-notification-content {
    padding: 14px 16px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.toast-notification-icon {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
}

.toast-notification-icon i {
    font-size: 18px;
}

.toast-notification-main {
    flex: 1;
    min-width: 0;
}

.toast-notification-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.toast-notification-title {
    font-weight: 600;
    font-size: 14px;
    color: #1a1a1a;
    line-height: 1.3;
    display: flex;
    align-items: center;
    gap: 6px;
}

.toast-notification-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    background: rgba(124, 58, 237, 0.1);
    color: #7c3aed;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
}

.toast-notification-close {
    background: none;
    border: none;
    color: #9ca3af;
    font-size: 18px;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.2s;
    flex-shrink: 0;
}

.toast-notification-close:hover {
    background: rgba(0, 0, 0, 0.05);
    color: #1a1a1a;
}

.toast-notification-body {
    color: #6b7280;
    font-size: 13px;
    line-height: 1.5;
    margin-top: 4px;
    word-wrap: break-word;
}

.toast-notification-progress {
    height: 3px;
    background: rgba(0, 0, 0, 0.05);
    overflow: hidden;
    position: relative;
}

.toast-notification-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
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
        rgba(255, 255, 255, 0.4),
        transparent
    );
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Type-specific styling */
.toast-notification.toast-success .toast-notification-icon {
    color: #10b981;
}

.toast-notification.toast-error .toast-notification-icon {
    color: #ef4444;
}

.toast-notification.toast-warning .toast-notification-icon {
    color: #f59e0b;
}

.toast-notification.toast-info .toast-notification-icon {
    color: #3b82f6;
}

.toast-notification.toast-loading .toast-notification-icon {
    color: #8b5cf6;
}

.toast-notification.toast-loading .toast-notification-icon i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.toast-notification.toast-success .toast-notification-progress-bar {
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

.toast-notification.toast-error .toast-notification-progress-bar {
    background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
}

.toast-notification.toast-warning .toast-notification-progress-bar {
    background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
}

.toast-notification.toast-info .toast-notification-progress-bar {
    background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
}

.toast-notification.toast-loading .toast-notification-progress-bar {
    background: linear-gradient(90deg, #8b5cf6 0%, #7c3aed 100%);
}

/* Accent line on top */
.toast-notification::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    opacity: 0.8;
}

.toast-notification.toast-success::before {
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

.toast-notification.toast-error::before {
    background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
}

.toast-notification.toast-warning::before {
    background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
}

.toast-notification.toast-info::before {
    background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
}

.toast-notification.toast-loading::before {
    background: linear-gradient(90deg, #8b5cf6 0%, #7c3aed 100%);
}
</style>

<div class="toast-notification-container" id="toastContainer"></div>

<script>
class ToastNotification {
    constructor() {
        this.container = document.getElementById('toastContainer');
        this.toasts = [];
        this.queue = [];
        this.maxVisible = 3;
        this.duplicateMap = new Map();
    }

    show(title, message, type = 'info', options = {}) {
        const {
            duration = 5000,
            showProgress = true,
            autoClose = true,
            onClose = null
        } = options;

        const key = `${type}:${title}:${message}`;
        
        // Check for duplicate
        if (this.duplicateMap.has(key)) {
            const existingToast = this.duplicateMap.get(key);
            existingToast.count++;
            this.updateCount(existingToast.id, existingToast.count);
            
            // Reset timer
            if (existingToast.autoCloseTimer) {
                clearTimeout(existingToast.autoCloseTimer);
            }
            if (autoClose && duration > 0) {
                existingToast.autoCloseTimer = setTimeout(() => {
                    this.close(existingToast.id);
                }, duration);
            }
            
            return existingToast.id;
        }

        // If we have too many visible toasts, queue this one
        const visibleCount = this.toasts.filter(t => t.visible).length;
        if (visibleCount >= this.maxVisible) {
            this.queue.push({ title, message, type, options });
            return null;
        }

        return this._createToast(title, message, type, { duration, showProgress, autoClose, onClose, key });
    }

    _createToast(title, message, type, config) {
        const { duration, showProgress, autoClose, onClose, key } = config;
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
            <div class="toast-notification-content">
                <div class="toast-notification-icon">
                    <i class="bi ${icon}"></i>
                </div>
                <div class="toast-notification-main">
                    <div class="toast-notification-header">
                        <div class="toast-notification-title">
                            <span class="toast-title-text">${title}</span>
                        </div>
                        <button class="toast-notification-close" onclick="window.toast.close('${toastId}')">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div class="toast-notification-body">${message}</div>
                </div>
            </div>
            ${showProgress ? `
                <div class="toast-notification-progress">
                    <div class="toast-notification-progress-bar" style="width: 100%"></div>
                </div>
            ` : ''}
        `;

        this.container.appendChild(toast);
        
        const toastData = { 
            id: toastId, 
            element: toast, 
            onClose,
            visible: true,
            count: 1,
            key,
            autoCloseTimer: null
        };
        
        this.toasts.push(toastData);
        
        if (key) {
            this.duplicateMap.set(key, toastData);
        }

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
            toastData.autoCloseTimer = setTimeout(() => {
                this.close(toastId);
            }, duration);
        }

        return toastId;
    }

    updateCount(toastId, count) {
        const toastData = this.toasts.find(t => t.id === toastId);
        if (!toastData) return;

        const titleElement = toastData.element.querySelector('.toast-notification-title');
        if (!titleElement) return;

        let countBadge = titleElement.querySelector('.toast-notification-count');
        if (!countBadge) {
            countBadge = document.createElement('span');
            countBadge.className = 'toast-notification-count';
            titleElement.appendChild(countBadge);
        }
        
        countBadge.textContent = count;
    }

    close(toastId) {
        const toastData = this.toasts.find(t => t.id === toastId);
        if (!toastData) return;

        const toast = toastData.element;
        toastData.visible = false;
        
        // Clear timer
        if (toastData.autoCloseTimer) {
            clearTimeout(toastData.autoCloseTimer);
        }
        
        // Remove from duplicate map
        if (toastData.key) {
            this.duplicateMap.delete(toastData.key);
        }

        toast.classList.add('hiding');

        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
            this.toasts = this.toasts.filter(t => t.id !== toastId);
            
            if (toastData.onClose) {
                toastData.onClose();
            }
            
            // Process queue
            this.processQueue();
        }, 250);
    }

    processQueue() {
        const visibleCount = this.toasts.filter(t => t.visible).length;
        if (visibleCount < this.maxVisible && this.queue.length > 0) {
            const next = this.queue.shift();
            this._createToast(next.title, next.message, next.type, {
                ...next.options,
                key: `${next.type}:${next.title}:${next.message}`
            });
        }
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
        
        const titleElement = toast.querySelector('.toast-title-text');
        const iconElement = toast.querySelector('.toast-notification-icon i');
        const messageElement = toast.querySelector('.toast-notification-body');
        
        if (titleElement) titleElement.textContent = title;
        if (iconElement) iconElement.className = `bi ${iconMap[type] || iconMap.info}`;
        if (messageElement) messageElement.textContent = message;
        
        // Update key in duplicate map
        if (toastData.key) {
            this.duplicateMap.delete(toastData.key);
        }
        toastData.key = `${type}:${title}:${message}`;
        this.duplicateMap.set(toastData.key, toastData);
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
            
            toast.autoCloseTimer = setTimeout(() => {
                this.close(toastId);
            }, 3000);
        }
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
            
            toast.autoCloseTimer = setTimeout(() => {
                this.close(toastId);
            }, 5000);
        }
    }

    quickSuccess(title, message) {
        const key = `success:${title}:${message || 'Vellykket'}`;
        
        // Check for duplicate
        if (this.duplicateMap.has(key)) {
            const existingToast = this.duplicateMap.get(key);
            existingToast.count++;
            this.updateCount(existingToast.id, existingToast.count);
            
            // Reset timer
            if (existingToast.autoCloseTimer) {
                clearTimeout(existingToast.autoCloseTimer);
            }
            existingToast.autoCloseTimer = setTimeout(() => {
                this.close(existingToast.id);
            }, 3000);
            
            return existingToast.id;
        }
        
        return this.show(title, message || 'Vellykket', 'success', { duration: 3000 });
    }

    quickError(title, message) {
        const key = `error:${title}:${message || 'En feil oppstod'}`;
        
        // Check for duplicate
        if (this.duplicateMap.has(key)) {
            const existingToast = this.duplicateMap.get(key);
            existingToast.count++;
            this.updateCount(existingToast.id, existingToast.count);
            
            // Reset timer
            if (existingToast.autoCloseTimer) {
                clearTimeout(existingToast.autoCloseTimer);
            }
            existingToast.autoCloseTimer = setTimeout(() => {
                this.close(existingToast.id);
            }, 5000);
            
            return existingToast.id;
        }
        
        return this.show(title, message || 'En feil oppstod', 'error', { duration: 5000 });
    }

    quickWarning(title, message) {
        const key = `warning:${title}:${message || 'Advarsel'}`;
        
        // Check for duplicate
        if (this.duplicateMap.has(key)) {
            const existingToast = this.duplicateMap.get(key);
            existingToast.count++;
            this.updateCount(existingToast.id, existingToast.count);
            
            // Reset timer
            if (existingToast.autoCloseTimer) {
                clearTimeout(existingToast.autoCloseTimer);
            }
            existingToast.autoCloseTimer = setTimeout(() => {
                this.close(existingToast.id);
            }, 4000);
            
            return existingToast.id;
        }
        
        return this.show(title, message || 'Advarsel', 'warning', { duration: 4000 });
    }

    quickInfo(title, message) {
        const key = `info:${title}:${message || 'Informasjon'}`;
        
        // Check for duplicate
        if (this.duplicateMap.has(key)) {
            const existingToast = this.duplicateMap.get(key);
            existingToast.count++;
            this.updateCount(existingToast.id, existingToast.count);
            
            // Reset timer
            if (existingToast.autoCloseTimer) {
                clearTimeout(existingToast.autoCloseTimer);
            }
            existingToast.autoCloseTimer = setTimeout(() => {
                this.close(existingToast.id);
            }, 4000);
            
            return existingToast.id;
        }
        
        return this.show(title, message || 'Informasjon', 'info', { duration: 4000 });
    }
}

window.toastNotificationSystem = new ToastNotification();
window.toast = window.toastNotificationSystem;
</script>
