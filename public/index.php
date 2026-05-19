<?php
/**
 * MX-Mall - Mobile Homepage
 */

// 安装锁检测 - 未安装时跳转到安装页
$lockFile = __DIR__ . '/../install/install.lock';
if (!file_exists($lockFile)) {
    header('Location: /install.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <title>MX-Mall</title>
    <script>
    // 在页面渲染前立即设置标题（从缓存，避免闪烁）
    try {
        let cached = JSON.parse(localStorage.getItem('site_config_cache') || '{}');
        if (cached.site) cached = { ...cached.site, ...cached.payment, ...cached };
        if (cached.site_name) document.title = cached.site_name;
    } catch(e) {}
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/mobile.css?v=5">
    <style>
        .bi::before { display: inline; }
        .home-banner { position: relative; }
        .home-banner .dots { display: flex; justify-content: center; gap: 6px; margin-top: 10px; }
        .home-banner .dot { width: 6px; height: 6px; border-radius: 3px; background: var(--border); }
        .home-banner .dot.active { width: 18px; background: var(--primary); }
        .flash-sale-header { display: flex; align-items: center; gap: 8px; }
        .flash-sale-header .flash-icon { font-size: 18px; }
        .flash-sale-header .flash-timer { background: var(--danger); color: #fff; font-size: 11px; padding: 2px 6px; border-radius: 3px; font-weight: 600; }
        .product-scroll { display: flex; overflow-x: auto; gap: 10px; padding: 0 12px 12px; scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; }
        .product-scroll::-webkit-scrollbar { display: none; }
        .product-scroll .scroll-card { flex-shrink: 0; width: 120px; scroll-snap-align: start; }
        .product-scroll .scroll-card .product-img { width: 120px; height: 120px; border-radius: var(--radius-md); background: var(--bg-elevated); display: flex; align-items: center; justify-content: center; font-size: 36px; color: var(--text-muted); margin-bottom: 6px; }
        .product-scroll .scroll-card .scroll-name { font-size: 12px; color: var(--text-primary); line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 4px; }
        .product-scroll .scroll-card .scroll-price { font-size: 14px; font-weight: 700; color: var(--danger); }
    </style>
</head>
<body>
<div class="app-container">
    <!-- Top Nav -->
    <nav class="top-nav" style="height:48px;">
        <div class="nav-content" style="display:flex;align-items:center;justify-content:center;height:100%;">
            <span class="logo-text" style="font-size:16px;font-weight:600;">MX-Mall</span>
        </div>
    </nav>

    <!-- Page Content -->
    <div class="page-content">
        <!-- Search Bar -->
        <div class="search-bar">
            <div class="search-input-wrap">
                <span class="search-icon">&#128269;</span>
                <input type="text" class="search-input" placeholder="搜索商品..." id="search-input" oninput="filterProducts()">
            </div>
        </div>

        <!-- Banner -->
        <div class="banner-section" id="default-banner">
            <div class="banner-card home-banner">
                <div class="banner-text">
                    <h2>MX-Mall</h2>
                    <p>探索无限可能</p>
                </div>
            </div>
            <div class="dots">
                <div class="dot active"></div>
                <div class="dot"></div>
                <div class="dot"></div>
            </div>
        </div>

        <!-- Dynamic Banner Carousel (loaded from API) -->
        <div id="carousel-section" style="display:none;padding:12px 16px 0;">
            <div id="carousel-container" style="position:relative;border-radius:var(--radius-md);overflow:hidden;">
                <div id="carousel-slides" style="display:flex;transition:transform 0.5s ease;">
                </div>
                <div id="carousel-dots" style="display:flex;justify-content:center;gap:6px;margin-top:8px;padding-bottom:4px;"></div>
            </div>
        </div>

        <!-- Hot Products -->
        <div class="section-header">
            <div class="section-title">热门推荐</div>
        </div>

        <!-- Product Grid -->
        <div class="product-grid" id="product-grid">
            <!-- Products loaded by AJAX -->
        </div>

        <!-- Loading More -->
        <div class="text-center p-16" id="load-more" style="display:none;">
            <span class="text-muted" style="font-size:13px;">加载更多...</span>
        </div>
    </div>

    <!-- Bottom Tab Bar -->
    <div class="tab-bar" id="mainTabBar">
        <a class="tab-item active" href="/index.php">
            <span class="tab-icon">&#127968;</span>
            <span class="tab-label">首页</span>
        </a>
        <a class="tab-item" id="categoryTab" onclick="toggleCategoryPanel()" style="display:none;">
            <span class="tab-icon"><i class="bi bi-grid-3x3-gap" style="font-size:20px;"></i></span>
            <span class="tab-label">分类</span>
        </a>
        <a class="tab-item" href="/user.php">
            <span class="tab-icon">&#128100;</span>
            <span class="tab-label">我的</span>
        </a>
    </div>

    <!-- Category Overlay -->
    <div id="categoryOverlay" onclick="toggleCategoryPanel()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:998;background:rgba(0,0,0,0.4);opacity:0;transition:opacity 0.3s ease;"></div>

    <!-- Category Panel (slide up from bottom) -->
    <div id="categoryPanel" style="position:fixed;left:0;right:0;bottom:0;z-index:999;background:var(--bg-white);border-radius:16px 16px 0 0;transform:translateY(100%);transition:transform 0.35s cubic-bezier(0.4,0,0.2,1);max-height:70vh;overflow-y:auto;box-shadow:0 -4px 20px rgba(0,0,0,0.1);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px 8px;border-bottom:1px solid var(--border-light);">
            <span style="font-size:16px;font-weight:600;color:var(--text-primary);">商品分类</span>
            <span onclick="toggleCategoryPanel()" style="font-size:20px;color:var(--text-secondary);cursor:pointer;padding:4px;">&#10005;</span>
        </div>
        <div style="padding:12px 16px 20px;" id="categoryPanelList">
        </div>
    </div>
</div>

<script src="/assets/js/app.js?v=5"></script>
<script>
    let allProducts = [];
    let currentCategory = 'all';

    // Load products from API
    async function loadProducts() {
        try {
            const res = await NexusApp.get('/products');
            const data = res.data || {};
            allProducts = data.list || [];

            // 动态渲染分类（从API获取，与后台同步）
            if (data.categories && data.categories.length > 0) {
                renderCategories(data.categories);
            }

            renderProducts(allProducts);
        } catch (e) {
            showDemoProducts();
        }
    }

    // Load site config (site name + subtitle)
    async function loadSiteConfig() {
        try {
            const res = await NexusApp.get('/site/config');
            if (res.code === 0 && res.data) {
                // API返回分组数据 {site:{...}, payment:{...}, general:{...}}，展平
                let config = res.data;
                // 展平所有分组到顶层
                Object.keys(config).forEach(group => {
                    if (typeof config[group] === 'object' && config[group] !== null) {
                        Object.assign(config, config[group]);
                    }
                });
                // 删除分组键
                ['site', 'payment', 'general', 'pay_epay', 'pay_lakala'].forEach(g => delete config[g]);

                // 更新站点名称
                if (config.site_name) {
                    document.title = config.site_name;
                    const bannerH2 = document.querySelector('#default-banner h2');
                    if (bannerH2) bannerH2.textContent = config.site_name;
                }
                // 更新副标题
                if (config.site_subtitle) {
                    const bannerP = document.querySelector('#default-banner p');
                    if (bannerP) bannerP.textContent = config.site_subtitle;
                }
                // 缓存配置到localStorage（下次刷新时立即使用）
                localStorage.setItem('site_config_cache', JSON.stringify(config));

                // Load banners from config
                try {
                    var banners = JSON.parse(config.banner_images || '[]');
                    if (banners.length > 0) {
                        var links = JSON.parse(config.banner_links || '[]');
                        renderCarousel(banners, links);
                    }
                } catch(e2) {}
            }
        } catch(e) {}
    }

    // Render banner carousel
    let carouselIndex = 0;
    let carouselTimer = null;

    function renderCarousel(banners, links) {
        const defaultBanner = document.getElementById('default-banner');
        const carouselSection = document.getElementById('carousel-section');
        const slidesContainer = document.getElementById('carousel-slides');
        const dotsContainer = document.getElementById('carousel-dots');

        if (!defaultBanner || !carouselSection) return;

        // Hide default banner, show carousel
        defaultBanner.style.display = 'none';
        carouselSection.style.display = 'block';

        // Render slides
        slidesContainer.innerHTML = banners.map(function(url, i) {
            var link = (links && links[i]) ? links[i] : '';
            var imgHtml = '<img src="' + url + '" style="width:100%;height:180px;object-fit:cover;border-radius:var(--radius-md);display:block;" onerror="this.parentElement.parentElement.parentElement.style.display=\'none\';document.getElementById(\'default-banner\').style.display=\'\';">';
            if (link) {
                return '<div style="flex-shrink:0;width:100%;"><a href="' + link + '" target="_blank" rel="noopener">' + imgHtml + '</a></div>';
            }
            return '<div style="flex-shrink:0;width:100%;">' + imgHtml + '</div>';
        }).join('');

        // Render dots
        dotsContainer.innerHTML = banners.map((_, i) =>
            `<div class="dot${i === 0 ? ' active' : ''}" onclick="goToSlide(${i})"></div>`
        ).join('');

        // Auto-play
        carouselIndex = 0;
        if (carouselTimer) clearInterval(carouselTimer);
        if (banners.length > 1) {
            carouselTimer = setInterval(() => {
                carouselIndex = (carouselIndex + 1) % banners.length;
                updateCarousel();
            }, 4000);
        }
    }

    function goToSlide(index) {
        carouselIndex = index;
        updateCarousel();
    }

    function updateCarousel() {
        const slidesContainer = document.getElementById('carousel-slides');
        if (slidesContainer) {
            slidesContainer.style.transform = `translateX(-${carouselIndex * 100}%)`;
        }
        const dots = document.querySelectorAll('#carousel-dots .dot');
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === carouselIndex);
        });
    }

    // Demo products for testing
    function showDemoProducts() {
        allProducts = [
            { id: 1, name: 'Apple iPhone 15 Pro Max 256GB 原色钛金属', price: 9999, image: '', category_id: 0, sold: 2341, tag: '热卖' },
            { id: 2, name: 'Sony WH-1000XM5 无线降噪头戴式耳机 黑色', price: 2299, image: '', category_id: 0, sold: 1823, tag: '新品' },
            { id: 3, name: 'Nike Air Max 270 React 男子运动鞋', price: 1099, image: '', category_id: 0, sold: 5621, tag: '' },
            { id: 4, name: 'SK-II 神仙水 护肤精华露 230ml', price: 1190, image: '', category_id: 0, sold: 8902, tag: '爆款' },
            { id: 5, name: '三只松鼠坚果大礼包 1458g 年货零食', price: 89.9, image: '', category_id: 0, sold: 12500, tag: '' },
            { id: 6, name: '小米14 Ultra 徕卡光学 骁龙8Gen3', price: 5999, image: '', category_id: 0, sold: 3421, tag: '新品' },
            { id: 7, name: '优衣库 男装 圆领T恤 短袖 休闲舒适', price: 79, image: '', category_id: 0, sold: 23100, tag: '' },
            { id: 8, name: '得物联名限定款 潮流运动套装', price: 459, image: '', category_id: 0, sold: 982, tag: '限定' },
            { id: 9, name: 'iPad Air M2 11英寸 256GB WiFi版', price: 4799, image: '', category_id: 0, sold: 4521, tag: '' },
            { id: 10, name: '星巴克臻选咖啡豆 礼盒装 250g*3', price: 298, image: '', category_id: 0, sold: 3200, tag: '' },
            { id: 11, name: 'LEGO 乐高 机械组 兰博基尼', price: 2599, image: '', category_id: 0, sold: 1200, tag: '热卖' },
            { id: 12, name: '得到APP 年度VIP会员卡', price: 365, image: '', category_id: 0, sold: 8900, tag: '' },
        ];
        // Demo模式不显示分类
        renderProducts(allProducts);
    }

    // Render products
    function renderProducts(products) {
        const grid = document.getElementById('product-grid');
        if (!grid) return;
        if (!products.length) {
            grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1;"><div class="empty-icon">&#128269;</div><div class="empty-text">暂无商品</div></div>';
            return;
        }

        const icons = ['&#128241;', '&#127911;', '&#128095;', '&#128132;', '&#127860;', '&#128241;', '&#128085;', '&#128085;', '&#128187;', '&#9749;', '&#129651;', '&#127918;'];

        grid.innerHTML = products.map((p, i) => `
            <div class="product-card" onclick="goToCheckout(${p.id})">
                <div class="product-img">
                    ${p.image ? `<img src="${p.image}" alt="${p.name}">` : icons[i % icons.length]}
                    ${p.tag ? `<span class="product-tag">${p.tag}</span>` : ''}
                </div>
                <div class="product-info">
                    <div class="product-name">${p.name}</div>
                    <div class="product-price-row">
                        <span class="product-price"><span class="yen">&yen;</span>${NexusApp.formatPrice(p.price)}</span>
                        <span class="product-sold">已售 ${p.sales_count || p.sold || 0}+</span>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Go to checkout directly (no cart)
    function goToCheckout(productId) {
        const token = localStorage.getItem('user_token');
        if (!token) {
            NexusApp.toast('请先登录', 'error');
            setTimeout(() => window.location.href = '/user.php', 500);
            return;
        }
        // 直接跳转到选择收银台模版页面，带上商品ID
        window.location.href = '/checkout.php?product_id=' + productId;
    }

    // Search filter
    function renderCategories(categories) {
        if (!categories || categories.length === 0) return;

        var catTab = document.getElementById('categoryTab');
        if (catTab) catTab.style.display = '';

        let html = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">';

        categories.forEach(cat => {
            html += '<div onclick="selectCategory(' + cat.id + ')" data-id="' + cat.id + '" style="padding:14px 8px;background:var(--bg);border-radius:10px;text-align:center;cursor:pointer;border:2px solid transparent;">' +
                '<div style="font-size:14px;font-weight:500;color:var(--text-primary);">' + cat.name + '</div></div>';
        });

        html += '</div>';
        document.getElementById('categoryPanelList').innerHTML = html;
    }

    let categoryPanelOpen = false;
    function toggleCategoryPanel() {
        var panel = document.getElementById('categoryPanel');
        var overlay = document.getElementById('categoryOverlay');
        categoryPanelOpen = !categoryPanelOpen;
        if (categoryPanelOpen) {
            overlay.style.display = 'block';
            setTimeout(function() { overlay.style.opacity = '1'; }, 10);
            panel.style.transform = 'translateY(0)';
        } else {
            overlay.style.opacity = '0';
            panel.style.transform = 'translateY(100%)';
            setTimeout(function() { overlay.style.display = 'none'; }, 300);
        }
    }

    function selectCategory(catId) {
        currentCategory = catId;
        toggleCategoryPanel();
        renderProducts(allProducts.filter(p => p.category_id == catId));
    }

    // Search filter
    function filterProducts() {
        const keyword = document.getElementById('search-input').value.trim().toLowerCase();
        if (!keyword) {
            if (currentCategory === 'all') {
                renderProducts(allProducts);
            } else {
                renderProducts(allProducts.filter(p => p.category_id == currentCategory));
            }
            return;
        }
        const filtered = allProducts.filter(p => p.name.toLowerCase().includes(keyword));
        renderProducts(filtered);
    }

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        // 处理邀请链接参数 ref=用户ID
        var urlParams = new URLSearchParams(window.location.search);
        var refId = urlParams.get('ref');
        if (refId) {
            localStorage.setItem('invite_ref', refId);
        }
        loadProducts();
        loadSiteConfig();
    });
</script>
</body>
</html>
