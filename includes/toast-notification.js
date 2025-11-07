class ToastNotification {
    constructor() {
        this.container = null;
        this.activeToasts = new Map();
        this.init();
    }

    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'toast-notification-container';
            document.body.appendChild(this.container);
        }
    }

    show(processName, type = 'loading', message = '', duration = 3000) {
        const toastId = `toast-${Date.now()}-${Math.random()}`;
        
        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.setAttribute('data-toast-id', toastId);
        
        const iconMap = {
            loading: '<div class="spinner-border spinner-border-sm me-2" role="status"></div>',
            success: '<i class="bi bi-check-circle-fill me-2"></i>',
            error: '<i class="bi bi-exclamation-circle-fill me-2"></i>',
            warning: '<i class="bi bi-exclamation-triangle-fill me-2"></i>',
            info: '<i class="bi bi-info-circle-fill me-2"></i>'
        };
        
        const messageMap = {
            loading: `${processName} pågår...`,
            success: message || `${processName} vellykket!`,
            error: message || `${processName} mislykket`,
            warning: message || processName,
            info: message || processName
        };
        
        toast.innerHTML = `
            <div class="toast-content">
                <div class="toast-header-custom">
                    ${iconMap[type] || ''}
                    <span class="toast-message">${messageMap[type]}</span>
                    ${type !== 'loading' ? '<button type="button" class="toast-close" onclick="toastManager.remove(\''+toastId+'\')">&times;</button>' : ''}
                </div>
                ${type === 'loading' ? '<div class="toast-progress"><div class="toast-progress-bar"></div></div>' : ''}
            </div>
        `;
        
        this.container.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 10);
        
        if (type === 'loading') {
            const progressBar = toast.querySelector('.toast-progress-bar');
            let progress = 0;
            const interval = setInterval(() => {
                progress += 1;
                if (progressBar) {
                    progressBar.style.width = `${progress}%`;
                }
                if (progress >= 100) {
                    clearInterval(interval);
                }
            }, duration / 100);
            
            this.activeToasts.set(toastId, { toast, interval, processName });
        } else {
            this.activeToasts.set(toastId, { toast, processName });
            
            if (type !== 'error') {
                setTimeout(() => {
                    this.remove(toastId);
                }, duration);
            }
        }
        
        return toastId;
    }

    update(toastId, type, message = '') {
        const toastData = this.activeToasts.get(toastId);
        if (!toastData) return;
        
        const { toast, interval, processName } = toastData;
        
        if (interval) {
            clearInterval(interval);
        }
        
        toast.className = `toast-notification toast-${type} show`;
        
        const iconMap = {
            success: '<i class="bi bi-check-circle-fill me-2"></i>',
            error: '<i class="bi bi-exclamation-circle-fill me-2"></i>',
            warning: '<i class="bi bi-exclamation-triangle-fill me-2"></i>',
            info: '<i class="bi bi-info-circle-fill me-2"></i>'
        };
        
        const messageMap = {
            success: message || `${processName} vellykket!`,
            error: message || `${processName} mislykket`,
            warning: message || processName,
            info: message || processName
        };
        
        const progressBar = toast.querySelector('.toast-progress-bar');
        if (progressBar) {
            progressBar.style.width = '100%';
        }
        
        setTimeout(() => {
            toast.innerHTML = `
                <div class="toast-content">
                    <div class="toast-header-custom">
                        ${iconMap[type] || ''}
                        <span class="toast-message">${messageMap[type]}</span>
                        <button type="button" class="toast-close" onclick="toastManager.remove('${toastId}')">&times;</button>
                    </div>
                </div>
            `;
        }, 300);
        
        this.activeToasts.set(toastId, { toast, processName });
        
        if (type !== 'error') {
            setTimeout(() => {
                this.remove(toastId);
            }, 3000);
        }
    }

    remove(toastId) {
        const toastData = this.activeToasts.get(toastId);
        if (!toastData) return;
        
        const { toast, interval } = toastData;
        
        if (interval) {
            clearInterval(interval);
        }
        
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
            this.activeToasts.delete(toastId);
        }, 300);
    }

    success(processName, message = '') {
        return this.show(processName, 'success', message);
    }

    error(processName, message = '') {
        return this.show(processName, 'error', message, 5000);
    }

    warning(processName, message = '') {
        return this.show(processName, 'warning', message);
    }

    info(processName, message = '') {
        return this.show(processName, 'info', message);
    }

    loading(processName, duration = 3000) {
        const toastId = this.show(processName, 'loading', '', duration);
        return {
            id: toastId,
            success: (message) => this.update(toastId, 'success', message),
            error: (message) => this.update(toastId, 'error', message),
            warning: (message) => this.update(toastId, 'warning', message),
            info: (message) => this.update(toastId, 'info', message),
            remove: () => this.remove(toastId)
        };
    }
}

const toastManager = new ToastNotification();
