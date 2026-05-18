<!-- Products Module -->
<div class="toolbar">
    <div class="toolbar-left" style="display:flex;align-items:center;gap:8px;">
        <button class="btn btn-primary" onclick="openProductModal()">
            <i class="bi bi-plus-lg"></i> 添加商品
        </button>
        <div id="batchActions" style="display:none;gap:6px;">
            <button class="btn btn-success btn-sm" onclick="batchUpdateStatus(1)">
                <i class="bi bi-check-circle"></i> 批量上架
            </button>
            <button class="btn btn-warning btn-sm" onclick="batchUpdateStatus(0)">
                <i class="bi bi-pause-circle"></i> 批量下架
            </button>
            <button class="btn btn-danger btn-sm" onclick="batchDelete()">
                <i class="bi bi-trash"></i> 批量删除
            </button>
            <span class="text-muted" style="font-size:12px;" id="selectedCount"></span>
        </div>
    </div>
    <div class="toolbar-right" style="display:flex;align-items:center;gap:8px;">
        <button class="btn btn-secondary btn-sm" onclick="Admin.exportCsv('/products/export?search=' + encodeURIComponent(productsSearch), 'products.csv')">
            <i class="bi bi-download"></i> 导出CSV
        </button>
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" id="productSearch" placeholder="搜索商品名称..." onkeyup="searchProducts()">
        </div>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" id="selectAll" onclick="toggleSelectAll()" title="全选"></th>
                    <th>ID</th>
                    <th>图片</th>
                    <th>名称</th>
                    <th>价格</th>
                    <th>状态</th>
                    <th>销量</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="productsBody">
                <tr><td colspan="8" class="text-center text-muted">加载中...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="pagination" id="productsPagination"></div>
</div>

<!-- Product Modal -->
<div class="modal-overlay" id="productModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title" id="productModalTitle">添加商品</span>
            <button class="modal-close" onclick="Admin.closeModal('productModal')"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body">
            <form id="productForm">
                <input type="hidden" name="id" id="productId">
                <div class="form-group">
                    <label class="form-label">商品名称 <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="请输入商品名称" required>
                </div>
                <div class="form-group">
                    <label class="form-label">价格 <span style="color:var(--danger);">*</span></label>
                    <input type="number" name="price" class="form-control" placeholder="0.00" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">商品图片</label>
                    <div style="display:flex;gap:8px;">
                        <input type="text" name="image" id="product_image" class="form-control" placeholder="输入URL或点击上传">
                        <label class="btn btn-secondary" style="white-space:nowrap;cursor:pointer;">
                            上传
                            <input type="file" accept="image/*" style="display:none;" onchange="uploadProductImage(this)">
                        </label>
                    </div>
                    <div id="product-image-preview" style="margin-top:8px;"></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">分类</label>
                        <select name="category_id" class="form-control" id="categorySelect">
                            <option value="">请选择分类</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">排序</label>
                        <input type="number" name="sort_order" class="form-control" placeholder="0" value="0" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">状态</label>
                    <select name="status" class="form-control">
                        <option value="1">上架</option>
                        <option value="0">下架</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Admin.closeModal('productModal')">取消</button>
            <button class="btn btn-primary" onclick="saveProduct()">保存</button>
        </div>
    </div>
</div>

<script>
let productsData = [];
let productsSearch = '';

async function uploadProductImage(input) {
    if (!input.files[0]) return;
    try {
        const formData = new FormData();
        formData.append('file', input.files[0]);
        const token = localStorage.getItem('admin_token');
        const response = await fetch('/api/upload', {
            method: 'POST',
            headers: token ? { 'Authorization': 'Bearer ' + token } : {},
            body: formData,
        });
        const data = await response.json();
        if (data.code === 0) {
            document.getElementById('product_image').value = data.data.url;
            document.getElementById('product-image-preview').innerHTML = `<img src="${data.data.url}" style="max-height:80px;border-radius:8px;">`;
            Admin.toast('图片上传成功', 'success');
        } else {
            Admin.toast(data.msg || '上传失败', 'error');
        }
    } catch (e) {
        Admin.toast(e.message || '上传失败', 'error');
    }
}

