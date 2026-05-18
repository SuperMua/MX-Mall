<!-- Orders Module -->
<div class="filter-tabs" id="orderFilters">
    <button class="filter-tab" data-status="" onclick="filterOrders(this, '')">全部</button>
    <button class="filter-tab" data-status="unpaid" onclick="filterOrders(this, 'unpaid')">待支付</button>
    <button class="filter-tab" data-status="paid" onclick="filterOrders(this, 'paid')">已支付</button>
    <button class="filter-tab" data-status="expired" onclick="filterOrders(this, 'expired')">已过期</button>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" id="orderSearch" placeholder="搜索订单号/商品名..." onkeyup="searchOrders()">
        </div>
    </div>
    <div class="toolbar-right">
        <button class="btn btn-secondary btn-sm" onclick="Admin.exportCsv('/orders/export?status=' + ordersFilter + '&search=' + encodeURIComponent(ordersSearch), 'orders.csv')">
            <i class="bi bi-download"></i> 导出CSV
        </button>
        <button class="btn btn-danger" onclick="cleanExpiredOrders()">
            <i class="bi bi-trash"></i> 清理过期订单
        </button>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>订单号</th>
                    <th>商品名</th>
                    <th>金额</th>
                    <th>支付模板</th>
                    <th>状态</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="ordersBody">
                <tr><td colspan="7" class="text-center text-muted">加载中...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="pagination" id="ordersPagination"></div>
</div>

<!-- Order Detail Modal -->
<div class="modal-overlay" id="orderDetailModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title">订单详情</span>
            <button class="modal-close" onclick="Admin.closeModal('orderDetailModal')"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body" id="orderDetailBody">
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Admin.closeModal('orderDetailModal')">关闭</button>
        </div>
    </div>
</div>

<script>
const templateNames = {
    'meituan': '美团外卖', 'jd': '京东', 'ctrip-flight': '携程机票',
    'didi': '滴滴出行', 'pdd': '拼多多', 'taobao': '淘宝',
    'ctrip-hotel': '携程酒店', 'fliggy': '飞猪', 'dewu': '得物',
    'maoyan': '猫眼电影', 'taobao2': '淘宝好物', 'douyin': '抖音',
    'didi2': '滴滴Pro', 'xianyu': '闲鱼'
};

let ordersData = [];
let ordersFilter = '';
let ordersSearch = '';

async function init_orders() {
    await loadOrders();
}

async function loadOrders() {
    const params = new URLSearchParams();
    params.append('page', Admin.pagination.page);
    params.append('per_page', Admin.pagination.perPage);
    if (ordersFilter) params.append('status', ordersFilter);
    if (ordersSearch) params.append('search', ordersSearch);

    const data = await Admin.get('/orders?' + params.toString());
    if (!data || data.code !== 0) {
        document.getElementById('ordersBody').innerHTML = '<tr><td colspan="8" class="text-center text-muted">加载失败</td></tr>';
        return;
    }

    ordersData = data.data.list || data.data || [];
    const total = data.data.total || ordersData.length;
    Admin.pagination.init(total, Admin.pagination.perPage);

    renderOrders();
    renderOrdersPagination();
}

function renderOrders() {
    const tbody = document.getElementById('ordersBody');

    if (ordersData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">暂无订单</td></tr>';
        return;
    }

    tbody.innerHTML = ordersData.map(o => `
        <tr>
            <td style="font-family:monospace;font-size:12px;">${o.out_trade_no || '-'}</td>
            <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${o.product_name || '-'}</td>
            <td style="font-weight:600;">${Admin.formatMoney(o.money)}</td>
            <td>${templateNames[o.cashier_tpl] || o.cashier_tpl || '-'}</td>
            <td>${Admin.statusBadge(o.status)}</td>
            <td style="font-size:12px;color:var(--text-secondary);">${Admin.formatDate(o.created_at)}</td>
            <td>
                <button class="btn btn-secondary btn-sm btn-icon" onclick="viewOrder(${o.id})" title="查看详情"><i class="bi bi-eye"></i></button>
                ${o.status == 1 ? '<button class="btn btn-danger btn-sm btn-icon" onclick="refundOrder(' + o.id + ')" title="退款" style="margin-left:4px;"><i class="bi bi-arrow-counterclockwise"></i></button>' : ''}
            </td>
        </tr>
    `).join('');
}

