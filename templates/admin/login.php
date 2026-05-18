<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MX-Mall - 管理后台登录</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/admin/admin.css?v=5">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-logo">
                <h1>MX-Mall</h1>
                <p>管理后台</p>
            </div>
            <form id="loginForm" onsubmit="return handleLogin(event)">
                <div class="form-group">
                    <label class="form-label">用户名</label>
                    <div class="input-icon-wrapper" style="position:relative;">
                        <i class="bi bi-person" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:14px;"></i>
                        <input type="text" id="username" name="username" class="form-control" placeholder="请输入用户名" required style="padding-left:36px;" autocomplete="username">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">密码</label>
                    <div style="position:relative;">
                        <i class="bi bi-lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:14px;"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="请输入密码" required style="padding-left:36px;" autocomplete="current-password">
                    </div>
                </div>
                <div id="loginError" style="color:var(--danger);font-size:13px;margin-bottom:12px;display:none;"></div>
                <button type="submit" class="btn btn-primary btn-block" id="loginBtn" style="padding:11px 16px;font-size:14px;margin-top:8px;">
                    <i class="bi bi-box-arrow-in-right"></i> 登 录
                </button>
            </form>
        </div>
    </div>

    <script>
        // Check if already logged in
        if (localStorage.getItem('admin_token')) {
            window.location.href = '/admin.php';
        }

        async function handleLogin(e) {
            e.preventDefault();

            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const errorEl = document.getElementById('loginError');
            const btn = document.getElementById('loginBtn');

            if (!username || !password) {
                errorEl.textContent = '请输入用户名和密码';
                errorEl.style.display = 'block';
                return false;
            }

            btn.disabled = true;
            btn.innerHTML = '<div class="spinner" style="width:16px;height:16px;border-width:2px;"></div> 登录中...';

            try {
                const response = await fetch('/api/admin/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                });

                const data = await response.json();

                if (response.ok && data.code === 0 && data.data && data.data.token) {
                    localStorage.setItem('admin_token', data.data.token);
                    window.location.href = '/admin.php';
                } else {
                    errorEl.textContent = data.msg || '登录失败，请检查用户名和密码';
                    errorEl.style.display = 'block';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> 登 录';
                }
            } catch (error) {
                errorEl.textContent = '网络错误，请稍后重试';
                errorEl.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> 登 录';
            }

            return false;
        }

        // Enter key support
        document.getElementById('password').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') handleLogin(e);
        });
    </script>
</body>
</html>
