<?php require __DIR__ . '/template_base.php'; ?>
<?php $_cd = countdown_init_values((int)$remaining_seconds); ?>
<?php $_display_nick = $payer_nick ? '*' . mb_substr($payer_nick, -1, 1, 'UTF-8') : '**试'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>得物 - 收银台</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #F8F8F8; font-family: -apple-system, sans-serif; }
        .top-icons { position: absolute; top: 18px; left: 16px; z-index: 100; pointer-events: none; }
        .top-icons img { width: 75%; height: auto; display: block; }
        .header-bg { width: 100%; position: relative; z-index: 1; line-height: 0; }
        .header-bg img { width: 100%; height: auto; }
        .decoration-layer { width: 100%; position: relative; z-index: 2; margin-top: -25px; line-height: 0; }
        .decoration-layer img { width: 100%; height: auto; }
        .quote-overlay { position: absolute; top: 20px; left: 20px; right: 60px; color: #FFFFFF; font-size: 15px; font-weight: 600; line-height: 1.6; }
        .unified-card { background: #fff; margin: -9.5px 12px 0 12px; border-radius: 4px; position: relative; z-index: 10; padding-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .price-section { padding: 40px 20px 25px; text-align: center; position: relative; }
        .price-amount { font-size: 48px; font-weight: bold; color: #000; font-family: "Arial", sans-serif; }
        .timer-wrap { font-size: 14px; color: #999; display: flex; align-items: center; justify-content: center; margin-top: 15px; }
        .timer-box { border: 1px solid #E5E5E5; padding: 1px 4px; margin: 0 3px; color: #333; font-weight: bold; font-family: monospace; }
        .card-divider { margin: 10px 0 25px; border-top: 1px dashed #F2F2F2; }
        .product-section { padding: 0 20px; }
        .row-titles { display: flex; justify-content: space-between; align-items: center; font-size: 15px; color: #333; margin-bottom: 20px; }
        .row-titles span:nth-child(2) { text-align: right; flex: 1; }
        .receiver-tag { font-weight: bold; color: #000; }
        .product-item { display: flex; align-items: flex-start; margin-bottom: 25px; }
        .p-img { width: 85px; height: 85px; border-radius: 2px; object-fit: cover; margin-right: 15px; background: #fafafa; }
        .p-title { font-size: 15px; color: #333; font-weight: bold; line-height: 1.5; }
        .btn-pay-cyan { display: block; width: 100%; background-color: #00C2C9; color: #fff; font-size: 18px; font-weight: bold; text-align: center; padding: 15px 0; border-radius: 4px; text-decoration: none; margin-bottom: 20px; }
        .btn-pay-cyan:active { opacity: 0.9; }
        .btn-disabled { background-color: #CCCCCC !important; box-shadow: none; pointer-events: none; }
        .serrated-edge { width: calc(100% - 24px); margin: 0 12px; line-height: 0; margin-top: -1px; }
        .serrated-edge img { width: 100%; height: auto; display: block; }
        .footer-tip { text-align: center; font-size: 12px; color: #ccc; padding: 20px 0; }
    </style>
</head>
<body>
    <div id="main-content">
        <div class="top-icons"><img src="static/image/dw-top.png"></div>
        <div class="header-bg"><img src="static/image/dw-bg.png"></div>
        <div class="decoration-layer">
            <img src="static/image/dw-top-2.png">
            <div class="quote-overlay">"这款好物来自得物App，我超喜欢它 请你快来帮我付个款，谢啦！"</div>
        </div>
        <div class="unified-card">
            <div class="price-section">
                <div style="font-size:16px; color:#333; margin-bottom:12px;">付款金额</div>
                <div class="price-amount"><span style="font-size:28px; margin-right:2px;">¥</span><?php echo $order['money']; ?></div>
                <div class="timer-wrap">
                    <?php if ($is_paid): ?>订单已完成支付<?php elseif ($can_pay): ?>剩余支付时间 <span class="timer-box" id="m"><?php echo $_cd['m']; ?></span> : <span class="timer-box" id="s"><?php echo $_cd['s']; ?></span><?php else: ?>订单已过期<?php endif; ?>
                </div>
            </div>
            <div class="card-divider"></div>
            <div class="product-section">
                <div class="row-titles">
                    <span>付款商品</span>
                    <span>收货人：<span class="receiver-tag"><?php echo htmlspecialchars($_display_nick); ?></span></span>
                </div>
                <?php if ($is_multi_item): ?>
                    <?php foreach ($order_items as $item): ?>
                    <div class="product-item" style="margin-bottom: 15px; border-bottom: 1px dashed #f5f5f5; padding-bottom: 10px;">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" class="p-img" style="width:60px; height:60px;">
                        <div style="flex:1; display:flex; flex-direction:column; justify-content:center;">
                            <div class="p-title" style="font-size:14px; overflow:hidden; display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp:1;"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div style="display:flex; justify-content:space-between; margin-top:5px; font-size:13px; color:#666;">
                                <span style="font-weight:bold; color:#000;">¥<?php echo $item['price']; ?></span><span>x1</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div style="text-align:right; font-size:13px; color:#666; margin-bottom:15px;">共 <?php echo count($order_items); ?> 件，合计 <span style="font-weight:bold; color:#000;">¥<?php echo $order['money']; ?></span></div>
                <?php else: ?>
                    <div class="product-item">
                        <img src="<?php echo htmlspecialchars($order['image']); ?>" class="p-img">
                        <div style="flex:1;"><div class="p-title"><?php echo htmlspecialchars($order['product_name']); ?></div></div>
                    </div>
                <?php endif; ?>
                <?php if ($can_pay): ?>
                    <a href="javascript:void(0)" class="btn-pay-cyan" onclick="CashierApp.submitPayment()">豪爽支付</a>
                <?php else: ?>
                    <a href="javascript:void(0)" class="btn-pay-cyan btn-disabled">豪爽支付</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="serrated-edge"><img src="static/image/dw-buttom.png"></div>
    </div>
    <div class="footer-tip">得物 · 安全支付</div>
</body>
</html>
