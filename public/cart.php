<?php
/**
 * MX-Mall - Cart Page
 */
// 安装锁检测 - 未安装时跳转到安装页
$lockFile = __DIR__ . '/../install/install.lock';
if (!file_exists($lockFile)) {
    header('Location: /install.php');
    exit;
}
$buyNow = isset($_GET['buy_now']) ? true : false;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <title>购物车 - MX-Mall</title>
    <link rel="stylesheet" href="/assets/css/mobile.css?v=5">
    <style>
        .cart-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
        }
        .cart-page-header .cart-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .cart-page-header .cart-edit {
            font-size: 14px;
            color: var(--primary);
            cursor: pointer;
        }
        .cart-empty-illustration {
            font-size: 80px;
            opacity: 0.3;
            margin-bottom: 16px;
        }
        .cart-item-enter {
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- Top Nav -->
    <nav class="top-nav">
        <div class="nav-back" onclick="NexusApp.go('/index.php')">&#8249;</div>
        <div class="nav-title">购物车</div>
        <div class="nav-action" id="btn-clear-cart" onclick="clearCart()" style="font-size:13px;color:var(--text-muted);">清空</div>
    </nav>

    <!-- Page Content -->
    <div class="page-content no-tab" style="padding-top:52px;padding-bottom:70px;">
        <div class="cart-list" id="cart-list">
            <!-- Cart items rendered by JS -->
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="cart-bottom-bar" id="cart-bottom-bar" style="display:none;">
        <div class="cart-select-all">
            <div class="checkbox-custom checked" id="select-all" onclick="toggleSelectAll()"></div>
            <span style="font-size:13px;color:var(--text-secondary);">全选</span>
        </div>
        <div class="cart-total">
            <span class="total-label">合计: </span>
            <span class="total-price"><span class="yen">&yen;</span><span id="total-amount">0.00</span></span>
        </div>
        <button class="btn-checkout" id="btn-checkout" onclick="goCheckout()">
            去结算(<span id="checkout-count">0</span>)
        </button>
    </div>
</div>

<script src="/assets/js/app.js?v=5"></script>
<script>
    let cartItems = [];
    let selectedIds = new Set();
    let isBuyNow = <?php echo $buyNow ? 'true' : 'false'; ?>;

    // Load cart
    function loadCart() {
        cartItems = NexusApp.cart.getItems();
        if (cartItems.length === 0) {
            renderEmpty();
            return;
        }
        // Select all by default
        selectedIds = new Set(cartItems.map(item => item.id));
        renderCart();
        updateTotal();

        // Auto checkout if buy_now
        if (isBuyNow && cartItems.length > 0) {
            // Small delay for UX
        }
    }

    function renderEmpty() {
        document.getElementById('cart-list').innerHTML = `
            <div class="empty-state">
                <div class="cart-empty-illustration">&#128722;</div>
                <div class="empty-text">购物车空空如也</div>
                <button class="btn-go-shop" onclick="NexusApp.go('/index.php')">去逛逛</button>
            </div>
        `;
        document.getElementById('cart-bottom-bar').style.display = 'none';
    }

    function renderCart() {
        const list = document.getElementById('cart-list');
        const icons = ['&#128241;', '&#127911;', '&#128095;', '&#128132;', '&#127860;', '&#128187;', '&#128085;', '&#129651;'];

        list.innerHTML = cartItems.map((item, i) => `
            <div class="cart-item cart-item-enter" data-id="${item.id}">
                <div class="checkbox-custom ${selectedIds.has(item.id) ? 'checked' : ''}"
                     onclick="toggleSelect(${item.id})" style="margin-right:10px;flex-shrink:0;"></div>
                <div class="cart-img">
                    ${item.image ? `<img src="${item.image}" alt="">` : icons[i % icons.length]}
                </div>
                <div class="cart-info">
                    <div class="cart-name">${item.name}</div>
                    <div class="cart-bottom">
                        <span class="cart-price"><span class="yen">&yen;</span>${NexusApp.formatPrice(item.price)}</span>
                        <div class="qty-selector">
                            <button class="qty-btn" onclick="updateQty(${item.id}, ${item.quantity - 1})">-</button>
                            <span class="qty-value">${item.quantity}</span>
                            <button class="qty-btn" onclick="updateQty(${item.id}, ${item.quantity + 1})">+</button>
                        </div>
                    </div>
                </div>
                <div class="cart-delete" onclick="removeItem(${item.id})">&#10005;</div>
            </div>
        `).join('');

        document.getElementById('cart-bottom-bar').style.display = 'flex';
    }

    function toggleSelect(id) {
        if (selectedIds.has(id)) {
            selectedIds.delete(id);
        } else {
            selectedIds.add(id);
        }
        renderCart();
        updateTotal();
    }

    function toggleSelectAll() {
        if (selectedIds.size === cartItems.length) {
            selectedIds.clear();
        } else {
            selectedIds = new Set(cartItems.map(item => item.id));
        }
        renderCart();
        updateTotal();
    }

    function updateQty(id, newQty) {
        if (newQty < 1) {
            removeItem(id);
            return;
        }
        NexusApp.cart.updateQuantity(id, newQty);
        loadCart();
    }

    function removeItem(id) {
        NexusApp.cart.removeItem(id);
        selectedIds.delete(id);
        loadCart();
    }

    function clearCart() {
        if (cartItems.length === 0) return;
        if (confirm('确定清空购物车吗？')) {
            NexusApp.cart.clear();
            selectedIds.clear();
            loadCart();
        }
    }

    function updateTotal() {
        const selectedItems = cartItems.filter(item => selectedIds.has(item.id));
        const total = selectedItems.reduce((sum, item) => sum + item.price * item.quantity, 0);
        const count = selectedItems.reduce((sum, item) => sum + item.quantity, 0);

        document.getElementById('total-amount').textContent = NexusApp.formatPrice(total);
        document.getElementById('checkout-count').textContent = count;

        const selectAllEl = document.getElementById('select-all');
        if (selectedIds.size === cartItems.length && cartItems.length > 0) {
            selectAllEl.classList.add('checked');
        } else {
            selectAllEl.classList.remove('checked');
        }
    }

    async function goCheckout() {
        const selectedItems = cartItems.filter(item => selectedIds.has(item.id));
        if (selectedItems.length === 0) {
            NexusApp.toast('请选择商品', 'error');
            return;
        }

        // 检查登录状态
        const token = localStorage.getItem('user_token');
        if (!token) {
            NexusApp.toast('请先登录', 'error');
            setTimeout(() => window.location.href = '/user.php', 500);
            return;
        }

        try {
            NexusApp.showLoading();
            const res = await NexusApp.post('/cart/checkout', {
                items: selectedItems.map(item => ({
                    product_id: item.id,
                    name: item.name,
                    price: item.price,
                    quantity: item.quantity,
                }))
            });

            if (res.data && res.data.trade_no) {
                NexusApp.go(`/checkout.php?trade_no=${res.data.trade_no}`);
            } else {
                // Fallback: go to checkout with cart data
                const tradeNo = res.trade_no || res.data?.order_no || 'NX' + Date.now();
                NexusApp.go(`/checkout.php?trade_no=${tradeNo}`);
            }
        } catch (e) {
            // Fallback: use local cart data
            const tradeNo = 'NX' + Date.now();
            // Store checkout data in sessionStorage
            sessionStorage.setItem('checkout_data', JSON.stringify({
                trade_no: tradeNo,
                items: selectedItems,
                total: selectedItems.reduce((s, i) => s + i.price * i.quantity, 0),
            }));
            NexusApp.go(`/checkout.php?trade_no=${tradeNo}`);
        } finally {
            NexusApp.hideLoading();
        }
    }

    document.addEventListener('DOMContentLoaded', loadCart);
</script>
</body>
</html>