async function init_products() {
    await loadProducts();
}

async function loadProducts() {
    const params = new URLSearchParams();
    params.append('page', Admin.pagination.page);
    params.append('per_page', Admin.pagination.perPage);
    if (productsSearch) params.append('search', productsSearch);

    const data = await Admin.get('/products?' + params.toString());
    if (!data || data.code !== 0) {
        document.getElementById('productsBody').innerHTML = '<tr><td colspan="8" class="text-center text-muted">加载失败</td></tr>';
        return;
    }

    productsData = data.data.list || data.data || [];
    const total = data.data.total || productsData.length;
    Admin.pagination.init(total, Admin.pagination.perPage);

    renderProducts();
    renderProductsPagination();
}

function renderProducts() {
    const tbody = document.getElementById('productsBody');

    if (productsData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">暂无商品</td></tr>';
        return;
    }

    tbody.innerHTML = productsData.map(p => `
        <tr>
            <td style="width:40px;"><input type="checkbox" class="row-check" data-id="${p.id}" onchange="onRowCheck()"></td>
            <td>${p.id}</td>
            <td>${p.image ? `<img src="${p.image}" class="thumb" alt="${p.name}" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22><rect fill=%22%23F3F4F6%22 width=%2240%22 height=%2240%22/><text x=%2220%22 y=%2224%22 text-anchor=%22middle%22 fill=%22%239CA3AF%22 font-size=%2212%22>N/A</text></svg>'">` : '<span class="text-muted">-</span>'}</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${p.name || '-'}</td>
            <td style="font-weight:600;color:var(--success);">${Admin.formatMoney(p.price)}</td>
            <td>${p.status == 1 ? '<span class="badge badge-success">已上架</span>' : '<span class="badge badge-muted">已下架</span>'}</td>
            <td>${p.sales || 0}</td>
            <td>
                <button class="btn btn-secondary btn-sm btn-icon" onclick="editProduct(${p.id})" title="编辑"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-danger btn-sm btn-icon" onclick="deleteProduct(${p.id})" title="删除"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

function renderProductsPagination() {
    const el = document.getElementById('productsPagination');
    el.innerHTML = `
        <span class="pagination-info">${Admin.pagination.getInfo()}</span>
        <div class="pagination-btns">
            <button class="pagination-btn" ${Admin.pagination.hasPrev() ? '' : 'disabled'} onclick="productsPrevPage()">
                <i class="bi bi-chevron-left"></i> 上一页
            </button>
            <button class="pagination-btn" ${Admin.pagination.hasNext() ? '' : 'disabled'} onclick="productsNextPage()">
                下一页 <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    `;
}

function productsPrevPage() {
    Admin.pagination.prev();
    loadProducts();
}

function productsNextPage() {
    Admin.pagination.next();
    loadProducts();
}

let searchTimer = null;
function searchProducts() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        productsSearch = document.getElementById('productSearch').value.trim();
        Admin.pagination.page = 1;
        loadProducts();
    }, 400);
}

function openProductModal(product = null) {
    document.getElementById('productModalTitle').textContent = product ? '编辑商品' : '添加商品';
    const form = document.getElementById('productForm');
    form.reset();
    document.getElementById('productId').value = '';

    if (product) {
        Admin.setFormData('productForm', product);
    }

    // 动态加载分类列表
    loadCategoryOptions(product ? product.category_id : 0);

    Admin.openModal('productModal');
}

