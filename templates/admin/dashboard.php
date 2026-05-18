<!-- Dashboard Module -->
<div class="stat-grid" id="statGrid">
    <div class="stat-card">
        <div class="stat-icon purple"><i class="bi bi-receipt"></i></div>
        <div class="stat-info">
            <h3 id="todayOrders">-</h3>
            <p>今日订单</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-currency-yen"></i></div>
        <div class="stat-info">
            <h3 id="todayAmount">-</h3>
            <p>今日金额</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-box-seam"></i></div>
        <div class="stat-info">
            <h3 id="totalProducts">-</h3>
            <p>总商品数</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-people"></i></div>
        <div class="stat-info">
            <h3 id="totalUsers">-</h3>
            <p>总用户数</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-cash-stack"></i></div>
        <div class="stat-info">
            <h3 id="pendingWithdrawals">-</h3>
            <p>待审核提现</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="bi bi-wallet2"></i></div>
        <div class="stat-info">
            <h3 id="totalBalance">-</h3>
            <p>用户总余额</p>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <!-- Recent Orders -->
    <div class="card" style="grid-column:span 1;">
        <div class="card-header">
            <span class="card-title">最近订单</span>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>订单号</th>
                        <th>商品</th>
                        <th>金额</th>
                        <th>状态</th>
                        <th>时间</th>
                    </tr>
                </thead>
                <tbody id="recentOrdersBody">
                    <tr><td colspan="5" class="text-center text-muted">加载中...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment Distribution -->
    <div class="card" style="grid-column:span 1;">
        <div class="card-header">
            <span class="card-title">支付模板分布</span>
        </div>
        <div id="paymentDistribution" style="padding:8px 0;">
            <div class="text-center text-muted" style="padding:40px 0;">加载中...</div>
        </div>
    </div>
</div>

<script>
async function init_dashboard() {
    const data = await Admin.get('/dashboard');
    if (!data || data.code !== 0) {
        Admin.toast('加载仪表盘数据失败', 'error');
        return;
    }

    const stats = data.data;

    // Stat cards
    document.getElementById('todayOrders').textContent = stats.today_orders || 0;
    document.getElementById('todayAmount').textContent = Admin.formatMoney(stats.today_income || 0);
    document.getElementById('totalProducts').textContent = stats.total_products || 0;
    document.getElementById('totalUsers').textContent = stats.total_users || 0;
    document.getElementById('pendingWithdrawals').textContent = stats.pending_withdrawals || 0;
    document.getElementById('totalBalance').textContent = Admin.formatMoney(stats.total_balance || 0);

    // Recent orders
    const ordersBody = document.getElementById('recentOrdersBody');
    const orders = stats.recent_orders || [];

    // 订单状态映射
    const statusMap = {0: '待支付', 1: '已支付', 2: '已退款', 3: '已过期'};
    const statusBadgeMap = {0: 'warning', 1: 'success', 2: 'danger', 3: 'secondary'};

    if (orders.length === 0) {
        ordersBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">暂无订单</td></tr>';
    } else {
        ordersBody.innerHTML = orders.map(order => `
            <tr>
                <td style="font-family:monospace;font-size:12px;">${order.out_trade_no || '-'}</td>
                <td>${order.product_name || '-'}</td>
                <td style="font-weight:600;">${Admin.formatMoney(order.money)}</td>
                <td><span class="badge badge-${statusBadgeMap[order.status] || 'secondary'}">${statusMap[order.status] || '未知'}</span></td>
                <td style="font-size:12px;color:var(--text-secondary);">${Admin.formatDate(order.created_at)}</td>
            </tr>
        `).join('');
    }

    // Payment distribution (by template)
    const distEl = document.getElementById('paymentDistribution');
    const templateStats = stats.template_stats || [];

    if (!templateStats || templateStats.length === 0) {
        distEl.innerHTML = '<div class="text-center text-muted" style="padding:40px 0;">暂无数据</div>';
    } else {
        const tNames = {
            'meituan': '美团外卖', 'jd': '京东', 'ctrip-flight': '携程机票',
            'didi': '滴滴出行', 'pdd': '拼多多', 'taobao': '淘宝',
            'ctrip-hotel': '携程酒店', 'fliggy': '飞猪', 'dewu': '得物',
            'maoyan': '猫眼电影', 'taobao2': '淘宝好物', 'douyin': '抖音',
            'didi2': '滴滴Pro', 'xianyu': '闲鱼'
        };
        const total = templateStats.reduce((s, v) => s + parseInt(v.count), 0);
        const colors = ['purple', 'green', 'blue', 'orange', 'teal', 'red'];
        let html = '';

        templateStats.forEach((item, i) => {
            const name = tNames[item.template_id] || item.template_id || '未知';
            const count = parseInt(item.count);
            const pct = total > 0 ? Math.round((count / total) * 100) : 0;
            html += `
                <div class="progress-bar-wrapper">
                    <div class="progress-bar-header">
                        <span>${name}</span>
                        <span>${count} 笔 (${pct}%)</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-bar-fill ${colors[i % colors.length]}" style="width:${pct}%"></div>
                    </div>
                </div>
            `;
        });

        distEl.innerHTML = html;
    }
}
</script>
