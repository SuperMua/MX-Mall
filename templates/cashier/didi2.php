<?php require __DIR__ . '/template_base.php'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>滴滴Pro - 收银台</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #F3F4F6; font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif; padding-bottom: 100px; color: #333; }
        .status-bar { background: #fff; padding: 15px 20px 15px; font-size: 20px; font-weight: bold; color: #000; display: flex; align-items: center; margin-bottom: 10px; }
        .status-bar i { margin-right: 10px; font-size: 18px; font-weight: bold; }
        .white-card { background: #fff; margin: 0 12px 12px; border-radius: 12px; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); position: relative; }
        .route-card-full { padding: 0 !important; overflow: hidden; }
        .route-image-wrapper { position: relative; width: 100%; }
        .route-bg { display: block; width: 100%; height: auto; }
        .addr-dot { position: absolute; left: 12px; width: 8px; height: 8px; border-radius: 50%; background: #ccc; z-index: 2; }
        .dot-start { top: 25%; }
        .dot-end { bottom: 25%; }
        .addr-text { position: absolute; left: 28px; font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 80%; }
        .addr-start { top: 23%; color: #333; }
        .addr-end { bottom: 23%; color: #FF8000; font-weight: 600; }
        .driver-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; position: relative; }
        .prod-name { font-size: 18px; font-weight: bold; color: #000; margin-bottom: 5px; }
        .driver-info { font-size: 12px; color: #999; display: flex; align-items: center; }
        .star-score { color: #FF8000; margin-left: 5px; margin-right: 5px; font-weight: bold; }
        .driver-avatar-group { position: relative; width: 120px; height: 50px; display: flex; justify-content: flex-end; }
        .car-bg-img { position: absolute; right: 40px; top: 10px; height: 35px; width: auto; z-index: 1; }
        .car-avatar { position: relative; width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; z-index: 2; background: #fff; box-shadow: -2px 2px 5px rgba(0,0,0,0.1); }
        .action-row { display: flex; justify-content: space-around; text-align: center; padding-top: 10px; }
        .action-item { display: flex; flex-direction: column; align-items: center; color: #666; font-size: 11px; }
        .action-icon-circle { width: 32px; height: 32px; border-radius: 50%; background: #fff; border: 1px solid #eee; display: flex; align-items: center; justify-content: center; margin-bottom: 6px; font-size: 16px; color: #555; }
        .icon-red { color: #FF4D4F; border-color: #FFE5E5; background: #FFF1F0; }
        .bill-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; font-size: 14px; color: #000; border-bottom: 1px solid #f9f9f9; }
        .bill-row:last-child { border-bottom: none; }
        .bill-val { font-weight: 500; font-family: DIN, sans-serif; font-size: 15px; }
        .bill-gray { color: #999; font-size: 13px; display: flex; align-items: center; }
        .bill-tag { background: #FF8000; color: #fff; font-size: 10px; padding: 1px 4px; border-radius: 2px; margin-right: 5px; }
        .total-price { text-align: center; padding: 20px 0 10px; font-size: 32px; font-weight: bold; font-family: DIN, sans-serif; color: #000; }
        .total-price small { font-size: 16px; }
        .bill-footer-tips { font-size: 11px; color: #999; text-align: center; margin-bottom: 10px; }
        .pay-method-row { display: flex; align-items: center; font-size: 14px; font-weight: 500; }
        .wx-icon { color: #09BB07; font-size: 18px; margin-right: 8px; }
        .check-icon { margin-left: auto; color: #FF8000; font-size: 18px; opacity: 0.6; }
        .blue-shield-float { position: fixed; bottom: 100px; left: 15px; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; z-index: 90; }
        .bottom-bar { position: fixed; bottom: 0; left: 0; width: 100%; background: #fff; padding: 15px 20px 30px; box-shadow: 0 -2px 10px rgba(0,0,0,0.03); z-index: 100; }
        .btn-pay { display: block; width: 100%; height: 50px; line-height: 50px; text-align: center; background: #274A8C; color: #fff; font-size: 18px; font-weight: bold; border-radius: 25px; text-decoration: none; border: none; }
        .btn-pay:active { opacity: 0.9; }
        .btn-disabled { background: #ccc !important; pointer-events: none; }
        .footer-tip { text-align: center; font-size: 12px; color: #ccc; padding: 20px 0; }
    </style>
</head>
<body>
    <div class="status-bar">
        <i class="bi bi-chevron-left"></i> <?php echo $is_paid ? '已支付' : ($is_expired ? '已过期' : '待支付'); ?>
    </div>
    <div class="white-card route-card-full">
        <div class="route-image-wrapper">
            <img src="static/image/henx.png" class="route-bg">
            <div class="addr-dot dot-start"></div>
            <div class="addr-text addr-start">当前位置</div>
            <div class="addr-dot dot-end"></div>
            <div class="addr-text addr-end">前往 <?php echo htmlspecialchars($order['shop_name'] ?: '目的地'); ?></div>
        </div>
    </div>
    <div class="white-card">
        <div class="driver-header">
            <div>
                <div class="prod-name"><?php echo htmlspecialchars($order['product_name']); ?></div>
                <div class="driver-info">周师傅 <span class="star-score">★5.0</span> 1w+ 单</div>
            </div>
            <div class="driver-avatar-group">
                <img src="static/image/che.png" class="car-bg-img">
                <img src="static/image/siji.png" class="car-avatar">
            </div>
        </div>
        <hr style="border:0; border-top:1px solid #f9f9f9; margin:10px 0;">
        <div class="action-row">
            <div class="action-item"><div class="action-icon-circle icon-red" style="color:#ff4d4f; border-color:#fee;">110</div>紧急</div>
            <div class="action-item"><div class="action-icon-circle"><i class="bi bi-telephone-fill"></i></div>打电话</div>
            <div class="action-item"><div class="action-icon-circle"><i class="bi bi-info-circle-fill"></i></div>商家帮助</div>
        </div>
    </div>
    <div class="white-card">
        <div class="bill-row"><div>行程费用</div><div class="bill-val"><?php echo $order['money']; ?>元</div></div>
        <div class="bill-row"><div>优惠券</div><div class="bill-gray">无可用</div></div>
        <div class="bill-row"><div>折上折优惠</div><div class="bill-gray"><span class="bill-tag">暂无优惠</span></div></div>
        <div class="total-price"><?php echo $order['money']; ?> <small>元</small></div>
        <div class="bill-footer-tips">您正在为好友代付车费,取消订单支付金额将原路退回。</div>
        <hr style="border:0; border-top:1px solid #f9f9f9; margin:15px 0;">
        <div class="pay-method-row">
            <i class="bi bi-wechat wx-icon"></i> 微信支付
            <i class="bi bi-check-circle-fill check-icon" style="color:#FF8000; opacity:0.5;"></i>
        </div>
    </div>
    <div class="blue-shield-float"><img src="static/image/safe.png" alt="safe"></div>
    <div class="bottom-bar">
        <?php if ($can_pay): ?>
            <a href="javascript:void(0)" class="btn-pay" onclick="CashierApp.submitPayment()">为好友代付</a>
        <?php elseif ($is_paid): ?>
            <a href="javascript:void(0)" class="btn-pay btn-disabled">来迟了，代付已付款</a>
        <?php else: ?>
            <a href="javascript:void(0)" class="btn-pay btn-disabled">订单已过期</a>
        <?php endif; ?>
    </div>
    <div class="footer-tip">滴滴出行 · 安全支付</div>
</body>
</html>
