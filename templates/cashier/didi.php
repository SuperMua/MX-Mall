<?php require __DIR__ . '/template_base.php'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>滴滴出行 - 收银台</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #F3F4F5; font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif; }
        .didi-header { background: #fff; text-align: center; padding: 12px 0; font-size: 17px; font-weight: 500; color: #000; border-bottom: 1px solid #eee; }
        .main-card { background: #fff; margin: 12px; border-radius: 12px; padding: 40px 20px 30px 20px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
        .pay-label { font-size: 16px; color: #333; margin-bottom: 15px; font-weight: 500; }
        .pay-price { font-size: 40px; color: #000; font-weight: 500; font-family: Arial, sans-serif; margin-bottom: 40px; letter-spacing: -0.5px; }
        .pay-price span { font-size: 18px; font-weight: normal; margin-left: 2px; }
        .notice-box { background: #F7F8FA; border-radius: 8px; padding: 20px; text-align: left; margin-bottom: 30px; }
        .notice-title { font-size: 14px; color: #999; margin-bottom: 10px; }
        .notice-item { font-size: 13px; color: #999; line-height: 1.6; margin-bottom: 8px; display: flex; }
        .notice-num { margin-right: 4px; }
        .btn-didi { display: block; width: 100%; background: #29C378; color: #fff; font-size: 17px; font-weight: bold; text-align: center; padding: 14px 0; border-radius: 25px; text-decoration: none; border: none; box-shadow: 0 4px 10px rgba(41, 195, 120, 0.2); }
        .btn-didi:active { opacity: 0.9; }
        .btn-disabled { background: #ccc !important; box-shadow: none !important; pointer-events: none; }
        .footer-info { text-align: center; font-size: 12px; color: #ccc; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="main-card">
        <div class="pay-label"><?php echo $is_paid ? '已付款' : ($is_expired ? '订单已失效' : '需付款'); ?></div>
        <div class="pay-price"><?php echo $order['money']; ?><span>元</span></div>
        <div class="notice-box">
            <div class="notice-title">请您知悉：</div>
            <div class="notice-item"><span class="notice-num">1.</span><span>您支付的订单，是亲友使用滴滴出行打车时发生的金额，详细账单以您亲友的滴滴出行或小程序订单明细为准</span></div>
            <div class="notice-item"><span class="notice-num">2.</span><span>若亲友选择了滴滴平台发放的各类优惠，则本金额为抵扣过亲友优惠的金额</span></div>
            <div class="notice-item"><span class="notice-num">3.</span><span>如发生退款，实付金额将原路退还给代付人</span></div>
        </div>
        <?php if ($can_pay): ?>
            <a href="javascript:void(0)" class="btn-didi" onclick="CashierApp.submitPayment()">为好友买单</a>
        <?php elseif ($is_paid): ?>
            <a href="javascript:void(0)" class="btn-didi btn-disabled">来迟了，代付已付款</a>
        <?php else: ?>
            <a href="javascript:void(0)" class="btn-didi btn-disabled">订单已过期</a>
        <?php endif; ?>
    </div>
    <div class="footer-info">滴滴出行 · 安全支付</div>
</body>
</html>
