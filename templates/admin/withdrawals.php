<!-- Withdrawals Module -->
<div class="toolbar">
    <div class="toolbar-left">
        <div class="tabs" id="withdrawalTabs">
            <button class="tab-item active" onclick="filterWithdrawals(this, '')">全部</button>
            <button class="tab-item" onclick="filterWithdrawals(this, '0')">待审核</button>
            <button class="tab-item" onclick="filterWithdrawals(this, '2')">已拒绝</button>
            <button class="tab-item" onclick="filterWithdrawals(this, '3')">已打款</button>
        </div>
    </div>
    <div class="toolbar-right">
        <button class="btn btn-secondary btn-sm" onclick="Admin.exportCsv('/withdrawals/export?status=' + (withdrawalsFilter || ''), 'withdrawals.csv')">
            <i class="bi bi-download"></i> 导出CSV
        </button>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>用户</th>
                    <th>提现单号</th>
                    <th>金额</th>
                    <th>收款码</th>
                    <th>真实姓名</th>
                    <th>状态</th>
                    <th>申请时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="withdrawalsBody">
                <tr><td colspan="9" class="text-center text-muted">加载中...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="pagination" id="withdrawalsPagination"></div>
</div>

<!-- 审核备注弹窗 -->
<div class="modal-overlay" id="withdrawReviewModal">
    <div class="modal" style="max-width:440px;">
        <div class="modal-header">
            <h3 id="withdrawReviewTitle">审核提现</h3>
            <button class="modal-close" onclick="Admin.closeModal('withdrawReviewModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p id="withdrawReviewMsg" style="margin-bottom:12px;color:var(--text-secondary);"></p>
            <div class="form-group">
                <label class="form-label">备注 (可选)</label>
                <textarea id="withdrawReviewRemark" class="form-control" rows="3" placeholder="请输入审核备注..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Admin.closeModal('withdrawReviewModal')">取消</button>
            <button class="btn btn-primary" id="withdrawReviewConfirmBtn" onclick="confirmWithdrawReview()">确定</button>
        </div>
    </div>
</div>

<script>
let withdrawalsData = [];
let withdrawalsFilter = '';
let pendingWithdrawAction = null; // { id, action }

async function init_withdrawals() {
    await loadWithdrawals();
}

async function loadWithdrawals() {
    const params = new URLSearchParams();
    params.append('page', Admin.pagination.page);
    params.append('per_page', Admin.pagination.perPage);
    if (withdrawalsFilter) {
        params.append('status', withdrawalsFilter);
    }

    const data = await Admin.get('/withdrawals?' + params.toString());
    if (!data || data.code !== 0) {
        document.getElementById('withdrawalsBody').innerHTML = '<tr><td colspan="9" class="text-center text-muted">加载失败</td></tr>';
        return;
    }

    withdrawalsData = data.data.list || data.data || [];
    const total = data.data.total || withdrawalsData.length;
    Admin.pagination.init(total, Admin.pagination.perPage);

    renderWithdrawals();
    renderWithdrawalsPagination();
}

function filterWithdrawals(btn, status) {
    document.querySelectorAll('#withdrawalTabs .tab-item').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    withdrawalsFilter = status;
    Admin.pagination.page = 1;
    loadWithdrawals();
}

function getWithdrawStatusBadge(status) {
    const map = {
        0: '<span class="badge badge-warning">待审核</span>',
        1: '<span class="badge badge-success">已提现到账</span>',
        2: '<span class="badge badge-danger">已拒绝</span>',
        3: '<span class="badge badge-success">已提现到账</span>'
    };
    return map[status] || '<span class="badge badge-secondary">未知</span>';
}

