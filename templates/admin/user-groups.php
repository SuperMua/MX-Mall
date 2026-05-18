<!-- User Groups Module -->
<div class="toolbar">
    <div class="toolbar-left">
        <span style="color:var(--text-secondary);font-size:13px;">用户分组管理</span>
    </div>
    <div class="toolbar-right">
        <button class="btn btn-primary" onclick="openGroupModal()">
            <i class="bi bi-plus-lg"></i> 添加分组
        </button>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>分组名称</th>
                    <th>佣金比例</th>
                    <th>购买价格</th>
                    <th>默认分组</th>
                    <th>排序</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="groupsBody">
                <tr><td colspan="8" class="text-center text-muted">加载中...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="groupModal">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <h3 id="groupModalTitle">添加分组</h3>
            <button class="modal-close" onclick="Admin.closeModal('groupModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="groupForm">
                <input type="hidden" name="id" id="groupId">
                <div class="form-group">
                    <label class="form-label">分组名称 <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="name" id="groupName" class="form-control" placeholder="如：普通用户、钻石用户、合作商" required>
                </div>
                <div class="form-group">
                    <label class="form-label">佣金比例 (%)</label>
                    <input type="number" name="commission_rate" id="groupCommission" class="form-control" placeholder="0" min="0" max="100" step="0.01">
                    <small style="color:var(--text-muted);">平台从该分组用户商品收入中扣除的佣金比例，0表示不扣佣金</small>
                </div>
                <div class="form-group">
                    <label class="form-label">购买价格 (元)</label>
                    <input type="number" name="price" id="groupPrice" class="form-control" placeholder="0" min="0" step="0.01">
                    <small style="color:var(--text-muted);">用户购买此身份的价格，0表示免费或不可购买</small>
                </div>
                <div class="form-group">
                    <label class="form-label">排序</label>
                    <input type="number" name="sort_order" id="groupSort" class="form-control" placeholder="0" min="0" value="0">
                    <small style="color:var(--text-muted);">数字越小越靠前</small>
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:16px;">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" name="is_default" id="groupIsDefault" value="1">
                        <span>设为默认分组</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" name="status" id="groupStatus" value="1" checked>
                        <span>启用</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Admin.closeModal('groupModal')">取消</button>
            <button class="btn btn-primary" onclick="saveGroup()">保存</button>
        </div>
    </div>
</div>

<script>
let groupsData = [];

async function init_user_groups() {
    await loadGroups();
}

async function loadGroups() {
    const data = await Admin.get('/user-groups');
    if (!data || data.code !== 0) {
        document.getElementById('groupsBody').innerHTML = '<tr><td colspan="8" class="text-center text-muted">加载失败</td></tr>';
        return;
    }
    groupsData = data.data || [];
    renderGroups();
}

function renderGroups() {
    const tbody = document.getElementById('groupsBody');
    if (groupsData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">暂无分组，请添加</td></tr>';
        return;
    }
    tbody.innerHTML = groupsData.map(g => {
        const isDefault = parseInt(g.is_default) === 1;
        const isActive = parseInt(g.status) === 1;
        const price = parseFloat(g.price || 0);
        return '<tr>' +
            '<td>' + g.id + '</td>' +
            '<td><strong>' + g.name + '</strong></td>' +
            '<td><span style="font-weight:600;color:var(--primary);">' + parseFloat(g.commission_rate || 0).toFixed(2) + '%</span></td>' +
            '<td>' + (price > 0 ? '<span style="font-weight:600;">&yen;' + price.toFixed(2) + '</span>' : '<span style="color:var(--text-muted);">免费</span>') + '</td>' +
            '<td>' + (isDefault ? '<span class="badge badge-success">默认</span>' : '<span class="badge badge-muted">否</span>') + '</td>' +
            '<td>' + (g.sort_order || 0) + '</td>' +
            '<td>' + (isActive ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-danger">禁用</span>') + '</td>' +
            '<td>' +
                '<div style="display:flex;gap:4px;">' +
                    '<button class="btn btn-secondary btn-sm" onclick="editGroup(' + g.id + ')">编辑</button>' +
                    (isDefault ? '' : '<button class="btn btn-danger btn-sm" onclick="deleteGroup(' + g.id + ', \'' + g.name.replace(/'/g, "\\'") + '\')">删除</button>') +
                '</div>' +
            '</td>' +
        '</tr>';
    }).join('');
}

function openGroupModal() {
    document.getElementById('groupId').value = '';
    document.getElementById('groupName').value = '';
    document.getElementById('groupCommission').value = '0';
    document.getElementById('groupPrice').value = '0';
    document.getElementById('groupSort').value = '0';
    document.getElementById('groupIsDefault').checked = false;
    document.getElementById('groupStatus').checked = true;
    document.getElementById('groupModalTitle').textContent = '添加分组';
    Admin.openModal('groupModal');
}

function editGroup(id) {
    const group = groupsData.find(g => g.id === id);
    if (!group) return;
    document.getElementById('groupId').value = group.id;
    document.getElementById('groupName').value = group.name;
    document.getElementById('groupCommission').value = group.commission_rate || 0;
    document.getElementById('groupPrice').value = group.price || 0;
    document.getElementById('groupSort').value = group.sort_order || 0;
    document.getElementById('groupIsDefault').checked = parseInt(group.is_default) === 1;
    document.getElementById('groupStatus').checked = parseInt(group.status) === 1;
    document.getElementById('groupModalTitle').textContent = '编辑分组';
    Admin.openModal('groupModal');
}

async function saveGroup() {
    const name = document.getElementById('groupName').value.trim();
    if (!name) {
        Admin.toast('分组名称不能为空', 'warning');
        return;
    }
    const params = {
        id: parseInt(document.getElementById('groupId').value) || 0,
        name: name,
        commission_rate: parseFloat(document.getElementById('groupCommission').value) || 0,
        price: parseFloat(document.getElementById('groupPrice').value) || 0,
        sort_order: parseInt(document.getElementById('groupSort').value) || 0,
        is_default: document.getElementById('groupIsDefault').checked ? 1 : 0,
        status: document.getElementById('groupStatus').checked ? 1 : 0,
    };
    const result = await Admin.post('/user-groups', params);
    if (result && result.code === 0) {
        Admin.toast('保存成功', 'success');
        Admin.closeModal('groupModal');
        await loadGroups();
    } else {
        Admin.toast(result ? result.msg : '保存失败', 'error');
    }
}

async function deleteGroup(id, name) {
    const ok = await Admin.confirm('删除分组', '确定删除分组「' + name + '」吗？该分组下的用户将被迁移到默认分组。');
    if (!ok) return;
    const result = await Admin.post('/delete-user-group', { id: id });
    if (result && result.code === 0) {
        Admin.toast('删除成功', 'success');
        await loadGroups();
    } else {
        Admin.toast(result ? result.msg : '删除失败', 'error');
    }
}
</script>
