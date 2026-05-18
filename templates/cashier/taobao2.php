<?php require __DIR__ . '/template_base.php'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>淘宝好物 - 收银台</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #f1f1f1; font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif; padding-bottom: 80px; }
        .nav-bar { background: #f1f1f1; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; height: 44px; }
        .price-section { text-align: center; padding: 30px 0 20px; color: #FF5000; }
        .price-symbol { font-size: 24px; font-weight: bold; margin-right: 2px; }
        .price-val { font-size: 46px; font-weight: bold; font-family: DIN, Arial, sans-serif; }
        .main-card { background: #fff; margin: 0 12px; border-radius: 12px; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .card-header { display: flex; justify-content: space-between; font-size: 14px; color: #333; margin-bottom: 15px; font-weight: 500; }
        .help-tips { color: #999; font-size: 12px; display: flex; align-items: center; }
        .help-tips i { margin-left: 3px; font-size: 12px; }
        .prod-row { display: flex; align-items: flex-start; }
        .p-img-box { position: relative; width: 85px; height: 85px; border-radius: 6px; overflow: hidden; margin-right: 12px; flex-shrink: 0; background: #f8f8f8; }
        .p-img { width: 100%; height: 100%; object-fit: cover; }
        .p-img-tag { position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.7); color: #fff; font-size: 10px; text-align: center; padding: 2px 0; transform: scale(0.85); width: 120%; margin-left: -10%; white-space: nowrap; }
        .p-info { flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: flex-start; height: 85px; }
        .p-title { font-size: 14px; color: #333; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .tag-black { background: #000; color: #d4b178; font-size: 10px; padding: 0 4px; border-radius: 2px; margin-right: 4px; vertical-align: 1px; font-weight: normal; display: inline-block; line-height: 14px; }
        .p-count-row { margin-top: 6px; font-size: 14px; color: #999; }
        .bottom-area { position: fixed; bottom: 0; left: 0; width: 100%; padding: 10px 15px; padding-bottom: max(15px, env(safe-area-inset-bottom)); background: transparent; pointer-events: none; }
        .btn-pay { display: block; width: 100%; height: 48px; line-height: 48px; text-align: center; background-image: linear-gradient(90deg, #FF9000 0%, #FF5000 100%); color: #fff; font-size: 17px; font-weight: bold; border-radius: 24px; text-decoration: none; box-shadow: 0 4px 10px rgba(255, 80, 0, 0.2); pointer-events: auto; }
        .btn-pay:active { opacity: 0.9; }
        .btn-disabled { background: #e0e0e0 !important; background-image: none !important; color: #aaa !important; box-shadow: none; pointer-events: none; }
        .footer-tip { text-align: center; font-size: 12px; color: #ccc; padding: 20px 0; }
    </style>
</head>
<body>
    <div class="nav-bar"></div>
    <div class="price-section">
        <span class="price-symbol">¥</span><span class="price-val"><?php echo $order['money']; ?></span>
    </div>
    <div class="main-card">
        <div class="card-header">
            <span>帮付订单信息</span>
            <span class="help-tips">帮我付说明 <i class="bi bi-question-circle"></i></span>
        </div>
        <?php if ($is_multi_item): ?>
            <?php foreach ($order_items as $item): ?>
            <div class="prod-row" style="margin-bottom: 15px; border-bottom: 1px dashed #f9f9f9; padding-bottom: 10px;">
                <div class="p-img-box" style="width: 60px; height: 60px;">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" class="p-img">
                </div>
                <div class="p-info" style="height: auto; justify-content: center;">
                    <div class="p-title" style="-webkit-line-clamp: 1;"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="p-count-row" style="margin-top: 4px;">
                        <span style="color:#FF5000;font-weight:bold;">¥<?php echo $item['price']; ?></span>
                        <span style="color:#999;font-size:12px;margin-left:5px;">x1</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <div style="text-align:right; font-size:12px; color:#999; padding-top:5px;">共 <?php echo count($order_items); ?> 件商品，合计 <span style="color:#333;font-weight:bold;">¥<?php echo $order['money']; ?></span></div>
        <?php else: ?>
            <div class="prod-row">
                <div class="p-img-box">
                    <img src="<?php echo htmlspecialchars($order['image']); ?>" class="p-img">
                    <div class="p-img-tag">正品保真 | 现货秒发</div>
                </div>
                <div class="p-info">
                    <div class="p-title"><span class="tag-black">次日达</span> <?php echo htmlspecialchars($order['product_name']); ?></div>
                    <div class="p-count-row">x1</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="bottom-area">
        <?php if ($can_pay): ?>
            <a href="javascript:void(0)" class="btn-pay" onclick="CashierApp.submitPayment()">立即支付</a>
        <?php elseif ($is_paid): ?>
            <a href="javascript:void(0)" class="btn-pay btn-disabled">订单已完成</a>
        <?php else: ?>
            <a href="javascript:void(0)" class="btn-pay btn-disabled">订单已过期</a>
        <?php endif; ?>
    </div>
    <div class="footer-tip">淘宝好物 · 安全支付</div>
</body>
</html>
