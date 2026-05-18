<?php
/**
 * MX-Mall - User Products Page
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
    <title>我的商品 - MX-Mall</title>
    <link rel="stylesheet" href="/assets/css/mobile.css?v=5">
    <style>
        .page-top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            height: 52px;
            background: var(--bg-white);
            border-bottom: 1px solid var(--border);
        }
        .page-top-bar .bar-back {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            font-size: 20px;
            cursor: pointer;
        }
        .page-top-bar .bar-title {
            font-size: 17px;
            font-weight: 600;
            color: var(--text-primary);
        }
        .page-top-bar .bar-action {
            height: 36px;
            padding: 0 16px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
        }
        .page-top-bar .bar-action:active {
            opacity: 0.85;
        }
        .product-list {
            padding: 12px;
        }
        .page-content.no-tab {
            padding-top: 0;
        }
        .product-card-item {
            display: flex;
            gap: 12px;
            padding: 14px;
            background: var(--bg-white);
            border-radius: var(--radius-md);
            margin-bottom: 10px;
            border: none;
            box-shadow: var(--shadow-sm);
        }
        .product-card-item .item-img {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-sm);
            background: var(--bg-elevated);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: var(--text-muted);
            overflow: hidden;
        }
        .product-card-item .item-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-card-item .item-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
        }
        .product-card-item .item-name {
            font-size: 14px;
            color: var(--text-primary);
            font-weight: 500;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-card-item .item-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .product-card-item .item-price {
            font-size: 16px;
            font-weight: 700;
            color: var(--danger);
        }
        .product-card-item .item-price .yen {
            font-size: 12px;
        }
        .product-card-item .item-meta {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .product-card-item .item-sold {
            font-size: 11px;
            color: var(--text-muted);
        }
        .status-tag {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }
        .status-tag.on-sale {
            background: rgba(0,184,148,0.15);
            color: var(--success);
        }
        .status-tag.off-sale {
            background: rgba(255,107,107,0.15);
            color: var(--danger);
        }
        .btn-toggle-status {
            height: 30px;
            padding: 0 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }
        .btn-toggle-status.off {
            background: rgba(255,107,107,0.15);
            color: var(--danger);
        }
        .btn-toggle-status.on {
            background: rgba(0,184,148,0.15);
            color: var(--success);
        }
        .btn-toggle-status:active {
            opacity: 0.7;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            display: none;
            align-items: flex-end;
            justify-content: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-content {
            width: 100%;
            max-width: 480px;
            background: var(--bg-white);
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
            padding: 24px 20px 40px;
            animation: slideUp 0.3s ease;
            max-height: 85vh;
            overflow-y: auto;
        }
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .modal-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .modal-close {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--bg-elevated);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-size: 18px;
            cursor: pointer;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-label {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 6px;
            display: block;
        }
        .form-input, .form-textarea, .form-select {
            width: 100%;
            background: #F9FAFB;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 0 14px;
            font-size: 15px;
            color: var(--text-primary);
            transition: var(--transition);
        }
        .form-input {
            height: 46px;
        }
        .form-textarea {
            height: 100px;
            padding: 12px 14px;
            resize: none;
        }
        .form-select {
            height: 46px;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23555570' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108,92,231,0.1);
        }
        .form-input::placeholder, .form-textarea::placeholder {
            color: var(--text-muted);
        }
        .form-row {
            display: flex;
            gap: 12px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .modal-btn-submit {
            width: 100%;
            height: 46px;
            border-radius: 23px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }
        .modal-btn-submit:active {
            opacity: 0.85;
            transform: scale(0.98);
        }
        .modal-btn-submit:disabled {
            opacity: 0.5;
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- Top Bar -->
    <div class="page-top-bar">
        <div class="bar-back" onclick="history.back()">&#8249;</div>
        <div class="bar-title">我的商品</div>
        <button class="bar-action" id="btn-add-product" onclick="openAddModal()" style="display:none;">上架商品</button>
        <button class="bar-action" id="btn-apply-merchant" onclick="handleApplyMerchant()" style="display:none;background:linear-gradient(135deg,#f59e0b,#f97316);">申请商户</button>
    </div>

    <!-- Product List -->
    <div class="page-content no-tab">
        <div id="no-merchant-tip" style="display:none;padding:16px;margin:12px 16px;background:rgba(245,158,11,0.1);border-radius:12px;text-align:center;">
            <div style="font-size:28px;margin-bottom:8px;">&#128274;</div>
            <div style="font-size:14px;color:var(--text-secondary);">您还没有商户权限</div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">点击右上角"申请商户"按钮申请权限</div>
        </div>
        <div id="merchant-pending-tip" style="display:none;padding:16px;margin:12px 16px;background:rgba(99,102,241,0.1);border-radius:12px;text-align:center;">
            <div style="font-size:28px;margin-bottom:8px;">&#9203;</div>
            <div style="font-size:14px;color:var(--text-secondary);">商户权限审核中</div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">请耐心等待管理员审核</div>
        </div>
        <div class="product-list" id="product-list">
            <!-- Loading -->
            <div style="text-align:center;padding:40px 0;color:var(--text-muted);font-size:14px;">
                <div class="spinner" style="margin:0 auto 12px;"></div>
                加载中...
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal-overlay" id="add-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">上架商品</div>
                <div class="modal-close" onclick="closeAddModal()">&#10005;</div>
            </div>
            <div class="form-group">
                <label class="form-label">商品名称 *</label>
                <input type="text" class="form-input" id="add-name" placeholder="请输入商品名称">
            </div>
            <div class="form-group">
                <label class="form-label">商品图片</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input type="text" class="form-input" id="add-image" placeholder="输入URL或点击上传" style="flex:1;">
                    <label style="flex-shrink:0;padding:8px 16px;background:var(--primary);color:#fff;border-radius:8px;cursor:pointer;font-size:13px;">
                        上传
                        <input type="file" accept="image/*" id="add-image-file" style="display:none;" onchange="previewAddImage(this)">
                    </label>
                </div>
                <div id="add-image-preview" style="margin-top:8px;"></div>
            </div>
            <div class="form-group">
                <label class="form-label">售价 *</label>
                <input type="number" class="form-input" id="add-price" placeholder="售价" step="0.01" min="0">
            </div>
            <div class="form-group">
                <label class="form-label">分类</label>
                <select class="form-select" id="add-category">
                    <option value="">请选择分类</option>
                </select>
            </div>
            <button class="modal-btn-submit" id="btn-add-submit" onclick="handleAddProduct()">保存商品</button>
        </div>
    </div>
</div>

<script src="/assets/js/app.js?v=5"></script>
<script>
    function getToken() {
        return localStorage.getItem('user_token');
    }

    // Check login
    (function() {
        const token = getToken();
        if (!token) {
            NexusApp.go('/user.php');
        }
    })();

    // Load products
    async function loadProducts() {
        const listEl = document.getElementById('product-list');
        try {
            const res = await NexusApp.get('/user/products');
            const data = res.data || {};
            const products = data.list || data || [];
            renderProducts(Array.isArray(products) ? products : []);

            try {
                const profileRes = await NexusApp.get('/user/profile');
                if (profileRes.code === 0 && profileRes.data) {
                    const ms = profileRes.data.merchant_status;
                    const addBtn = document.getElementById('btn-add-product');
                    const applyBtn = document.getElementById('btn-apply-merchant');
                    const noPermTip = document.getElementById('no-merchant-tip');
                    const pendingTip = document.getElementById('merchant-pending-tip');

                    if (ms === 2) {
                        addBtn.style.display = '';
                        applyBtn.style.display = 'none';
                        noPermTip.style.display = 'none';
                        pendingTip.style.display = 'none';
                    } else if (ms === 1) {
                        addBtn.style.display = 'none';
                        applyBtn.style.display = 'none';
                        noPermTip.style.display = 'none';
                        pendingTip.style.display = 'block';
                    } else {
                        addBtn.style.display = 'none';
                        applyBtn.style.display = '';
                        noPermTip.style.display = 'block';
                        pendingTip.style.display = 'none';
                    }
                }
            } catch(e) {}
        } catch (e) {
            listEl.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128230;</div><div class="empty-text">加载失败，请重试</div></div>';
        }
    }

    async function handleApplyMerchant() {
        const btn = document.getElementById('btn-apply-merchant');
        btn.disabled = true;
        btn.textContent = '申请中...';
        try {
            const res = await NexusApp.post('/user/apply-merchant', {});
            if (res.code === 0) {
                NexusApp.toast('商户申请已提交，请等待审核', 'success');
                loadProducts();
            } else {
                NexusApp.toast(res.msg || '申请失败', 'error');
            }
        } catch (e) {
            NexusApp.toast('网络错误，请重试', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = '申请商户';
        }
    }

    function renderProducts(products) {
        const listEl = document.getElementById('product-list');
        if (!products.length) {
            listEl.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128230;</div><div class="empty-text">暂无商品</div></div>';
            return;
        }

        listEl.innerHTML = products.map(p => `
            <div class="product-card-item">
                <div class="item-img">
                    ${p.image ? '<img src="' + p.image + '" alt="">' : '&#128230;'}
                </div>
                <div class="item-info">
                    <div class="item-name">${p.name || '未命名商品'}</div>
                    <div class="item-bottom">
                        <div>
                            <span class="item-price"><span class="yen">&yen;</span>${NexusApp.formatPrice(p.price || 0)}</span>
                            <span class="status-tag ${p.status == 1 ? 'on-sale' : 'off-sale'}">${p.status == 1 ? '上架' : '下架'}</span>
                            <span class="item-sold">已售 ${p.sold || 0}</span>
                        </div>
                        <button class="btn-toggle-status ${p.status == 1 ? 'off' : 'on'}" onclick="toggleProductStatus(${p.id}, ${p.status})">
                            ${p.status == 1 ? '下架' : '上架'}
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Toggle product status
    async function toggleProductStatus(id, currentStatus) {
        const newStatus = currentStatus == 1 ? 0 : 1;
        try {
            const res = await NexusApp.post('/user/products/status', { id, status: newStatus });
            if (res.code === 0) {
                NexusApp.toast(newStatus == 1 ? '已上架' : '已下架', 'success');
                loadProducts();
            } else {
                NexusApp.toast(res.msg || '操作失败', 'error');
            }
        } catch (e) {
            NexusApp.toast(e.message || '网络错误，请重试', 'error');
        }
    }

    // Add product modal
    function openAddModal() {
        document.getElementById('add-modal').classList.add('active');
    }

    function closeAddModal() {
        document.getElementById('add-modal').classList.remove('active');
    }

    async function previewAddImage(input) {
        if (!input.files[0]) return;
        const file = input.files[0];
        // 预览
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('add-image-preview').innerHTML = `<img src="${e.target.result}" style="max-height:80px;border-radius:8px;">`;
        };
        reader.readAsDataURL(file);
        // 上传
        try {
            const url = await NexusApp.uploadImage(file);
            document.getElementById('add-image').value = url;
            NexusApp.toast('图片上传成功', 'success');
        } catch (e) {
            NexusApp.toast(e.message || '上传失败', 'error');
        }
    }

    async function handleAddProduct() {
        const name = document.getElementById('add-name').value.trim();
        const image = document.getElementById('add-image').value.trim();
        const price = parseFloat(document.getElementById('add-price').value);
        const category = document.getElementById('add-category').value;

        if (!name) {
            NexusApp.toast('请输入商品名称', 'error');
            return;
        }
        if (!price || price <= 0) {
            NexusApp.toast('请输入有效价格', 'error');
            return;
        }

        const btn = document.getElementById('btn-add-submit');
        btn.disabled = true;
        btn.textContent = '保存中...';

        try {
            const res = await NexusApp.post('/user/products', {
                name,
                image,
                price,
                category_id: category,
            });
            if (res.code === 0) {
                NexusApp.toast('商品上架成功', 'success');
                closeAddModal();
                // Clear form
                document.getElementById('add-name').value = '';
                document.getElementById('add-image').value = '';
                document.getElementById('add-image-preview').innerHTML = '';
                document.getElementById('add-price').value = '';
                document.getElementById('add-category').value = '';
                loadProducts();
            } else {
                NexusApp.toast(res.msg || '上架失败', 'error');
            }
        } catch (e) {
            NexusApp.toast('网络错误，请重试', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = '保存商品';
        }
    }

    // Load categories from API
    async function loadCategories() {
        try {
            const res = await NexusApp.get('/categories');
            const categories = res.data || [];
            const select = document.getElementById('add-category');
            if (!select) return;

            let html = '<option value="">请选择分类</option>';
            categories.forEach(cat => {
                html += `<option value="${cat.id}">${cat.name}</option>`;
            });
            select.innerHTML = html;
        } catch(e) {
            console.warn('加载分类失败');
        }
    }

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        loadProducts();
        loadCategories();
    });
</script>
</body>
</html>
