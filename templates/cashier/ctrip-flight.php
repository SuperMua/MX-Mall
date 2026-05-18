<?php require __DIR__ . '/template_base.php'; ?>
<?php $_cd = countdown_init_values((int)$remaining_seconds); ?>
<?php $_cd_str = format_countdown_init((int)$remaining_seconds, true); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>携程机票 - 收银台</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #F4F6F8; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .ctrip-header { margin: 12px 12px 0 12px; border-radius: 12px 12px 0 0; background: linear-gradient(180deg, #499BF7 0%, #2986F6 100%); padding: 25px 20px 80px 20px; color: #fff; position: relative; }
        .header-content { display: flex; align-items: center; }
        .logo-img { width: 44px; height: 44px; border-radius: 50%; margin-right: 12px; background: #fff; padding: 8px; object-fit: contain; flex-shrink: 0; }
        .header-text-col { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .route-title { font-size: 15px; font-weight: bold; color: #fff; margin-bottom: 2px; line-height: 1.3; }
        .pay-msg { font-size: 13px; color: #fff; line-height: 1.4; font-weight: normal; opacity: 0.95; }
        .main-card { background: #fff; margin: 0 12px; border-radius: 12px; position: relative; top: -60px; box-shadow: 0 8px 20px rgba(0,0,0,0.06); overflow: hidden; padding-bottom: 20px; z-index: 10; }
        .timer-bar { background: #FFF8F2; color: #FF7D00; font-size: 13px; padding: 14px 15px; text-align: center; font-weight: 500; }
        .timer-bar.paid { color: #28a745; background: #e8f5e9; }
        .pay-area { padding: 35px 20px 10px 20px; text-align: center; }
        .pay-label { color: #666; font-size: 15px; margin-bottom: 10px; }
        .pay-price { font-size: 42px; color: #333; font-weight: bold; font-family: Arial, sans-serif; margin-bottom: 35px; letter-spacing: -1px; }
        .pay-price::before { content: '\00A5'; font-size: 26px; margin-right: 4px; font-weight: normal; }
        .btn-ctrip { display: block; width: 100%; background: linear-gradient(90deg, #FFA500 0%, #FF7700 100%); color: #fff; font-size: 18px; font-weight: bold; text-align: center; padding: 14px 0; border-radius: 6px; text-decoration: none; border: none; box-shadow: 0 6px 15px rgba(255, 119, 0, 0.3); margin-bottom: 15px; }
        .btn-ctrip:active { opacity: 0.9; }
        .btn-disabled { background: #ccc !important; box-shadow: none !important; pointer-events: none; }
        .info-box { background: #F7F8FA; border-radius: 8px; margin: 0 20px 20px 20px; padding: 18px; color: #888; font-size: 12px; line-height: 1.8; }
        .info-title { color: #333; font-weight: bold; font-size: 14px; margin-bottom: 6px; }
        .bottom-info-card { background: #fff; margin: -45px 12px 20px 12px; border-radius: 12px; padding: 15px 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); position: relative; z-index: 5; color: #666; font-size: 13px; }
        .footer-tip { text-align: center; font-size: 12px; color: #ccc; padding: 20px 0; }
    </style>
</head>
<body>
    <div class="ctrip-header">
        <div class="header-content">
            <img src="static/image/xiec.png" class="logo-img" alt="Ctrip">
            <div class="header-text-col">
                <div class="route-title"><?php echo htmlspecialchars($order['product_name']); ?></div>
                <div class="pay-msg"><?php echo htmlspecialchars($payer_slogan); ?></div>
            </div>
        </div>
    </div>

    <div class="main-card">
        <?php if ($is_paid): ?>
            <div class="timer-bar paid">该订单已完成支付</div>
        <?php elseif ($can_pay): ?>
            <div class="timer-bar">
                剩余支付时间：<span id="timer_display"><?php echo $_cd_str; ?></span>，请尽快完成支付。
            </div>
        <?php else: ?>
            <div class="timer-bar" style="color:#999; background:#f5f5f5;">订单已过期</div>
        <?php endif; ?>

        <div class="pay-area">
            <div class="pay-label">待付金额</div>
            <div class="pay-price"><?php echo $order['money']; ?></div>

            <?php if ($can_pay): ?>
                <a href="javascript:void(0)" class="btn-ctrip" onclick="CashierApp.submitPayment()">帮TA付款</a>
            <?php elseif ($is_paid): ?>
                <a href="javascript:void(0)" class="btn-ctrip btn-disabled">支付已完成</a>
            <?php else: ?>
                <a href="javascript:void(0)" class="btn-ctrip btn-disabled">订单已过期</a>
            <?php endif; ?>
        </div>

        <div class="info-box">
            <div class="info-title">代付说明</div>
            1. 付款前务必和好友再次确认，避免诈骗行为。<br>
            2. 如果发生退款，钱将退还到您的微信账户里。
        </div>
    </div>

    <div class="bottom-info-card">
        订单信息：<span style="font-family: monospace;"><?php echo htmlspecialchars($trade_no); ?></span>
    </div>

    <div class="footer-tip">携程旅行 · 安全支付</div>
</body>
</html>