function renderWithdrawals() {
    const tbody = document.getElementById('withdrawalsBody');

    if (withdrawalsData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">暂无提现记录</td></tr>';
        return;
    }

    tbody.innerHTML = withdrawalsData.map(w => `
        <tr>
            <td>${w.id}</td>
            <td>${w.nickname || w.username || '-'}</td>
            <td style="font-family:monospace;font-size:12px;">${w.out_trade_no || '-'}</td>
            <td style="font-weight:600;color:var(--primary);">${Admin.formatMoney(w.amount)}</td>
            <td>
                ${w.qr_code ? `<img src="${w.qr_code}" style="width:36px;height:36px;border-radius:4px;cursor:pointer;" onclick="window.open('${w.qr_code}','_blank')">` : '<span style="color:var(--text-muted);">-</span>'}
            </td>
            <td>${w.real_name || '-'}</td>
            <td>${getWithdrawStatusBadge(w.status)}</td>
            <td style="font-size:12px;color:var(--text-secondary);">${Admin.formatDate(w.created_at)}</td>
            <td>
                ${w.status == 0 ? `
                    <button class="btn btn-success btn-sm" onclick="openWithdrawReview(${w.id}, 'approve')" title="通过">通过</button>
                    <button class="btn btn-danger btn-sm" onclick="openWithdrawReview(${w.id}, 'reject')" title="拒绝">拒绝</button>
                ` : '<span style="color:var(--text-muted);font-size:12px;">已处理</span>'}
            </td>
        </tr>
    `).join('');
}

function renderWithdrawalsPagination() {
    const el = document.getElementById('withdrawalsPagination');
    el.innerHTML = `
        <span class="pagination-info">${Admin.pagination.getInfo()}</span>
        <div class="pagination-btns">
            <button class="pagination-btn" ${Admin.pagination.hasPrev() ? '' : 'disabled'} onclick="withdrawalsPrevPage()">
                <i class="bi bi-chevron-left"></i> 上一页
            </button>
            <button class="pagination-btn" ${Admin.pagination.hasNext() ? '' : 'disabled'} onclick="withdrawalsNextPage()">
                下一页 <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    `;
}

function withdrawalsPrevPage() {
    Admin.pagination.prev();
    loadWithdrawals();
}

function withdrawalsNextPage() {
    Admin.pagination.next();
    loadWithdrawals();
}

function openWithdrawReview(id, action) {
    pendingWithdrawAction = { id, action };
    const titleEl = document.getElementById('withdrawReviewTitle');
    const msgEl = document.getElementById('withdrawReviewMsg');
    const btnEl = document.getElementById('withdrawReviewConfirmBtn');
    const remarkEl = document.getElementById('withdrawReviewRemark');

    remarkEl.value = '';

    if (action === 'approve') {
        titleEl.textContent = '通过提现审核';
        msgEl.textContent = '确定要通过该提现申请吗？通过后将自动标记为已提现到账。';
        btnEl.className = 'btn btn-success';
        btnEl.textContent = '确认通过';
    } else {
        titleEl.textContent = '拒绝提现申请';
        msgEl.textContent = '确定要拒绝该提现申请吗？拒绝后金额将退回用户余额。';
        btnEl.className = 'btn btn-danger';
        btnEl.textContent = '确认拒绝';
    }

    Admin.openModal('withdrawReviewModal');
}

async function confirmWithdrawReview() {
    if (!pendingWithdrawAction) return;

    const { id, action } = pendingWithdrawAction;
    const remark = document.getElementById('withdrawReviewRemark').value.trim();

    const endpoint = action === 'approve' ? '/review-withdraw' : '/review-withdraw';
    const status = action === 'approve' ? 3 : 2;

    const result = await Admin.post(endpoint, {
        id: id,
        status: status,
        remark: remark
    });

    Admin.closeModal('withdrawReviewModal');
    pendingWithdrawAction = null;

    if (result && result.code === 0) {
        Admin.toast(result.msg || '操作成功', 'success');
        await loadWithdrawals();
    } else {
        Admin.toast(result?.msg || '操作失败', 'error');
    }
}

async function completeWithdraw(id) {
    const confirmed = await Admin.confirm('确认打款', '确定已完成线下打款操作？确认后状态将变为"已打款"。');
    if (!confirmed) return;

    const result = await Admin.post('/complete-withdraw', { id: id });
    if (result && result.code === 0) {
        Admin.toast(result.msg || '打款确认成功', 'success');
        await loadWithdrawals();
    } else {
        Admin.toast(result?.msg || '操作失败', 'error');
    }
}
</script>
