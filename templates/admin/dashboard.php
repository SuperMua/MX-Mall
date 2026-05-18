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

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
    <!-- Weekly Trend Chart -->
    <div class="card" style="grid-column:span 1;">
        <div class="card-header">
            <span class="card-title">近7天订单趋势</span>
        </div>
        <div style="padding:16px;position:relative;height:280px;">
            <canvas id="weekTrendChart"></canvas>
            <div id="weekTrendEmpty" class="text-center text-muted" style="display:none;padding:60px 0;">暂无数据</div>
        </div>
    </div>

    <!-- Payment Distribution Chart -->
    <div class="card" style="grid-column:span 1;">
        <div class="card-header">
            <span class="card-title">支付模板分布</span>
        </div>
        <div style="padding:16px;position:relative;height:280px;display:flex;align-items:center;justify-content:center;">
            <canvas id="templateDistChart" style="max-width:260px;max-height:260px;"></canvas>
            <div id="templateDistEmpty" class="text-center text-muted" style="display:none;">暂无数据</div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="card" style="margin-bottom:20px;">
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

<script>
// Dynamic Chart.js loader
function loadChartJS() {
    if (window.Chart) return Promise.resolve();
    return new Promise(function(resolve) {
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
        s.onload = function() { resolve(); };
        document.head.appendChild(s);
    });
}

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

    // Load Chart.js and render charts
    await loadChartJS();
    renderWeekTrendChart(stats.week_trend || []);
    renderTemplateDistChart(stats.template_stats || []);
}

function renderWeekTrendChart(trendData) {
    const canvas = document.getElementById('weekTrendChart');
    const empty = document.getElementById('weekTrendEmpty');
    if (!canvas || !empty) return;

    if (!trendData || trendData.length === 0) {
        canvas.style.display = 'none';
        empty.style.display = 'block';
        return;
    }

    canvas.style.display = 'block';
    empty.style.display = 'none';

    // Destroy previous chart instance
    if (canvas._chart) canvas._chart.destroy();

    const labels = trendData.map(function(d) { return d.date; });
    const orders = trendData.map(function(d) { return parseInt(d.order_count) || 0; });
    const amounts = trendData.map(function(d) { return parseFloat(d.amount) || 0; });

    canvas._chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '订单数',
                    data: orders,
                    borderColor: '#6C5CE7',
                    backgroundColor: 'rgba(108,92,231,0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#6C5CE7',
                    yAxisID: 'y'
                },
                {
                    label: '金额 (¥)',
                    data: amounts,
                    borderColor: '#00C897',
                    backgroundColor: 'rgba(0,200,151,0.06)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#00C897',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 24,
                        font: { size: 12 }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 }, color: '#9CA0B5' }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: '订单数', font: { size: 11 }, color: '#9CA0B5' },
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: { font: { size: 11 }, color: '#9CA0B5', stepSize: 1 }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: '金额', font: { size: 11 }, color: '#9CA0B5' },
                    grid: { drawOnChartArea: false },
                    ticks: {
                        font: { size: 11 },
                        color: '#9CA0B5',
                        callback: function(v) { return '¥' + v; }
                    }
                }
            }
        }
    });
}

function renderTemplateDistChart(templateStats) {
    const canvas = document.getElementById('templateDistChart');
    const empty = document.getElementById('templateDistEmpty');
    if (!canvas || !empty) return;

    if (!templateStats || templateStats.length === 0) {
        canvas.style.display = 'none';
        empty.style.display = 'block';
        return;
    }

    canvas.style.display = 'block';
    empty.style.display = 'none';

    if (canvas._chart) canvas._chart.destroy();

    const tNames = {
        'meituan': '美团外卖', 'jd': '京东', 'ctrip-flight': '携程机票',
        'didi': '滴滴出行', 'pdd': '拼多多', 'taobao': '淘宝',
        'ctrip-hotel': '携程酒店', 'fliggy': '飞猪', 'dewu': '得物',
        'maoyan': '猫眼电影', 'taobao2': '淘宝好物', 'douyin': '抖音',
        'didi2': '滴滴Pro', 'xianyu': '闲鱼'
    };
    const chartColors = ['#6C5CE7', '#00C897', '#45AAF2', '#F7B731', '#FC5C65', '#26D0CE',
        '#A29BFE', '#55E6C1', '#74B9FF', '#FDCB6E', '#FF7675', '#81ECEC'];

    const labels = templateStats.map(function(item) {
        return tNames[item.template_id] || item.template_id || '未知';
    });
    const counts = templateStats.map(function(item) { return parseInt(item.count); });
    const colors = chartColors.slice(0, templateStats.length);

    canvas._chart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: counts,
                backgroundColor: colors,
                borderColor: '#fff',
                borderWidth: 2,
                hoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 16,
                        font: { size: 11 },
                        generateLabels: function(chart) {
                            var ds = chart.data.datasets[0];
                            var total = ds.data.reduce(function(s, v) { return s + v; }, 0);
                            return chart.data.labels.map(function(label, i) {
                                var pct = total > 0 ? Math.round((ds.data[i] / total) * 100) : 0;
                                return {
                                    text: label + '  ' + ds.data[i] + ' (' + pct + '%)',
                                    fillStyle: ds.backgroundColor[i],
                                    strokeStyle: ds.backgroundColor[i],
                                    lineWidth: 0,
                                    hidden: false,
                                    index: i
                                };
                            });
                        }
                    }
                }
            }
        }
    });
}
</script>
