<!-- Payment Config Module -->
<div class="card">
    <div class="card-header">
        <span class="card-title">支付通道配置</span>
    </div>
    <form id="paymentForm">
        <!-- 支付通道选择 -->
        <div class="form-group">
            <label class="form-label">支付通道 <span style="color:var(--danger);">*</span></label>
            <select name="pay_channel" class="form-control" onchange="switchPayChannel(this.value)">
                <option value="epay">彩虹易支付</option>
                <option value="lakala_moss">拉卡拉 MOSS 支付</option>
                <option value="wxpay">微信官方支付</option>
            </select>
        </div>

        <!-- 易支付配置 -->
        <div id="epay_config" class="config-group">
            <h6 style="margin-bottom:12px;color:var(--text-primary);">彩虹易支付参数</h6>
            <div class="form-group">
                <label class="form-label">API URL <span style="color:var(--danger);">*</span></label>
                <input type="text" name="epay_api" class="form-control" placeholder="https://pay.example.com">
                <small style="color:var(--text-muted);">支付网关地址，不带末尾斜杠</small>
            </div>
            <div class="form-group">
                <label class="form-label">商户ID (PID) <span style="color:var(--danger);">*</span></label>
                <input type="text" name="epay_id" class="form-control" placeholder="请输入商户ID">
            </div>
            <div class="form-group">
                <label class="form-label">商户密钥 (Key) <span style="color:var(--danger);">*</span></label>
                <input type="password" name="epay_key" class="form-control" placeholder="请输入商户密钥">
                <small style="color:var(--text-muted);">用于签名验证，请妥善保管</small>
            </div>
        </div>

        <!-- 拉卡拉MOSS配置 -->
        <div id="moss_config" class="config-group" style="display:none;">
            <h6 style="margin-bottom:12px;color:var(--text-primary);">拉卡拉 MOSS 支付参数</h6>
            <div class="form-group">
                <label class="form-label">APPID (reqId) <span style="color:var(--danger);">*</span></label>
                <input type="text" name="moss_appid" class="form-control" placeholder="请输入APPID">
                <small style="color:var(--text-muted);">MOSS平台分配的业务渠道号</small>
            </div>
            <div class="form-group">
                <label class="form-label">商户ID <span style="color:var(--danger);">*</span></label>
                <input type="text" name="moss_mer_no" class="form-control" placeholder="M00000036">
                <small style="color:var(--text-muted);">MOSS平台分配的商户号，M+8位数字</small>
            </div>
            <div class="form-group">
                <label class="form-label">客户私钥 <span style="color:var(--danger);">*</span></label>
                <textarea name="moss_private_key" class="form-control" rows="4" placeholder="请输入客户RSA私钥" style="font-family:monospace;font-size:12px;"></textarea>
                <small style="color:var(--text-muted);">用于签名和回调解密，请妥善保管</small>
            </div>
        </div>

        <!-- 微信官方支付配置 -->
        <div id="wxpay_config" class="config-group" style="display:none;">
            <h6 style="margin-bottom:12px;color:var(--text-primary);">微信官方支付参数</h6>
            <div class="form-group">
                <label class="form-label">商户号 (MchID) <span style="color:var(--danger);">*</span></label>
                <input type="text" name="wxpay_mchid" class="form-control" placeholder="1488888888">
                <small style="color:var(--text-muted);">微信支付商户平台分配的商户号</small>
            </div>
            <div class="form-group">
                <label class="form-label">API密钥 (Key) <span style="color:var(--danger);">*</span></label>
                <input type="password" name="wxpay_key" class="form-control" placeholder="请输入API密钥">
                <small style="color:var(--text-muted);">商户平台设置的APIv2密钥，用于签名</small>
            </div>
            <div class="alert alert-info" style="margin-top:12px;">
                公众号AppID/AppSecret请在「系统设置」中配置
            </div>
        </div>

        <div style="padding-top:16px;">
            <button type="button" class="btn btn-primary" onclick="savePaymentConfig()">
                <i class="bi bi-check-lg"></i> 保存配置
            </button>
        </div>
    </form>
</div>

<script>
let paymentConfigData = {};

async function init_payment() {
    await loadPaymentConfig();
}

async function loadPaymentConfig() {
    const data = await Admin.get('/config');
    if (!data || data.code !== 0) {
        Admin.toast('加载配置失败', 'error');
        return;
    }

    paymentConfigData = data.data || {};

    if (paymentConfigData.site) {
        paymentConfigData = { ...paymentConfigData.general, ...paymentConfigData.site, ...paymentConfigData.payment, ...paymentConfigData.pay_epay, ...paymentConfigData.pay_lakala, ...paymentConfigData.pay_moss, ...paymentConfigData.pay_wxpay };
    }

    const form = document.getElementById('paymentForm');
    const elements = form.querySelectorAll('input, select, textarea');
    elements.forEach(el => {
        if (el.name && paymentConfigData[el.name] !== undefined) {
            el.value = paymentConfigData[el.name];
        }
    });

    let channel = paymentConfigData.pay_channel || '';
    if (!channel) {
        if (paymentConfigData.wxpay_mchid) {
            channel = 'wxpay';
        } else if (paymentConfigData.moss_appid || paymentConfigData.moss_mer_no) {
            channel = 'lakala_moss';
        } else if (paymentConfigData.epay_id || paymentConfigData.epay_api) {
            channel = 'epay';
        } else {
            channel = 'epay';
        }
    }
    const select = form.querySelector('select[name="pay_channel"]');
    if (select) select.value = channel;
    switchPayChannel(channel);

    const notifyDisplay = document.querySelector('input[name="moss_notify_url_display"]');
    if (notifyDisplay) {
        notifyDisplay.value = location.origin + '/api/pay/notify/lakala_moss';
    }
}

function switchPayChannel(channel) {
    document.getElementById('epay_config').style.display = (channel === 'epay') ? 'block' : 'none';
    document.getElementById('moss_config').style.display = (channel === 'lakala_moss') ? 'block' : 'none';
    document.getElementById('wxpay_config').style.display = (channel === 'wxpay') ? 'block' : 'none';

    if (channel === 'wxpay') {
    }
}

async function savePaymentConfig() {
    const formData = {};

    const channel = document.querySelector('select[name="pay_channel"]').value;
    formData.pay_channel = channel;

    let configEl;
    if (channel === 'epay') {
        configEl = document.getElementById('epay_config');
    } else if (channel === 'lakala_moss') {
        configEl = document.getElementById('moss_config');
    } else if (channel === 'wxpay') {
        configEl = document.getElementById('wxpay_config');
    }

    if (configEl) {
        const inputs = configEl.querySelectorAll('input, select, textarea');
        inputs.forEach(el => {
            if (el.name && !el.name.endsWith('_display') && !el.readOnly) {
                formData[el.name] = el.value.trim();
            }
        });
    }

    const result = await Admin.post('/config', formData);
    if (result && result.code === 0) {
        Admin.toast('配置保存成功', 'success');
    } else {
        Admin.toast(result?.msg || '保存失败', 'error');
    }
}
</script>
