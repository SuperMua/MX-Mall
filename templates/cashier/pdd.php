<?php require __DIR__ . '/template_base.php'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>拼多多 - 收银台</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #F4F4F4; font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif; }
        .pdd-header { background-color: #F3554F; padding: 20px 20px 60px 20px; display: flex; align-items: flex-start; }
        .avatar-img { width: 50px; height: 50px; border-radius: 50%; margin-right: 12px; object-fit: cover; border: 1px solid rgba(255,255,255,0.2); }
        .user-col { display: flex; flex-direction: column; padding-top: 2px; }
        .nick-name { color: #fff; font-size: 16px; font-weight: 500; margin-bottom: 8px; }
        .bubble-msg { background-color: rgba(255, 255, 255, 0.2); color: #fff; font-size: 13px; padding: 6px 12px; border-radius: 4px; position: relative; line-height: 1.4; }
        .main-card { background: #fff; margin: -40px 12px 20px 12px; border-radius: 8px; padding: 30px 20px 0 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); text-align: center; }
        .pay-label { font-size: 15px; color: #333; margin-bottom: 15px; font-weight: 500; }
        .price-wrap { color: #000; font-weight: bold; font-family: Arial, sans-serif; margin-bottom: 25px; display: flex; align-items: baseline; justify-content: center; }
        .symbol { font-size: 24px; margin-right: 4px; }
        .amount { font-size: 42px; letter-spacing: -1px; }
        .btn-pdd { display: block; width: 100%; background-color: #E02E24; color: #fff; font-size: 17px; font-weight: bold; padding: 12px 0; border-radius: 6px; text-decoration: none; border: none; margin-bottom: 15px; }
        .btn-pdd:active { opacity: 0.9; }
        .btn-disabled { background: #ccc !important; pointer-events: none; }
        .note-text { color: #9C9C9C; font-size: 12px; margin-bottom: 25px; }
        .prod-section { border-top: 1px solid #F2F2F2; padding: 20px 0; text-align: left; }
        .prod-item-row { display: flex; align-items: center; margin-bottom: 15px; }
        .prod-item-row:last-child { margin-bottom: 0; }
        .prod-img { width: 60px; height: 60px; border-radius: 4px; object-fit: cover; margin-right: 12px; flex-shrink: 0; background: #f8f8f8; }
        .prod-info { flex: 1; display: flex; flex-direction: column; justify-content: center; overflow: hidden; }
        .prod-title { font-size: 14px; color: #151516; margin-bottom: 4px; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .prod-price-row { display: flex; justify-content: space-between; align-items: center; font-size: 14px; color: #9C9C9C; }
        .footer-tip { text-align: center; font-size: 12px; color: #ccc; padding: 20px 0; }
    </style>
</head>
<body>
    <div class="pdd-header">
        <img src="<?php echo htmlspecialchars($payer_avatar); ?>" class="avatar-img">
        <div class="user-col">
            <div class="nick-name"><?php echo htmlspecialchars($payer_nick); ?></div>
            <div class="bubble-msg"><?php echo htmlspecialchars($payer_slogan); ?></div>
        </div>
    </div>
    <div class="main-card">
        <div class="pay-label">代付金额</div>
        <div class="price-wrap">
            <span class="symbol">¥</span>
            <span class="amount"><?php echo $order['money']; ?></span>
        </div>
        <?php if ($can_pay): ?>
            <a href="javascript:void(0)" class="btn-pdd" onclick="CashierApp.submitPayment()">立即支付</a>
        <?php elseif ($is_paid): ?>
            <a href="javascript:void(0)" class="btn-pdd btn-disabled">来迟了，代付已付款</a>
        <?php else: ?>
            <a href="javascript:void(0)" class="btn-pdd btn-disabled">订单已过期</a>
        <?php endif; ?>
        <div class="note-text">如果订单申请退款，已支付金额将原路退还给您</div>
        <div class="prod-section">
            <?php if ($is_multi_item): ?>
                <?php foreach ($order_items as $item): ?>
                <div class="prod-item-row">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" class="prod-img">
                    <div class="prod-info">
                        <div class="prod-title"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="prod-price-row"><span>¥<?php echo $item['price']; ?></span><span>x1</span></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div style="text-align:right; border-top:1px dashed #eee; margin-top:15px; padding-top:10px; font-size:12px; color:#999;">
                    共 <?php echo count($order_items); ?> 件商品，合计 <span style="color:#E02E24;font-weight:bold;">¥<?php echo $order['money']; ?></span>
                </div>
            <?php else: ?>
                <div class="prod-item-row">
                    <img src="<?php echo htmlspecialchars($order['image']); ?>" class="prod-img">
                    <div class="prod-info">
                        <div class="prod-title"><?php echo htmlspecialchars($order['product_name']); ?></div>
                        <div class="prod-price-row"><span>¥<?php echo $order['price']; ?></span><span>x1</span></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="footer-tip">拼多多 · 安全支付</div>
</body>
</html>
