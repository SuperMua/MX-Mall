<!-- Settings Module -->
<div class="card">
    <div class="card-header">
        <span class="card-title">系统设置</span>
    </div>
    <form id="settingsForm">
        <div class="form-group">
            <label class="form-label">站点名称 <span style="color:var(--danger);">*</span></label>
            <input type="text" name="site_name" class="form-control" placeholder="请输入站点名称" required>
        </div>

        <div class="form-group">
            <label class="form-label">副标题</label>
            <input type="text" name="site_subtitle" class="form-control" placeholder="请输入副标题">
        </div>

        <h6 style="margin:20px 0 12px;color:var(--text-primary);border-top:1px solid var(--border);padding-top:16px;">微信公众号配置</h6>
        <div class="form-group">
            <label class="form-label">AppID</label>
            <input type="text" name="wx_appid" class="form-control" placeholder="微信公众号AppID">
        </div>
        <div class="form-group">
            <label class="form-label">AppSecret</label>
            <input type="password" name="wx_appsecret" class="form-control" placeholder="微信公众号AppSecret">
        </div>

        <h6 style="margin:20px 0 12px;color:var(--text-primary);border-top:1px solid var(--border);padding-top:16px;">微信OAuth代理（无限回调）</h6>
        <div class="form-group">
            <label class="form-label">OAuth代理模式</label>
            <select name="wx_oauth_mode" class="form-control">
                <option value="0">关闭（使用微信官方授权地址）</option>
                <option value="1">开启（使用自定义代理地址）</option>
            </select>
            <small style="color:var(--text-muted);">开启后使用无限回调系统代理OAuth授权，突破微信2个域名限制</small>
        </div>
        <div class="form-group">
            <label class="form-label">OAuth代理地址</label>
            <input type="text" name="wx_oauth_url" class="form-control" placeholder="如：https://zaizai.3.xnan01.cn">
            <small style="color:var(--text-muted);">无限回调系统地址，不含末尾斜杠。开启代理模式后生效，将替换微信官方授权域名</small>
        </div>

        <div style="padding-top:8px;">
            <button type="button" class="btn btn-primary" onclick="saveSettings()">
                <i class="bi bi-check-lg"></i> 保存设置
            </button>
        </div>
    </form>
</div>

<script>
async function init_settings() {
    await loadSettings();
}

async function loadSettings() {
    const data = await Admin.get('/config');
    if (!data || data.code !== 0) {
        Admin.toast('加载设置失败', 'error');
        return;
    }

    let config = data.data || {};
    Object.keys(config).forEach(group => {
        if (typeof config[group] === 'object' && config[group] !== null) {
            Object.assign(config, config[group]);
        }
    });
    ['site', 'payment', 'general', 'pay_epay', 'pay_lakala', 'pay_moss', 'pay_wxpay'].forEach(g => delete config[g]);

    Admin.setFormData('settingsForm', config);
}

async function saveSettings() {
    if (!Admin.validateRequired('settingsForm')) return;

    const formData = Admin.getFormData('settingsForm');
    const result = await Admin.post('/config', formData);

    if (result && result.code === 0) {
        Admin.toast('设置保存成功', 'success');
    } else {
        Admin.toast(result?.msg || '保存失败', 'error');
    }
}
</script>
