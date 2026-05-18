<?php
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
    <title>用户身份 - MX-Mall</title>
    <link rel="stylesheet" href="/assets/css/mobile.css?v=5">
    <style>
        .group-page {
            min-height: 100vh;
            background: var(--bg);
            padding-bottom: 100px;
        }
        .group-header {
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            padding: 52px 20px 28px;
            position: relative;
            overflow: hidden;
        }
        .group-header::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }
        .group-header .back-btn {
            position: absolute;
            top: 12px;
            left: 16px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            z-index: 10;
            text-decoration: none;
        }
        .group-header h2 {
            color: #fff;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
            position: relative;
            z-index: 1;
        }
        .group-header p {
            color: rgba(255,255,255,0.7);
            font-size: 13px;
            position: relative;
            z-index: 1;
        }
        .current-group-card {
            background: var(--bg-white);
            border-radius: var(--radius-md);
            margin: -16px 16px 16px;
            padding: 20px;
            position: relative;
            z-index: 2;
            box-shadow: var(--shadow-md);
        }
        .current-group-card .label {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        .current-group-card .group-name {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
        }
        .current-group-card .group-rate {
            font-size: 13px;
            color: var(--text-secondary);
        }
        .group-list {
            padding: 0 16px;
        }
        .group-list h3 {
            font-size: 15px;
            color: var(--text-primary);
            margin-bottom: 12px;
            font-weight: 600;
        }
        .group-card {
            background: var(--bg-white);
            border-radius: var(--radius-md);
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 14px;
            transition: var(--transition);
            border: 2px solid transparent;
        }
        .group-card.active {
            border-color: var(--primary);
            background: rgba(99,102,241,0.04);
        }
        .group-card .group-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .group-card .group-info {
            flex: 1;
        }
        .group-card .group-info .name {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 2px;
        }
        .group-card .group-info .desc {
            font-size: 12px;
            color: var(--text-secondary);
        }
        .group-card .group-badge {
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
            flex-shrink: 0;
        }
        .group-card .group-badge.current {
            background: rgba(99,102,241,0.1);
            color: var(--primary);
        }
        .group-card .group-badge.default {
            background: rgba(16,185,129,0.1);
            color: var(--success);
        }
        .group-card .btn-buy-group {
            font-size: 12px;
            padding: 6px 14px;
            border-radius: 14px;
            font-weight: 600;
            flex-shrink: 0;
            border: none;
            cursor: pointer;
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            color: #fff;
            transition: var(--transition);
        }
        .group-card .btn-buy-group:active {
            opacity: 0.8;
            transform: scale(0.96);
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
<div class="app-container">
    <div class="group-page">
        <div class="group-header">
            <a class="back-btn" href="/user.php">&#8249;</a>
            <h2>用户身份</h2>
            <p>查看和了解不同的用户身份</p>
        </div>

        <div class="current-group-card" id="currentGroupCard">
            <div class="label">当前身份</div>
            <div class="group-name" id="currentGroupName">加载中...</div>
            <div class="group-rate" id="currentGroupRate"></div>
        </div>

        <div class="group-list">
            <h3>所有身份</h3>
            <div id="groupsContainer">
                <div class="empty-state">
                    <div class="icon">&#128230;</div>
                    <p>加载中...</p>
                </div>
            </div>
        </div>
    </div>

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
    var groupIcons = ['&#128081;', '&#128142;', '&#129309;', '&#11088;', '&#127775;', '&#128640;'];
    var groupColors = [
        'background:rgba(99,102,241,0.1);color:#6366f1',
        'background:rgba(245,158,11,0.1);color:#f59e0b',
        'background:rgba(16,185,129,0.1);color:#10b981',
        'background:rgba(239,68,68,0.1);color:#ef4444',
        'background:rgba(139,92,246,0.1);color:#8b5cf6',
        'background:rgba(236,72,153,0.1);color:#ec4899'
    ];

    async function loadGroupInfo() {
        var token = localStorage.getItem('user_token');
        if (!token) {
            window.location.href = '/user.php';
            return;
        }

        try {
            var res = await NexusApp.get('/user/group-info');
            if (res.code === 0 && res.data) {
                renderGroups(res.data);
            } else if (res.code === 1 && (res.msg || '').includes('登录')) {
                localStorage.removeItem('user_token');
                window.location.href = '/user.php';
            } else {
                document.getElementById('groupsContainer').innerHTML =
                    '<div class="empty-state"><div class="icon">&#128533;</div><p>' + (res.msg || '加载失败') + '</p></div>';
            }
        } catch (e) {
            document.getElementById('groupsContainer').innerHTML =
                '<div class="empty-state"><div class="icon">&#128533;</div><p>网络错误</p></div>';
        }
    }

    function renderGroups(data) {
        var currentGroup = data.current_group;
        var groups = data.groups || [];

        // Current group card
        if (currentGroup) {
            document.getElementById('currentGroupName').textContent = currentGroup.name;
            document.getElementById('currentGroupRate').textContent = '佣金比例: ' + parseFloat(currentGroup.commission_rate).toFixed(2) + '%';
        } else {
            document.getElementById('currentGroupName').textContent = '未分组';
            document.getElementById('currentGroupRate').textContent = '';
        }

        // Group list
        if (groups.length === 0) {
            document.getElementById('groupsContainer').innerHTML =
                '<div class="empty-state"><div class="icon">&#128230;</div><p>暂无用户身份</p></div>';
            return;
        }

        var html = '';
        groups.forEach(function(g, i) {
            var isCurrent = currentGroup && parseInt(currentGroup.id) === parseInt(g.id);
            var isDefault = parseInt(g.is_default) === 1;
            var iconIndex = i % groupIcons.length;
            var colorIndex = i % groupColors.length;

            html += '<div class="group-card' + (isCurrent ? ' active' : '') + '">' +
                '<div class="group-icon" style="' + groupColors[colorIndex] + '">' + groupIcons[iconIndex] + '</div>' +
                '<div class="group-info">' +
                    '<div class="name">' + g.name + '</div>' +
                    '<div class="desc">佣金比例 ' + parseFloat(g.commission_rate).toFixed(2) + '%' +
                    (parseFloat(g.price) > 0 ? ' | 购买价格 ¥' + parseFloat(g.price).toFixed(2) : '') + '</div>' +
                '</div>';

            if (isCurrent) {
                html += '<span class="group-badge current">当前身份</span>';
            } else if (parseFloat(g.price) > 0 && parseFloat(g.commission_rate) < parseFloat(currentGroup ? currentGroup.commission_rate : 100)) {
                html += '<button class="btn-buy-group" onclick="purchaseGroup(' + g.id + ', \'' + g.name.replace(/'/g, "\\'") + '\', ' + parseFloat(g.price).toFixed(2) + ')">¥' + parseFloat(g.price).toFixed(2) + ' 购买</button>';
            } else if (isDefault) {
                html += '<span class="group-badge default">默认</span>';
            }
            html += '</div>';
        });

        document.getElementById('groupsContainer').innerHTML = html;
    }

    document.addEventListener('DOMContentLoaded', loadGroupInfo);

    async function purchaseGroup(groupId, groupName, price) {
        if (!confirm('确定购买「' + groupName + '」身份吗？将从余额扣除¥' + price.toFixed(2))) {
            return;
        }
        try {
            var res = await NexusApp.post('/user/purchase-group', { group_id: groupId });
            if (res.code === 0) {
                NexusApp.toast('购买成功！已升级为「' + (res.data.group_name || groupName) + '」', 'success');
                setTimeout(function() { loadGroupInfo(); }, 500);
            } else {
                NexusApp.toast(res.msg || '购买失败', 'error');
            }
        } catch (e) {
            NexusApp.toast('网络错误，请重试', 'error');
        }
    }
</script>
</body>
</html>
