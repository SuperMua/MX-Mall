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

        <h6 style="margin:20px 0 12px;color:var(--text-primary);border-top:1px solid var(--border);padding-top:16px;">首页轮播图</h6>
        <div id="bannerList">
            <div class="banner-item" style="display:flex;gap:8px;margin-bottom:8px;align-items:center;">
                <input type="text" name="banner_image_0" class="form-control" placeholder="轮播图图片URL" style="flex:1;">
                <input type="text" name="banner_link_0" class="form-control" placeholder="跳转链接(可选)" style="flex:1;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="removeBannerItem(this)" style="flex-shrink:0;" title="删除"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
        <div style="margin-top:8px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="addBannerItem()">
                <i class="bi bi-plus-lg"></i> 添加轮播图
            </button>
            <small style="color:var(--text-muted);margin-left:8px;">最多5张，建议尺寸 750×360</small>
        </div>
        <div id="bannerPreview" style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;"></div>

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
var maxBanners = 5;

function addBannerItem(imageUrl, linkUrl) {
    var list = document.getElementById('bannerList');
    var items = list.querySelectorAll('.banner-item');
    if (items.length >= maxBanners) {
        Admin.toast('最多添加 ' + maxBanners + ' 张轮播图', 'warning');
        return;
    }
    var idx = items.length;
    var div = document.createElement('div');
    div.className = 'banner-item';
    div.style.cssText = 'display:flex;gap:8px;margin-bottom:8px;align-items:center;';
    div.innerHTML = `
        <input type="text" name="banner_image_${idx}" class="form-control" placeholder="轮播图图片URL" style="flex:1;" value="${imageUrl || ''}">
        <input type="text" name="banner_link_${idx}" class="form-control" placeholder="跳转链接(可选)" style="flex:1;" value="${linkUrl || ''}">
        <button type="button" class="btn btn-secondary btn-sm" onclick="removeBannerItem(this)" style="flex-shrink:0;" title="删除"><i class="bi bi-x-lg"></i></button>
    `;
    list.appendChild(div);
    updateBannerPreview();
}

function removeBannerItem(btn) {
    var item = btn.closest('.banner-item');
    if (item) {
        item.remove();
        reindexBannerItems();
        updateBannerPreview();
    }
}

function reindexBannerItems() {
    var items = document.querySelectorAll('#bannerList .banner-item');
    items.forEach(function(item, i) {
        var inputs = item.querySelectorAll('input');
        if (inputs[0]) inputs[0].name = 'banner_image_' + i;
        if (inputs[1]) inputs[1].name = 'banner_link_' + i;
    });
}

function updateBannerPreview() {
    var preview = document.getElementById('bannerPreview');
    var images = document.querySelectorAll('#bannerList input[name^="banner_image_"]');
    var html = '';
    images.forEach(function(input) {
        var url = input.value.trim();
        if (url) {
            html += '<div style="width:80px;height:48px;border-radius:6px;overflow:hidden;border:1px solid var(--border);"><img src="' + url + '" style="width:100%;height:100%;object-fit:cover;" onerror="this.parentElement.remove();"></div>';
        }
    });
    preview.innerHTML = html || '';
}

// Delegate change events on banner image inputs for live preview
document.addEventListener('input', function(e) {
    if (e.target && e.target.name && e.target.name.startsWith('banner_image_')) {
        updateBannerPreview();
    }
});

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
    Object.keys(config).forEach(function(group) {
        if (typeof config[group] === 'object' && config[group] !== null) {
            Object.assign(config, config[group]);
        }
    });
    ['site', 'payment', 'general', 'pay_epay', 'pay_lakala', 'pay_moss', 'pay_wxpay'].forEach(function(g) { delete config[g]; });

    // Parse banner images and links
    var bannerImages = [];
    var bannerLinks = [];
    try { bannerImages = JSON.parse(config.banner_images || '[]'); } catch(e) {}
    try { bannerLinks = JSON.parse(config.banner_links || '[]'); } catch(e) {}
    // Remove raw JSON from config so setFormData doesn't put it in wrong fields
    delete config.banner_images;
    delete config.banner_links;

    Admin.setFormData('settingsForm', config);

    // Populate banner items
    var list = document.getElementById('bannerList');
    list.innerHTML = '';
    if (bannerImages.length > 0) {
        bannerImages.forEach(function(url, i) {
            addBannerItem(url, bannerLinks[i] || '');
        });
    } else {
        addBannerItem('', '');
    }
    updateBannerPreview();
}

async function saveSettings() {
    if (!Admin.validateRequired('settingsForm')) return;

    var formData = Admin.getFormData('settingsForm');

    // Collect banner images and links into JSON arrays
    var bannerImages = [];
    var bannerLinks = [];
    document.querySelectorAll('#bannerList input[name^="banner_image_"]').forEach(function(input) {
        var url = input.value.trim();
        if (url) bannerImages.push(url);
    });
    document.querySelectorAll('#bannerList input[name^="banner_link_"]').forEach(function(input) {
        bannerLinks.push(input.value.trim());
    });

    // Trim links to match images length
    bannerLinks = bannerLinks.slice(0, bannerImages.length);

    // Remove individual banner fields from formData
    Object.keys(formData).forEach(function(key) {
        if (key.startsWith('banner_image_') || key.startsWith('banner_link_')) {
            delete formData[key];
        }
    });

    formData.banner_images = JSON.stringify(bannerImages);
    formData.banner_links = JSON.stringify(bannerLinks);

    // Save via configs parameter
    var result = await Admin.post('/config', { configs: formData });

    if (result && result.code === 0) {
        Admin.toast('设置保存成功', 'success');
    } else {
        Admin.toast(result?.msg || '保存失败', 'error');
    }
}
</script>
