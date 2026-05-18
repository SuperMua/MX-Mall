<?php require __DIR__ . '/template_base.php'; ?>
<?php $_cd = countdown_init_values((int)$remaining_seconds); ?>
<?php $_cd_str = format_countdown_init((int)$remaining_seconds, true); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>抖音商城 - 收银台</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #F4F5F6; font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif; padding-bottom: 100px; }
        .page-header-text { text-align: center; padding: 40px 0 15px; }
        .ph-title { font-size: 19px; font-weight: bold; color: #161823; margin-bottom: 8px; }
        .ph-timer { font-size: 13px; color: #888; }
        .ph-timer span { color: #FE2C55; font-weight: 500; }
        .main-card { background: #fff; margin: 10px 16px; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .user-row { display: flex; align-items: flex-start; margin-bottom: 15px; }
        .u-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 12px; }
        .u-info { flex: 1; }
        .u-name { font-size: 15px; font-weight: bold; color: #161823; display: flex; align-items: center; }
        .u-verify { font-size: 11px; color: #2a6ae2; font-weight: normal; margin-left: 6px; background: rgba(42, 106, 226, 0.08); padding: 2px 6px; border-radius: 4px; cursor: pointer; }
        .u-msg { font-size: 13px; color: #999; margin-top: 4px; line-height: 1.4; }
        .inner-gray-box { background: #F9F9FA; border-radius: 8px; padding: 20px 16px; transition: all 0.3s; }
        .expired-dim { opacity: 0.6; filter: grayscale(100%); background: #f0f0f0; }
        .price-row { display: flex; align-items: center; margin-bottom: 20px; }
        .p-symbol { font-size: 20px; color: #161823; font-weight: bold; margin-right: 2px; margin-top: 6px; }
        .p-val { font-size: 42px; color: #161823; font-weight: bold; font-family: DIN, Arial; }
        .p-tag { margin-left: 10px; font-size: 11px; color: #FE2C55; border: 1px solid rgba(254, 44, 85, 0.3); padding: 1px 4px; border-radius: 3px; height: 18px; line-height: 16px; margin-top: 8px; white-space: nowrap; }
        .prod-row { display: flex; align-items: center; margin-bottom: 12px; }
        .prod-row:last-child { margin-bottom: 0; }
        .prod-img { width: 36px; height: 36px; border-radius: 4px; object-fit: cover; margin-right: 10px; flex-shrink: 0; background: #eee; }
        .prod-info-col { flex: 1; display: flex; flex-direction: column; justify-content: center; overflow: hidden; }
        .prod-name { font-size: 13px; color: #333; line-height: 1.3; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .prod-count { font-size: 12px; color: #999; }
        .footer-tips { margin: 15px 20px; font-size: 12px; color: #999; line-height: 1.5; }
        .bottom-fixed { position: fixed; bottom: 0; left: 0; width: 100%; background: #F4F5F6; padding: 10px 15px 40px; z-index: 100; }
        .btn-pay { display: block; width: 100%; height: 48px; line-height: 48px; text-align: center; background: #FE2C55; color: #fff; font-size: 17px; font-weight: bold; border-radius: 4px; text-decoration: none; border: none; }
        .btn-pay:active { opacity: 0.9; }
        .btn-disabled { background: #DDDEE0 !important; color: #fff !important; pointer-events: none; }
        .dy-footer-logo { position: fixed; bottom: 15px; left: 0; width: 100%; text-align: center; font-size: 11px; color: #ccc; display: flex; align-items: center; justify-content: center; z-index: 101; padding-bottom: env(safe-area-inset-bottom); }
        .dy-footer-logo i { margin-right: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="page-header-text">
        <div class="ph-title">亲友付</div>
        <?php if ($can_pay): ?>
        <div class="ph-timer" id="top-timer">剩 <span id="time-str"><?php echo $_cd_str; ?></span> 订单关闭</div>
        <?php endif; ?>
    </div>
    <div class="main-card">
        <div class="user-row">
            <img src="<?php echo htmlspecialchars($payer_avatar); ?>" class="u-avatar">
            <div class="u-info">
                <div class="u-name">
                    <?php echo htmlspecialchars($payer_nick); ?>
                    <?php if ($can_pay): ?>
                    (**店) <span class="u-verify">校验姓名</span>
                    <?php endif; ?>
                </div>
                <div class="u-msg"><?php echo htmlspecialchars($payer_slogan); ?></div>
            </div>
        </div>
        <div class="inner-gray-box <?php echo $is_expired ? 'expired-dim' : ''; ?>">
            <div class="price-row">
                <span class="p-symbol">¥</span>
                <span class="p-val"><?php echo $order['money']; ?></span>
                <span class="p-tag"><?php echo $is_paid ? '已支付' : ($is_expired ? '支付申请已失效' : '待支付'); ?></span>
            </div>
            <?php if ($is_multi_item): ?>
                <?php foreach ($order_items as $item): ?>
                <div class="prod-row">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" class="prod-img">
                    <div class="prod-info-col">
                        <div class="prod-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="prod-count">¥<?php echo $item['price']; ?> x 1</div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="prod-row">
                    <img src="<?php echo htmlspecialchars($order['image']); ?>" class="prod-img">
                    <div class="prod-info-col">
                        <div class="prod-name"><?php echo htmlspecialchars($order['product_name']); ?></div>
                        <div class="prod-count">x1</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($can_pay): ?>
    <div class="footer-tips">如果订单申请退款，已支付金额将原路退还给你。</div>
    <?php endif; ?>
    <div class="bottom-fixed">
        <?php if ($can_pay): ?>
            <a href="javascript:void(0)" class="btn-pay" onclick="CashierApp.submitPayment()">好友代付</a>
        <?php else: ?>
            <a href="javascript:void(0)" class="btn-pay btn-disabled">发消息告诉Ta</a>
        <?php endif; ?>
    </div>
    <div class="dy-footer-logo"><i class="bi bi-tiktok"></i> 抖音支付 | 9亿人都在用</div>
</body>
</html>
