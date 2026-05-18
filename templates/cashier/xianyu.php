<?php require __DIR__ . '/template_base.php'; ?>
<?php $_original_price = number_format(floatval($order['money']) * 1.5, 2); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>闲鱼 - 收银台</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #fff; font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif; padding-bottom: 80px; color: #111; }
        .header { display: flex; align-items: center; padding: 12px 15px; background: #fff; }
        .u-avatar { width: 36px; height: 36px; border-radius: 50%; margin-right: 10px; object-fit: cover; border: 1px solid #f0f0f0; }
        .u-info { flex: 1; display: flex; justify-content: space-between; align-items: center; }
        .u-left-group { display: flex; align-items: center; }
        .u-name { font-size: 15px; font-weight: bold; color: #111; margin-right: 6px; }
        .u-tag { background: #E6F1FC; color: #108EE9; font-size: 10px; padding: 1px 4px; border-radius: 2px; font-weight: bold; display: flex; align-items: center; white-space: nowrap; }
        .u-status { font-size: 11px; color: #999; text-align: right; white-space: nowrap; }
        .price-section { padding: 5px 15px; display: flex; align-items: flex-end; }
        .p-symbol { font-size: 18px; color: #FF2B2B; font-weight: bold; margin-bottom: 3px; }
        .p-val { font-size: 32px; color: #FF2B2B; font-weight: bold; font-family: DIN, sans-serif; line-height: 1; }
        .p-badge-red { color: #FF2B2B; font-size: 11px; background: rgba(255, 43, 43, 0.08); padding: 2px 5px; border-radius: 4px; margin-left: 8px; margin-bottom: 4px; }
        .p-sub-row { padding: 0 15px; margin-top: 5px; }
        .p-sub-tag { border: 1px solid #FF2B2B; color: #FF2B2B; font-size: 10px; padding: 0 2px; border-radius: 2px; display: inline-block; }
        .p-stats-row { padding: 0 15px; margin-top: 8px; color: #999; font-size: 12px; display: flex; align-items: center; }
        .text-del { text-decoration: line-through; margin-left: 2px; margin-right: 2px; }
        .content { padding: 10px 15px 0; }
        .safety-tip { margin-bottom: 15px; font-size: 12px; color: #333; line-height: 1.6; }
        .st-title { font-weight: bold; display: flex; align-items: center; margin-bottom: 4px; }
        .st-title i { margin-left: 4px; color: #999; font-size: 13px; }
        .st-desc { color: #999; display: block; }
        .c-text { font-size: 16px; line-height: 1.6; color: #000; font-weight: 500; margin-bottom: 20px; margin-top: 0; white-space: pre-wrap; text-align: left; display: block; }
        .prod-img-box { padding: 0 15px; margin-bottom: 20px; }
        .prod-img { width: 100%; border-radius: 12px; display: block; }
        .bottom-bar { position: fixed; bottom: 0; left: 0; width: 100%; background: #fff; display: flex; align-items: center; padding: 8px 10px; padding-bottom: max(8px, env(safe-area-inset-bottom)); box-shadow: 0 -1px 5px rgba(0,0,0,0.05); z-index: 100; }
        .b-icon-grp { display: flex; margin-right: 10px; }
        .b-icon-item { width: 48px; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 10px; color: #333; cursor: pointer; }
        .b-icon-item:active { opacity: 0.7; }
        .b-icon-item i { font-size: 20px; margin-bottom: 2px; }
        .b-btns { flex: 1; display: flex; gap: 10px; }
        .btn-chat { width: 80px; height: 44px; background: #F6F7F9; border-radius: 22px; display: flex; align-items: center; justify-content: center; color: #111; font-weight: bold; font-size: 14px; cursor: pointer; }
        .btn-chat:active { background: #eee; }
        .btn-pay-friend { flex: 1; height: 44px; background: #FFDA44; border-radius: 22px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #111; text-decoration: none; line-height: 1.1; }
        .btn-pay-friend:active { opacity: 0.9; }
        .btn-pay-price { font-size: 16px; font-weight: 900; font-family: DIN, sans-serif; }
        .btn-pay-label { font-size: 11px; font-weight: 500; }
        .btn-disabled { background: #eee !important; color: #999 !important; pointer-events: none; }
    </style>
</head>
<body>
    <div class="header">
        <img src="<?php echo htmlspecialchars($payer_avatar); ?>" class="u-avatar">
        <div class="u-info">
            <div class="u-left-group">
                <div class="u-name"><?php echo htmlspecialchars($order['shop_name'] ?: '卖家'); ?></div>
                <div class="u-tag">鱼小铺 L1</div>
            </div>
            <div class="u-status">1小时前来过 | 济南</div>
        </div>
    </div>
    <div class="price-section">
        <span class="p-symbol">¥</span>
        <span class="p-val"><?php echo $order['money']; ?></span>
        <span class="p-badge-red">2人小刀价</span>
    </div>
    <div class="p-sub-row"><span class="p-sub-tag">包邮</span></div>
    <div class="p-stats-row">直接买 <span class="text-del">¥<?php echo $_original_price; ?></span> | 2人想要 | 128人浏览</div>
    <div class="content">
        <div class="safety-tip">
            <div class="st-title">闲鱼交易须知 <i class="bi bi-info-circle-fill"></i></div>
            <div class="st-desc">买前了解退货规则，保障你的交易权益</div>
        </div>
        <div class="c-text"><?php echo htmlspecialchars($order['product_name']); ?></div>
    </div>
    <div class="prod-img-box">
        <img src="<?php echo htmlspecialchars($order['image']); ?>" class="prod-img">
    </div>
    <div class="bottom-bar">
        <div class="b-icon-grp">
            <div class="b-icon-item" onclick="alert('留言功能暂不可用')"><i class="bi bi-chat-dots"></i>留言</div>
            <div class="b-icon-item" onclick="alert('收藏成功')"><i class="bi bi-star"></i>收藏</div>
        </div>
        <div class="b-btns">
            <div class="btn-chat" onclick="alert('非买家账号不能聊天')">聊一聊</div>
            <?php if ($can_pay): ?>
                <a href="javascript:void(0)" class="btn-pay-friend" onclick="CashierApp.submitPayment()">
                    <div class="btn-pay-price">¥<?php echo $order['money']; ?></div>
                    <div class="btn-pay-label">为好友买单</div>
                </a>
            <?php else: ?>
                <a href="javascript:void(0)" class="btn-pay-friend btn-disabled">
                    <div class="btn-pay-price"><?php echo $is_paid ? '已卖出' : '已失效'; ?></div>
                    <div class="btn-pay-label"><?php echo $is_paid ? '交易完成' : '宝贝不存在'; ?></div>
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
