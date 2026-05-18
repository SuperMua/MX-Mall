<?php require __DIR__ . '/template_base.php'; ?>
<?php
$_cd = countdown_init_values((int)$remaining_seconds);
$_display_nick = mb_substr($payer_nick, 1);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>京东 - 收银台</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #F6F6F6; font-family: -apple-system, Helvetica, sans-serif; padding-bottom: 80px; }

        .header-bg { position: relative; width: 100%; margin-bottom: -20px; z-index: 0; }
        .bg-img { width: 100%; display: block; height: auto; position: absolute; top: 0; left: 0; z-index: 1; }
        .user-interaction { position: relative; z-index: 100; display: flex; align-items: flex-start; padding: 20px 15px 40px 15px; }
        .u-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; margin-right: 12px; margin-top: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
        .bubble { flex: 1; background: transparent; border: none; box-shadow: none; padding: 20px 15px 20px 20px; position: relative; font-size: 14px; color: #333; line-height: 1.5; }

        .jd-card { background: #fff; border-radius: 12px; margin: 0 12px 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); position: relative; overflow: hidden; z-index: 10; }
        .amount-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .amount-label { font-size: 16px; color: #333; font-weight: 500; }
        .receiver-info { font-size: 14px; color: #666; }
        .price-large { font-size: 36px; color: #F2270C; font-weight: bold; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; letter-spacing: -1px; }
        .timer-row { display: flex; justify-content: flex-end; align-items: center; gap: 8px; }
        .timer-label { font-size: 13px; color: #999; }
        .timer-box { display: flex; align-items: center; font-size: 13px; color: #333; font-variant-numeric: tabular-nums; }
        .t-num { border: 1px solid #ddd; border-radius: 4px; padding: 1px 5px; margin: 0 3px; font-family: monospace; min-width: 24px; text-align: center; background: #fff; }
        .pay-method-title { font-size: 14px; color: #666; margin-bottom: 15px; }
        .pay-row { display: flex; align-items: center; justify-content: space-between; padding: 5px 0; }
        .pay-left { display: flex; align-items: center; }
        .check-icon { color: #F2270C; font-size: 20px; }
        .btn-pay-now { display: block; width: 100%; background: linear-gradient(90deg, #ff3100 0%, #f2270c 100%); color: #fff; font-size: 17px; font-weight: bold; text-align: center; padding: 13px 0; border-radius: 25px; text-decoration: none; margin-top: 15px; box-shadow: 0 4px 12px rgba(242, 39, 12, 0.3); border: none; }
        .btn-pay-now:active { opacity: 0.9; }
        .btn-disabled { background: #ccc !important; box-shadow: none !important; pointer-events: none; }
        .order-title { font-size: 14px; color: #333; margin-bottom: 15px; font-weight: bold; }
        .prod-row { display: flex; align-items: center; }
        .prod-img { width: 80px; height: 80px; border-radius: 6px; object-fit: cover; margin-right: 15px; border: 1px solid #f0f0f0; flex-shrink: 0; }
        .prod-info { flex: 1; display: flex; flex-direction: column; justify-content: space-between; padding: 2px 0; min-width: 0; }
        .prod-name { font-size: 14px; color: #333; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .prod-price-row { display: flex; justify-content: space-between; align-items: center; font-size: 14px; margin-top: 5px; }
        .prod-price { font-family: Arial; font-weight: bold; color: #F2270C; }
        .prod-qty { color: #999; font-size: 12px; }
        .footer-tip { text-align: center; font-size: 12px; color: #ccc; padding: 20px 0; }
    </style>
</head>
<body>

    <div class="header-bg">
        <img src="static/image/dingbu.png" class="bg-img" alt="bg">
        <div class="user-interaction">
            <img src="<?php echo htmlspecialchars($payer_avatar); ?>" class="u-avatar">
            <div class="bubble">
                我在京东上挑好了商品，是时候该你仗义疏财啦，快帮我付个款吧~
            </div>
        </div>
    </div>

    <div class="jd-card">
        <div class="amount-row">
            <span class="amount-label" style="<?php echo $is_paid ? 'color:#bbb;' : ''; ?>">代付金额</span>
            <span class="receiver-info">收货人：*<?php echo htmlspecialchars($_display_nick); ?></span>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 10px;">
            <div class="price-large" style="line-height: 1; <?php echo $is_paid ? 'color:#bbb; font-size:30px; font-weight:normal;' : ''; ?>">
                ¥<?php echo $order['money']; ?>
            </div>

            <?php if ($can_pay): ?>
            <div class="timer-row">
                <span class="timer-label">剩余支付时间</span>
                <div class="timer-box">
                    <span class="t-num" id="h"><?php echo $_cd['h']; ?></span> :
                    <span class="t-num" id="m"><?php echo $_cd['m']; ?></span> :
                    <span class="t-num" id="s"><?php echo $_cd['s']; ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($can_pay): ?>
    <div class="jd-card">
        <div class="pay-method-title">支付方式</div>
        <div class="pay-row">
            <div class="pay-left">
                <i class="bi bi-wechat" style="color:#09BB07; font-size:22px; margin-right:8px;"></i>
                <span>微信支付</span>
            </div>
            <i class="bi bi-check-circle-fill check-icon"></i>
        </div>
        <a href="javascript:void(0)" class="btn-pay-now" onclick="CashierApp.submitPayment()">立即支付</a>
    </div>
    <?php endif; ?>

    <div class="jd-card">
        <div class="order-title">代付订单信息</div>

        <?php if ($is_multi_item): ?>
            <?php foreach ($order_items as $item): ?>
            <div class="prod-row" style="margin-bottom: 12px; border-bottom: 1px dashed #f5f5f5; padding-bottom: 10px;">
                <img src="<?php echo htmlspecialchars($item['image']); ?>" class="prod-img" style="width:60px; height:60px;">
                <div class="prod-info">
                    <div class="prod-name" style="-webkit-line-clamp: 1;"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="prod-price-row">
                        <span class="prod-price">¥<?php echo $item['price']; ?></span>
                        <span class="prod-qty">x1</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <div style="text-align:right; font-size:12px; color:#999; padding-top:5px;">
                共 <?php echo count($order_items); ?> 件商品，合计 <span style="color:#F2270C;font-weight:bold;">¥<?php echo $order['money']; ?></span>
            </div>
        <?php else: ?>
            <div class="prod-row">
                <img src="<?php echo htmlspecialchars($order['image']); ?>" class="prod-img">
                <div class="prod-info">
                    <div class="prod-name"><?php echo htmlspecialchars($order['product_name']); ?></div>
                    <div class="prod-price-row">
                        <span class="prod-price">¥<?php echo $order['price']; ?></span>
                        <span class="prod-qty">数量：1</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer-tip">京东 · 安全支付</div>

</body>
</html>
