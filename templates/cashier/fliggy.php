<?php require __DIR__ . '/template_base.php'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>飞猪旅行 - 收银台</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #F7F7F7; font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif; }
        .fliggy-header { background: #fff; text-align: center; padding: 12px 0; position: relative; border-bottom: 1px solid #eee; }
        .header-title { font-size: 17px; color: #000; font-weight: 500; }
        .container { padding: 15px; }
        .user-info-row { display: flex; align-items: flex-start; margin-bottom: 20px; }
        .avatar-img { width: 45px; height: 45px; border-radius: 4px; object-fit: cover; margin-right: 12px; flex-shrink: 0; }
        .user-content { flex: 1; display: flex; flex-direction: column; }
        .nick-line { font-size: 15px; font-weight: bold; color: #333; margin-bottom: 8px; line-height: 1; padding-top: 2px; }
        .nick-suffix { color: #333; font-weight: normal; margin-left: 2px; }
        .chat-bubble { background: #FFF1E0; border-radius: 4px; padding: 12px 15px; position: relative; font-size: 14px; color: #666; line-height: 1.5; }
        .chat-bubble::before { content: ''; position: absolute; left: -8px; top: 10px; border-width: 5px 8px 5px 0; border-style: solid; border-color: transparent #FFF1E0 transparent transparent; }
        .orange-card { background: linear-gradient(90deg, #FF7E40 0%, #FF5A1E 100%); border-radius: 8px; padding: 22px 20px; color: #fff; position: relative; margin-bottom: 15px; overflow: hidden; }
        .card-header { display: flex; justify-content: space-between; font-size: 13px; opacity: 0.95; margin-bottom: 15px; }
        .price-row { margin-bottom: 25px; display: flex; align-items: baseline; }
        .price-symbol { font-size: 22px; margin-right: 2px; }
        .price-val { font-size: 38px; font-weight: bold; font-family: Arial; }
        .card-footer { font-size: 12px; opacity: 0.85; padding-top: 12px; border-top: 0.5px solid rgba(255,255,255,0.3); }
        .card-heart { position: absolute; right: -10px; bottom: -20px; font-size: 110px; opacity: 0.12; transform: rotate(15deg); pointer-events: none; }
        .product-box-colored { background: #FFF9E6; border-radius: 8px; padding: 10px 12px; display: flex; align-items: center; margin-bottom: 25px; border: 0.5px solid #FFF2CC; }
        .product-img { width: 45px; height: 45px; border-radius: 4px; object-fit: cover; margin-right: 12px; background: #fff; flex-shrink: 0; }
        .product-name { font-size: 14px; color: #333; font-weight: 500; line-height: 1.4; flex: 1; }
        .btn-blue { display: block; width: 100%; background: #1677FF; color: #fff; text-align: center; padding: 14px 0; border-radius: 25px; font-size: 17px; font-weight: bold; text-decoration: none; border: none; margin-bottom: 30px; box-shadow: 0 4px 12px rgba(22, 119, 255, 0.2); }
        .btn-blue:active { opacity: 0.9; }
        .btn-disabled { background: #ccc !important; box-shadow: none !important; pointer-events: none; }
        .instr-section { font-size: 13px; color: #777; line-height: 1.8; }
        .instr-title { font-size: 14px; font-weight: bold; color: #333; margin-bottom: 10px; }
        .instr-item { margin-bottom: 5px; }
        .instr-orange { color: #FF5A1E; }
        .provider-footer { text-align: center; font-size: 11px; color: #BBB; margin-top: 50px; padding-bottom: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="user-info-row">
            <img src="<?php echo htmlspecialchars($payer_avatar); ?>" class="avatar-img">
            <div class="user-content">
                <div class="nick-line"><?php echo htmlspecialchars($payer_nick); ?><span class="nick-suffix">(**萌)</span></div>
                <div class="chat-bubble"><?php echo htmlspecialchars($payer_slogan); ?></div>
            </div>
        </div>
        <div class="orange-card">
            <div class="card-header">
                <span>帮我付订单信息</span>
                <span><?php echo $is_paid ? '订单已完成' : '订单未支付'; ?></span>
            </div>
            <div class="price-row"><span class="price-symbol">¥</span><span class="price-val"><?php echo $order['money']; ?></span></div>
            <div class="card-footer">实际金额以付款人确认付款时为准</div>
            <div class="card-heart">&#10084;</div>
        </div>
        <div class="product-box-colored">
            <img src="<?php echo htmlspecialchars($order['image']); ?>" class="product-img">
            <div class="product-name"><?php echo htmlspecialchars($order['product_name']); ?></div>
        </div>
        <?php if ($can_pay): ?>
            <a href="javascript:void(0)" class="btn-blue" onclick="CashierApp.submitPayment()">帮他人付款</a>
        <?php else: ?>
            <a href="javascript:void(0)" class="btn-blue btn-disabled">帮他人付款</a>
        <?php endif; ?>
        <div class="instr-section">
            <div class="instr-title">帮我付说明：</div>
            <div class="instr-item">1. 本产品正在为您提供帮亲友代付款的服务，您应在自愿法规定政策允许的范围内使用本产品。</div>
            <div class="instr-item">2. 付款前请务必确认认付方身份，以免造成诈骗行为。</div>
            <div class="instr-item">3. 选择[长期用TA付款]将持续获选为对方开通亲情付，完成开通后，您仍需通过当下页面继续完成当前付款行为。</div>
            <div class="instr-item instr-orange">4. 如果交易发生退款，已支付金额将原路退回付款人的付款账户。</div>
        </div>
    </div>
    <div class="provider-footer">本服务由支付宝（中国）网络科技有限公司提供</div>
</body>
</html>
