/* ============================================
   MX-Mall - Admin Panel JavaScript
   Modern UI interactions and animations
   ============================================ */

// ============ Core Admin Object ============
const Admin = {
    // API base URL
    apiBase: '/api/admin',

    // Current module
    currentModule: 'dashboard',

    // Module titles
    moduleTitles: {
        dashboard: '仪表盘',
        products: '商品管理',
        categories: '分类管理',
        orders: '订单管理',
        users: '用户管理',
        'user-groups': '用户分组',
        payment: '支付配置',
        settings: '系统设置',
        withdrawals: '提现管理'
    },

    // ============ API Request ============
    async request(url, options = {}) {
        const token = localStorage.getItem('admin_token');
        if (!token) {
            window.location.href = '/admin/login';
            return null;
        }

        const config = {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            ...options
        };

        try {
            const response = await fetch(this.apiBase + url, config);
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                // 如果不是JSON，可能是授权失败的HTML页面
                if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                    // 直接显示授权失败页面
                    document.open();
                    document.write(text);
                    document.close();
                    return null;
                }
                console.error('JSON parse failed:', text);
                this.toast('服务器响应异常', 'error');
                return null;
            }

            if (response.status === 401) {
                localStorage.removeItem('admin_token');
                window.location.href = '/admin/login';
                return null;
            }

            return data;
        } catch (error) {
            console.error('API request failed:', error);
            this.toast('网络请求失败，请稍后重试', 'error');
            return null;
        }
    },

    get(url) {
        return this.request(url, { method: 'GET' });
    },

    post(url, body) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(body)
        });
    },

    put(url, body) {
        return this.request(url, {
            method: 'PUT',
            body: JSON.stringify(body)
        });
    },

    delete(url) {
        return this.request(url, { method: 'DELETE' });
    },

    // ============ Module Loading ============
    async loadModule(module) {
        this.currentModule = module;
        var content = document.getElementById('content');
        var title = document.getElementById('pageTitle');

        // Update URL
        history.replaceState(null, '', '/admin/' + module);

        if (!content) return;

        // Update title
        if (title) {
            title.style.opacity = '0';
            title.style.transform = 'translateY(-6px)';
            setTimeout(function() {
                title.textContent = Admin.moduleTitles[module] || module;
                title.style.transition = 'all 0.35s cubic-bezier(0.16, 1, 0.3, 1)';
                title.style.opacity = '1';
                title.style.transform = 'translateY(0)';
            }, 120);
        }

        // Update active nav
        document.querySelectorAll('.nav-item').forEach(function(item) {
            item.classList.remove('active');
            if (item.dataset.module === module) {
                item.classList.add('active');
            }
        });

        // Close mobile sidebar after navigation
        if (window.innerWidth <= 768) {
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebarOverlay');
            if (sidebar) sidebar.classList.remove('mobile-open');
            if (overlay) overlay.classList.remove('show');
        }

        // Fade out then load
        content.style.opacity = '0';
        content.style.transform = 'translateY(8px)';
        content.style.transition = 'opacity 0.2s ease, transform 0.2s ease';

        setTimeout(function() {
            content.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><span>加载中...</span></div>';
            content.style.opacity = '1';
            content.style.transform = 'translateY(0)';
        }, 180);

        try {
            var response = await fetch('/admin/' + module, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error('Module not found');
            var html = await response.text();

            content.style.opacity = '0';
            content.style.transform = 'translateY(8px)';

            setTimeout(function() {
                content.innerHTML = html;
                content.style.opacity = '1';
                content.style.transform = 'translateY(0)';

                // Re-execute scripts
                var scripts = content.querySelectorAll('script');
                scripts.forEach(function(oldScript) {
                    var newScript = document.createElement('script');
                    if (oldScript.src) {
                        newScript.src = oldScript.src;
                    } else {
                        newScript.textContent = oldScript.textContent;
                    }
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });

                // Execute module init
                var initFnName = 'init_' + module.replace(/-/g, '_');
                if (typeof window[initFnName] === 'function') {
                    try {
                        var result = window[initFnName]();
                        if (result && typeof result.catch === 'function') {
                            result.catch(function(e) { console.error('Module init error:', e); });
                        }
                    } catch(e) { console.error('Module init error:', e); }
                }

                Admin.initContentAnimations();
            }, 180);
        } catch (error) {
            console.error('Module load error:', error);
            content.innerHTML = '<div class="empty-state"><i class="bi bi-exclamation-triangle"></i><p>模块加载失败: ' + error.message + '</p></div>';
            content.style.opacity = '1';
            content.style.transform = 'translateY(0)';
        }
    },

    // ============ Content Animations ============
    initContentAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.stat-card, .template-card').forEach((el, i) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = `opacity 0.5s ease ${i * 0.05}s, transform 0.5s ease ${i * 0.05}s`;
            observer.observe(el);
        });
    },

    // ============ Toast Notifications ============
    toast(message, type = 'info', duration = 3000) {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const icons = {
            success: 'bi-check-circle-fill',
            error: 'bi-x-circle-fill',
            warning: 'bi-exclamation-triangle-fill',
            info: 'bi-info-circle-fill'
        };

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `<i class="bi ${icons[type] || icons.info}"></i><span>${message}</span>`;
        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.style.animation = 'toastIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
        });

        setTimeout(() => {
            toast.classList.add('toast-out');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    // ============ Confirm Dialog ============
    confirm(title, message) {
        return new Promise((resolve) => {
            let overlay = document.querySelector('.confirm-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'confirm-overlay';
                overlay.innerHTML = `
                    <div class="confirm-box">
                        <div class="confirm-icon"><i class="bi bi-question-circle"></i></div>
                        <div class="confirm-title"></div>
                        <div class="confirm-message"></div>
                        <div class="confirm-actions">
                            <button class="btn btn-secondary" id="confirmCancel">取消</button>
                            <button class="btn btn-danger" id="confirmOk">确定</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(overlay);
            }

            overlay.querySelector('.confirm-title').textContent = title;
            overlay.querySelector('.confirm-message').textContent = message;

            const okBtn = overlay.querySelector('#confirmOk');
            const cancelBtn = overlay.querySelector('#confirmCancel');

            const cleanup = (result) => {
                overlay.classList.remove('show');
                okBtn.replaceWith(okBtn.cloneNode(true));
                cancelBtn.replaceWith(cancelBtn.cloneNode(true));
                resolve(result);
            };

            okBtn.onclick = () => cleanup(true);
            cancelBtn.onclick = () => cleanup(false);
            overlay.onclick = (e) => {
                if (e.target === overlay) cleanup(false);
            };

            overlay.classList.add('show');
        });
    },

    // ============ Modal Management ============
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';

            // Focus first input after animation
            setTimeout(() => {
                const firstInput = modal.querySelector('input, select, textarea');
                if (firstInput) firstInput.focus();
            }, 300);
        }
    },

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    },

    // ============ Form Helpers ============
    getFormData(formId) {
        const form = document.getElementById(formId);
        if (!form) return {};

        const data = {};
        const elements = form.querySelectorAll('input, select, textarea');

        elements.forEach(el => {
            if (el.name) {
                if (el.type === 'checkbox') {
                    data[el.name] = el.checked ? 1 : 0;
                } else if (el.type === 'number') {
                    data[el.name] = parseFloat(el.value) || 0;
                } else {
                    data[el.name] = el.value.trim();
                }
            }
        });

        return data;
    },

    setFormData(formId, data) {
        const form = document.getElementById(formId);
        if (!form || !data) return;

        const elements = form.querySelectorAll('input, select, textarea');
        elements.forEach(el => {
            if (el.name && data[el.name] !== undefined) {
                if (el.type === 'checkbox') {
                    el.checked = parseInt(data[el.name]) === 1;
                } else {
                    el.value = data[el.name];
                }
            }
        });
    },

    validateRequired(formId) {
        const form = document.getElementById(formId);
        if (!form) return true;

        const required = form.querySelectorAll('[required]');
        let valid = true;

        required.forEach(el => {
            el.style.borderColor = '';
            el.style.boxShadow = '';
            if (!el.value.trim()) {
                el.style.borderColor = 'var(--danger)';
                el.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.15)';
                valid = false;

                // Shake animation
                el.style.animation = 'shake 0.5s ease';
                setTimeout(() => {
                    el.style.animation = '';
                }, 500);
            }
        });

        if (!valid) {
            this.toast('请填写所有必填项', 'warning');
        }

        return valid;
    },

    // ============ Pagination Helper ============
    pagination: {
        page: 1,
        perPage: 15,
        total: 0,

        init(total, perPage = 15) {
            this.page = 1;
            this.perPage = perPage;
            this.total = total;
        },

        getInfo() {
            const start = (this.page - 1) * this.perPage + 1;
            const end = Math.min(this.page * this.perPage, this.total);
            return this.total > 0 ? `${start}-${end} / ${this.total}` : '0 条记录';
        },

        getTotalPages() {
            return Math.ceil(this.total / this.perPage);
        },

        hasPrev() {
            return this.page > 1;
        },

        hasNext() {
            return this.page < this.getTotalPages();
        },

        prev() {
            if (this.hasPrev()) this.page--;
        },

        next() {
            if (this.hasNext()) this.page++;
        }
    },

    // ============ Format Helpers ============
    formatMoney(amount) {
        return '¥' + parseFloat(amount || 0).toFixed(2);
    },

    formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        const pad = n => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    },

    // ============ Status Helpers ============
    statusBadge(status) {
        const map = {
            // Order status (number)
            '0': '<span class="badge badge-warning">待支付</span>',
            '1': '<span class="badge badge-success">已支付</span>',
            '2': '<span class="badge badge-danger">已退款</span>',
            '3': '<span class="badge badge-muted">已过期</span>',
            // Order status (text)
            'paid': '<span class="badge badge-success">已支付</span>',
            'unpaid': '<span class="badge badge-warning">待支付</span>',
            'refunded': '<span class="badge badge-danger">已退款</span>',
            'expired': '<span class="badge badge-muted">已过期</span>',
            // General status
            'active': '<span class="badge badge-success">启用</span>',
            'inactive': '<span class="badge badge-muted">禁用</span>',
            'success': '<span class="badge badge-success">成功</span>',
            'failed': '<span class="badge badge-danger">失败</span>',
            'pending': '<span class="badge badge-warning">处理中</span>'
        };
        return map[status] || map[String(status)] || `<span class="badge badge-muted">${status}</span>`;
    },

    // ============ CSV Export ============
    async exportCsv(url, filename) {
        try {
            const token = localStorage.getItem('admin_token');
            const response = await fetch(this.apiBase + url, {
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) {
                this.toast('导出失败', 'error');
                return;
            }
            const blob = await response.blob();
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(a.href);
            this.toast('导出成功', 'success');
        } catch (e) {
            this.toast('导出失败: ' + e.message, 'error');
        }
    },

    // ============ Logout ============
    logout() {
        localStorage.removeItem('admin_token');
        window.location.href = '/admin/login';
    }
};

// ============ Global Event Listeners ============
document.addEventListener('DOMContentLoaded', function() {
    // Close modal on overlay click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            e.target.classList.remove('show');
            document.body.style.overflow = '';
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.show').forEach(modal => {
                modal.classList.remove('show');
            });
            document.querySelectorAll('.confirm-overlay.show').forEach(overlay => {
                overlay.classList.remove('show');
            });
            document.body.style.overflow = '';
        }
    });

    // Ripple effect for buttons
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-primary, .btn-success, .btn-danger');
        if (!btn) return;

        const ripple = document.createElement('span');
        const rect = btn.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
        `;

        btn.style.position = 'relative';
        btn.style.overflow = 'hidden';
        btn.appendChild(ripple);

        setTimeout(() => ripple.remove(), 600);
    });

    // Add keyframe animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to { transform: scale(2); opacity: 0; }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        @keyframes toastIn {
            from { opacity: 0; transform: translateX(40px) scale(0.9); }
            to { opacity: 1; transform: translateX(0) scale(1); }
        }
    `;
    document.head.appendChild(style);

    // Initialize content animations
    setTimeout(() => {
        if (typeof Admin !== 'undefined' && Admin.initContentAnimations) {
            Admin.initContentAnimations();
        }
    }, 100);
});
