<!-- Categories Module -->
<div class="toolbar">
    <div class="toolbar-left">
        <button class="btn btn-primary" onclick="openCategoryModal()">
            <i class="bi bi-plus-lg"></i> 添加分类
        </button>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>排序</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="categoriesBody">
                <tr><td colspan="5" class="text-center text-muted">加载中...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="categoryModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title" id="categoryModalTitle">添加分类</span>
            <button class="modal-close" onclick="Admin.closeModal('categoryModal')"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body" style="max-height:60vh;overflow-y:auto;">
            <form id="categoryForm">
                <input type="hidden" name="id" id="categoryId">
                <div class="form-group">
                    <label class="form-label">分类名称 <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="请输入分类名称" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">排序</label>
                        <input type="number" name="sort_order" class="form-control" placeholder="0" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">状态</label>
                        <select name="status" class="form-control">
                            <option value="1">启用</option>
                            <option value="0">禁用</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Admin.closeModal('categoryModal')">取消</button>
            <button class="btn btn-primary" onclick="saveCategory()">保存</button>
        </div>
    </div>
</div>

<script>
let categoriesData = [];

async function init_categories() {
    await loadCategories();
}

async function loadCategories() {
    const data = await Admin.get('/categories');
    if (!data || data.code !== 0) {
        document.getElementById('categoriesBody').innerHTML = '<tr><td colspan="5" class="text-center text-muted">加载失败</td></tr>';
        return;
    }

    categoriesData = data.data || [];
    renderCategories();
}

function renderCategories() {
    const tbody = document.getElementById('categoriesBody');

    if (categoriesData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">暂无分类</td></tr>';
        return;
    }

    tbody.innerHTML = categoriesData.map(c => {
        return '<tr>' +
            '<td>' + c.id + '</td>' +
            '<td><strong>' + (c.name || '-') + '</strong></td>' +
            '<td>' + (c.sort_order || 0) + '</td>' +
            '<td>' + (c.status == 1 ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-muted">禁用</span>') + '</td>' +
            '<td>' +
                '<button class="btn btn-secondary btn-sm" onclick="editCategory(' + c.id + ')">编辑</button>' +
                '<button class="btn btn-danger btn-sm" onclick="deleteCategory(' + c.id + ')">删除</button>' +
            '</td>' +
        '</tr>';
    }).join('');
}

function openCategoryModal(category) {
    document.getElementById('categoryModalTitle').textContent = category ? '编辑分类' : '添加分类';
    var form = document.getElementById('categoryForm');
    form.reset();
    document.getElementById('categoryId').value = '';

    if (category) {
        Admin.setFormData('categoryForm', category);
    }

    Admin.openModal('categoryModal');
}

function editCategory(id) {
    var category = categoriesData.find(function(c) { return c.id === id; });
    if (category) openCategoryModal(category);
}

async function saveCategory() {
    if (!Admin.validateRequired('categoryForm')) return;

    var formData = Admin.getFormData('categoryForm');
    var result = await Admin.post('/categories', formData);

    if (result && result.code === 0) {
        Admin.toast('保存成功', 'success');
        Admin.closeModal('categoryModal');
        await loadCategories();
    } else {
        Admin.toast(result ? result.msg : '保存失败', 'error');
    }
}

async function deleteCategory(id) {
    var confirmed = await Admin.confirm('删除分类', '确定要删除该分类吗？如果该分类下有商品则无法删除。');
    if (!confirmed) return;

    var result = await Admin.post('/delete-category', { id: id });
    if (result && result.code === 0) {
        Admin.toast('删除成功', 'success');
        await loadCategories();
    } else {
        Admin.toast(result ? result.msg : '删除失败', 'error');
    }
}
</script>
