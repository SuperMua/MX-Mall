<?php
/**
 * MX-Mall - User Orders Page (Shared Payment Orders)
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
    <title>代付订单 - MX-Mall</title>
    <link rel="stylesheet" href="/assets/css/mobile.css?v=5">
    <style>
        .page-top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            height: 52px;
            background: var(--bg-white);
            border-bottom: 1px solid var(--border);
        }
        .page-top-bar .bar-back {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            font-size: 20px;
            cursor: pointer;
        }
        .page-top-bar .bar-title {
            font-size: 17px;
            font-weight: 600;
            color: var(--text-primary);
        }
        .page-top-bar .bar-action {
            width: 36px;
        }

        /* Tabs */
        .order-tabs {
            display: flex;
            background: var(--bg-white);
            border-bottom: 1px solid var(--border);
            padding: 0 16px;
        }
        .order-tab {
            flex: 1;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-muted);
            cursor: pointer;
            position: relative;
            transition: var(--transition);
        }
        .order-tab.active {
            color: var(--primary);
            font-weight: 600;
        }
        .order-tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 24px;
            height: 3px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 2px;
        }

        /* Order List */
        .order-list {
            padding: 12px;
        }
        .order-card {
            background: var(--bg-white);
            border-radius: var(--radius-md);
            border: none;
            margin-bottom: 10px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .order-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px 8px;
        }
        .order-card-header .order-time {
            font-size: 12px;
            color: var(--text-muted);
        }
        .order-card-body {
            display: flex;
            gap: 12px;
            padding: 8px 14px 12px;
        }
        .order-card-body .order-img {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-sm);
            background: var(--bg-elevated);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--text-muted);
            overflow: hidden;
        }
        .order-card-body .order-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .order-card-body .order-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
        }
        .order-card-body .order-name {
            font-size: 14px;
            color: var(--text-primary);
            font-weight: 500;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .order-card-body .order-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .order-card-body .order-amount {
            font-size: 16px;
            font-weight: 700;
            color: var(--danger);
        }
        .order-card-body .order-amount .yen {
            font-size: 12px;
        }
        .order-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 14px 12px;
            border-top: 1px solid var(--border-light);
        }
        .order-card-footer .order-tpl {
            font-size: 12px;
            color: var(--text-muted);
        }
        .status-tag {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }
        .status-tag.pending {
            background: rgba(253,203,110,0.15);
            color: var(--warning);
        }
        .status-tag.paid {
            background: rgba(0,184,148,0.15);
            color: var(--success);
        }
        .status-tag.expired {
            background: rgba(255,107,107,0.15);
            color: var(--danger);
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- Top Bar -->
    <div class="page-top-bar">
        <div class="bar-back" onclick="history.back()">&#8249;</div>
        <div class="bar-title">代付订单</div>
        <div class="bar-action"></div>
    </div>

    <!-- Tabs -->
    <div class="order-tabs">
        <div class="order-tab active" data-status="all" onclick="switchTab(this, 'all')">全部</div>
        <div class="order-tab" data-status="0" onclick="switchTab(this, 0)">待支付</div>
        <div class="order-tab" data-status="1" onclick="switchTab(this, 1)">已支付</div>
    </div>

    <!-- Order List -->
    <div class="page-content no-tab" style="padding-top:0;">
        <div class="order-list" id="order-list">
            <div style="text-align:center;padding:40px 0;color:var(--text-muted);font-size:14px;">
                <div class="spinner" style="margin:0 auto 12px;"></div>
                加载中...
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/app.js?v=5"></script>
<script>
    let currentStatus = 'all';
    let allOrders = [];

    function getToken() {
        return localStorage.getItem('user_token');
    }

    // Check login
    (function() {
        const token = getToken();
        if (!token) {
            NexusApp.go('/user.php');
        }
    })();

    function switchTab(el, status) {
        document.querySelectorAll('.order-tab').forEach(t => t.classList.remove('active'));
        el.classList.add('active');
        currentStatus = status;
        renderOrders();
    }

    // Load orders
    async function loadOrders() {
        const listEl = document.getElementById('order-list');
        try {
            const res = await NexusApp.get('/user/orders');
            const data = res.data || {};
            allOrders = data.list || (Array.isArray(data) ? data : []);
            renderOrders();
        } catch (e) {
            console.error('Load orders error:', e);
            listEl.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128203;</div><div class="empty-text">加载失败，请重试</div></div>';
        }
    }

    function renderOrders() {
        const listEl = document.getElementById('order-list');
        let orders = allOrders;
        if (currentStatus !== 'all') {
            orders = allOrders.filter(o => o.status == currentStatus);
        }

        if (!orders.length) {
            listEl.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128203;</div><div class="empty-text">暂无订单</div></div>';
            return;
        }

        listEl.innerHTML = orders.map(o => {
            const statusText = o.status == 1 ? '已支付' : (o.status >= 2 ? '已过期' : '待支付');
            const statusCls = o.status == 1 ? 'paid' : (o.status >= 2 ? 'expired' : 'pending');
            const tplName = o.cashier_tpl || o.tpl_name || '';
            const createTime = o.created_at || o.create_time || '';
            const timeStr = createTime ? new Date(createTime).toLocaleString('zh-CN') : '';

            return `
                <div class="order-card">
                    <div class="order-card-header">
                        <span class="order-time">${timeStr}</span>
                        <span class="status-tag ${statusCls}">${statusText}</span>
                    </div>
                    <div class="order-card-body">
                        <div class="order-img">
                            ${o.product_image ? '<img src="' + o.product_image + '" alt="">' : '&#128230;'}
                        </div>
                        <div class="order-info">
                            <div class="order-name">${o.product_name || o.subject || '商品订单'}</div>
                            <div class="order-meta">
                                <span class="order-amount"><span class="yen">&yen;</span>${NexusApp.formatPrice(o.money || o.amount || 0)}</span>
                            </div>
                        </div>
                    </div>
                    <div class="order-card-footer">
                        <span class="order-tpl">${tplName ? '模版: ' + tplName : ''}</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Init
    document.addEventListener('DOMContentLoaded', loadOrders);
</script>
</body>
</html>
