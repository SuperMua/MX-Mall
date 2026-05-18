<?php
/**
 * MX-Mall - Payment Result Page
 */
// 安装锁检测 - 未安装时跳转到安装页
$lockFile = __DIR__ . '/../install/install.lock';
if (!file_exists($lockFile)) {
    header('Location: /install.php');
    exit;
}
$status = isset($_GET['status']) ? trim($_GET['status']) : 'success';
$tradeNo = isset($_GET['trade_no']) ? trim($_GET['trade_no']) : '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <title>支付结果 - MX-Mall</title>
    <link rel="stylesheet" href="/assets/css/mobile.css?v=5">
    <style>
        .result-page {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 40px 24px;
            text-align: center;
        }
        .result-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-bottom: 20px;
            animation: resultPop 0.5s ease;
        }
        .result-icon.success {
            background: linear-gradient(135deg, var(--success), #34D399);
            box-shadow: 0 4px 20px rgba(16,185,129,0.3);
        }
        .result-icon.failed {
            background: var(--danger);
            box-shadow: 0 4px 20px rgba(239,68,68,0.3);
        }
        @keyframes resultPop {
            0% { transform: scale(0); opacity: 0; }
            60% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }
        .result-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        .result-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 32px;
        }
        .result-card {
            width: 100%;
            background: var(--bg-white);
            border-radius: var(--radius-lg);
            padding: 20px;
            border: none;
            margin-bottom: 32px;
            box-shadow: var(--shadow-md);
        }
        .result-card .result-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 13px;
            border-bottom: 1px solid var(--border-light);
        }
        .result-card .result-row:last-child {
            border-bottom: none;
        }
        .result-card .result-row .label {
            color: var(--text-muted);
        }
        .result-card .result-row .value {
            color: var(--text-primary);
            font-weight: 500;
            text-align: right;
            max-width: 60%;
            word-break: break-all;
        }
        .result-card .result-row .value.amount {
            color: var(--danger);
            font-size: 18px;
            font-weight: 700;
        }
        .result-actions {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .result-actions .btn-primary {
            width: 100%;
        }
        .result-actions .btn-secondary {
            width: 100%;
        }
        .result-tips {
            margin-top: 24px;
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.8;
        }
        .confetti {
            position: fixed;
            top: -10px;
            width: 10px;
            height: 10px;
            border-radius: 2px;
            animation: confettiFall 3s ease-in forwards;
            z-index: 0;
        }
        @keyframes confettiFall {
            0% { transform: translateY(0) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
    </style>
</head>
<body>
<div class="app-container">
    <div class="result-page" id="result-page">
        <!-- Result Icon -->
        <div class="result-icon <?php echo $status === 'success' ? 'success' : 'failed'; ?>">
            <?php echo $status === 'success' ? '&#10003;' : '&#10007;'; ?>
        </div>

        <!-- Result Text -->
        <div class="result-title">
            <?php echo $status === 'success' ? '支付成功' : '支付失败'; ?>
        </div>
        <div class="result-subtitle">
            <?php echo $status === 'success' ? '感谢您的购买，商品将尽快为您发货' : '支付遇到问题，请稍后重试或联系客服'; ?>
        </div>

        <!-- Order Info Card -->
        <div class="result-card">
            <div class="result-row">
                <span class="label">订单编号</span>
                <span class="value" id="result-order-no"><?php echo htmlspecialchars($tradeNo) ?: '-'; ?></span>
            </div>
            <div class="result-row">
                <span class="label">支付状态</span>
                <span class="value" style="color:<?php echo $status === 'success' ? 'var(--success)' : 'var(--danger)'; ?>">
                    <?php echo $status === 'success' ? '已支付' : '未支付'; ?>
                </span>
            </div>
            <div class="result-row">
                <span class="label">支付金额</span>
                <span class="value amount" id="result-amount">&yen;0.00</span>
            </div>
            <div class="result-row">
                <span class="label">支付时间</span>
                <span class="value" id="result-time"><?php echo date('Y-m-d H:i:s'); ?></span>
            </div>
        </div>

        <!-- Actions -->
        <div class="result-actions">
            <?php if ($status === 'success'): ?>
                <button class="btn-primary" onclick="NexusApp.go('/index.php')">返回首页</button>
                <button class="btn-secondary" onclick="NexusApp.go('/index.php')">继续购物</button>
            <?php else: ?>
                <button class="btn-primary" onclick="retryPayment()">重新支付</button>
                <button class="btn-secondary" onclick="NexusApp.go('/index.php')">返回首页</button>
            <?php endif; ?>
        </div>

        <!-- Tips -->
        <div class="result-tips">
            如有疑问，请联系客服<br>
            MX-Mall &middot; 正品保障
        </div>
    </div>
</div>

<script src="/assets/js/app.js?v=5"></script>
<script>
    const tradeNo = '<?php echo htmlspecialchars($tradeNo); ?>';
    const status = '<?php echo $status; ?>';

    // Load order info for display
    async function loadResult() {
        if (!tradeNo) return;
        try {
            // Use out_trade_no from URL params if available
            const urlParams = new URLSearchParams(window.location.search);
            const outTradeNo = urlParams.get('out_trade_no') || tradeNo;
            const urlMoney = urlParams.get('money');

            // Try API first
            const res = await NexusApp.get('/order', { trade_no: outTradeNo });
            const data = res.data || res;
            const order = data.order || data;

            if (order) {
                const amountEl = document.getElementById('result-amount');
                if (amountEl) {
                    amountEl.textContent = '\u00a5' + NexusApp.formatPrice(order.money || order.amount || order.total_amount || urlMoney || 0);
                }
                const orderNoEl = document.getElementById('result-order-no');
                if (orderNoEl && order.out_trade_no) {
                    orderNoEl.textContent = order.out_trade_no;
                }
            }
        } catch (e) {
            // Fallback: use money from URL params
            const urlParams = new URLSearchParams(window.location.search);
            const urlMoney = urlParams.get('money');
            const urlOutTradeNo = urlParams.get('out_trade_no');
            if (urlMoney) {
                const amountEl = document.getElementById('result-amount');
                if (amountEl) {
                    amountEl.textContent = '\u00a5' + NexusApp.formatPrice(urlMoney);
                }
            }
            if (urlOutTradeNo) {
                const orderNoEl = document.getElementById('result-order-no');
                if (orderNoEl) {
                    orderNoEl.textContent = urlOutTradeNo;
                }
            }
        }
    }

    function retryPayment() {
        if (tradeNo) {
            NexusApp.go(`/checkout.php?trade_no=${tradeNo}`);
        } else {
            NexusApp.go('/index.php');
        }
    }

    // Confetti for success
    function showConfetti() {
        if (status !== 'success') return;
        const colors = ['#6c5ce7', '#00cec9', '#fdcb6e', '#ff6b6b', '#55efc4', '#a29bfe'];
        for (let i = 0; i < 30; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDuration = (2 + Math.random() * 2) + 's';
                confetti.style.animationDelay = Math.random() * 0.5 + 's';
                confetti.style.width = (6 + Math.random() * 8) + 'px';
                confetti.style.height = (6 + Math.random() * 8) + 'px';
                document.body.appendChild(confetti);
                setTimeout(() => confetti.remove(), 4000);
            }, i * 50);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadResult();
        showConfetti();
    });
</script>
</body>
</html>
