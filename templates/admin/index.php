<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MX-Mall - 管理后台</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/admin/admin.css?v=5">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <h2>MX-Mall</h2>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item active" data-module="dashboard" onclick="Admin.loadModule('dashboard')">
                    <i class="bi bi-grid-1x2"></i>
                    <span>仪表盘</span>
                </div>
                <div class="nav-item" data-module="products" onclick="Admin.loadModule('products')">
                    <i class="bi bi-box-seam"></i>
                    <span>商品管理</span>
                </div>
                <div class="nav-item" data-module="categories" onclick="Admin.loadModule('categories')">
                    <i class="bi bi-tags"></i>
                    <span>分类管理</span>
                </div>
                <div class="nav-item" data-module="orders" onclick="Admin.loadModule('orders')">
                    <i class="bi bi-receipt"></i>
                    <span>订单管理</span>
                </div>
                <div class="nav-item" data-module="users" onclick="Admin.loadModule('users')">
                    <i class="bi bi-people"></i>
                    <span>用户管理</span>
                </div>
                <div class="nav-item" data-module="user-groups" onclick="Admin.loadModule('user-groups')">
                    <i class="bi bi-person-badge"></i>
                    <span>用户分组</span>
                </div>
                <div class="nav-item" data-module="payment" onclick="Admin.loadModule('payment')">
                    <i class="bi bi-credit-card"></i>
                    <span>支付配置</span>
                </div>
                <div class="nav-item" data-module="withdrawals" onclick="Admin.loadModule('withdrawals')">
                    <i class="bi bi-cash-stack"></i>
                    <span>提现管理</span>
                </div>
                <div class="nav-item" data-module="settings" onclick="Admin.loadModule('settings')">
                    <i class="bi bi-gear"></i>
                    <span>系统设置</span>
                </div>
            </nav>
        </aside>

        <!-- Main Area -->
        <div class="main-area">
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-left">
                    <span class="topbar-title" id="pageTitle">仪表盘</span>
                </div>
                <div class="topbar-right">
                    <div class="admin-dropdown" id="adminDropdown">
                        <div class="admin-info" onclick="toggleAdminMenu()">
                            <i class="bi bi-shield-check"></i>
                            <span id="adminName">管理员</span>
                            <i class="bi bi-chevron-down" style="font-size:12px;margin-left:4px;"></i>
                        </div>
                        <div class="admin-dropdown-menu" id="adminDropdownMenu">
                            <a href="javascript:void(0)" onclick="showChangePassword()">
                                <i class="bi bi-key"></i> 修改密码
                            </a>
                            <a href="javascript:void(0)" onclick="Admin.logout()">
                                <i class="bi bi-box-arrow-right"></i> 退出登录
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="content-area" id="content">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <span>加载中...</span>
                </div>
            </main>

            <!-- Footer -->
            <footer class="admin-footer">
                &copy; 2026 MX-Mall
            </footer>
        </div>
    </div>

    <script src="/assets/admin/admin.js?v=5"></script>
    <script>
        // Auth check
        if (!localStorage.getItem('admin_token')) {
            window.location.href = '/admin.php?page=login';
        }

        // 管理员下拉菜单
        function toggleAdminMenu() {
            const menu = document.getElementById('adminDropdownMenu');
            menu.classList.toggle('show');
        }
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('adminDropdown');
            if (!dropdown.contains(e.target)) {
                document.getElementById('adminDropdownMenu').classList.remove('show');
            }
        });

        // 修改密码弹窗
        function showChangePassword() {
            document.getElementById('adminDropdownMenu').classList.remove('show');
            // 移除已有弹窗
            closePasswordModal();
            const modal = document.createElement('div');
            modal.id = 'passwordModal';
            modal.innerHTML = `
                <div class="modal-overlay show" onclick="closePasswordModal()">
                    <div class="modal" style="max-width:400px;" onclick="event.stopPropagation()">
                        <div class="modal-header">
                            <h3><i class="bi bi-key"></i> 修改密码</h3>
                            <button class="modal-close" onclick="closePasswordModal()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="passwordForm">
                                <div class="form-group">
                                    <label>当前密码</label>
                                    <input type="password" name="old_password" class="form-control" placeholder="请输入当前密码" required>
                                </div>
                                <div class="form-group">
                                    <label>新密码</label>
                                    <input type="password" name="new_password" class="form-control" placeholder="请输入新密码（至少6位）" required>
                                </div>
                                <div class="form-group">
                                    <label>确认新密码</label>
                                    <input type="password" name="confirm_password" class="form-control" placeholder="请再次输入新密码" required>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" onclick="closePasswordModal()">取消</button>
                            <button class="btn btn-primary" onclick="changePassword()">确认修改</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function closePasswordModal() {
            const modal = document.getElementById('passwordModal');
            if (modal) modal.remove();
        }

        async function changePassword() {
            const formData = Admin.getFormData('passwordForm');
            if (!formData.old_password || !formData.new_password || !formData.confirm_password) {
                Admin.toast('请填写所有字段', 'error');
                return;
            }
            if (formData.new_password.length < 6) {
                Admin.toast('新密码至少6位', 'error');
                return;
            }
            if (formData.new_password !== formData.confirm_password) {
                Admin.toast('两次密码不一致', 'error');
                return;
            }
            try {
                const res = await Admin.post('/password', {
                    old_password: formData.old_password,
                    new_password: formData.new_password,
                    confirm_password: formData.confirm_password
                });
                if (res && res.code === 0) {
                    Admin.toast('密码修改成功，请重新登录', 'success');
                    closePasswordModal();
                    setTimeout(function() {
                        localStorage.removeItem('admin_token');
                        window.location.href = '/admin.php?page=login';
                    }, 1500);
                } else {
                    Admin.toast(res?.msg || '修改失败', 'error');
                }
            } catch (e) {
                Admin.toast(e.message || '修改失败', 'error');
            }
        }

        // 从URL参数获取当前模块（刷新时保持）
        const urlParams = new URLSearchParams(window.location.search);
        const initialPage = urlParams.get('page') || 'dashboard';
        if (initialPage !== 'index') {
            Admin.loadModule(initialPage);
        } else {
            Admin.loadModule('dashboard');
        }
    </script>
</body>
</html>
