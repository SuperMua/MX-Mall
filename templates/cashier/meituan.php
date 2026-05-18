<?php require __DIR__ . '/template_base.php'; ?>
<?php
$_cd = countdown_init_values((int)$remaining_seconds);
$_cd_str = format_countdown_init((int)$remaining_seconds, false);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>美团外卖 - 收银台</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #F5F5F5; font-family: -apple-system, sans-serif; }
        .user-header { padding: 20px 15px; display: flex; align-items: center; }
        .avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-right: 12px; border: 1px solid #fff; }
        .user-info .nick { font-size: 16px; font-weight: bold; margin-bottom: 4px; color: #222; }
        .user-info .slogan { font-size: 12px; color: #666; }
        .card { background: #fff; border-radius: 12px; margin: 0 12px 12px; padding: 25px 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
        .pay-amount { text-align: center; font-size: 38px; font-weight: bold; margin-bottom: 5px; font-family: Arial, sans-serif; }
        .pay-amount::before { content: '\00A5'; font-size: 22px; margin-right: 4px; }
        .timer-wrap { display: flex; justify-content: center; align-items: center; margin-bottom: 30px; font-size: 14px; color: #333; }
        .timer-box { background: #333; color: #fff; padding: 2px 4px; border-radius: 4px; margin: 0 4px; font-weight: bold; }
        .notice-box { background: #FEF8E5; border-radius: 8px; padding: 15px; font-size: 12px; color: #8B572A; line-height: 1.8; margin-bottom: 15px; }
        .notice-title { color: #8B572A; margin-bottom: 5px; font-weight: bold; font-size: 13px; }
        .btn-main { display: block; width: 100%; background: #FDD934; color: #000; font-size: 16px; font-weight: bold; text-align: center; padding: 14px 0; border-radius: 25px; text-decoration: none; border: none; cursor: pointer; margin-top: 15px; }
        .btn-main:active { opacity: 0.9; }
        .btn-disabled { background: #ccc !important; color: #999 !important; pointer-events: none; }
        .product-row { display: flex; align-items: center; margin-top: 15px; padding-bottom: 15px; border-bottom: 1px dashed #f5f5f5; }
        .product-row:last-child { border-bottom: none; padding-bottom: 0; }
        .p-img { width: 60px; height: 60px; background: #f8f8f8; border-radius: 6px; margin-right: 12px; object-fit: cover; flex-shrink: 0; }
        .p-info { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .p-name { font-size: 14px; color: #333; line-height: 1.4; margin-bottom: 4px; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .footer-tip { text-align: center; font-size: 12px; color: #ccc; padding: 20px 0; }
    </style>
</head>
<body>

    <div class="user-header">
        <img src="<?php echo htmlspecialchars($payer_avatar); ?>" class="avatar">
        <div class="user-info">
            <div class="nick"><?php echo htmlspecialchars($payer_nick); ?></div>
            <div class="slogan"><?php echo htmlspecialchars($payer_slogan); ?></div>
        </div>
    </div>

    <div class="card">
        <?php if ($is_paid): ?>
            <div style="text-align:center; padding: 10px 0;">
                <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <svg viewBox="0 0 1024 1024" width="26" height="26" style="margin-right: 10px;">
                        <path d="M512 0C229.23 0 0 229.23 0 512s229.23 512 512 512 512-229.23 512-512S794.77 0 512 0z" fill="#FFC300"/>
                        <path d="M426.67 746.67a32 32 0 0 1-22.61-9.39l-213.33-213.33a32 32 0 1 1 45.27-45.27L426.67 669.33l362.66-362.66a32 32 0 0 1 45.27 45.27l-385.33 385.33a32 32 0 0 1-22.6 9.4z" fill="#FFFFFF"/>
                    </svg>
                    <span style="font-size: 20px; font-weight: bold; color: #333;">美团用户已付款</span>
                </div>
                <div class="pay-amount"><?php echo $order['money']; ?></div>
                <div style="color: #888; font-size: 14px; margin-bottom: 20px;">微信支付</div>
            </div>
        <?php else: ?>
            <div style="text-align:center;">
                <div style="font-size: 16px; font-weight: bold; margin-bottom: 15px; color: #333;">需付款</div>
                <div class="pay-amount"><?php echo $order['money']; ?></div>
                <?php if ($can_pay): ?>
                <div class="timer-wrap">
                    支付剩余时间 <span class="timer-box" id="m"><?php echo $_cd['m']; ?></span> : <span class="timer-box" id="s"><?php echo $_cd['s']; ?></span>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="notice-box">
            <div class="notice-title">付款须知</div>
            1.代付订单创建成功后15分钟内未付款，订单会自动取消，你可以重新下单。<br>
            2.当代付订单退款成功后，实付金额将原路退还代付人。
        </div>

        <?php if ($can_pay): ?>
            <a href="javascript:void(0)" class="btn-main" onclick="CashierApp.submitPayment()">为好友买单</a>
        <?php elseif ($is_paid): ?>
            <a href="javascript:void(0)" class="btn-main btn-disabled">已支付</a>
        <?php else: ?>
            <a href="javascript:void(0)" class="btn-main btn-disabled">订单已过期</a>
        <?php endif; ?>
    </div>

    <div class="card">
        <div style="font-size: 14px; color: #666; font-weight: bold; border-bottom: 1px solid #f5f5f5; padding-bottom: 10px; margin-bottom: 5px;">
            <?php echo htmlspecialchars($order['shop_name'] ?: '外卖订单'); ?>
        </div>

        <?php if ($is_multi_item): ?>
            <?php foreach ($order_items as $item): ?>
            <div class="product-row">
                <img src="<?php echo htmlspecialchars($item['image']); ?>" class="p-img">
                <div class="p-info">
                    <div class="p-name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:#999; font-size:12px;">X 1</span>
                        <span style="font-weight:bold; font-family:Arial;">¥ <?php echo $item['price']; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <div style="text-align:right; margin-top:10px; font-size:13px; color:#666;">
                共 <?php echo count($order_items); ?> 件商品，合计 <span style="color:#000;font-weight:bold;">¥<?php echo $order['money']; ?></span>
            </div>
        <?php else: ?>
            <div class="product-row" style="border-bottom:none; margin-top:10px;">
                <img src="<?php echo htmlspecialchars($order['image']); ?>" class="p-img">
                <div class="p-info">
                    <div class="p-name"><?php echo htmlspecialchars($order['product_name']); ?></div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:#999; font-size:12px;">X 1</span>
                        <span style="font-weight:bold;">¥ <?php echo $order['price']; ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer-tip">美团外卖 · 安全支付</div>

</body>
</html>
