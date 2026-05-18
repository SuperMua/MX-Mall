<!-- Users Module -->
<div class="toolbar">
    <div class="toolbar-left">
        <span style="color:var(--text-secondary);font-size:13px;">用户列表</span>
    </div>
    <div class="toolbar-right" style="display:flex;align-items:center;gap:8px;">
        <button class="btn btn-secondary btn-sm" onclick="Admin.exportCsv('/users/export?search=' + encodeURIComponent(usersSearch), 'users.csv')">
            <i class="bi bi-download"></i> 导出CSV
        </button>
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" id="userSearch" placeholder="搜索用户昵称/用户名..." onkeyup="searchUsers()">
        </div>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="data-table" style="table-layout:fixed;">
            <colgroup>
                <col style="width:5%">
                <col style="width:10%">
                <col style="width:10%">
                <col style="width:9%">
                <col style="width:10%">
                <col style="width:9%">
                <col style="width:9%">
                <col style="width:7%">
                <col style="width:8%">
                <col style="width:7%">
            </colgroup>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>用户名</th>
                    <th>昵称</th>
                    <th>余额</th>
                    <th>用户身份</th>
                    <th>注册审核</th>
                    <th>商户</th>
                    <th>状态</th>
                    <th>注册时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="usersBody">
                <tr><td colspan="10" class="text-center text-muted">加载中...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="pagination" id="usersPagination"></div>
</div>

<div class="modal-overlay" id="userEditModal">
    <div class="modal" style="max-width:520px;">
        <div class="modal-header">
            <h3>编辑用户</h3>
            <button class="modal-close" onclick="Admin.closeModal('userEditModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editUserId">
            <div id="editUserInfo" style="margin-bottom:16px;padding:12px;background:var(--bg-elevated);border-radius:8px;font-size:13px;color:var(--text-secondary);"></div>

            <div class="form-group">
                <label class="form-label">用户身份</label>
                <select id="editGroupId" class="form-control">
                    <option value="0">未分组</option>
                </select>
            </div>

            <div id="editReviewSection" style="display:none;margin-bottom:12px;">
                <label class="form-label" style="margin-bottom:8px;">注册审核</label>
                <div style="display:flex;gap:8px;">
                    <button class="btn btn-success btn-sm" onclick="doReviewRegister(1)">通过</button>
                    <button class="btn btn-danger btn-sm" onclick="doReviewRegister(2)">拒绝</button>
                </div>
            </div>

            <div id="editMerchantSection" style="display:none;margin-bottom:12px;">
                <label class="form-label" style="margin-bottom:8px;">商户审核</label>
                <div style="display:flex;gap:8px;">
                    <button class="btn btn-success btn-sm" onclick="doReviewMerchant(2)">通过</button>
                    <button class="btn btn-danger btn-sm" onclick="doReviewMerchant(3)">拒绝</button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">账号状态</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <span id="editCurrentStatus"></span>
                    <button class="btn btn-secondary btn-sm" id="btnToggleStatus" onclick="toggleUserStatus()">切换状态</button>
                </div>
            </div>

            <h6 style="margin:16px 0 12px;color:var(--text-primary);border-top:1px solid var(--border);padding-top:16px;">调整余额</h6>
            <div class="form-group">
                <label class="form-label">调整金额</label>
                <input type="number" id="editBalanceAmount" class="form-control" step="0.01" placeholder="正数加余额，负数减余额，0或不填不调整">
                <small style="color:var(--text-muted);">输入正数增加余额，负数减少余额</small>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Admin.closeModal('userEditModal')">取消</button>
            <button class="btn btn-danger" id="btnDeleteUser" onclick="deleteUserFromModal()" style="margin-right:auto;">删除用户</button>
            <button class="btn btn-primary" onclick="saveUserEdit()">保存</button>
        </div>
    </div>
</div>

<script>
let usersData = [];
let usersSearch = '';
let allGroups = [];
let currentEditUser = null;

async function init_users() {
    await Promise.all([loadGroups(), loadUsers()]);
}

async function loadGroups() {
    const data = await Admin.get('/user-groups');
    if (data && data.code === 0) {
        allGroups = data.data || [];
    }
}

async function loadUsers() {
    const params = new URLSearchParams();
    params.append('page', Admin.pagination.page);
    params.append('per_page', Admin.pagination.perPage);
    if (usersSearch) params.append('keyword', usersSearch);

    const data = await Admin.get('/users?' + params.toString());
    if (!data || data.code !== 0) {
        document.getElementById('usersBody').innerHTML = '<tr><td colspan="10" class="text-center text-muted">加载失败</td></tr>';
        return;
    }

    usersData = data.data.list || data.data || [];
    const total = data.data.total || usersData.length;
    Admin.pagination.init(total, Admin.pagination.perPage);

    renderUsers();
    renderUsersPagination();
}

