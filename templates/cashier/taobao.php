<?php require __DIR__ . '/template_base.php'; ?>
<?php $_cd = countdown_init_values((int)$remaining_seconds); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>淘宝 - 收银台</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #f5f5f5; font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif; padding-bottom: 80px; }
        .tb-header { background-color: #FF5000; padding: 30px 20px 80px 20px; text-align: center; color: #fff; }
        .avatar-box { position: relative; display: inline-block; margin-bottom: 10px; }
        .u-avatar { width: 60px; height: 60px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.5); object-fit: cover; background: #fff; }
        .slogan { font-size: 15px; font-weight: 500; opacity: 0.95; margin-top: 5px; position: relative; display: inline-block; padding-bottom: 15px; }
        .ticket-wrapper { margin: -50px 15px 15px 15px; position: relative; z-index: 10; }
        .card-top { background: #fff; border-radius: 12px 12px 0 0; padding: 35px 20px 35px 20px; text-align: center; position: relative; box-shadow: inset 0 4px 6px -4px rgba(0,0,0,0.15); }
        .tear-line { height: 16px; background: #fff; position: relative; margin: 0 10px; background-image: radial-gradient(circle at 0 8px, #f5f5f5 8px, transparent 8.5px), radial-gradient(circle at right 8px, #f5f5f5 8px, transparent 8.5px); background-size: 100% 100%; background-position: 0 0; background-repeat: no-repeat; }
        .tear-line::after { content: ''; position: absolute; top: 50%; left: 10px; right: 10px; height: 1px; border-top: 1px dashed #e0e0e0; transform: translateY(-50%); z-index: 5; }
        .card-bottom { background: #fff; border-radius: 0 0 12px 12px; padding: 25px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .price-label { font-size: 42px; color: #333; font-weight: bold; font-family: Arial, sans-serif; margin-bottom: 12px; }
        .price-symbol { font-size: 26px; margin-right: 2px; }
        .timer-text { font-size: 13px; color: #999; }
        .timer-num { color: #FF5000; font-weight: bold; margin: 0 2px; font-family: monospace; font-size: 14px; }
        .shop-name { font-size: 13px; color: #999; margin-bottom: 12px; }
        .prod-row { display: flex; align-items: flex-start; }
        .prod-img { width: 68px; height: 68px; border-radius: 6px; object-fit: cover; margin-right: 14px; background: #f8f8f8; flex-shrink: 0; }
        .prod-info { flex: 1; }
        .prod-title { font-size: 14px; color: #333; font-weight: 500; line-height: 1.5; margin-bottom: 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .prod-qty { font-size: 13px; color: #999; }
        .footer-note { padding: 15px 30px; color: #999; font-size: 12px; line-height: 1.6; text-align: center; }
        .fixed-footer { position: fixed; bottom: 0; left: 0; width: 100%; background: #fff; padding: 10px 15px 25px 15px; box-shadow: 0 -2px 10px rgba(0,0,0,0.03); z-index: 100; }
        .btn-tb { display: block; width: 100%; background: linear-gradient(90deg, #FF9000 0%, #FF5000 100%); color: #fff; font-size: 17px; font-weight: bold; text-align: center; padding: 13px 0; border-radius: 25px; text-decoration: none; border: none; }
        .btn-tb:active { opacity: 0.9; }
        .btn-disabled { background: #ccc !important; color: #fff !important; pointer-events: none; }
    </style>
</head>
<body>
    <div class="tb-header">
        <div class="avatar-box">
            <img src="<?php echo htmlspecialchars($payer_avatar); ?>" class="u-avatar">
        </div>
        <br>
        <div class="slogan"><?php echo htmlspecialchars($payer_slogan); ?></div>
    </div>
    <div class="ticket-wrapper">
        <div class="card-top">
            <div class="price-label"><span class="price-symbol">¥</span><?php echo $order['money']; ?></div>
            <?php if ($is_paid): ?>
                <div class="timer-text" style="color: #29C378; font-weight:bold;"><i class="bi bi-check-circle-fill"></i> 支付已完成</div>
            <?php elseif ($can_pay): ?>
                <div class="timer-text">请在 <span class="timer-num" id="m"><?php echo $_cd['m']; ?></span> : <span class="timer-num" id="s"><?php echo $_cd['s']; ?></span> 内完成支付，超时将自动取消订单</div>
            <?php else: ?>
                <div class="timer-text">订单已超时关闭</div>
            <?php endif; ?>
        </div>
        <div class="tear-line"></div>
        <div class="card-bottom">
            <div class="shop-name"><?php echo htmlspecialchars($order['shop_name'] ?: '淘宝店铺'); ?></div>
            <?php if ($is_multi_item): ?>
                <?php foreach ($order_items as $item): ?>
                <div class="prod-row" style="margin-bottom: 15px;">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" class="prod-img">
                    <div class="prod-info">
                        <div class="prod-title"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="prod-qty" style="color:#FF5000; font-weight:bold;">¥<?php echo $item['price']; ?> <span style="color:#999;font-weight:normal;margin-left:5px;">x1</span></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div style="text-align:right; font-size:12px; color:#999; border-top:1px dashed #eee; padding-top:10px;">共 <span style="color:#333;font-weight:bold;"><?php echo count($order_items); ?></span> 件，合计 <span style="color:#FF5000;font-weight:bold;">¥<?php echo $order['money']; ?></span></div>
            <?php else: ?>
                <div class="prod-row">
                    <img src="<?php echo htmlspecialchars($order['image']); ?>" class="prod-img">
                    <div class="prod-info">
                        <div class="prod-title"><?php echo htmlspecialchars($order['product_name']); ?></div>
                        <div class="prod-qty">x1</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="footer-note">付款前请先与好友确认无误，避免资金受损<br>当代付订单申请退款成功后，实付金额将原路退还给代付人</div>
    <div class="fixed-footer">
        <?php if ($can_pay): ?>
            <a href="javascript:void(0)" class="btn-tb" onclick="CashierApp.submitPayment()">立即支付</a>
        <?php elseif ($is_paid): ?>
            <a href="javascript:void(0)" class="btn-tb btn-disabled">来迟了，代付已付款</a>
        <?php else: ?>
            <a href="javascript:void(0)" class="btn-tb btn-disabled">订单已过期</a>
        <?php endif; ?>
    </div>
</body>
</html>
