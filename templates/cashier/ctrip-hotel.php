<?php require __DIR__ . '/template_base.php'; ?>
<?php $_cd = countdown_init_values((int)$remaining_seconds); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>携程酒店 - 收银台</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #f1f3f5; font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", sans-serif; }
        .ctrip-header { background-color: #2dbb9a; height: 180px; padding: 15px; color: #fff; text-align: center; position: relative; }
        .header-nav { font-size: 20px; font-weight: bold; margin-top: 15px; }
        .main-card { background: #fff; margin: -60px 15px 20px; border-radius: 12px; position: relative; z-index: 10; padding-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .avatar-wrap { width: 70px; height: 70px; margin: -35px auto 10px; background: #fff; padding: 3px; border-radius: 50%; overflow: hidden; }
        .avatar-wrap img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .invite-title { text-align: center; font-size: 18px; font-weight: bold; color: #333; margin-bottom: 5px; }
        .invite-desc { text-align: center; font-size: 13px; color: #666; margin-bottom: 20px; }
        .prod-box { display: flex; padding: 0 20px 20px; }
        .prod-img { width: 85px; height: 85px; border-radius: 8px; object-fit: cover; margin-right: 15px; }
        .prod-info { flex: 1; display: flex; flex-direction: column; justify-content: space-around; }
        .prod-name { font-size: 16px; font-weight: bold; color: #333; }
        .prod-detail { font-size: 13px; color: #888; }
        .divider { height: 1px; border-top: 1px dashed #eee; margin: 0 5px; position: relative; }
        .divider::before, .divider::after { content: ''; position: absolute; top: -8px; width: 16px; height: 16px; background: #f1f3f5; border-radius: 50%; }
        .divider::before { left: -15px; }
        .divider::after { right: -15px; }
        .pay-area { text-align: center; padding: 25px 20px; }
        .price-row { font-size: 16px; color: #333; display: flex; align-items: baseline; justify-content: center; margin-bottom: 15px; }
        .price-tag { margin-right: 5px; }
        .price-symbol { color: #f85e13; font-weight: bold; font-size: 18px; margin-right: 3px; }
        .price-val { color: #f85e13; font-weight: bold; font-size: 32px; font-family: Arial; }
        .timer-row { font-size: 13px; color: #666; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; }
        .timer-box { background: #ff6b3d; color: #fff; padding: 1px 4px; border-radius: 2px; margin: 0 3px; font-family: monospace; }
        .btn-pay { display: block; width: 100%; background-color: #2dbb9a; color: #fff; font-size: 16px; font-weight: bold; text-align: center; padding: 12px 0; border-radius: 25px; text-decoration: none; border: none; box-shadow: 0 4px 10px rgba(45, 187, 154, 0.2); }
        .btn-pay:active { opacity: 0.9; }
        .btn-disabled { background: #ccc !important; box-shadow: none !important; pointer-events: none; }
        .notice-section { padding: 0 20px; color: #888; font-size: 12px; line-height: 1.8; }
        .notice-title { font-size: 13px; font-weight: bold; color: #666; margin-bottom: 8px; }
        .footer-tip { text-align: center; font-size: 12px; color: #ccc; padding: 20px 0; }
    </style>
</head>
<body>
    <div class="ctrip-header">
        <div class="header-nav">他人代付</div>
    </div>
    <div class="main-card">
        <div class="avatar-wrap"><img src="<?php echo htmlspecialchars($payer_avatar); ?>"></div>
        <div class="invite-title"><?php echo htmlspecialchars($payer_nick); ?> 发起的代付邀请</div>
        <div class="invite-desc">我选好了商品 你来买单吧~</div>
        <?php if ($is_multi_item): ?>
            <div style="padding: 0 20px 20px;">
                <?php foreach ($order_items as $item): ?>
                <div style="display:flex; margin-bottom:15px; border-bottom:1px dashed #f5f5f5; padding-bottom:10px;">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" style="width:60px; height:60px; border-radius:4px; object-fit:cover; margin-right:10px; flex-shrink:0;">
                    <div style="flex:1; display:flex; flex-direction:column; justify-content:center;">
                        <div style="font-size:14px; font-weight:bold; color:#333; margin-bottom:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div style="font-size:13px; color:#f85e13; font-weight:bold;">¥<?php echo $item['price']; ?> <span style="color:#999;font-weight:normal;font-size:12px;">x1</span></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div style="text-align:right; font-size:12px; color:#666;">共 <?php echo count($order_items); ?> 件，总额 <span style="color:#f85e13;font-weight:bold;">¥<?php echo $order['money']; ?></span></div>
            </div>
        <?php else: ?>
            <div class="prod-box">
                <img src="<?php echo htmlspecialchars($order['image']); ?>" class="prod-img">
                <div class="prod-info">
                    <div class="prod-name"><?php echo htmlspecialchars($order['product_name']); ?></div>
                    <div class="prod-detail">1天 x1</div>
                    <div class="prod-detail"><?php echo htmlspecialchars($order['shop_name'] ?: '携程精选'); ?></div>
                </div>
            </div>
        <?php endif; ?>
        <div class="divider"></div>
        <div class="pay-area">
            <div class="price-row">
                <span class="price-tag"><?php echo $is_paid ? '已支付' : ($is_expired ? '已过期' : '待支付'); ?></span>
                <span class="price-symbol">¥</span>
                <span class="price-val"><?php echo $order['money']; ?></span>
            </div>
            <?php if ($is_paid): ?>
                <a href="javascript:void(0)" class="btn-pay btn-disabled">帮ta付款</a>
                <div style="color:#2dbb9a; font-weight:bold; margin-top:10px;"><i class="bi bi-check-circle-fill"></i> 代付已完成</div>
            <?php elseif ($can_pay): ?>
                <div class="timer-row">支付倒计时 <span class="timer-box" id="m"><?php echo $_cd['m']; ?></span> : <span class="timer-box" id="s"><?php echo $_cd['s']; ?></span></div>
                <a href="javascript:void(0)" class="btn-pay" onclick="CashierApp.submitPayment()">帮ta付款</a>
            <?php else: ?>
                <a href="javascript:void(0)" class="btn-pay btn-disabled">帮ta付款</a>
                <div style="color:#999; margin-top:10px;">订单已关闭</div>
            <?php endif; ?>
        </div>
        <div class="notice-section">
            <div class="notice-title">代付说明</div>
            1. 代付订单创建后30分钟内未付款，订单会自动取消，你可以重新下单。<br>
            2. 当代付订单退款成功后，实付金额将原路退还代付人。
        </div>
    </div>
    <div class="footer-tip">携程酒店 · 安全支付</div>
</body>
</html>
