<?php
/**
 * MX-Mall - Product Detail Page
 */
// 安装锁检测 - 未安装时跳转到安装页
$lockFile = __DIR__ . '/../install/install.lock';
if (!file_exists($lockFile)) {
    header('Location: /install.php');
    exit;
}
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <title>商品详情 - MX-Mall</title>
    <link rel="stylesheet" href="/assets/css/mobile.css?v=5">
    <style>
        .detail-img-swiper {
            width: 100%;
            aspect-ratio: 1;
            background: var(--bg-white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
            color: var(--text-muted);
            position: relative;
        }
        .detail-img-swiper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .detail-img-counter {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(0,0,0,0.5);
            color: #fff;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
        }
        .detail-price-box {
            background: var(--bg-white);
            padding: 16px;
        }
        .detail-price-box .price-main {
            font-size: 28px;
            font-weight: 700;
            color: var(--danger);
        }
        .detail-price-box .price-main .yen { font-size: 16px; }
        .detail-price-box .price-original {
            font-size: 13px;
            color: var(--text-muted);
            text-decoration: line-through;
            margin-left: 8px;
        }
        .detail-price-box .price-info {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }
        .detail-price-box .price-tag {
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 10px;
            background: rgba(108,92,231,0.1);
            color: var(--primary);
        }
        .detail-name-box {
            padding: 16px;
            background: var(--bg-white);
            margin-top: 8px;
        }
        .detail-name-box .goods-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.5;
            margin-bottom: 8px;
        }
        .detail-name-box .goods-subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.5;
        }
        .detail-specs {
            padding: 16px;
            background: var(--bg-white);
            margin-top: 8px;
        }
        .detail-specs .spec-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-light);
            font-size: 13px;
        }
        .detail-specs .spec-row:last-child { border-bottom: none; }
        .detail-specs .spec-label { color: var(--text-muted); }
        .detail-specs .spec-value { color: var(--text-primary); }
        .detail-desc-box {
            padding: 16px;
            background: var(--bg-white);
            margin-top: 8px;
        }
        .detail-desc-box .desc-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 12px;
        }
        .detail-desc-box .desc-content {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.8;
        }
        .detail-body {
            padding-bottom: 70px;
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- Top Nav -->
    <nav class="top-nav">
        <div class="nav-back" onclick="history.back()">&#8249;</div>
        <div class="nav-title">商品详情</div>
        <div class="nav-action">&#8942;</div>
    </nav>

    <!-- Product Detail -->
    <div class="page-content no-tab" style="padding-top:52px;">
        <div class="detail-body">
            <!-- Product Image -->
            <div class="detail-img-swiper" id="product-image">
                &#128241;
                <div class="detail-img-counter">1/1</div>
            </div>

            <!-- Price -->
            <div class="detail-price-box">
                <div class="flex" style="align-items:baseline;">
                    <span class="price-main"><span class="yen">&yen;</span><span id="product-price">0.00</span></span>
                    <span class="price-original" id="product-original-price">&yen;0.00</span>
                </div>
                <div class="price-info">
                    <span class="price-tag">MX-Mall优选</span>
                    <span class="price-tag" style="background:rgba(255,107,107,0.15);color:var(--danger);">限时特惠</span>
                    <span style="font-size:12px;color:var(--text-muted);margin-left:auto;">已售 <span id="product-sold">0</span>+</span>
                </div>
            </div>

            <!-- Name -->
            <div class="detail-name-box">
                <div class="goods-name" id="product-name">加载中...</div>
                <div class="goods-subtitle" id="product-subtitle"></div>
            </div>

            <!-- Specs -->
            <div class="detail-specs">
                <div class="spec-row">
                    <span class="spec-label">配送</span>
                    <span class="spec-value">全国包邮 | 预计48小时内发货</span>
                </div>
                <div class="spec-row">
                    <span class="spec-label">服务</span>
                    <span class="spec-value">7天无理由退换 | 正品保障</span>
                </div>
                <div class="spec-row">
                    <span class="spec-label">数量</span>
                    <span class="spec-value">
                        <div class="qty-selector">
                            <button class="qty-btn" onclick="changeQty(-1)">-</button>
                            <span class="qty-value" id="qty-value">1</span>
                            <button class="qty-btn" onclick="changeQty(1)">+</button>
                        </div>
                    </span>
                </div>
            </div>

            <!-- Description -->
            <div class="detail-desc-box">
                <div class="desc-title">商品详情</div>
                <div class="desc-content" id="product-desc">
                    精选优质商品，品质保证。MX-Mall 为您提供正品行货，全场包邮，售后无忧。
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="detail-bottom-bar">
        <div class="bar-icon-btn" onclick="NexusApp.go('/index.php')">
            <span class="icon">&#127968;</span>
            <span>首页</span>
        </div>
        <button class="btn-buy-now" onclick="buyNow()" style="flex:1;">立即购买</button>
    </div>
</div>

<script src="/assets/js/app.js?v=5"></script>
<script>
    let product = null;
    let quantity = 1;
    const productId = <?php echo $id; ?>;

    // Load product detail
    async function loadProduct() {
        if (!productId) {
            NexusApp.toast('商品不存在', 'error');
            return;
        }

        try {
            const res = await NexusApp.get(`/products/${productId}`);
            product = res.data || res;
            renderProduct();
        } catch (e) {
            // Demo product
            loadDemoProduct();
        }
    }

    function loadDemoProduct() {
        const demoProducts = {
            1: { id: 1, name: 'Apple iPhone 15 Pro Max 256GB 原色钛金属', price: 9999, original_price: 10999, image: '', category: 'digital', sold: 2341, tag: '热卖', desc: 'iPhone 15 Pro Max 搭载 A17 Pro 芯片，钛金属设计，4800万像素主摄系统，支持USB-C接口。超强性能，极致体验。' },
            2: { id: 2, name: 'Sony WH-1000XM5 无线降噪头戴式耳机 黑色', price: 2299, original_price: 2999, image: '', category: 'digital', sold: 1823, tag: '新品', desc: '业界领先降噪技术，30小时续航，自适应声音控制，轻量化舒适设计。' },
            3: { id: 3, name: 'Nike Air Max 270 React 男子运动鞋', price: 1099, original_price: 1399, image: '', category: 'sports', sold: 5621, tag: '', desc: 'React泡棉中底搭配Max Air 270气垫，轻盈缓震，时尚百搭。' },
            4: { id: 4, name: 'SK-II 神仙水 护肤精华露 230ml', price: 1190, original_price: 1540, image: '', category: 'beauty', sold: 8902, tag: '爆款', desc: '含超过90%PITERA精华，改善肤质五大维度，让肌肤晶莹剔透。' },
            5: { id: 5, name: '三只松鼠坚果大礼包 1458g 年货零食', price: 89.9, original_price: 139, image: '', category: 'food', sold: 12500, tag: '', desc: '精选8种坚果零食，每日坚果组合，营养健康，送礼自用皆宜。' },
        };

        product = demoProducts[productId] || {
            id: productId,
            name: 'MX-Mall 精选商品 #' + productId,
            price: 99.9 + productId * 100,
            original_price: 199.9 + productId * 100,
            image: '',
            category: 'digital',
            sold: Math.floor(Math.random() * 5000) + 100,
            tag: '推荐',
            desc: '这是一款来自MX-Mall的精选优质商品。我们严格把控品质，确保每一件商品都符合最高标准。正品保障，售后无忧。'
        };
        renderProduct();
    }

    function renderProduct() {
        if (!product) return;

        document.getElementById('product-price').textContent = NexusApp.formatPrice(product.price);
        document.getElementById('product-original-price').textContent = '\u00a5' + NexusApp.formatPrice(product.original_price || product.price * 1.2);
        document.getElementById('product-name').textContent = product.name;
        document.getElementById('product-subtitle').textContent = product.tag ? product.tag + ' | MX-Mall优选' : 'MX-Mall优选';
        document.getElementById('product-sold').textContent = product.sales_count || product.sold || 0;
        document.getElementById('product-desc').textContent = product.desc || '精选优质商品，品质保证。';
        document.title = product.name + ' - MX-Mall';

        const imgEl = document.getElementById('product-image');
        if (product.image) {
            imgEl.innerHTML = `<img src="${product.image}" alt="${product.name}"><div class="detail-img-counter">1/1</div>`;
        }
    }

    function changeQty(delta) {
        quantity = Math.max(1, Math.min(99, quantity + delta));
        document.getElementById('qty-value').textContent = quantity;
    }

    function buyNow() {
        if (!product) return;
        // 直接跳转到选择收银台模版页面
        window.location.href = '/checkout.php?product_id=' + product.id;
    }

    document.addEventListener('DOMContentLoaded', loadProduct);
</script>
</body>
</html>
