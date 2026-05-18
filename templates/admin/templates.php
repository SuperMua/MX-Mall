<!-- Templates Module -->
<div class="toolbar">
    <div class="toolbar-left">
        <span style="color:var(--text-secondary);font-size:13px;">收银台模版管理 - 点击卡片编辑配置</span>
    </div>
</div>

<div class="template-grid" id="templateGrid">
    <div class="loading-spinner" style="grid-column:1/-1;">
        <div class="spinner"></div>
        <span>加载中...</span>
    </div>
</div>

<!-- Template Edit Modal -->
<div class="modal-overlay" id="templateModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title" id="templateModalTitle">编辑模版</span>
            <button class="modal-close" onclick="Admin.closeModal('templateModal')"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body">
            <form id="templateForm">
                <input type="hidden" name="id" id="templateId">
                <div class="form-group">
                    <label class="form-label">模版名称 <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="请输入模版名称" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">品牌色</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="color" name="color" id="templateColor" style="width:48px;height:36px;border:1px solid var(--border-color);border-radius:var(--radius-sm);background:var(--bg-input);cursor:pointer;padding:2px;">
                            <input type="text" name="color_text" class="form-control" placeholder="#6c5ce7" style="flex:1;" oninput="document.getElementById('templateColor').value=this.value">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">图标</label>
                        <input type="text" name="icon" class="form-control" placeholder="bi-shop">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">模版描述</label>
                    <input type="text" name="description" class="form-control" placeholder="请输入模版描述">
                </div>
                <div class="form-group">
                    <label class="form-label">标语</label>
                    <input type="text" name="slogan" class="form-control" placeholder="请输入收银台标语">
                </div>
                <div class="form-group">
                    <label class="form-label">昵称</label>
                    <input type="text" name="nickname" class="form-control" placeholder="商户显示昵称">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">排序</label>
                        <input type="number" name="sort_order" class="form-control" placeholder="0" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">状态</label>
                        <div style="padding-top:6px;">
                            <label class="toggle-switch">
                                <input type="checkbox" name="status" value="1">
                                <span class="toggle-slider"></span>
                            </label>
                            <span style="margin-left:8px;font-size:13px;color:var(--text-secondary);">启用</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Admin.closeModal('templateModal')">取消</button>
            <button class="btn btn-primary" onclick="saveTemplate()">保存</button>
        </div>
    </div>
</div>

<script>
let templatesData = [];

async function init_templates() {
    await loadTemplates();
}

async function loadTemplates() {
    const data = await Admin.get('/templates');
    if (!data || data.code !== 0) {
        document.getElementById('templateGrid').innerHTML = '<div class="empty-state" style="grid-column:1/-1;"><i class="bi bi-exclamation-triangle"></i><p>加载失败</p></div>';
        return;
    }

    templatesData = data.data || [];
    renderTemplates();
}

function renderTemplates() {
    const grid = document.getElementById('templateGrid');

    if (templatesData.length === 0) {
        grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1;"><i class="bi bi-inbox"></i><p>暂无模版</p></div>';
        return;
    }

    grid.innerHTML = templatesData.map(t => `
        <div class="template-card ${t.status == 1 ? 'active' : ''}" onclick="editTemplate(${t.id})">
            <div class="template-color-bar" style="background:${t.color || '#6c5ce7'};"></div>
            <div class="template-card-body">
                <div class="template-card-top">
                    <span class="template-card-icon" style="color:${t.color || '#6c5ce7'};">
                        <i class="bi ${t.icon || 'bi-shop'}"></i>
                    </span>
                    <label class="toggle-switch" onclick="event.stopPropagation();">
                        <input type="checkbox" ${t.status == 1 ? 'checked' : ''} onchange="toggleTemplate(${t.id}, this.checked)">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="template-card-name">${t.name || '未命名'}</div>
                <div class="template-card-desc">${t.description || '暂无描述'}</div>
            </div>
        </div>
    `).join('');
}

function editTemplate(id) {
    const tpl = templatesData.find(t => t.id === id);
    if (!tpl) return;

    document.getElementById('templateModalTitle').textContent = '编辑模版 - ' + (tpl.name || '');
    const form = document.getElementById('templateForm');
    form.reset();
    document.getElementById('templateId').value = '';

    // Set form data
    Admin.setFormData('templateForm', tpl);

    // Sync color picker
    if (tpl.color) {
        document.getElementById('templateColor').value = tpl.color;
        const colorText = form.querySelector('[name="color_text"]');
        if (colorText) colorText.value = tpl.color;
    }

    Admin.openModal('templateModal');
}

async function saveTemplate() {
    if (!Admin.validateRequired('templateForm')) return;

    const formData = Admin.getFormData('templateForm');
    // Use color_text if available, fallback to color picker
    const colorText = document.querySelector('#templateForm [name="color_text"]');
    if (colorText && colorText.value) {
        formData.color = colorText.value;
    }

    const result = await Admin.post('/templates', formData);
    if (result && result.code === 0) {
        Admin.toast('保存成功', 'success');
        Admin.closeModal('templateModal');
        await loadTemplates();
    } else {
        Admin.toast(result?.message || '保存失败', 'error');
    }
}

async function toggleTemplate(id, checked) {
    const result = await Admin.post('/templates', {
        id: id,
        status: checked ? 1 : 0
    });

    if (result && result.code === 0) {
        Admin.toast(checked ? '已启用' : '已禁用', 'success');
        await loadTemplates();
    } else {
        Admin.toast(result?.message || '操作失败', 'error');
        await loadTemplates();
    }
}
</script>
