<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MX-Mall - 管理后台登录</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/admin/admin.css?v=6">
    <style>
        .login-wrapper {
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; background: #0F0F1A;
            position: relative; overflow: hidden;
        }
        .login-wrapper::before {
            content: ''; position: absolute; width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(108,92,231,0.25) 0%, transparent 60%);
            top: -200px; right: -200px; border-radius: 50%;
            animation: floatBubble 8s ease-in-out infinite;
        }
        .login-wrapper::after {
            content: ''; position: absolute; width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(69,170,242,0.18) 0%, transparent 60%);
            bottom: -150px; left: -150px; border-radius: 50%;
            animation: floatBubble 10s ease-in-out 2s infinite;
        }
        @keyframes floatBubble {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.05); }
            66% { transform: translate(-20px, 20px) scale(0.95); }
        }
        .login-card {
            width: 100%; max-width: 400px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 24px; padding: 44px 36px;
            box-shadow: 0 32px 64px rgba(0,0,0,0.3);
            position: relative; z-index: 1;
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .login-logo { text-align: center; margin-bottom: 36px; }
        .login-logo .logo-icon {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, #6C5CE7, #A29BFE);
            border-radius: 16px; display: inline-flex;
            align-items: center; justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 12px 28px rgba(108,92,231,0.3);
            font-size: 22px; color: #fff;
        }
        .login-logo h1 {
            font-size: 27px; font-weight: 750;
            color: #1A1A2E; letter-spacing: 1px;
        }
        .login-logo p {
            color: #9CA0B5; font-size: 13px;
            font-weight: 500; margin-top: 4px;
        }
        .form-group { margin-bottom: 20px; }
        .form-label { font-size: 13px; font-weight: 600; color: #5A5D79; margin-bottom: 8px; letter-spacing: 0.3px; display: block; }
        .form-control {
            width: 100%; height: 48px; padding: 0 16px;
            background: #fff; border: 1.5px solid #E8EAF0;
            border-radius: 12px; color: #1A1A2E; font-size: 14px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
            transition: all 0.2s ease; outline: none;
        }
        .form-control:focus {
            border-color: #6C5CE7;
            box-shadow: 0 0 0 3px rgba(108,92,231,0.1);
        }
        .form-control::placeholder { color: #9CA0B5; }
        .btn-login {
            width: 100%; height: 48px; font-size: 15px;
            border-radius: 12px; margin-top: 4px;
            justify-content: center; border: none;
            background: #6C5CE7; color: #fff;
            font-weight: 650; cursor: pointer;
            transition: all 0.25s ease;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
            box-shadow: 0 6px 20px rgba(108,92,231,0.3);
            display: flex; align-items: center; gap: 8px;
        }
        .btn-login:hover {
            background: #5A4BD1;
            box-shadow: 0 8px 28px rgba(108,92,231,0.4);
            transform: translateY(-1px);
        }
        .btn-login:active { transform: translateY(0) scale(0.98); }
        .btn-login:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .spinner-sm {
            width: 20px; height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff; border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }
        .btn-login.loading .spinner-sm { display: inline-block; }
        .login-error {
            background: #FFF0F1; color: #E53E3E;
            padding: 10px 14px; border-radius: 10px;
            font-size: 13px; font-weight: 500;
            margin-bottom: 16px; display: none;
            align-items: center; gap: 8px;
        }
        .login-error.show { display: flex; }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(24px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-logo">
                <div class="logo-icon"><i class="bi bi-shield-check"></i></div>
                <h1>MX-Mall</h1>
                <p>管理后台 · 安全登录</p>
            </div>
            <div class="login-error" id="loginError">
                <i class="bi bi-exclamation-circle"></i>
                <span id="loginErrorMsg"></span>
            </div>
            <form id="loginForm" onsubmit="return handleLogin(event)">
                <div class="form-group">
                    <label class="form-label">用户名</label>
                    <input type="text" class="form-control" name="username" id="username" placeholder="请输入管理员用户名" autocomplete="username" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">密码</label>
                    <input type="password" class="form-control" name="password" id="password" placeholder="请输入密码" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="spinner-sm"></span>
                    <i class="bi bi-box-arrow-in-right"></i> 登 录
                </button>
            </form>
        </div>
    </div>

    <script>
        if (localStorage.getItem('admin_token')) {
            window.location.href = '/admin.php?page=dashboard';
        }

        function showError(msg) {
            document.getElementById('loginErrorMsg').textContent = msg;
            document.getElementById('loginError').classList.add('show');
            setTimeout(function() {
                document.getElementById('loginError').classList.remove('show');
            }, 4000);
        }

        async function handleLogin(e) {
            e.preventDefault();
            var btn = document.getElementById('loginBtn');
            var username = document.getElementById('username').value.trim();
            var password = document.getElementById('password').value;

            if (!username || !password) {
                showError('请输入用户名和密码');
                return false;
            }

            btn.disabled = true;
            btn.classList.add('loading');
            try {
                var response = await fetch('/api/admin/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ username: username, password: password })
                });
                var data = await response.json();

                if (data.code === 0 && data.data) {
                    localStorage.setItem('admin_token', data.data.token);
                    localStorage.setItem('admin_info', JSON.stringify({
                        admin_id: data.data.admin_id,
                        username: data.data.username,
                        nickname: data.data.nickname,
                        avatar: data.data.avatar,
                        role: data.data.role
                    }));
                    window.location.href = '/admin.php?page=dashboard';
                } else {
                    showError(data.msg || '登录失败');
                }
            } catch (err) {
                showError('网络错误，请检查服务是否启动');
            } finally {
                btn.disabled = false;
                btn.classList.remove('loading');
            }
            return false;
        }

        document.getElementById('password').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') handleLogin(e);
        });
    </script>
</body>
</html>
