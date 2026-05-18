<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MX-Mall - 管理后台</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/admin/admin.css?v=6">
</head>
<body>
    <div class="admin-layout">
        <!-- Mobile overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

        <!-- Floating Sidebar Card -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <h2>MX-Mall</h2>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">主菜单</div>
                <div class="nav-item active" data-module="dashboard" onclick="Admin.loadModule('dashboard')">
                    <i class="bi bi-grid-1x2"></i>
                    <span>仪表盘</span>
                    <span class="nav-dot"></span>
                </div>

                <div class="nav-section">商品与交易</div>
                <div class="nav-item" data-module="products" onclick="Admin.loadModule('products')">
                    <i class="bi bi-box-seam"></i>
                    <span>商品管理</span>
                    <span class="nav-dot"></span>
                </div>
                <div class="nav-item" data-module="categories" onclick="Admin.loadModule('categories')">
                    <i class="bi bi-tags"></i>
                    <span>分类管理</span>
                    <span class="nav-dot"></span>
                </div>
                <div class="nav-item" data-module="orders" onclick="Admin.loadModule('orders')">
                    <i class="bi bi-receipt"></i>
                    <span>订单管理</span>
                    <span class="nav-dot"></span>
                </div>

                <div class="nav-section">用户与财务</div>
                <div class="nav-item" data-module="users" onclick="Admin.loadModule('users')">
                    <i class="bi bi-people"></i>
                    <span>用户管理</span>
                    <span class="nav-dot"></span>
                </div>
                <div class="nav-item" data-module="user-groups" onclick="Admin.loadModule('user-groups')">
                    <i class="bi bi-person-badge"></i>
                    <span>用户分组</span>
                    <span class="nav-dot"></span>
                </div>
                <div class="nav-item" data-module="withdrawals" onclick="Admin.loadModule('withdrawals')">
                    <i class="bi bi-cash-stack"></i>
                    <span>提现管理</span>
                    <span class="nav-dot"></span>
                </div>

                <div class="nav-section">系统</div>
                <div class="nav-item" data-module="payment" onclick="Admin.loadModule('payment')">
                    <i class="bi bi-credit-card"></i>
                    <span>支付配置</span>
                    <span class="nav-dot"></span>
                </div>
                <div class="nav-item" data-module="settings" onclick="Admin.loadModule('settings')">
                    <i class="bi bi-gear"></i>
                    <span>系统设置</span>
                    <span class="nav-dot"></span>
                </div>
            </nav>
            <div class="sidebar-footer">
                <div class="user-mini" onclick="toggleAdminMenu()" title="账号管理">
                    <div class="user-avatar" id="sidebarAvatar">A</div>
                    <div class="user-meta">
                        <div class="user-name" id="sidebarName">管理员</div>
                        <div class="user-role" id="sidebarRole">超级管理员</div>
                    </div>
                    <i class="bi bi-chevron-up sidebar-chevron" id="sidebarChevron"></i>
                </div>
                <div class="sidebar-user-menu" id="sidebarUserMenu">
                    <a href="javascript:void(0)" onclick="showChangePassword(); document.getElementById('sidebarUserMenu').classList.remove('show'); document.getElementById('sidebarChevron').classList.remove('rotated');">
                        <i class="bi bi-key"></i> 修改密码
                    </a>
                    <a href="javascript:void(0)" onclick="Admin.logout()">
                        <i class="bi bi-box-arrow-right"></i> 退出登录
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Area -->
        <div class="main-area">
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-left">
                    <button class="header-btn mobile-menu-btn" onclick="toggleSidebar()" title="菜单">
                        <i class="bi bi-list"></i>
                    </button>
                    <div>
                        <span class="topbar-title" id="pageTitle">仪表盘</span>
                    </div>
                </div>
                <div class="topbar-right">
                    <button class="header-btn" onclick="window.location.reload()" title="刷新">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <div class="admin-dropdown" id="adminDropdown">
                        <div class="admin-info" onclick="toggleAdminMenu()">
                            <i class="bi bi-shield-check"></i>
                            <span id="adminName">管理员</span>
                            <i class="bi bi-chevron-down" style="font-size:11px;margin-left:2px;"></i>
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

    <script src="/assets/admin/admin.js?v=7"></script>
    <script>
        // Preload module on hover (fast navigation)
        (function() {
            var nav = document.querySelector('.sidebar-nav');
            if (nav) {
                nav.addEventListener('mouseover', function(e) {
                    var item = e.target.closest('.nav-item');
                    if (item && item.dataset.module && typeof Admin !== 'undefined') {
                        Admin.preloadModule(item.dataset.module);
                    }
                });
            }
        })();
    </script>
    <script>
        // Auth check
        if (!localStorage.getItem('admin_token')) {
            window.location.href = '/admin/login';
        }

        // Init sidebar user info
        (function initSidebarUser() {
            try {
                var info = JSON.parse(localStorage.getItem('admin_info') || '{}');
                if (info.nickname || info.username) {
                    var name = info.nickname || info.username;
                    document.getElementById('sidebarName').textContent = name;
                    document.getElementById('sidebarAvatar').textContent = name.charAt(0).toUpperCase();
                    document.getElementById('adminName').textContent = name;
                    document.getElementById('sidebarRole').textContent = info.role === 'super' ? '超级管理员' : '管理员';
                }
            } catch(e) {}
        })();

        // Mobile sidebar toggle
        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('show');
        }

        // Sidebar user menu toggle
        function toggleAdminMenu() {
            if (window.innerWidth <= 768) {
                toggleSidebar();
                return;
            }
            var menu = document.getElementById('sidebarUserMenu');
            var chevron = document.getElementById('sidebarChevron');
            menu.classList.toggle('show');
            chevron.classList.toggle('rotated');
        }

        document.addEventListener('click', function(e) {
            var dropdown = document.getElementById('adminDropdown');
            var sidebarMenu = document.getElementById('sidebarUserMenu');
            var sidebarFooter = document.querySelector('.sidebar-footer');
            if (dropdown && !dropdown.contains(e.target)) {
                document.getElementById('adminDropdownMenu').classList.remove('show');
            }
            if (sidebarMenu && sidebarFooter && !sidebarFooter.contains(e.target)) {
                sidebarMenu.classList.remove('show');
                document.getElementById('sidebarChevron').classList.remove('rotated');
            }
        });

        // Change password modal
        function showChangePassword() {
            document.getElementById('adminDropdownMenu').classList.remove('show');
            closePasswordModal();
            var modal = document.createElement('div');
            modal.id = 'passwordModal';
            modal.innerHTML = `
                <div class="modal-overlay show" onclick="closePasswordModal()">
                    <div class="modal" style="max-width:400px;" onclick="event.stopPropagation()">
                        <div class="modal-header">
                            <h3 class="modal-title"><i class="bi bi-key"></i> 修改密码</h3>
                            <button class="modal-close" onclick="closePasswordModal()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="passwordForm">
                                <div class="form-group">
                                    <label class="form-label">当前密码</label>
                                    <input type="password" name="old_password" class="form-control" placeholder="请输入当前密码" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">新密码</label>
                                    <input type="password" name="new_password" class="form-control" placeholder="请输入新密码（至少6位）" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">确认新密码</label>
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
            var modal = document.getElementById('passwordModal');
            if (modal) modal.remove();
        }

        async function changePassword() {
            var formData = Admin.getFormData('passwordForm');
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
                var res = await Admin.post('/password', {
                    old_password: formData.old_password,
                    new_password: formData.new_password,
                    confirm_password: formData.confirm_password
                });
                if (res && res.code === 0) {
                    Admin.toast('密码修改成功，请重新登录', 'success');
                    closePasswordModal();
                    setTimeout(function() {
                        localStorage.removeItem('admin_token');
                        window.location.href = '/admin/login';
                    }, 1500);
                } else {
                    Admin.toast(res?.msg || '修改失败', 'error');
                }
            } catch (e) {
                Admin.toast(e.message || '修改失败', 'error');
            }
        }

        // Load initial module
        var urlParams = new URLSearchParams(window.location.search);
        var initialPage = urlParams.get('page') || 'dashboard';
        if (initialPage !== 'index') {
            Admin.loadModule(initialPage);
        } else {
            Admin.loadModule('dashboard');
        }
    </script>
</body>
</html>
