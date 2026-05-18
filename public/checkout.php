<?php
/**
 * MX-Mall - Checkout Page (Select Cashier Template)
 */
// 安装锁检测 - 未安装时跳转到安装页
$lockFile = __DIR__ . '/../install/install.lock';
if (!file_exists($lockFile)) {
    header('Location: /install.php');
    exit;
}
$tradeNo = isset($_GET['trade_no']) ? trim($_GET['trade_no']) : '';
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <title>选择收银台 - MX-Mall</title>
    <link rel="stylesheet" href="/assets/css/mobile.css?v=5">
    <style>
        .checkout-page { padding-bottom: 80px; }
        .tpl-section-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            padding: 16px 16px 8px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .template-card .tpl-icon-wrap {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 8px;
        }
        .template-card .tpl-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 3px;
        }
        .template-card .tpl-desc {
            font-size: 11px;
            color: var(--text-muted);
            line-height: 1.3;
        }
        .template-card .tpl-color-dots {
            display: flex;
            gap: 4px;
            margin-top: 8px;
        }
        .template-card .tpl-color-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- Top Nav -->
    <nav class="top-nav">
        <div class="nav-back" onclick="history.back()">&#8249;</div>
        <div class="nav-title">选择收银台</div>
        <div class="nav-action"></div>
    </nav>

    <!-- Page Content -->
    <div class="page-content no-tab checkout-page" style="padding-top:52px;">
        <!-- Order Summary -->
        <div class="order-summary" id="order-summary">
            <div class="summary-title">订单摘要</div>
            <div id="summary-items">
                <!-- Rendered by JS -->
            </div>
            <div class="summary-total">
                <span class="total-label">应付金额</span>
                <span class="total-value"><span class="yen" style="font-size:14px;">&yen;</span><span id="summary-total-amount">0.00</span></span>
            </div>
        </div>

        <!-- Template Selection -->
        <div class="tpl-section-title">选择收银台模版</div>
        <div class="template-grid" id="template-grid">
            <!-- 14 templates -->
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="checkout-bottom-bar">
        <div class="checkout-info">
            <div class="pay-label">支付金额</div>
            <div class="pay-amount"><span class="yen" style="font-size:14px;">&yen;</span><span id="pay-amount-bottom">0.00</span></div>
        </div>
        <button class="btn-go-pay" id="btn-go-pay" onclick="goToCashier()" disabled>去支付</button>
    </div>
</div>