function renderOrdersPagination() {
    const el = document.getElementById('ordersPagination');
    el.innerHTML = `
        <span class="pagination-info">${Admin.pagination.getInfo()}</span>
        <div class="pagination-btns">
            <button class="pagination-btn" ${Admin.pagination.hasPrev() ? '' : 'disabled'} onclick="ordersPrevPage()">
                <i class="bi bi-chevron-left"></i> 上一页
            </button>
            <button class="pagination-btn" ${Admin.pagination.hasNext() ? '' : 'disabled'} onclick="ordersNextPage()">
                下一页 <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    `;
}

function ordersPrevPage() {
    Admin.pagination.prev();
    loadOrders();
}

function ordersNextPage() {
    Admin.pagination.next();
    loadOrders();
}

function filterOrders(btn, status) {
    document.querySelectorAll('#orderFilters .filter-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    ordersFilter = status;
    Admin.pagination.page = 1;
    loadOrders();
}

let ordersSearchTimer = null;
function searchOrders() {
    clearTimeout(ordersSearchTimer);
    ordersSearchTimer = setTimeout(() => {
        ordersSearch = document.getElementById('orderSearch').value.trim();
        Admin.pagination.page = 1;
        loadOrders();
    }, 400);
}

function viewOrder(id) {
    const order = ordersData.find(o => o.id === id);
    if (!order) return;

    const body = document.getElementById('orderDetailBody');
    body.innerHTML = `
        <div class="detail-grid">
            <span class="detail-label">订单号</span>
            <span class="detail-value" style="font-family:monospace;" id="detail-order-id">${order.out_trade_no || '-'}</span>

            <span class="detail-label">商品名称</span>
            <span class="detail-value">${order.product_name || '-'}</span>

            <span class="detail-label">订单金额</span>
            <span class="detail-value" style="font-weight:600;color:var(--success);">${Admin.formatMoney(order.money)}</span>

            <span class="detail-label">支付模板</span>
            <span class="detail-value">${templateNames[order.cashier_tpl] || order.cashier_tpl || '-'}</span>

            <span class="detail-label">订单状态</span>
            <span class="detail-value">${Admin.statusBadge(order.status)}</span>

            <span class="detail-label">创建时间</span>
            <span class="detail-value">${Admin.formatDate(order.created_at)}</span>

            <span class="detail-label">支付时间</span>
            <span class="detail-value">${Admin.formatDate(order.pay_time)}</span>

            <span class="detail-label">备注</span>
            <span class="detail-value">${order.remark || '-'}</span>
        </div>
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
            <label style="font-size:13px;color:var(--text-muted);">更改订单状态</label>
            <select id="order-status-select" style="margin-left:8px;padding:4px 8px;border-radius:6px;border:1px solid var(--border);">
                <option value="0" ${order.status == 0 ? 'selected' : ''}>待支付</option>
                <option value="1" ${order.status == 1 ? 'selected' : ''}>已支付</option>
                <option value="3" ${order.status == 3 ? 'selected' : ''}>已过期</option>
            </select>
            <button onclick="updateOrderStatus()" style="margin-left:8px;padding:4px 12px;border-radius:6px;background:var(--primary);color:#fff;border:none;cursor:pointer;">保存</button>
        </div>
    `;

    Admin.openModal('orderDetailModal');
}

async function updateOrderStatus() {
    const orderIdEl = document.getElementById('detail-order-id');
    const orderId = orderIdEl ? orderIdEl.textContent : '';
    // 从 ordersData 中查找对应的数字 ID
    const order = ordersData.find(o => o.out_trade_no === orderId);
    if (!order) return;

    const newStatus = document.getElementById('order-status-select')?.value;

    const res = await Admin.post('/order/status', { order_id: order.id, status: newStatus });
    if (res.code === 0) {
        Admin.toast('状态更新成功', 'success');
        loadOrders();
        Admin.closeModal('orderDetailModal');
    } else {
        Admin.toast(res.msg || '更新失败', 'error');
    }
}

async function refundOrder(id) {
    const confirmed = await Admin.confirm('订单退款', '确定要对该订单执行退款吗？退款将原路返回。');
    if (!confirmed) return;

    const result = await Admin.post('/orders/refund', { order_id: id });
    if (result && result.code === 0) {
        Admin.toast('退款成功', 'success');
        await loadOrders();
    } else {
        Admin.toast(result?.msg || '退款失败', 'error');
    }
}

async function cleanExpiredOrders() {
    if (!confirm('确定要删除所有过期订单吗？')) return;
    const res = await Admin.post('/orders/clean', {});
    if (res.code === 0) {
        Admin.toast('清理完成，删除了 ' + (res.data?.deleted || 0) + ' 条订单', 'success');
        loadOrders();
    } else {
        Admin.toast(res.msg || '清理失败', 'error');
    }
}
</script>