function getReviewBadge(status) {
    const map = {
        0: '<span class="badge badge-warning">待审核</span>',
        1: '<span class="badge badge-success">已通过</span>',
        2: '<span class="badge badge-danger">已拒绝</span>',
    };
    return map[status] !== undefined ? map[status] : '<span class="badge badge-muted">未知</span>';
}

function getMerchantBadge(status) {
    const map = {
        0: '<span class="badge badge-muted">未申请</span>',
        1: '<span class="badge badge-warning">待审核</span>',
        2: '<span class="badge badge-success">已通过</span>',
        3: '<span class="badge badge-danger">已拒绝</span>',
    };
    return map[status] !== undefined ? map[status] : '<span class="badge badge-muted">未知</span>';
}

function getGroupName(groupId, groupName) {
    if (groupName) return '<span class="badge badge-primary">' + groupName + '</span>';
    if (groupId && parseInt(groupId) > 0) return '<span class="badge badge-primary">ID:' + groupId + '</span>';
    return '<span class="badge badge-muted">未分组</span>';
}

function renderUsers() {
    const tbody = document.getElementById('usersBody');

    if (usersData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">暂无用户</td></tr>';
        return;
    }

    tbody.innerHTML = usersData.map(u => {
        const reviewStatus = u.review_status !== undefined ? parseInt(u.review_status) : 1;
        const merchantStatus = u.merchant_status !== undefined ? parseInt(u.merchant_status) : 0;
        const userStatus = u.status !== undefined ? parseInt(u.status) : 1;

        return '<tr>' +
            '<td>' + u.id + '</td>' +
            '<td>' + (u.username || '-') + '</td>' +
            '<td>' + (u.nickname || '-') + '</td>' +
            '<td style="font-weight:600;color:var(--primary);">&yen;' + parseFloat(u.balance || 0).toFixed(2) + '</td>' +
            '<td>' + getGroupName(u.group_id, u.group_name) + '</td>' +
            '<td>' + getReviewBadge(reviewStatus) + '</td>' +
            '<td>' + getMerchantBadge(merchantStatus) + '</td>' +
            '<td>' + (userStatus === 1 ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-danger">禁用</span>') + '</td>' +
            '<td style="font-size:11px;color:var(--text-secondary);">' + Admin.formatDate(u.created_at) + '</td>' +
            '<td>' +
                '<button class="btn btn-primary btn-sm" onclick="openEditModal(' + u.id + ')">编辑</button>' +
            '</td>' +
        '</tr>';
    }).join('');
}

function renderUsersPagination() {
    const el = document.getElementById('usersPagination');
    el.innerHTML = '<span class="pagination-info">' + Admin.pagination.getInfo() + '</span>' +
        '<div class="pagination-btns">' +
            '<button class="pagination-btn" ' + (Admin.pagination.hasPrev() ? '' : 'disabled') + ' onclick="usersPrevPage()">' +
                '<i class="bi bi-chevron-left"></i> 上一页' +
            '</button>' +
            '<button class="pagination-btn" ' + (Admin.pagination.hasNext() ? '' : 'disabled') + ' onclick="usersNextPage()">' +
                '下一页 <i class="bi bi-chevron-right"></i>' +
            '</button>' +
        '</div>';
}

function usersPrevPage() {
    Admin.pagination.prev();
    loadUsers();
}

function usersNextPage() {
    Admin.pagination.next();
    loadUsers();
}

let usersSearchTimer = null;
function searchUsers() {
    clearTimeout(usersSearchTimer);
    usersSearchTimer = setTimeout(function() {
        usersSearch = document.getElementById('userSearch').value.trim();
        Admin.pagination.page = 1;
        loadUsers();
    }, 400);
}

function openEditModal(userId) {
    const u = usersData.find(function(item) { return item.id === userId; });
    if (!u) return;
    currentEditUser = u;

    document.getElementById('editUserId').value = u.id;
    document.getElementById('editUserInfo').innerHTML =
        '<strong>' + (u.nickname || u.username || '-') + '</strong> (ID: ' + u.id + ')' +
        ' | 余额: <strong style="color:var(--primary);">&yen;' + parseFloat(u.balance || 0).toFixed(2) + '</strong>' +
        ' | 冻结: &yen;' + parseFloat(u.frozen_balance || 0).toFixed(2);

    var groupSelect = document.getElementById('editGroupId');
    groupSelect.innerHTML = '<option value="0">未分组</option>';
    allGroups.forEach(function(g) {
        var selected = parseInt(u.group_id) === parseInt(g.id) ? ' selected' : '';
        groupSelect.innerHTML += '<option value="' + g.id + '"' + selected + '>' + g.name + ' (佣金' + parseFloat(g.commission_rate).toFixed(2) + '%)</option>';
    });

    var reviewSection = document.getElementById('editReviewSection');
    reviewSection.style.display = parseInt(u.review_status) === 0 ? 'block' : 'none';

    var merchantSection = document.getElementById('editMerchantSection');
    merchantSection.style.display = parseInt(u.merchant_status) === 1 ? 'block' : 'none';

    var currentStatus = parseInt(u.status) === 1 ? '启用' : '禁用';
    document.getElementById('editCurrentStatus').innerHTML = '<span class="badge ' + (parseInt(u.status) === 1 ? 'badge-success' : 'badge-danger') + '">' + currentStatus + '</span>';

    document.getElementById('editBalanceAmount').value = '';

    Admin.openModal('userEditModal');
}

async function doReviewRegister(status) {
    var userId = parseInt(document.getElementById('editUserId').value);
    var result = await Admin.post('/review-user', { id: userId, review_status: status });
    if (result && result.code === 0) {
        Admin.toast(status === 1 ? '注册审核已通过' : '注册审核已拒绝', 'success');
        document.getElementById('editReviewSection').style.display = 'none';
        await loadUsers();
    } else {
        Admin.toast(result ? result.msg : '操作失败', 'error');
    }
}

async function doReviewMerchant(status) {
    var userId = parseInt(document.getElementById('editUserId').value);
    var result = await Admin.post('/review-merchant', { id: userId, merchant_status: status });
    if (result && result.code === 0) {
        Admin.toast(status === 2 ? '商户审核已通过' : '商户审核已拒绝', 'success');
        document.getElementById('editMerchantSection').style.display = 'none';
        await loadUsers();
    } else {
        Admin.toast(result ? result.msg : '操作失败', 'error');
    }
}

async function toggleUserStatus() {
    var userId = parseInt(document.getElementById('editUserId').value);
    var newStatus = currentEditUser && parseInt(currentEditUser.status) === 1 ? 0 : 1;
    var result = await Admin.post('/review-user', { id: userId, status: newStatus });
    if (result && result.code === 0) {
        Admin.toast(newStatus === 1 ? '用户已启用' : '用户已禁用', 'success');
        if (currentEditUser) currentEditUser.status = newStatus;
        var currentStatus = newStatus === 1 ? '启用' : '禁用';
        document.getElementById('editCurrentStatus').innerHTML = '<span class="badge ' + (newStatus === 1 ? 'badge-success' : 'badge-danger') + '">' + currentStatus + '</span>';
        await loadUsers();
    } else {
        Admin.toast(result ? result.msg : '操作失败', 'error');
    }
}

async function saveUserEdit() {
    var userId = parseInt(document.getElementById('editUserId').value);
    var groupId = document.getElementById('editGroupId').value;
    var balanceAmount = document.getElementById('editBalanceAmount').value;

    var params = { id: userId, group_id: groupId };

    var result = await Admin.post('/review-user', params);

    if (result && result.code === 0) {
        if (balanceAmount !== '' && parseFloat(balanceAmount) !== 0) {
            var balanceResult = await Admin.post('/adjust-balance', {
                user_id: userId,
                amount: parseFloat(balanceAmount)
            });
            if (balanceResult && balanceResult.code !== 0) {
                Admin.toast(balanceResult.msg || '余额调整失败', 'error');
            }
        }
        Admin.toast('操作成功', 'success');
        Admin.closeModal('userEditModal');
        await loadUsers();
    } else {
        Admin.toast(result ? result.msg : '操作失败', 'error');
    }
}

async function deleteUserFromModal() {
    var userId = parseInt(document.getElementById('editUserId').value);
    var u = usersData.find(function(item) { return item.id === userId; });
    if (!u) return;

    var userName = (u.nickname || u.username || '');
    var ok = await Admin.confirm('删除用户', '确定删除用户「' + userName + '」吗？该操作不可恢复！');
    if (!ok) return;

    try {
        var result = await Admin.delete('/users/' + userId);
        if (result && result.code === 0) {
            Admin.toast('用户已删除', 'success');
            Admin.closeModal('userEditModal');
            await loadUsers();
        } else {
            Admin.toast(result ? result.msg : '删除失败', 'error');
        }
    } catch (e) {
        Admin.toast(e.message || '删除失败', 'error');
    }
}
</script>