<script src="/assets/js/app.js?v=5"></script>
<script>
    const tradeNo = '<?php echo htmlspecialchars($tradeNo); ?>';
    const productId = <?php echo $productId; ?>;
    let selectedTpl = '';
    let orderInfo = null;

    // Template definitions
    const templates = [
        { id: 'meituan', name: '美团外卖', color: '#FDD934', icon: 'static/image/meituan.png' },
        { id: 'jd', name: '京东', color: '#F2270C', icon: 'static/image/jingdong.png' },
        { id: 'ctrip-flight', name: '携程机票', color: '#2986F6', icon: 'static/image/xiecheng.png' },
        { id: 'didi', name: '滴滴出行', color: '#29C378', icon: 'static/image/didi.png' },
        { id: 'pdd', name: '拼多多', color: '#E02E24', icon: 'static/image/pinduoduo.png' },
        { id: 'taobao', name: '淘宝', color: '#FF5000', icon: 'static/image/taobao.png' },
        { id: 'ctrip-hotel', name: '携程酒店', color: '#2dbb9a', icon: 'static/image/xiecheng.png' },
        { id: 'fliggy', name: '飞猪旅行', color: '#FF5A1E', icon: 'static/image/feizhu.png' },
        { id: 'dewu', name: '得物', color: '#00C2C9', icon: 'static/image/dewu.png' },
        { id: 'maoyan', name: '猫眼电影', color: '#F03D37', icon: 'static/image/maoyan.png' },
        { id: 'taobao2', name: '淘宝好物', color: '#FF5000', icon: 'static/image/taobaohaowu.png' },
        { id: 'douyin', name: '抖音商城', color: '#FE2C55', icon: 'static/image/douyin.png' },
        { id: 'didi2', name: '滴滴Pro', color: '#29C378', icon: 'static/image/didipro.png' },
        { id: 'xianyu', name: '闲鱼', color: '#FF6600', icon: 'static/image/xianyu.png' },
    ];

    // Load order info
    async function loadOrder() {
        // If product_id is provided, load product info directly
        if (productId > 0) {
            try {
                const res = await NexusApp.get('/products/' + productId);
                const product = res.data || res;
                if (product) {
                    orderInfo = {
                        amount: product.price,
                        subject: product.name,
                        items: [{ name: product.name, price: product.price, quantity: 1 }]
                    };
                    renderOrderSummary();
                    return;
                }
            } catch (e) {
                // Fall through to trade_no logic
            }
        }

        // Original trade_no based logic
        try {
            const res = await NexusApp.get(`/order?trade_no=${tradeNo}`);
            orderInfo = res.data || res;
            renderOrderSummary();
        } catch (e) {
            // Try session storage
            const sessionData = sessionStorage.getItem('checkout_data');
            if (sessionData) {
                orderInfo = JSON.parse(sessionData);
                renderOrderSummary();
            } else {
                // Demo order
                orderInfo = {
                    trade_no: tradeNo,
                    amount: 99.9,
                    subject: 'MX-Mall 商品订单',
                    items: [{ name: '精选商品', price: 99.9, quantity: 1 }]
                };
                renderOrderSummary();
            }
        }
    }

    function renderOrderSummary() {
        if (!orderInfo) return;

        const items = orderInfo.items || [];
        const itemsHtml = items.length > 0
            ? items.map(item => `
                <div class="summary-item">
                    <span class="label">${item.name} x${item.quantity}</span>
                    <span class="value">&yen;${NexusApp.formatPrice(item.price * item.quantity)}</span>
                </div>
            `).join('')
            : `<div class="summary-item">
                <span class="label">${orderInfo.subject || '商品订单'}</span>
                <span class="value">&yen;${NexusApp.formatPrice(orderInfo.amount || orderInfo.total_amount || 0)}</span>
            </div>`;

        document.getElementById('summary-items').innerHTML = itemsHtml;

        const total = orderInfo.amount || orderInfo.total_amount || (items.reduce((s, i) => s + i.price * i.quantity, 0));
        document.getElementById('summary-total-amount').textContent = NexusApp.formatPrice(total);
        document.getElementById('pay-amount-bottom').textContent = NexusApp.formatPrice(total);
    }

    // Render template grid
    function renderTemplates() {
        const grid = document.getElementById('template-grid');
        grid.innerHTML = templates.map(tpl => `
            <div class="template-card" id="tpl-${tpl.id}" onclick="selectTemplate('${tpl.id}')">
                <div class="tpl-brand-bar" style="background:${tpl.color};"></div>
                <div class="tpl-icon" style="background:${tpl.color}20; color:${tpl.color};">
                    ${tpl.icon ? `<img src="/${tpl.icon}" style="width:28px;height:28px;border-radius:6px;object-fit:contain;">` : `<span style="font-size:16px;font-weight:700;color:${tpl.color};">${tpl.name.charAt(0)}</span>`}
                </div>
                <div class="tpl-name">${tpl.name}</div>
                <div class="tpl-check">&#10003;</div>
            </div>
        `).join('');
    }

    function selectTemplate(tplId) {
        console.log('Selecting template:', tplId);
        // Remove previous selection
        document.querySelectorAll('.template-card').forEach(el => el.classList.remove('selected'));
        // Add selection
        const selectedEl = document.getElementById('tpl-' + tplId);
        if (selectedEl) {
            selectedEl.classList.add('selected');
            selectedTpl = tplId;
            const btn = document.getElementById('btn-go-pay');
            btn.disabled = false;
            btn.style.opacity = '1';
            console.log('Template selected, button enabled');
        }
    }

    async function goToCashier() {
        console.log('goToCashier called, selectedTpl:', selectedTpl);
        if (!selectedTpl) {
            NexusApp.toast('请选择收银台模版', 'error');
            return;
        }

        const btn = document.getElementById('btn-go-pay');
        btn.disabled = true;
        btn.textContent = '处理中...';

        // 先创建订单
        try {
            const res = await NexusApp.post('/cart/checkout', {
                product_id: productId,
                template_id: selectedTpl,
            });

            console.log('Checkout response:', res);

            if (res.code === 0 && res.data) {
                const cashierUrl = res.data.cashier_url;
                if (cashierUrl) {
                    window.location.href = cashierUrl;
                    return;
                }
                // fallback
                const tradeNo = res.data.out_trade_no || res.data.trade_no;
                if (tradeNo) {
                    window.location.href = '/cashier.php?trade_no=' + tradeNo + '&tpl=' + selectedTpl;
                    return;
                }
            }
            NexusApp.toast(res.msg || '创建订单失败', 'error');
        } catch (e) {
            console.error('Checkout error:', e);
            NexusApp.toast(e.message || '创建订单失败', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = '去支付';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderTemplates();
        loadOrder();
    });
</script>
</body>
</html>
