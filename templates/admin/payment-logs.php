<!-- Payment Logs Module -->
<div class="toolbar">
    <div class="toolbar-left">
        <span style="color:var(--text-secondary);font-size:13px;">支付日志记录</span>
    </div>
    <div class="toolbar-right">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" id="logSearch" placeholder="搜索订单号..." onkeyup="searchLogs()">
        </div>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:40px;"></th>
                    <th>ID</th>
                    <th>订单号</th>
                    <th>支付方式</th>
                    <th>渠道</th>
                    <th>状态</th>
                    <th>创建时间</th>
                </tr>
            </thead>
            <tbody id="logsBody">
                <tr><td colspan="7" class="text-center text-muted">加载中...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="pagination" id="logsPagination"></div>
</div>

<script>
let logsData = [];
let logsSearch = '';

async function init_payment_logs() {
    await loadLogs();
}

async function loadLogs() {
    const params = new URLSearchParams();
    params.append('page', Admin.pagination.page);
    params.append('per_page', Admin.pagination.perPage);
    if (logsSearch) params.append('search', logsSearch);

    const data = await Admin.get('/payment-logs?' + params.toString());
    if (!data || data.code !== 0) {
        document.getElementById('logsBody').innerHTML = '<tr><td colspan="7" class="text-center text-muted">加载失败</td></tr>';
        return;
    }

    logsData = data.data.list || data.data || [];
    const total = data.data.total || logsData.length;
    Admin.pagination.init(total, Admin.pagination.perPage);

    renderLogs();
    renderLogsPagination();
}

function renderLogs() {
    const tbody = document.getElementById('logsBody');

    if (logsData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">暂无日志</td></tr>';
        return;
    }

    tbody.innerHTML = logsData.map((log, idx) => `
        <tr>
            <td>
                <span class="expand-toggle" onclick="toggleLogDetail(${idx})" title="展开详情">
                    <i class="bi bi-chevron-right" id="logToggle_${idx}"></i>
                </span>
            </td>
            <td>${log.id}</td>
            <td style="font-family:monospace;font-size:12px;">${log.order_no || '-'}</td>
            <td>${log.pay_type || '-'}</td>
            <td>${log.channel || '-'}</td>
            <td>${Admin.statusBadge(log.status)}</td>
            <td style="font-size:12px;color:var(--text-secondary);">${Admin.formatDate(log.created_at)}</td>
        </tr>
        <tr class="expand-row" id="logDetail_${idx}">
            <td colspan="7" style="padding:4px 14px 14px;">
                <div class="expand-content">
                    <div style="margin-bottom:12px;">
                        <strong style="color:var(--text-secondary);font-size:12px;display:block;margin-bottom:6px;">请求参数</strong>
                        <pre>${formatJson(log.request_data)}</pre>
                    </div>
                    <div>
                        <strong style="color:var(--text-secondary);font-size:12px;display:block;margin-bottom:6px;">响应结果</strong>
                        <pre>${formatJson(log.response_data)}</pre>
                    </div>
                </div>
            </td>
        </tr>
    `).join('');
}

function renderLogsPagination() {
    const el = document.getElementById('logsPagination');
    el.innerHTML = `
        <span class="pagination-info">${Admin.pagination.getInfo()}</span>
        <div class="pagination-btns">
            <button class="pagination-btn" ${Admin.pagination.hasPrev() ? '' : 'disabled'} onclick="logsPrevPage()">
                <i class="bi bi-chevron-left"></i> 上一页
            </button>
            <button class="pagination-btn" ${Admin.pagination.hasNext() ? '' : 'disabled'} onclick="logsNextPage()">
                下一页 <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    `;
}

function logsPrevPage() {
    Admin.pagination.prev();
    loadLogs();
}

function logsNextPage() {
    Admin.pagination.next();
    loadLogs();
}

let logsSearchTimer = null;
function searchLogs() {
    clearTimeout(logsSearchTimer);
    logsSearchTimer = setTimeout(() => {
        logsSearch = document.getElementById('logSearch').value.trim();
        Admin.pagination.page = 1;
        loadLogs();
    }, 400);
}

function toggleLogDetail(idx) {
    const row = document.getElementById('logDetail_' + idx);
    const icon = document.getElementById('logToggle_' + idx);

    if (row.classList.contains('show')) {
        row.classList.remove('show');
        icon.className = 'bi bi-chevron-right';
    } else {
        row.classList.add('show');
        icon.className = 'bi bi-chevron-down';
    }
}

function formatJson(data) {
    if (!data) return '无数据';
    try {
        if (typeof data === 'string') {
            return JSON.stringify(JSON.parse(data), null, 2);
        }
        return JSON.stringify(data, null, 2);
    } catch (e) {
        return String(data);
    }
}
</script>
