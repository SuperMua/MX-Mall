<?php require __DIR__ . '/template_base.php'; ?>
<?php $_cd = countdown_init_values((int)$remaining_seconds); ?>
<?php $_cd_str = format_countdown_init((int)$remaining_seconds, true); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>猫眼电影 - 收银台</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #F5F5F5; font-family: -apple-system, "PingFang SC", sans-serif; padding-bottom: 80px; }
        .heavy-red-card { background: #fff; border-radius: 12px; margin: 15px 12px; overflow: hidden; box-shadow: 0 5px 35px rgba(240, 61, 55, 0.4); border: 1px solid rgba(240, 61, 55, 0.2); }
        .faint-red-card { background: #fff; border-radius: 12px; margin: 15px 12px; overflow: hidden; box-shadow: 0 0 20px rgba(240, 61, 55, 0.12); border: 1px solid rgba(240, 61, 55, 0.05); }
        .timer-inner { padding: 12px 0; text-align: center; font-size: 13px; color: #555; font-weight: 500; }
        .timer-icon { margin-right: 6px; font-size: 14px; color: inherit; vertical-align: -1px; }
        #time-str { color: inherit; font-weight: 500; }
        .prod-content { display: flex; padding: 15px; padding-bottom: 0; }
        .p-img { width: 75px; height: 75px; border-radius: 6px; object-fit: cover; margin-right: 12px; flex-shrink: 0; border: 1px solid #f0f0f0; }
        .p-info { flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .p-title { font-size: 16px; font-weight: bold; color: #333; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .p-desc { font-size: 12px; color: #999; margin-top: 4px; }
        .p-count { font-size: 12px; color: #999; margin-top: 2px; }
        .tag-row { display: flex; align-items: center; margin-top: 15px; font-size: 12px; padding: 12px 15px; border-top: 1px dashed #f5f5f5; }
        .tag-item { display: flex; align-items: center; margin-right: 15px; }
        .tag-item.red i { color: #F03D37; margin-right: 4px; font-size: 14px; }
        .tag-item.green i { color: #1BC57F; margin-right: 4px; font-size: 14px; }
        .normal-card { background: #fff; border-radius: 12px; margin: 12px; padding: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .card-title { font-size: 16px; font-weight: bold; margin-bottom: 12px; color: #333; }
        .list-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
        .list-label { color: #333; }
        .list-val { color: #999; }
        .notice-list { font-size: 12px; color: #666; line-height: 1.8; padding-left: 0; list-style: none; margin: 0; }
        .notice-list li { margin-bottom: 4px; }
        .bottom-bar { position: fixed; bottom: 0; left: 0; width: 100%; background: #fff; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 -1px 10px rgba(0,0,0,0.05); z-index: 100; padding-bottom: max(10px, env(safe-area-inset-bottom)); }
        .total-price { color: #F03D37; font-size: 28px; font-weight: bold; font-family: DIN, sans-serif; }
        .total-price small { font-size: 16px; margin-right: 2px; }
        .status-text { font-size: 11px; color: #999; margin-top: -2px; }
        .btn-pay { background: #F03D37; color: #fff; font-size: 16px; font-weight: bold; border-radius: 22px; padding: 12px 35px; text-decoration: none; border: none; display: inline-block; }
        .btn-pay:active { opacity: 0.9; }
        .btn-disabled { background: #CCC !important; pointer-events: none; }
        .footer-tip { text-align: center; font-size: 12px; color: #ccc; padding: 20px 0; }
    </style>
</head>
<body>
    <div class="heavy-red-card">
        <div class="timer-inner">
            <?php if ($is_paid): ?>
                <i class="bi bi-check-circle-fill" style="color:#1BC57F"></i> 订单已完成支付
            <?php elseif ($is_expired): ?>
                <i class="bi bi-x-circle-fill" style="color:#999"></i> 订单已关闭
            <?php else: ?>
                <i class="bi bi-clock-fill timer-icon"></i> 等待您的付款 <span id="time-str"><?php echo $_cd_str; ?></span> 后订单自动关闭
            <?php endif; ?>
        </div>
    </div>
    <div class="faint-red-card">
        <div class="prod-content">
            <img src="<?php echo htmlspecialchars($order['image']); ?>" class="p-img">
            <div class="p-info">
                <div class="p-title"><?php echo htmlspecialchars($order['product_name']); ?></div>
                <div class="p-desc">【热卖限定包装】官方正品</div>
                <div class="p-count">1张</div>
            </div>
        </div>
        <div class="tag-row">
            <div class="tag-item red"><i class="bi bi-exclamation-circle-fill"></i> 不支持退票</div>
            <div class="tag-item green"><i class="bi bi-check-circle-fill"></i> 限时改签</div>
        </div>
    </div>
    <div class="normal-card">
        <div class="card-title">订单优惠</div>
        <div class="list-row"><span class="list-label">影票活动与优惠券</span><span class="list-val">无可用</span></div>
        <div class="list-row"><span class="list-label">猫享卡</span><span class="list-val">无可用</span></div>
        <div class="list-row"><span class="list-label">观影卡</span><span class="list-val">无可用</span></div>
    </div>
    <div class="normal-card">
        <div class="card-title">购票须知</div>
        <ul class="notice-list">
            <li>1. 请提前30分钟左右到达影院现场，通过影院自助取票机完成取票。</li>
            <li>2. 若取票过程中遇到无法取票等其它问题，请联系影院工作人员进行处理。</li>
            <li>3. 请及时关注电影开场时间，凭票有序检票入场。</li>
        </ul>
    </div>
    <div class="bottom-bar">
        <div>
            <div class="total-price"><small>¥</small><?php echo $order['money']; ?></div>
            <div class="status-text"><?php echo $is_paid ? '订单已完成' : ($is_expired ? '订单已过期' : '订单未支付'); ?></div>
        </div>
        <?php if ($can_pay): ?>
            <a href="javascript:void(0)" class="btn-pay" onclick="CashierApp.submitPayment()">确认支付</a>
        <?php else: ?>
            <a href="javascript:void(0)" class="btn-pay btn-disabled">查看详情</a>
        <?php endif; ?>
    </div>
    <div class="footer-tip">猫眼电影 · 安全支付</div>
</body>
</html>
