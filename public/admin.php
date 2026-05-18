<?php
/**
 * MX-Mall - 后台管理入口
 *
 * 根据请求参数加载对应的admin模板
 * 通过 /admin 或 /admin.php?page=xxx 访问
 */

// 后台模板目录
$tplDir = __DIR__ . '/../templates/admin/';

// 获取请求的页面
$page = $_GET['page'] ?? 'index';

// 允许访问的页面白名单
$allowed = [
    'login',      // 登录页
    'index',      // 后台主页（框架）
    'dashboard',  // 仪表盘模块
    'products',   // 商品管理模块
    'categories', // 分类管理模块
    'orders',     // 订单管理模块
    'users',      // 用户管理模块
    'user-groups', // 用户分组模块
    'payment',    // 支付配置模块
    'settings',   // 系统设置模块
    'withdrawals',  // 提现管理模块
];

// 安装入口（独立页面）
if ($page === 'install') {
    require __DIR__ . '/../install/install.php';
    exit;
}

// 安装锁检测 - 如果系统未安装，跳转到安装页
$lockFile = __DIR__ . '/../install/install.lock';
if (!file_exists($lockFile) && $page !== 'login') {
    header('Location: install.php');
    exit;
}

// 安装完成后禁止访问安装页
if (file_exists($lockFile) && $page === 'install') {
    http_response_code(403);
    echo '系统已安装，禁止重复安装。如需重新安装，请删除 install/install.lock 文件。';
    exit;
}

// 安装完成后禁止访问登录页之外的页面（未登录时）
// 注意：login 和 index 页面自己处理认证逻辑

// 非 login/install 页面：如果是AJAX则返回模块内容，如果是浏览器直接访问则加载框架
if ($page !== 'login' && $page !== 'install' && $page !== 'index') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
           || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    
    if (!$isAjax) {
        // 浏览器直接访问：加载 index 框架，JS会根据URL参数加载对应模块
        $_GET['page'] = 'index';
        $page = 'index';
    }
    // AJAX请求：正常返回模块HTML片段
}

// 安全校验：只允许白名单中的页面
if (!in_array($page, $allowed)) {
    $page = 'login';
}

// 构建模板文件路径
$tplPath = $tplDir . $page . '.php';

if (file_exists($tplPath)) {
    require $tplPath;
} else {
    http_response_code(404);
    echo 'Page not found: ' . htmlspecialchars($page);
}