async function loadCategoryOptions(selectedId) {
    const select = document.querySelector('#productForm select[name="category_id"]');
    if (!select) return;

    // 保留第一个默认选项
    select.innerHTML = '<option value="">请选择分类</option>';

    try {
        const data = await Admin.get('/categories');
        if (data && data.code === 0 && Array.isArray(data.data)) {
            data.data.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.name;
                if (cat.id == selectedId) opt.selected = true;
                select.appendChild(opt);
            });
        }
    } catch (e) {
        console.error('加载分类失败:', e);
    }
}

function editProduct(id) {
    const product = productsData.find(p => p.id === id);
    if (product) openProductModal(product);
}

async function saveProduct() {
    if (!Admin.validateRequired('productForm')) return;

    const formData = Admin.getFormData('productForm');
    const result = await Admin.post('/products', formData);

    if (result && result.code === 0) {
        Admin.toast('保存成功', 'success');
        Admin.closeModal('productModal');
        await loadProducts();
    } else {
        Admin.toast(result?.msg || '保存失败', 'error');
    }
}

async function deleteProduct(id) {
    const confirmed = await Admin.confirm('删除商品', '确定要删除该商品吗？此操作不可恢复。');
    if (!confirmed) return;

    const result = await Admin.delete('/products?id=' + id);
    if (result && result.code === 0) {
        Admin.toast('删除成功', 'success');
        await loadProducts();
    } else {
        Admin.toast(result?.msg || '删除失败', 'error');
    }
}

// ===== Batch Operations =====

function toggleSelectAll() {
    var selectAll = document.getElementById('selectAll');
    var checked = selectAll.checked;
    document.querySelectorAll('.row-check').forEach(function(cb) { cb.checked = checked; });
    updateBatchUI();
}

function onRowCheck() {
    var selectAll = document.getElementById('selectAll');
    var allCbs = document.querySelectorAll('.row-check');
    var checked = document.querySelectorAll('.row-check:checked');
    selectAll.checked = allCbs.length > 0 && checked.length === allCbs.length;
    selectAll.indeterminate = checked.length > 0 && checked.length < allCbs.length;
    updateBatchUI();
}

function updateBatchUI() {
    var checked = document.querySelectorAll('.row-check:checked');
    var batchDiv = document.getElementById('batchActions');
    var countSpan = document.getElementById('selectedCount');
    if (batchDiv) {
        batchDiv.style.display = checked.length > 0 ? 'flex' : 'none';
    }
    if (countSpan) {
        countSpan.textContent = '已选 ' + checked.length + ' 件';
    }
}

function getSelectedIds() {
    var ids = [];
    document.querySelectorAll('.row-check:checked').forEach(function(cb) {
        ids.push(parseInt(cb.dataset.id));
    });
    return ids;
}

async function batchUpdateStatus(status) {
    var ids = getSelectedIds();
    if (ids.length === 0) { Admin.toast('请先选择商品', 'warning'); return; }
    var action = status === 1 ? '上架' : '下架';
    var confirmed = await Admin.confirm('批量' + action, '确定要批量' + action + '这 ' + ids.length + ' 件商品吗？');
    if (!confirmed) return;

    var result = await Admin.post('/products/batch-status', { ids: ids, status: status });
    if (result && result.code === 0) {
        Admin.toast(result.msg || '操作成功', 'success');
        document.getElementById('selectAll').checked = false;
        await loadProducts();
    } else {
        Admin.toast(result?.msg || '操作失败', 'error');
    }
}

async function batchDelete() {
    var ids = getSelectedIds();
    if (ids.length === 0) { Admin.toast('请先选择商品', 'warning'); return; }
    var confirmed = await Admin.confirm('批量删除', '确定要删除这 ' + ids.length + ' 件商品吗？此操作不可恢复。');
    if (!confirmed) return;

    var result = await Admin.post('/products/batch-delete', { ids: ids });
    if (result && result.code === 0) {
        Admin.toast(result.msg || '删除成功', 'success');
        document.getElementById('selectAll').checked = false;
        await loadProducts();
    } else {
        Admin.toast(result?.msg || '删除失败', 'error');
    }
}
</script>
