<?php
/**
 * MX-Mall - User Profile Page
 */
// 安装锁检测 - 未安装时跳转到安装页
$lockFile = __DIR__ . '/../install/install.lock';
if (!file_exists($lockFile)) {
    header('Location: /install.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <title>我的 - MX-Mall</title>
    <link rel="stylesheet" href="/assets/css/mobile.css?v=5">
    <style>
        /* ===== Auth Page - Modern Design ===== */
        .auth-page {
            min-height: 100vh;
            background: linear-gradient(160deg, #f8f9fb 0%, #eef0f5 50%, #f0f2f8 100%);
            position: relative;
            overflow: hidden;
        }
        /* Decorative background elements */
        .auth-page::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -80px;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(129,140,248,0.04));
            border-radius: 50%;
            z-index: 0;
        }
        .auth-page::after {
            content: '';
            position: absolute;
            bottom: -60px;
            left: -60px;
            width: 220px;
            height: 220px;
            background: linear-gradient(135deg, rgba(99,102,241,0.06), rgba(129,140,248,0.02));
            border-radius: 50%;
            z-index: 0;
        }
        .auth-container {
            padding: 0 24px 40px;
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 100vh;
            box-sizing: border-box;
        }
        /* Input hints */
        .input-hint {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 6px;
            padding-left: 4px;
            transition: var(--transition);
        }
        .input-hint.valid {
            color: var(--success);
        }
        .input-hint.invalid {
            color: var(--danger);
        }
        /* Password Strength */
        .password-strength {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .strength-bar {
            flex: 1;
            height: 4px;
            background: var(--border-light);
            border-radius: 2px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        .strength-fill.weak { width: 33%; background: #ef4444; }
        .strength-fill.medium { width: 66%; background: #f59e0b; }
        .strength-fill.strong { width: 100%; background: #10b981; }
        .strength-text {
            font-size: 11px;
            color: var(--text-muted);
            min-width: 48px;
        }
        .strength-text.weak { color: #ef4444; }
        .strength-text.medium { color: #f59e0b; }
        .strength-text.strong { color: #10b981; }
        /* Match hint */
        .match-hint {
            font-size: 11px;
            margin-top: 6px;
            padding-left: 4px;
        }
        .match-hint.match {
            color: var(--success);
        }
        .match-hint.mismatch {
            color: var(--danger);
        }
        /* Simple register tip */
        .reg-tip {
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 20px;
            letter-spacing: 0.5px;
        }
        /* Auth Footer */
        .auth-footer {
            text-align: center;
            margin-top: 32px;
            padding-bottom: 24px;
        }
        .auth-footer p {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        .auth-footer-links {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 12px;
        }
        .auth-footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
        }
        .auth-footer-links a:hover {
            color: var(--primary);
        }
        .auth-footer-links .dot {
            color: var(--text-muted);
        }
        /* Hero Section with gradient card */
        .auth-hero {
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            margin: 0 -24px 32px;
            padding: 60px 24px 80px;
            position: relative;
            overflow: hidden;
            border-radius: 0 0 32px 32px;
        }
        .auth-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 250px;
            height: 250px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }
        .auth-hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 180px;
            height: 180px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .auth-hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        .auth-hero .logo-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 16px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .auth-hero .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 3px;
            margin-bottom: 6px;
        }
        .auth-hero .logo-sub {
            font-size: 13px;
            color: rgba(255,255,255,0.7);
            font-weight: 400;
        }
        /* Floating card for forms */
        .auth-card {
            background: var(--bg-white);
            border-radius: var(--radius-xl);
            padding: 28px 24px;
            margin-top: -50px;
            position: relative;
            z-index: 2;
            box-shadow: 0 8px 32px rgba(0,0,0,0.06), 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid rgba(229,231,235,0.5);
        }
        /* Modern tabs with underline indicator */
        .auth-tabs {
            display: flex;
            margin-bottom: 28px;
            position: relative;
            border-bottom: 2px solid var(--border-light);
        }
        .auth-tab {
            flex: 1;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 600;
            color: var(--text-muted);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            background: none;
            border: none;
        }
        .auth-tab.active {
            color: var(--primary);
        }
        .auth-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 20%;
            right: 20%;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            border-radius: 3px 3px 0 0;
        }
        .auth-form {
            display: none;
        }
        .auth-form.active {
            display: block;
            animation: formSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes formSlideIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Modern input fields with floating labels */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        .form-label {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 8px;
            display: block;
            font-weight: 500;
        }
        .form-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .form-input-icon {
            position: absolute;
            left: 14px;
            color: var(--text-muted);
            font-size: 16px;
            z-index: 1;
            transition: var(--transition);
        }
        .form-input {
            width: 100%;
            height: 50px;
            background: var(--bg);
            border: 2px solid transparent;
            border-radius: var(--radius-md);
            padding: 0 14px;
            font-size: 15px;
            color: var(--text-primary);
            transition: var(--transition);
            outline: none;
        }
        .form-input-wrap .form-input {
            padding-left: 42px;
        }
        .form-input:focus {
            background: var(--bg-white);
            border-color: var(--primary-light);
            box-shadow: 0 0 0 4px rgba(99,102,241,0.08);
        }
        .form-input:focus + .form-input-icon,
        .form-input-wrap:focus-within .form-input-icon {
            color: var(--primary);
        }
        .form-input::placeholder {
            color: var(--text-muted);
            font-size: 14px;
        }
        /* Modern auth button with shine effect */
        .btn-auth {
            width: 100%;
            height: 50px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 28px;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(99,102,241,0.3);
        }
        .btn-auth::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        .btn-auth:hover::before {
            left: 100%;
        }
        .btn-auth:active {
            transform: translateY(1px);
            box-shadow: 0 2px 8px rgba(99,102,241,0.2);
        }
        .btn-auth:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        /* Auth switch text */
        .auth-switch {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: var(--text-secondary);
        }
        .auth-switch a {
            color: var(--primary);
            cursor: pointer;
            font-weight: 600;
            margin-left: 4px;
            transition: var(--transition);
        }
        .auth-switch a:hover {
            color: var(--primary-dark);
        }
        /* Social login divider */
        .auth-divider {
            display: flex;
            align-items: center;
            margin: 24px 0;
            color: var(--text-muted);
            font-size: 12px;
        }
        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-light);
        }
        .auth-divider span {
            padding: 0 16px;
        }
        /* Agreement checkbox */
        .auth-agreement {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-top: 16px;
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.5;
        }
        .auth-agreement input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin-top: 1px;
            accent-color: var(--primary);
            flex-shrink: 0;
        }
        .auth-agreement a {
            color: var(--primary);
        }

        /* ===== Profile Page ===== */
        .profile-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            padding: 60px 20px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .profile-header::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }
        .profile-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -15%;
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .profile-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            border: 3px solid rgba(255,255,255,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            margin: 0 auto 12px;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-nickname {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
            position: relative;
            z-index: 1;
        }
        .profile-username {
            font-size: 13px;
            color: rgba(255,255,255,0.7);
            position: relative;
            z-index: 1;
            margin-bottom: 8px;
        }
        .merchant-status-tag {
            display: inline-block;
            font-size: 11px;
            padding: 2px 10px;
            border-radius: 10px;
            position: relative;
            z-index: 1;
        }
        .merchant-status-tag.status-0 {
            background: rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.8);
        }
        .merchant-status-tag.status-1 {
            background: rgba(253,203,110,0.3);
            color: #fff;
        }
        .merchant-status-tag.status-2 {
            background: rgba(0,184,148,0.3);
            color: #fff;
        }
        .merchant-status-tag.status-3 {
            background: rgba(255,107,107,0.3);
            color: #fff;
        }
        .group-status-tag {
            display: inline-block;
            font-size: 11px;
            padding: 2px 12px;
            border-radius: 10px;
            position: relative;
            z-index: 1;
            background: rgba(255,255,255,0.2);
            color: #fff;
            font-weight: 500;
        }

        /* ===== Balance Card ===== */
        .balance-card {
            background: var(--bg-white);
            border-radius: var(--radius-md);
            border: none;
            margin: -20px 16px 12px;
            padding: 20px;
            position: relative;
            z-index: 2;
            box-shadow: var(--shadow-md);
        }
        .balance-card .balance-label {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        .balance-card .balance-amount {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        .balance-card .balance-amount .yen {
            font-size: 18px;
        }
        .balance-card .balance-frozen {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 16px;
        }
        .balance-card .btn-withdraw {
            height: 38px;
            padding: 0 24px;
            border-radius: 19px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }
        .balance-card .btn-withdraw:active {
            opacity: 0.85;
            transform: scale(0.98);
        }

        /* ===== Menu ===== */
        .profile-menu {
            padding: 0 16px;
        }
        .menu-group {
            background: var(--bg-white);
            border-radius: var(--radius-md);
            border: none;
            margin-bottom: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .menu-item {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            cursor: pointer;
            transition: var(--transition);
            border-bottom: 1px solid var(--border-light);
        }
        .menu-item:last-child {
            border-bottom: none;
        }
        .menu-item:active {
            background: var(--bg-elevated);
        }
        .menu-item .menu-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .menu-item .menu-text {
            flex: 1;
            font-size: 14px;
            color: var(--text-primary);
            font-weight: 500;
        }
        .menu-item .menu-arrow {
            color: var(--text-muted);
            font-size: 14px;
        }
        .btn-logout {
            width: calc(100% - 32px);
            margin: 16px 16px 24px;
            height: 46px;
            border-radius: 23px;
            background: var(--bg-white);
            color: var(--danger);
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 80px;
        }
        .btn-logout:active {
            background: var(--bg-elevated);
        }

        /* ===== Modal ===== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            display: none;
            align-items: flex-end;
            justify-content: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-content {
            width: 100%;
            max-width: 480px;
            background: var(--bg-white);
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
            padding: 24px 20px 40px;
            animation: slideUp 0.3s ease;
            max-height: 80vh;
            overflow-y: auto;
        }
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .modal-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .modal-close {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--bg-elevated);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-size: 18px;
            cursor: pointer;
        }
        .modal-btn-submit {
            width: 100%;
            height: 46px;
            border-radius: 23px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }
        .modal-btn-submit:active {
            opacity: 0.85;
            transform: scale(0.98);
        }
        .modal-btn-submit:disabled {
            opacity: 0.5;
        }
        .modal-confirm-text {
            font-size: 15px;
            color: var(--text-secondary);
            line-height: 1.6;
            text-align: center;
            padding: 20px 0;
        }
        .modal-confirm-actions {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }
        .modal-confirm-actions .btn-cancel {
            flex: 1;
            height: 44px;
            border-radius: 22px;
            background: var(--bg-elevated);
            color: var(--text-secondary);
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
        }
        .modal-confirm-actions .btn-confirm {
            flex: 1;
            height: 44px;
            border-radius: 22px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- ===== 未登录：登录/注册表单 ===== -->
    <div id="auth-view" class="auth-page">
        <div class="auth-container">
            <!-- Floating Card -->
            <div class="auth-card">
                <!-- Tabs -->
                <div class="auth-tabs">
                    <button class="auth-tab active" id="tab-login" onclick="switchTab('login')">登录</button>
                    <button class="auth-tab" id="tab-register" onclick="switchTab('register')">注册</button>
                </div>

                <!-- Login Form -->
                <div class="auth-form active" id="form-login">
                    <div class="form-group">
                        <label class="form-label">用户名</label>
                        <div class="form-input-wrap">
                            <span class="form-input-icon">&#128100;</span>
                            <input type="text" class="form-input" id="login-username" placeholder="请输入用户名">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">密码</label>
                        <div class="form-input-wrap">
                            <span class="form-input-icon">&#128274;</span>
                            <input type="password" class="form-input" id="login-password" placeholder="请输入密码">
                        </div>
                    </div>
                    <button class="btn-auth" id="btn-login" onclick="handleLogin()">登 录</button>
                    <div class="auth-switch">
                        还没有账号？<a onclick="switchTab('register')">去注册</a>
                    </div>
                </div>

                <!-- Register Form -->
                <div class="auth-form" id="form-register">
                    <div class="form-group">
                        <label class="form-label">用户名</label>
                        <div class="form-input-wrap">
                            <span class="form-input-icon">&#128100;</span>
                            <input type="text" class="form-input" id="reg-username" placeholder="请输入用户名（2-20个字符）" maxlength="20" oninput="checkUsername(this)">
                        </div>
                        <div class="input-hint" id="username-hint">用户名由字母、数字组成，2-20个字符</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">密码</label>
                        <div class="form-input-wrap">
                            <span class="form-input-icon">&#128274;</span>
                            <input type="password" class="form-input" id="reg-password" placeholder="请输入密码（至少6位）" oninput="checkPasswordStrength(this)">
                        </div>
                        <!-- Password Strength Bar -->
                        <div class="password-strength" id="password-strength" style="display:none;">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strength-fill"></div>
                            </div>
                            <span class="strength-text" id="strength-text">密码强度</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">确认密码</label>
                        <div class="form-input-wrap">
                            <span class="form-input-icon">&#9989;</span>
                            <input type="password" class="form-input" id="reg-password2" placeholder="请再次输入密码" oninput="checkPasswordMatch(this)">
                        </div>
                        <div class="match-hint" id="match-hint" style="display:none;"></div>
                    </div>

                    <button class="btn-auth" id="btn-register" onclick="handleRegister()">立即注册</button>

                    <div class="auth-switch">
                        已有账号？<a onclick="switchTab('login')">去登录</a>
                    </div>
                </div>
            </div>

            <div class="reg-tip">注册即代表同意用户协议和隐私政策</div>
        </div>
    </div>

    <!-- ===== 已登录：用户信息 ===== -->
    <div id="profile-view" style="display:none;">
        <div class="page-content no-tab" style="padding-top:0; padding-bottom: 80px;">
            <!-- Profile Header with Settings -->
            <div class="profile-header" style="position: relative; padding-top: 52px;">
                <!-- Settings Button -->
                <div class="profile-settings-btn" onclick="toggleSettingsMenu()" style="position: absolute; top: 12px; right: 16px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 20px; cursor: pointer; border-radius: 50%; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); z-index: 10;">&#9881;</div>
                <!-- Settings Dropdown -->
                <div class="settings-dropdown" id="settings-dropdown" style="display: none; position: absolute; top: 52px; right: 16px; background: var(--bg-white); border-radius: var(--radius-md); box-shadow: var(--shadow-lg); border: 1px solid var(--border); min-width: 140px; z-index: 100; overflow: hidden;">
                    <div class="settings-item" onclick="openEditProfileModal(); toggleSettingsMenu();" style="padding: 12px 16px; font-size: 14px; color: var(--text-primary); cursor: pointer; display: flex; align-items: center; gap: 8px; transition: var(--transition);">
                        <span style="font-size: 16px;">&#128221;</span> 编辑资料
                    </div>
                    <div class="settings-item" onclick="handleLogout(); toggleSettingsMenu();" style="padding: 12px 16px; font-size: 14px; color: var(--danger); cursor: pointer; display: flex; align-items: center; gap: 8px; border-top: 1px solid var(--border-light); transition: var(--transition);">
                        <span style="font-size: 16px;">&#128682;</span> 退出登录
                    </div>
                </div>
                <div class="profile-avatar" id="profile-avatar" onclick="document.getElementById('avatar-input').click()" style="cursor:pointer;"></div>
                <input type="file" accept="image/*" id="avatar-input" style="display:none;" onchange="uploadAvatar(this)">
                <div class="profile-nickname" id="profile-nickname">加载中...</div>
                <div class="profile-username" id="profile-username"></div>
                <div id="merchant-status-wrap"></div>
            </div>

            <!-- Balance Card -->
            <div class="balance-card">
                <div class="balance-label">可用余额</div>
                <div class="balance-amount"><span class="yen">&yen;</span><span id="balance-amount">0.00</span></div>
                <div class="balance-frozen">冻结余额：<span id="balance-frozen">&yen;0.00</span></div>
                <button class="btn-withdraw" onclick="openWithdrawModal()">申请提现</button>
            </div>

            <!-- Menu -->
            <div class="profile-menu">
                <div class="menu-group">
                    <div class="menu-item" onclick="NexusApp.go('/user-products.php')">
                        <div class="menu-icon" style="background:rgba(108,92,231,0.1);color:var(--primary);">&#128230;</div>
                        <span class="menu-text">我的商品</span>
                        <span class="menu-arrow">&#8250;</span>
                    </div>
                    <div class="menu-item" onclick="NexusApp.go('/user-orders.php')">
                        <div class="menu-icon" style="background:rgba(108,92,231,0.1);color:var(--primary);">&#128203;</div>
                        <span class="menu-text">代付订单</span>
                        <span class="menu-arrow">&#8250;</span>
                    </div>
                    <div class="menu-item" onclick="NexusApp.go('/user-withdrawals.php')">
                        <div class="menu-icon" style="background:rgba(245,158,11,0.1);color:var(--warning);">&#128176;</div>
                        <span class="menu-text">提现记录</span>
                        <span class="menu-arrow">&#8250;</span>
                    </div>
                </div>
                <div class="menu-group">
                    <div class="menu-item" onclick="openInviteModal()">
                        <div class="menu-icon" style="background:rgba(239,68,68,0.1);color:#ef4444;">&#128150;</div>
                        <span class="menu-text">邀请好友</span>
                        <span class="menu-arrow">&#8250;</span>
                    </div>
                    <div class="menu-item" onclick="NexusApp.go('/user-groups.php')">
                        <div class="menu-icon" style="background:rgba(16,185,129,0.1);color:var(--success);">&#127942;</div>
                        <span class="menu-text">用户身份</span>
                        <span class="menu-arrow">&#8250;</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== 邀请好友弹窗 ===== -->
    <div class="modal-overlay" id="invite-modal" onclick="closeInviteModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <div class="modal-title">邀请好友</div>
                <div class="modal-close" onclick="closeInviteModal()">&#10005;</div>
            </div>
            <div style="text-align:center;padding:20px 0;">
                <div style="font-size:56px;margin-bottom:12px;">&#128150;</div>
                <div style="font-size:15px;font-weight:600;color:#333;margin-bottom:4px;">分享给你的好友</div>
                <div style="font-size:12px;color:#999;margin-bottom:8px;">好友通过你的链接注册后，你可获得佣金奖励</div>
                <div id="invite-commission-info" style="background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:8px;padding:8px 14px;display:inline-block;font-size:13px;color:#92400e;margin-bottom:16px;">
                    分佣比例：加载中...
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">你的邀请链接</label>
                <div style="display:flex;gap:6px;">
                    <input type="text" class="form-input" id="invite-url" readonly style="font-size:12px;flex:1;" onclick="this.select()">
                    <button class="btn btn-primary" style="flex-shrink:0;white-space:nowrap;font-size:13px;padding:0 16px;height:44px;border-radius:10px;border:none;background:var(--primary);color:#fff;cursor:pointer;" onclick="copyInviteLink()">复制链接</button>
                </div>
            </div>
            <div style="text-align:center;margin-top:4px;">
                <span id="invite-copy-msg" style="font-size:12px;color:var(--success);display:none;">已复制到剪贴板</span>
            </div>
        </div>
    </div>

    <!-- ===== 申请提现弹窗 ===== -->
    <div class="modal-overlay" id="withdraw-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">申请提现</div>
                <div class="modal-close" onclick="closeWithdrawModal()">&#10005;</div>
            </div>
            <div class="form-group">
                <label class="form-label">提现金额</label>
                <input type="number" class="form-input" id="withdraw-amount" placeholder="请输入提现金额" step="0.01" min="0.01">
            </div>
            <div class="form-group">
                <label class="form-label">收款码</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <label style="flex-shrink:0;padding:8px 16px;background:var(--primary);color:#fff;border-radius:8px;cursor:pointer;font-size:13px;">
                        选择图片
                        <input type="file" accept="image/*" id="withdraw-qrcode-file" style="display:none;" onchange="previewWithdrawQR(this)">
                    </label>
                    <span id="withdraw-qrcode-name" style="font-size:12px;color:var(--text-muted);">未选择文件</span>
                </div>
                <input type="hidden" id="withdraw-qrcode" value="">
                <div id="withdraw-qr-preview" style="margin-top:8px;"></div>
            </div>
            <div class="form-group">
                <label class="form-label">真实姓名</label>
                <input type="text" class="form-input" id="withdraw-realname" placeholder="请输入真实姓名">
            </div>
            <button class="modal-btn-submit" id="btn-withdraw-submit" onclick="handleWithdraw()">提交申请</button>
        </div>
    </div>

    <!-- ===== 编辑资料弹窗 ===== -->
    <div class="modal-overlay" id="edit-profile-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">编辑资料</div>
                <div class="modal-close" onclick="closeEditProfileModal()">&#10005;</div>
            </div>
            <div class="form-group">
                <label class="form-label">头像</label>
                <div style="display:flex;align-items:center;gap:12px;">
                    <div id="edit-avatar-preview" style="width:56px;height:56px;border-radius:50%;background:var(--bg-elevated);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:var(--primary);overflow:hidden;flex-shrink:0;cursor:pointer;" onclick="document.getElementById('edit-avatar-input').click()"></div>
                    <label style="padding:8px 16px;background:var(--primary);color:#fff;border-radius:8px;cursor:pointer;font-size:13px;">
                        选择图片
                        <input type="file" accept="image/*" id="edit-avatar-input" style="display:none;" onchange="previewEditAvatar(this)">
                    </label>
                    <input type="hidden" id="edit-avatar-url" value="">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">昵称</label>
                <input type="text" class="form-input" id="edit-nickname" placeholder="请输入昵称" maxlength="20">
            </div>
            <button class="modal-btn-submit" id="btn-save-profile" onclick="handleSaveProfile()">保存</button>
        </div>
    </div>

    <!-- Bottom Tab Bar -->
    <div class="tab-bar">
        <a class="tab-item" href="/index.php">
            <span class="tab-icon">&#127968;</span>
            <span class="tab-label">首页</span>
        </a>
        <a class="tab-item active" href="/user.php">
            <span class="tab-icon">&#128100;</span>
            <span class="tab-label">我的</span>
        </a>
    </div>
</div>

<script src="/assets/js/app.js?v=5"></script>
<script>
    // ===== Token Management =====
    const TOKEN_KEY = 'user_token';
    const USER_KEY = 'nexus_user_info';

    function getToken() {
        return localStorage.getItem(TOKEN_KEY);
    }

    function setToken(token) {
        localStorage.setItem(TOKEN_KEY, token);
    }

    function removeToken() {
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(USER_KEY);
    }

    function getUserInfo() {
        try {
            const data = localStorage.getItem(USER_KEY);
            return data ? JSON.parse(data) : null;
        } catch (e) {
            return null;
        }
    }

    function setUserInfo(user) {
        localStorage.setItem(USER_KEY, JSON.stringify(user));
    }

    // ===== Tab Switching =====
    function switchTab(tab) {
        document.getElementById('tab-login').classList.toggle('active', tab === 'login');
        document.getElementById('tab-register').classList.toggle('active', tab === 'register');
        document.getElementById('form-login').classList.toggle('active', tab === 'login');
        document.getElementById('form-register').classList.toggle('active', tab === 'register');
    }

    // ===== Login =====
    async function handleLogin() {
        const username = document.getElementById('login-username').value.trim();
        const password = document.getElementById('login-password').value;

        if (!username || !password) {
            NexusApp.toast('请输入用户名和密码', 'error');
            return;
        }

        const btn = document.getElementById('btn-login');
        btn.disabled = true;
        btn.textContent = '登录中...';

        try {
            const res = await NexusApp.post('/user/login', { username, password });
            if (res.code === 0) {
                setToken(res.data.token);
                setUserInfo(res.data.user);
                NexusApp.toast('登录成功', 'success');
                // 直接用登录返回的数据显示profile，不再额外请求API
                showProfileView(res.data.user);
            } else {
                NexusApp.toast(res.msg || '登录失败', 'error');
            }
        } catch (e) {
            NexusApp.toast(e.message || '网络错误，请重试', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = '登 录';
        }
    }

    // ===== Username validation =====
    function checkUsername(input) {
        const hint = document.getElementById('username-hint');
        const val = input.value.trim();
        if (val.length === 0) {
            hint.textContent = '用户名由字母、数字组成，2-20个字符';
            hint.className = 'input-hint';
            return;
        }
        if (val.length >= 2 && val.length <= 20) {
            hint.textContent = '&#10004; 用户名格式正确';
            hint.className = 'input-hint valid';
        } else {
            hint.textContent = '&#10008; 用户名长度需在2-20个字符之间';
            hint.className = 'input-hint invalid';
        }
    }

    // ===== Password strength check =====
    function checkPasswordStrength(input) {
        const val = input.value;
        const strengthBox = document.getElementById('password-strength');
        const fill = document.getElementById('strength-fill');
        const text = document.getElementById('strength-text');

        if (val.length === 0) {
            strengthBox.style.display = 'none';
            return;
        }
        strengthBox.style.display = 'flex';

        let score = 0;
        if (val.length >= 6) score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        fill.className = 'strength-fill';
        text.className = 'strength-text';

        if (score <= 2) {
            fill.classList.add('weak');
            text.classList.add('weak');
            text.textContent = '弱';
        } else if (score <= 4) {
            fill.classList.add('medium');
            text.classList.add('medium');
            text.textContent = '中';
        } else {
            fill.classList.add('strong');
            text.classList.add('strong');
            text.textContent = '强';
        }
    }

    // ===== Password match check =====
    function checkPasswordMatch(input) {
        const pwd = document.getElementById('reg-password').value;
        const hint = document.getElementById('match-hint');
        if (input.value.length === 0) {
            hint.style.display = 'none';
            return;
        }
        hint.style.display = 'block';
        if (input.value === pwd && pwd.length > 0) {
            hint.innerHTML = '&#10004; 密码一致';
            hint.className = 'match-hint match';
        } else {
            hint.innerHTML = '&#10008; 两次密码不一致';
            hint.className = 'match-hint mismatch';
        }
    }

    // ===== Register =====
    async function handleRegister() {
        const username = document.getElementById('reg-username').value.trim();
        const password = document.getElementById('reg-password').value;
        const password2 = document.getElementById('reg-password2').value;

        if (!username || !password) {
            NexusApp.toast('请输入用户名和密码', 'error');
            return;
        }
        if (username.length < 2 || username.length > 20) {
            NexusApp.toast('用户名长度2-20个字符', 'error');
            return;
        }
        if (password.length < 6) {
            NexusApp.toast('密码至少6位', 'error');
            return;
        }
        if (password !== password2) {
            NexusApp.toast('两次密码不一致', 'error');
            return;
        }

        const btn = document.getElementById('btn-register');
        btn.disabled = true;
        btn.textContent = '注册中...';

        try {
            var body = { username: username, password: password };
            // 传递邀请人ID
            var inviteRef = localStorage.getItem('invite_ref');
            if (inviteRef) {
                body.ref = parseInt(inviteRef) || 0;
                localStorage.removeItem('invite_ref');
            }
            const res = await NexusApp.post('/user/register', body);
            if (res.code === 0) {
                NexusApp.toast(res.msg || '注册成功，请等待管理员审核', 'success');
                switchTab('login');
                // Clear register form
                document.getElementById('reg-username').value = '';
                document.getElementById('reg-password').value = '';
                document.getElementById('reg-password2').value = '';
            } else {
                NexusApp.toast(res.msg || '注册失败', 'error');
            }
        } catch (e) {
            NexusApp.toast('网络错误，请重试', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = '注 册';
        }
    }

    // ===== Settings Menu Toggle =====
    function toggleSettingsMenu() {
        const dropdown = document.getElementById('settings-dropdown');
        if (dropdown.style.display === 'none' || dropdown.style.display === '') {
            dropdown.style.display = 'block';
            dropdown.style.animation = 'formSlideIn 0.2s ease';
        } else {
            dropdown.style.display = 'none';
        }
    }

    // Close settings dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('settings-dropdown');
        const btn = document.querySelector('.profile-settings-btn');
        if (dropdown && btn && !dropdown.contains(e.target) && !btn.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // ===== Profile View =====
    function showProfileView(user) {
        document.getElementById('auth-view').style.display = 'none';
        document.getElementById('profile-view').style.display = 'block';

        if (user) {
            document.getElementById('profile-nickname').textContent = user.nickname || 'MX-Mall用户';
            document.getElementById('profile-username').textContent = user.username ? '@' + user.username : '';

            const avatarEl = document.getElementById('profile-avatar');
            if (user.avatar) {
                avatarEl.innerHTML = '<img src="' + user.avatar + '" alt="avatar">';
            } else {
                // Show first letter of username or nickname
                const name = user.nickname || user.username || 'U';
                avatarEl.textContent = name.charAt(0).toUpperCase();
            }

            // User group identity
            var groupName = user.group_name || '';
            var statusWrap = document.getElementById('merchant-status-wrap');
            if (groupName) {
                statusWrap.innerHTML = '<span class="group-status-tag">' + groupName + '</span>';
            } else {
                statusWrap.innerHTML = '<span class="group-status-tag">普通用户</span>';
            }

            // Balance
            document.getElementById('balance-amount').textContent = NexusApp.formatPrice(user.balance || 0);
            document.getElementById('balance-frozen').textContent = '\u00A5' + NexusApp.formatPrice(user.frozen_balance || 0);
        }
    }

    function showAuthView() {
        document.getElementById('auth-view').style.display = 'block';
        document.getElementById('profile-view').style.display = 'none';
    }

    // ===== Logout =====
    function handleLogout() {
        if (confirm('确定要退出登录吗？')) {
            removeToken();
            showAuthView();
            NexusApp.toast('已退出登录', 'success');
        }
    }

    // ===== Withdraw Modal =====
    function openWithdrawModal() {
        document.getElementById('withdraw-modal').classList.add('active');
    }

    function closeWithdrawModal() {
        document.getElementById('withdraw-modal').classList.remove('active');
    }

    function previewWithdrawQR(input) {
        if (!input.files[0]) return;
        const file = input.files[0];
        document.getElementById('withdraw-qrcode-name').textContent = file.name;

        // 预览
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('withdraw-qr-preview').innerHTML = `<img src="${e.target.result}" style="max-height:100px;border-radius:8px;">`;
        };
        reader.readAsDataURL(file);
    }

    async function handleWithdraw() {
        const amount = parseFloat(document.getElementById('withdraw-amount').value);
        const realname = document.getElementById('withdraw-realname').value.trim();

        if (!amount || amount <= 0) {
            NexusApp.toast('请输入有效的提现金额', 'error');
            return;
        }
        if (!realname) {
            NexusApp.toast('请输入真实姓名', 'error');
            return;
        }

        const qrFile = document.getElementById('withdraw-qrcode-file').files[0];
        let qrCode = document.getElementById('withdraw-qrcode').value;

        // 如果选择了文件但还没上传，先上传
        if (qrFile && !qrCode) {
            try {
                qrCode = await NexusApp.uploadImage(qrFile);
                document.getElementById('withdraw-qrcode').value = qrCode;
            } catch (e) {
                NexusApp.toast('收款码上传失败', 'error');
                return;
            }
        }

        if (!qrCode) {
            NexusApp.toast('请上传收款码', 'error');
            return;
        }

        const btn = document.getElementById('btn-withdraw-submit');
        btn.disabled = true;
        btn.textContent = '提交中...';

        try {
            const res = await NexusApp.post('/user/withdraw', { amount, qr_code: qrCode, real_name: realname });
            if (res.code === 0) {
                NexusApp.toast('提现申请已提交', 'success');
                closeWithdrawModal();
                document.getElementById('withdraw-amount').value = '';
                document.getElementById('withdraw-qrcode').value = '';
                document.getElementById('withdraw-qrcode-name').textContent = '未选择文件';
                document.getElementById('withdraw-qr-preview').innerHTML = '';
                document.getElementById('withdraw-realname').value = '';
                // Refresh profile
                loadProfile();
            } else {
                NexusApp.toast(res.msg || '提交失败', 'error');
            }
        } catch (e) {
            NexusApp.toast('网络错误，请重试', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = '提交申请';
        }
    }

    // ===== Edit Profile Modal =====
    function openEditProfileModal() {
        var user = getUserInfo();
        if (!user) return;

        document.getElementById('edit-nickname').value = user.nickname || '';
        document.getElementById('edit-avatar-url').value = user.avatar || '';

        var preview = document.getElementById('edit-avatar-preview');
        if (user.avatar) {
            preview.innerHTML = '<img src="' + user.avatar + '" style="width:100%;height:100%;object-fit:cover;">';
        } else {
            var name = user.nickname || user.username || 'U';
            preview.textContent = name.charAt(0).toUpperCase();
        }

        document.getElementById('edit-profile-modal').classList.add('active');
    }

    function closeEditProfileModal() {
        document.getElementById('edit-profile-modal').classList.remove('active');
    }

    function previewEditAvatar(input) {
        if (!input.files[0]) return;
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('edit-avatar-preview').innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">';
        };
        reader.readAsDataURL(input.files[0]);
    }

    async function handleSaveProfile() {
        var nickname = document.getElementById('edit-nickname').value.trim();
        var avatar = document.getElementById('edit-avatar-url').value;
        var avatarFile = document.getElementById('edit-avatar-input').files[0];

        if (!nickname) {
            NexusApp.toast('请输入昵称', 'error');
            return;
        }

        var btn = document.getElementById('btn-save-profile');
        btn.disabled = true;
        btn.textContent = '保存中...';

        try {
            // 如果选择了新头像文件，先上传
            if (avatarFile) {
                try {
                    avatar = await NexusApp.uploadImage(avatarFile);
                    document.getElementById('edit-avatar-url').value = avatar;
                } catch (e) {
                    NexusApp.toast('头像上传失败', 'error');
                    btn.disabled = false;
                    btn.textContent = '保存';
                    return;
                }
            }

            var params = { nickname: nickname };
            if (avatar) params.avatar = avatar;

            var res = await NexusApp.post('/user/profile', params);
            if (res.code === 0) {
                NexusApp.toast('资料修改成功', 'success');
                closeEditProfileModal();
                loadProfile();
            } else {
                NexusApp.toast(res.msg || '修改失败', 'error');
            }
        } catch (e) {
            NexusApp.toast('网络错误，请重试', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = '保存';
        }
    }

    // ===== Upload Avatar (header click) =====
    async function uploadAvatar(input) {
        if (!input.files[0]) return;
        try {
            const url = await NexusApp.uploadImage(input.files[0]);
            const res = await NexusApp.post('/user/profile', { avatar: url });
            if (res.code === 0) {
                NexusApp.toast('头像更新成功', 'success');
                loadProfile();
            }
        } catch(e) {
            NexusApp.toast('上传失败', 'error');
        }
    }

    // ===== Invite Modal =====
    function openInviteModal() {
        var user = getUserInfo();
        if (user) {
            var refUrl = window.location.origin + '/?ref=' + user.id;
            document.getElementById('invite-url').value = refUrl;
            if (user.group_commission_rate && parseFloat(user.group_commission_rate) > 0) {
                document.getElementById('invite-commission-info').textContent = '分佣比例：' + parseFloat(user.group_commission_rate).toFixed(1) + '%';
            } else {
                document.getElementById('invite-commission-info').textContent = '暂无分佣奖励';
            }
        } else {
            document.getElementById('invite-url').value = '请先登录';
            document.getElementById('invite-commission-info').textContent = '分佣比例：加载中...';
        }
        document.getElementById('invite-modal').classList.add('active');
        // Refresh from API for latest rate
        if (getToken()) {
            NexusApp.get('/user/profile').then(function(res) {
                if (res.code === 0 && res.data) {
                    setUserInfo(res.data);
                    var refUrl2 = window.location.origin + '/?ref=' + res.data.id;
                    document.getElementById('invite-url').value = refUrl2;
                    if (res.data.group_commission_rate && parseFloat(res.data.group_commission_rate) > 0) {
                        document.getElementById('invite-commission-info').textContent = '分佣比例：' + parseFloat(res.data.group_commission_rate).toFixed(1) + '%';
                    } else {
                        document.getElementById('invite-commission-info').textContent = '暂无分佣奖励';
                    }
                }
            }).catch(function() {});
        }
    }

    function closeInviteModal() {
        document.getElementById('invite-modal').classList.remove('active');
        document.getElementById('invite-copy-msg').style.display = 'none';
    }

    function copyInviteLink() {
        var url = document.getElementById('invite-url').value;
        if (!url || url === '请先登录') return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function() {
                document.getElementById('invite-copy-msg').style.display = 'block';
                setTimeout(function() {
                    document.getElementById('invite-copy-msg').style.display = 'none';
                }, 2000);
            }).catch(function() {
                fallbackCopyInvite(url);
            });
        } else {
            fallbackCopyInvite(url);
        }
    }

    function fallbackCopyInvite(text) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            document.getElementById('invite-copy-msg').style.display = 'block';
            setTimeout(function() {
                document.getElementById('invite-copy-msg').style.display = 'none';
            }, 2000);
        } catch (e) {
            NexusApp.toast('复制失败，请手动复制', 'error');
        }
        document.body.removeChild(textarea);
    }

    // ===== Load Profile from API =====
    async function loadProfile() {
        const token = getToken();
        if (!token) {
            showAuthView();
            return;
        }

        // Try to load from local cache first
        const cachedUser = getUserInfo();
        if (cachedUser) {
            showProfileView(cachedUser);
        }

        // Then refresh from API
        try {
            const res = await NexusApp.get('/user/profile');
            if (res.code === 0 && res.data) {
                setUserInfo(res.data);
                showProfileView(res.data);
            }
            // 如果API返回错误但不抛异常，保持当前显示（不强制退出登录）
        } catch (e) {
            // API失败时，如果有缓存就保持显示，不强制退出
            console.warn('Profile load failed:', e.message);
        }
    }

    // ===== Init =====
    document.addEventListener('DOMContentLoaded', function() {
        // 处理邀请链接参数 ref=用户ID
        var urlParams = new URLSearchParams(window.location.search);
        var refId = urlParams.get('ref');
        if (refId) {
            localStorage.setItem('invite_ref', refId);
        }
        loadProfile();
    });
</script>
</body>
</html>
