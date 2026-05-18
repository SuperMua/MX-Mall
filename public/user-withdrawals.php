<?php
/**
 * MX-Mall - User Withdrawals Page
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
    <title>提现记录 - MX-Mall</title>
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

        /* Withdrawal List */
        .withdrawal-list {
            padding: 12px;
        }
        .withdrawal-card {
            background: var(--bg-white);
            border-radius: var(--radius-md);
            border: none;
            padding: 16px;
            margin-bottom: 10px;
            box-shadow: var(--shadow-sm);
        }
        .withdrawal-card .wd-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .withdrawal-card .wd-amount {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .withdrawal-card .wd-amount .yen {
            font-size: 14px;
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
        .status-tag.approved {
            background: rgba(0,184,148,0.15);
            color: var(--success);
        }
        .status-tag.rejected {
            background: rgba(255,107,107,0.15);
            color: var(--danger);
        }
        .status-tag.transferred {
            background: rgba(108,92,231,0.1);
            color: var(--primary);
        }
        .withdrawal-card .wd-body {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
        }
        .withdrawal-card .wd-qrcode {
            width: 60px;
            height: 60px;
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
        .withdrawal-card .wd-qrcode img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .withdrawal-card .wd-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 4px;
        }
        .withdrawal-card .wd-detail-row {
            font-size: 12px;
            color: var(--text-secondary);
        }
        .withdrawal-card .wd-detail-row span {
            color: var(--text-muted);
        }
        .withdrawal-card .wd-remark {
            font-size: 12px;
            color: var(--text-muted);
            padding-top: 10px;
            border-top: 1px solid var(--border-light);
        }
        .withdrawal-card .wd-remark strong {
            color: var(--text-secondary);
            font-weight: 500;
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- Top Bar -->
    <div class="page-top-bar">
        <div class="bar-back" onclick="history.back()">&#8249;</div>
        <div class="bar-title">提现记录</div>
        <div class="bar-action"></div>
    </div>

    <!-- Withdrawal List -->
    <div class="page-content no-tab" style="padding-top:0;">
        <div class="withdrawal-list" id="withdrawal-list">
            <div style="text-align:center;padding:40px 0;color:var(--text-muted);font-size:14px;">
                <div class="spinner" style="margin:0 auto 12px;"></div>
                加载中...
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/app.js?v=5"></script>
<script>
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

    // Load withdrawals
    async function loadWithdrawals() {
        const listEl = document.getElementById('withdrawal-list');
        try {
            const res = await NexusApp.get('/user/withdrawals');
            const resData = res.data || {};
            const withdrawals = resData.list || resData || [];
            renderWithdrawals(Array.isArray(withdrawals) ? withdrawals : []);
        } catch (e) {
            listEl.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128176;</div><div class="empty-text">加载失败，请重试</div></div>';
        }
    }

    function renderWithdrawals(withdrawals) {
        const listEl = document.getElementById('withdrawal-list');
        if (!withdrawals.length) {
            listEl.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128176;</div><div class="empty-text">暂无提现记录</div></div>';
            return;
        }

        const statusMap = {
            0: { text: '待审核', cls: 'pending' },
            1: { text: '已通过', cls: 'approved' },
            2: { text: '已拒绝', cls: 'rejected' },
            3: { text: '已打款', cls: 'transferred' },
        };

        listEl.innerHTML = withdrawals.map(w => {
            const statusInfo = statusMap[w.status] || statusMap[0];
            const createTime = w.created_at || w.create_time || '';
            const timeStr = createTime ? new Date(createTime).toLocaleString('zh-CN') : '';

            return `
                <div class="withdrawal-card">
                    <div class="wd-header">
                        <span class="wd-amount"><span class="yen">&yen;</span>${NexusApp.formatPrice(w.amount || 0)}</span>
                        <span class="status-tag ${statusInfo.cls}">${statusInfo.text}</span>
                    </div>
                    <div class="wd-body">
                        <div class="wd-qrcode">
                            ${w.qr_code ? '<img src="' + w.qr_code + '" alt="收款码">' : '&#128247;'}
                        </div>
                        <div class="wd-details">
                            <div class="wd-detail-row"><span>收款人：</span>${w.real_name || w.realname || '-'}</div>
                            <div class="wd-detail-row"><span>申请时间：</span>${timeStr}</div>
                        </div>
                    </div>
                    ${w.remark ? '<div class="wd-remark"><strong>管理员备注：</strong>' + w.remark + '</div>' : ''}
                </div>
            `;
        }).join('');
    }

    // Init
    document.addEventListener('DOMContentLoaded', loadWithdrawals);
</script>
</body>
</html>
