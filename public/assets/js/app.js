/**
 * MX-Mall - Mobile App JS
 * Modern UI interactions and animations
 */

(function() {
    'use strict';

    // ============================================
    // Toast Notification System
    // ============================================
    const Toast = {
        container: null,

        init() {
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.className = 'toast-container';
                document.body.appendChild(this.container);
            }
        },

        show(message, type = 'success', duration = 2500) {
            this.init();
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            this.container.appendChild(toast);

            // Remove after animation
            setTimeout(() => {
                toast.style.animation = 'toastOut 0.3s ease forwards';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, duration);
        },

        success(msg) { this.show(msg, 'success'); },
        error(msg) { this.show(msg, 'error'); },
        info(msg) { this.show(msg, 'info'); }
    };

    // ============================================
    // Loading Overlay
    // ============================================
    const Loading = {
        overlay: null,

        show() {
            if (!this.overlay) {
                this.overlay = document.createElement('div');
                this.overlay.className = 'loading-overlay';
                this.overlay.innerHTML = '<div class="spinner"></div>';
                document.body.appendChild(this.overlay);
            }
            this.overlay.style.display = 'flex';
        },

        hide() {
            if (this.overlay) {
                this.overlay.style.display = 'none';
            }
        }
    };

    // ============================================
    // Cart Management
    // ============================================
    const Cart = {
        async add(productId, quantity = 1) {
            try {
                Loading.show();
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('quantity', quantity);

                const res = await fetch('api/cart.php?action=add', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                Loading.hide();

                if (data.success) {
                    Toast.success('已加入购物车');
                    this.updateBadge(data.cart_count);
                    this.animateCartIcon();
                } else {
                    Toast.error(data.message || '添加失败');
                }
                return data;
            } catch (err) {
                Loading.hide();
                Toast.error('网络错误');
                throw err;
            }
        },

        async update(cartId, quantity) {
            try {
                const formData = new FormData();
                formData.append('cart_id', cartId);
                formData.append('quantity', quantity);

                const res = await fetch('api/cart.php?action=update', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    this.updateBadge(data.cart_count);
                } else {
                    Toast.error(data.message || '更新失败');
                }
                return data;
            } catch (err) {
                Toast.error('网络错误');
                throw err;
            }
        },

        async remove(cartId) {
            try {
                const formData = new FormData();
                formData.append('cart_id', cartId);

                const res = await fetch('api/cart.php?action=remove', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    Toast.success('已删除');
                    this.updateBadge(data.cart_count);
                } else {
                    Toast.error(data.message || '删除失败');
                }
                return data;
            } catch (err) {
                Toast.error('网络错误');
                throw err;
            }
        },

        updateBadge(count) {
            document.querySelectorAll('.cart-badge').forEach(badge => {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
                badge.style.animation = 'none';
                badge.offsetHeight;
                badge.style.animation = 'badgePop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
            });
        },

        animateCartIcon() {
            const cartIcons = document.querySelectorAll('.tab-item .tab-icon');
            cartIcons.forEach(icon => {
                icon.style.transform = 'scale(1.3)';
                setTimeout(() => {
                    icon.style.transform = 'scale(1)';
                }, 300);
            });
        }
    };

    // ============================================
    // Checkbox Custom
    // ============================================
    function initCheckboxes() {
        document.querySelectorAll('.checkbox-custom').forEach(box => {
            box.addEventListener('click', function() {
                this.classList.toggle('checked');
                const input = this.querySelector('input[type="checkbox"]');
                if (input) {
                    input.checked = this.classList.contains('checked');
                    input.dispatchEvent(new Event('change'));
                }
            });
        });
    }

    // ============================================
    // Quantity Selector
    // ============================================
    function initQuantitySelectors() {
        document.querySelectorAll('.qty-selector').forEach(selector => {
            const minus = selector.querySelector('.qty-minus');
            const plus = selector.querySelector('.qty-plus');
            const value = selector.querySelector('.qty-value');
            const input = selector.querySelector('input[type="hidden"]');

            if (minus && plus && value) {
                minus.addEventListener('click', () => {
                    let v = parseInt(value.value) || 1;
                    if (v > 1) {
                        v--;
                        value.value = v;
                        if (input) input.value = v;
                        value.style.transform = 'scale(0.9)';
                        setTimeout(() => value.style.transform = 'scale(1)', 150);
                    }
                });

                plus.addEventListener('click', () => {
                    let v = parseInt(value.value) || 1;
                    v++;
                    value.value = v;
                    if (input) input.value = v;
                    value.style.transform = 'scale(1.1)';
                    setTimeout(() => value.style.transform = 'scale(1)', 150);
                });
            }
        });
    }

    // ============================================
    // Cart Page
    // ============================================
    function initCartPage() {
        const cartList = document.querySelector('.cart-list');
        if (!cartList) return;

        cartList.addEventListener('click', function(e) {
            const checkbox = e.target.closest('.cart-checkbox');
            if (checkbox) {
                checkbox.classList.toggle('checked');
                updateCartTotal();
            }

            const deleteBtn = e.target.closest('.cart-delete');
            if (deleteBtn) {
                const item = deleteBtn.closest('.cart-item');
                const cartId = item?.dataset.cartId;
                if (cartId) {
                    item.style.transition = 'all 0.3s ease';
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(-100%)';
                    setTimeout(() => {
                        Cart.remove(cartId);
                    }, 300);
                }
            }
        });

        cartList.addEventListener('click', function(e) {
            const btn = e.target.closest('.qty-btn');
            if (!btn) return;

            const item = btn.closest('.cart-item');
            const cartId = item?.dataset.cartId;
            const valueEl = btn.parentElement.querySelector('.qty-value');
            let qty = parseInt(valueEl.value) || 1;

            if (btn.classList.contains('qty-minus') && qty > 1) {
                qty--;
            } else if (btn.classList.contains('qty-plus')) {
                qty++;
            }

            valueEl.value = qty;
            Cart.update(cartId, qty);
            updateCartTotal();
        });

        const selectAll = document.querySelector('.cart-select-all .checkbox-custom');
        if (selectAll) {
            selectAll.addEventListener('click', function() {
                const checked = this.classList.contains('checked');
                document.querySelectorAll('.cart-item .checkbox-custom').forEach(box => {
                    if (checked) {
                        box.classList.add('checked');
                    } else {
                        box.classList.remove('checked');
                    }
                });
                updateCartTotal();
            });
        }

        const checkoutBtn = document.querySelector('.btn-checkout');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', function() {
                const selected = [];
                document.querySelectorAll('.cart-item').forEach(item => {
                    const cb = item.querySelector('.checkbox-custom');
                    if (cb && cb.classList.contains('checked')) {
                        selected.push(item.dataset.cartId);
                    }
                });

                if (selected.length === 0) {
                    Toast.error('请选择商品');
                    return;
                }

                window.location.href = 'checkout.php?cart_ids=' + selected.join(',');
            });
        }
    }

    function updateCartTotal() {
        let total = 0;
        let count = 0;
        document.querySelectorAll('.cart-item').forEach(item => {
            const cb = item.querySelector('.checkbox-custom');
            if (cb && cb.classList.contains('checked')) {
                const price = parseFloat(item.dataset.price) || 0;
                const qty = parseInt(item.querySelector('.qty-value')?.value) || 1;
                total += price * qty;
                count++;
            }
        });

        const totalEl = document.querySelector('.cart-total .total-price');
        if (totalEl) {
            totalEl.innerHTML = `<span class="yen">¥</span>${total.toFixed(2)}`;
        }

        const checkoutBtn = document.querySelector('.btn-checkout');
        if (checkoutBtn) {
            checkoutBtn.disabled = count === 0;
            checkoutBtn.textContent = count > 0 ? `结算(${count})` : '结算';
        }
    }

    // ============================================
    // Checkout Page
    // ============================================
    function initCheckoutPage() {
        const templateGrid = document.querySelector('.template-grid');
        if (!templateGrid) return;

        // Note: checkout.php handles template selection and pay button via inline onclick
        // We only add the ripple/visual effects here, not override the click handlers
        templateGrid.addEventListener('click', function(e) {
            const card = e.target.closest('.template-card');
            if (!card) return;

            // Visual selection only - the actual selection logic is in checkout.php's selectTemplate()
            templateGrid.querySelectorAll('.template-card').forEach(c => {
                c.classList.remove('selected');
            });
            card.classList.add('selected');
        });

        // Do NOT attach click handler to .btn-go-pay here.
        // checkout.php uses inline onclick="goToCashier()" which handles the full flow.
        // Attaching a listener here would conflict with or override that logic.
    }

    // ============================================
    // Product Detail Page
    // ============================================
    function initProductDetail() {
        const addCartBtn = document.querySelector('.btn-add-cart');
        const buyNowBtn = document.querySelector('.btn-buy-now');
        const productId = document.querySelector('[data-product-id]')?.dataset.productId;

        if (addCartBtn && productId) {
            addCartBtn.addEventListener('click', async function() {
                const qty = parseInt(document.querySelector('.qty-value')?.value) || 1;
                this.style.transform = 'scale(0.95)';
                setTimeout(() => this.style.transform = 'scale(1)', 150);
                await Cart.add(productId, qty);
            });
        }

        if (buyNowBtn && productId) {
            buyNowBtn.addEventListener('click', async function() {
                const qty = parseInt(document.querySelector('.qty-value')?.value) || 1;
                this.style.transform = 'scale(0.95)';
                setTimeout(() => this.style.transform = 'scale(1)', 150);

                const data = await Cart.add(productId, qty);
                if (data.success) {
                    window.location.href = 'checkout.php?cart_ids=' + data.cart_id;
                }
            });
        }
    }

    // ============================================
    // Category Filter
    // ============================================
    function initCategoryFilter() {
        const categoryGrid = document.querySelector('.category-grid');
        if (!categoryGrid) return;

        categoryGrid.addEventListener('click', function(e) {
            const item = e.target.closest('.category-item');
            if (!item) return;

            const catId = item.dataset.catId;

            if (item.classList.contains('active')) {
                item.classList.remove('active');
                loadProducts();
            } else {
                categoryGrid.querySelectorAll('.category-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                loadProducts(catId);
            }
        });
    }

    async function loadProducts(catId = '') {
        const productGrid = document.querySelector('.product-grid');
        if (!productGrid) return;

        productGrid.innerHTML = Array(4).fill('<div class="product-card"><div class="product-img skeleton" style="height:160px"></div><div class="product-info"><div class="skeleton" style="height:16px;margin-bottom:8px"></div><div class="skeleton" style="height:12px;width:60%"></div></div></div>').join('');

        try {
            const url = catId ? `api/products.php?cat_id=${catId}` : 'api/products.php';
            const res = await fetch(url);
            const data = await res.json();

            if (data.success) {
                renderProducts(data.products);
            }
        } catch (err) {
            console.error('Failed to load products:', err);
        }
    }

    function renderProducts(products) {
        const productGrid = document.querySelector('.product-grid');
        if (!productGrid) return;

        if (!products || products.length === 0) {
            productGrid.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <div class="empty-icon">🛒</div>
                    <p class="empty-text">暂无商品</p>
                </div>
            `;
            return;
        }

        productGrid.innerHTML = products.map(p => `
            <div class="product-card" onclick="location.href='product.php?id=${p.id}'">
                <div class="product-img">
                    ${p.image ? `<img src="${p.image}" alt="${p.name}" loading="lazy">` : '📦'}
                    ${p.tag ? `<span class="product-tag">${p.tag}</span>` : ''}
                </div>
                <div class="product-info">
                    <div class="product-name">${p.name}</div>
                    <div class="product-price-row">
                        <span class="product-price"><span class="yen">¥</span>${p.price}</span>
                        <button class="product-add-cart" onclick="event.stopPropagation(); Cart.add(${p.id})">+</button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // ============================================
    // Search
    // ============================================
    function initSearch() {
        const searchInput = document.querySelector('.search-input');
        if (!searchInput) return;

        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const keyword = this.value.trim();
                if (keyword) {
                    searchProducts(keyword);
                } else {
                    loadProducts();
                }
            }, 300);
        });
    }

    async function searchProducts(keyword) {
        const productGrid = document.querySelector('.product-grid');
        if (!productGrid) return;

        productGrid.innerHTML = Array(4).fill('<div class="product-card"><div class="product-img skeleton" style="height:160px"></div><div class="product-info"><div class="skeleton" style="height:16px;margin-bottom:8px"></div><div class="skeleton" style="height:12px;width:60%"></div></div></div>').join('');

        try {
            const res = await fetch(`api/products.php?search=${encodeURIComponent(keyword)}`);
            const data = await res.json();
            if (data.success) {
                renderProducts(data.products);
            }
        } catch (err) {
            console.error('Search failed:', err);
        }
    }

    // ============================================
    // Tab Bar Active State
    // ============================================
    function initTabBar() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        const tabMap = {
            'index.php': 'home',
            'user.php': 'user',
            'cart.php': 'cart'
        };

        const activeTab = tabMap[currentPage];
        if (activeTab) {
            document.querySelectorAll('.tab-item').forEach(tab => {
                if (tab.dataset.tab === activeTab) {
                    tab.classList.add('active');
                }
            });
        }
    }

    // ============================================
    // Pull to Refresh (Visual only)
    // ============================================
    function initPullToRefresh() {
        let startY = 0;
        let isPulling = false;
        const content = document.querySelector('.page-content');
        if (!content) return;

        content.addEventListener('touchstart', function(e) {
            if (content.scrollTop === 0) {
                startY = e.touches[0].clientY;
                isPulling = true;
            }
        }, { passive: true });

        content.addEventListener('touchmove', function(e) {
            if (!isPulling) return;
            const diff = e.touches[0].clientY - startY;
            if (diff > 0 && diff < 100) {
                content.style.transform = `translateY(${diff * 0.4}px)`;
            }
        }, { passive: true });

        content.addEventListener('touchend', function() {
            if (!isPulling) return;
            isPulling = false;
            content.style.transition = 'transform 0.3s ease';
            content.style.transform = 'translateY(0)';
            setTimeout(() => {
                content.style.transition = '';
            }, 300);
        });
    }

    // ============================================
    // Page Entrance Animation
    // ============================================
    function initPageEntrance() {
        const pageContent = document.querySelector('.page-content');
        if (pageContent) {
            pageContent.classList.add('page-transition');
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.product-card, .cart-item, .template-card').forEach((el, i) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = `opacity 0.5s ease ${i * 0.05}s, transform 0.5s ease ${i * 0.05}s`;
            observer.observe(el);
        });
    }

    // ============================================
    // Back Button
    // ============================================
    function initBackButton() {
        document.querySelectorAll('.nav-back').forEach(btn => {
            btn.addEventListener('click', function() {
                window.history.back();
            });
        });
    }

    // ============================================
    // Image Lazy Loading
    // ============================================
    function initLazyLoad() {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        img.style.opacity = '0';
                        img.onload = () => {
                            img.style.transition = 'opacity 0.3s ease';
                            img.style.opacity = '1';
                        };
                    }
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // ============================================
    // Ripple Effect for Buttons
    // ============================================
    function initRippleEffect() {
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-primary, .btn-add-cart, .btn-buy-now, .btn-go-pay, .btn-checkout, .product-add-cart');
            if (!btn) return;

            const ripple = document.createElement('span');
            const rect = btn.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255,255,255,0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                pointer-events: none;
            `;

            btn.style.position = 'relative';
            btn.style.overflow = 'hidden';
            btn.appendChild(ripple);

            setTimeout(() => ripple.remove(), 600);
        });
    }

    // Add ripple keyframe
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to { transform: scale(2); opacity: 0; }
        }
    `;
    document.head.appendChild(style);

    // ============================================
    // Initialize
    // ============================================
    function init() {
        initCheckboxes();
        initQuantitySelectors();
        initCartPage();
        initCheckoutPage();
        initProductDetail();
        initCategoryFilter();
        initSearch();
        initTabBar();
        initPullToRefresh();
        initPageEntrance();
        initBackButton();
        initLazyLoad();
        initRippleEffect();

        // Expose globals
        window.Cart = Cart;
        window.Toast = Toast;
        window.Loading = Loading;
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

// ============================================
// NexusApp - Global API Helper
// ============================================
const NexusApp = {
    apiBase: '/api',
    config: {},
    user: null,

    // Token management - support both token keys for compatibility
    getToken() {
        return localStorage.getItem('user_token') || localStorage.getItem('nexus_token') || '';
    },

    setToken(token) {
        localStorage.setItem('user_token', token);
        localStorage.setItem('nexus_token', token);
    },

    removeToken() {
        localStorage.removeItem('user_token');
        localStorage.removeItem('nexus_token');
    },

    // User management
    getUser() {
        try {
            return JSON.parse(localStorage.getItem('nexus_user') || 'null');
        } catch(e) {
            return null;
        }
    },

    setUser(user) {
        this.user = user;
        localStorage.setItem('nexus_user', JSON.stringify(user));
    },

    removeUser() {
        this.user = null;
        localStorage.removeItem('nexus_user');
    },

    isLogin() {
        return !!this.getToken();
    },

    // Navigation
    go(url) {
        window.location.href = url;
    },

    // Toast wrapper
    toast(message, type = 'success') {
        const container = document.querySelector('.toast-container') || (() => {
            const c = document.createElement('div');
            c.className = 'toast-container';
            document.body.appendChild(c);
            return c;
        })();

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'toastOut 0.3s ease forwards';
            setTimeout(() => {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
        }, 2500);
    },

    // Format price
    formatPrice(price) {
        return parseFloat(price || 0).toFixed(2);
    },

    // API Request
    async request(method, url, data = null) {
        const token = this.getToken();
        const options = {
            method: method.toUpperCase(),
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };

        if (token) {
            options.headers['Authorization'] = 'Bearer ' + token;
        }

        if (data && method.toLowerCase() !== 'get') {
            options.body = JSON.stringify(data);
        }

        // For GET requests with data, append as query params
        let fullUrl = this.apiBase + url;
        if (data && method.toLowerCase() === 'get') {
            const params = new URLSearchParams(data).toString();
            if (params) fullUrl += (url.includes('?') ? '&' : '?') + params;
        }

        try {
            const response = await fetch(fullUrl, options);
            const text = await response.text();
            // 如果返回的是HTML（授权失败页面），直接显示
            if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                document.open();
                document.write(text);
                document.close();
                return null;
            }
            const result = JSON.parse(text);
            return result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    async get(url, data = null) {
        return this.request('get', url, data);
    },

    async post(url, data = null) {
        return this.request('post', url, data);
    },

    async put(url, data = null) {
        return this.request('put', url, data);
    },

    async delete(url, data = null) {
        return this.request('delete', url, data);
    },

    // Image upload
    async uploadImage(file) {
        const formData = new FormData();
        // API expects 'file' field name, not 'image'
        formData.append('file', file);

        const token = this.getToken();
        // Don't set Content-Type manually - browser will set it with boundary for FormData
        const headers = {};
        if (token) headers['Authorization'] = 'Bearer ' + token;

        try {
            const response = await fetch(this.apiBase + '/upload', {
                method: 'POST',
                headers,
                body: formData
            });
            const result = await response.json();
            if (result.code === 0 || result.success) {
                return result.data?.url || result.url || result.data;
            }
            throw new Error(result.msg || '上传失败');
        } catch (error) {
            console.error('Upload Error:', error);
            throw error;
        }
    },

    // Initialize
    init() {
        this.user = this.getUser();
    }
};

// Auto init
NexusApp.init();
