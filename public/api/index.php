<?php
/**
 * MX-Mall - Clean API Router (DeckPHP-free)
 * Replaces the obfuscated commercial router
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// ===== Bootstrap =====
define('ROOT_PATH', dirname(__DIR__, 2));
define('API_PATH', __DIR__);
define('CONFIG_PATH', ROOT_PATH . '/config');

// Load core classes
require_once CONFIG_PATH . '/config.php';
require_once CONFIG_PATH . '/database.php';
require_once CONFIG_PATH . '/jwt.php';
require_once CONFIG_PATH . '/Crypto.php';

// Load middleware
require_once API_PATH . '/middleware/Cors.php';
require_once API_PATH . '/middleware/Auth.php';
require_once API_PATH . '/middleware/UserAuth.php';

// Load payment classes
require_once ROOT_PATH . '/payments/Epay.php';
require_once ROOT_PATH . '/payments/Lakala.php';
require_once ROOT_PATH . '/payments/LakalaMoss.php';
require_once ROOT_PATH . '/payments/NotifyHandler.php';
require_once ROOT_PATH . '/payments/PayFactory.php';

// Load controllers
require_once API_PATH . '/controllers/BaseController.php';
require_once API_PATH . '/controllers/ProductController.php';
require_once API_PATH . '/controllers/OrderController.php';
require_once API_PATH . '/controllers/PayController.php';
require_once API_PATH . '/controllers/UserController.php';
require_once API_PATH . '/controllers/AdminController.php';
require_once API_PATH . '/controllers/SiteController.php';
require_once API_PATH . '/controllers/UploadController.php';

// ===== CORS =====
Cors::handle();

// ===== Route Parsing =====
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$path = '/' . trim($uri, '/');

// Parse segments
$segments = array_values(array_filter(explode('/', $path)));

// Global route params (for controllers that use $routeParams)
global $routeParams;
$routeParams = [];

// ===== Public Routes (No Auth Required) =====

// GET /api/products
if ($method === 'GET' && $path === '/api/products') {
    (new ProductController())->index();
}

// GET /api/products/{id}
if ($method === 'GET' && preg_match('#^/api/products/(\d+)$#', $path, $m)) {
    $_GET['id'] = $m[1];
    (new ProductController())->detail();
}

// GET /api/site/config
if ($method === 'GET' && $path === '/api/site/config') {
    (new SiteController())->publicConfig();
}

// GET /api/categories
if ($method === 'GET' && $path === '/api/categories') {
    header('Content-Type: application/json; charset=utf-8');
    $db = DB::getInstance();
    $list = $db->getAll("SELECT id, name, icon FROM categories WHERE status = 1 ORDER BY sort_order ASC");
    echo json_encode(['code' => 0, 'msg' => 'success', 'data' => $list], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== User Auth Routes =====

// POST /api/user/register
if ($method === 'POST' && $path === '/api/user/register') {
    (new UserController())->register();
}

// POST /api/user/login
if ($method === 'POST' && $path === '/api/user/login') {
    (new UserController())->login();
}

// ===== User Routes (Auth Required) =====
if (strpos($path, '/api/user') === 0 || strpos($path, '/api/cart') === 0) {
    if (!UserAuth::handle()) {
        exit;
    }
}

// GET /api/user/profile
if ($method === 'GET' && $path === '/api/user/profile') {
    (new UserController())->getProfile();
}

// POST /api/user/profile
if ($method === 'POST' && $path === '/api/user/profile') {
    (new UserController())->updateProfile();
}

// POST /api/user/apply-merchant
if ($method === 'POST' && $path === '/api/user/apply-merchant') {
    (new UserController())->applyMerchant();
}

// GET /api/user/products
if ($method === 'GET' && $path === '/api/user/products') {
    (new UserController())->getMyProducts();
}

// POST /api/user/products
if ($method === 'POST' && $path === '/api/user/products') {
    (new UserController())->createProduct();
}

// POST /api/user/products/status
if ($method === 'POST' && $path === '/api/user/products/status') {
    (new UserController())->updateProductStatus();
}

// GET /api/user/orders
if ($method === 'GET' && $path === '/api/user/orders') {
    (new UserController())->getMyOrders();
}

// POST /api/user/withdraw
if ($method === 'POST' && $path === '/api/user/withdraw') {
    (new UserController())->requestWithdraw();
}

// GET /api/user/withdrawals
if ($method === 'GET' && $path === '/api/user/withdrawals') {
    (new UserController())->getWithdrawals();
}

// GET /api/user/balance-log
if ($method === 'GET' && $path === '/api/user/balance-log') {
    (new UserController())->getBalanceLog();
}

// GET /api/user/group-info
if ($method === 'GET' && $path === '/api/user/group-info') {
    (new UserController())->getGroupInfo();
}

// POST /api/user/purchase-group
if ($method === 'POST' && $path === '/api/user/purchase-group') {
    (new UserController())->purchaseGroup();
}

// POST /api/cart/checkout
if ($method === 'POST' && $path === '/api/cart/checkout') {
    (new OrderController())->create();
}

// ===== Order Routes =====

// GET /api/order (with trade_no param)
if ($method === 'GET' && $path === '/api/order') {
    (new OrderController())->detail();
}

// GET /api/order/{trade_no}
if ($method === 'GET' && preg_match('#^/api/order/([A-Za-z0-9]+)$#', $path, $m)) {
    $_GET['trade_no'] = $m[1];
    (new OrderController())->detail();
}

// POST /api/order/status
if ($method === 'POST' && $path === '/api/order/status') {
    (new OrderController())->updateStatus();
}

// ===== Payment Routes (Some need auth, some are callbacks) =====

// POST /api/pay/submit
if ($method === 'POST' && $path === '/api/pay/submit') {
    (new PayController())->submit();
}

// GET /api/pay/notify/epay (callback - no auth)
if ($path === '/api/pay/notify/epay') {
    (new PayController())->epayNotify();
}

// POST /api/pay/notify/lakala (callback - no auth)
if ($path === '/api/pay/notify/lakala') {
    (new PayController())->lakalaNotify();
}

// POST /api/pay/notify/lakala_moss (callback - no auth)
if ($path === '/api/pay/notify/lakala_moss') {
    (new PayController())->mossNotify();
}

// POST /api/pay/notify/wxpay (callback - no auth)
if ($path === '/api/pay/notify/wxpay') {
    (new PayController())->wxpayNotify();
}

// ===== Upload Route =====
if ($method === 'POST' && $path === '/api/upload') {
    (new UploadController())->upload();
}

// ===== Admin Routes (Auth Required) =====

// POST /api/admin/login (no auth required)
if ($method === 'POST' && $path === '/api/admin/login') {
    (new AdminController())->login();
}

// All other admin routes require authentication
if (strpos($path, '/api/admin') === 0 && $path !== '/api/admin/login') {
    if (!Auth::handle()) {
        exit;
    }
    // Set current_admin for controller use
    $_REQUEST['current_admin'] = (object) Auth::admin();
}

// GET /api/admin/dashboard
if ($method === 'GET' && $path === '/api/admin/dashboard') {
    (new AdminController())->dashboard();
}

// GET /api/admin/users
if ($method === 'GET' && $path === '/api/admin/users') {
    (new AdminController())->users();
}

// POST /api/admin/review-user
if ($method === 'POST' && $path === '/api/admin/review-user') {
    // Also handle PUT/DELETE /api/admin/users/{id}
    (new AdminController())->reviewUser();
}

// DELETE /api/admin/users/{id}
if ($method === 'DELETE' && preg_match('#^/api/admin/users/(\d+)$#', $path, $m)) {
    $routeParams['id'] = $m[1];
    (new AdminController())->deleteUser();
}

// GET /api/admin/products
if ($method === 'GET' && $path === '/api/admin/products') {
    (new AdminController())->products();
}

// POST /api/admin/products (save)
if ($method === 'POST' && $path === '/api/admin/products') {
    (new AdminController())->saveProduct();
}

// DELETE /api/admin/products/{id}
if ($method === 'DELETE' && preg_match('#^/api/admin/products/(\d+)$#', $path, $m)) {
    $_REQUEST['id'] = $m[1];
    (new AdminController())->deleteProduct();
}

// POST /api/admin/products/batch-delete
if ($method === 'POST' && $path === '/api/admin/products/batch-delete') {
    (new AdminController())->batchDeleteProducts();
}

// POST /api/admin/products/batch-status
if ($method === 'POST' && $path === '/api/admin/products/batch-status') {
    (new AdminController())->batchUpdateProductStatus();
}

// GET /api/admin/orders
if ($method === 'GET' && $path === '/api/admin/orders') {
    (new AdminController())->orders();
}

// GET /api/admin/templates
if ($method === 'GET' && $path === '/api/admin/templates') {
    (new AdminController())->templates();
}

// POST /api/admin/templates (save)
if ($method === 'POST' && $path === '/api/admin/templates') {
    (new AdminController())->saveTemplate();
}

// GET /api/admin/config
if ($method === 'GET' && $path === '/api/admin/config') {
    (new AdminController())->getConfig();
}

// POST /api/admin/config (save)
if ($method === 'POST' && $path === '/api/admin/config') {
    (new AdminController())->saveConfig();
}

// GET /api/admin/payment-logs
if ($method === 'GET' && $path === '/api/admin/payment-logs') {
    (new AdminController())->paymentLogs();
}

// GET /api/admin/withdrawals
if ($method === 'GET' && $path === '/api/admin/withdrawals') {
    (new AdminController())->withdrawList();
}

// POST /api/admin/review-withdraw
if ($method === 'POST' && $path === '/api/admin/review-withdraw') {
    (new AdminController())->reviewWithdraw();
}

// POST /api/admin/complete-withdraw
if ($method === 'POST' && $path === '/api/admin/complete-withdraw') {
    (new AdminController())->completeWithdraw();
}

// POST /api/admin/review-merchant
if ($method === 'POST' && $path === '/api/admin/review-merchant') {
    (new AdminController())->reviewMerchant();
}

// GET /api/admin/categories
if ($method === 'GET' && $path === '/api/admin/categories') {
    (new AdminController())->categories();
}

// POST /api/admin/categories (save)
if ($method === 'POST' && $path === '/api/admin/categories') {
    (new AdminController())->saveCategory();
}

// POST /api/admin/delete-category
if ($method === 'POST' && $path === '/api/admin/delete-category') {
    (new AdminController())->deleteCategory();
}

// GET /api/admin/user-groups
if ($method === 'GET' && $path === '/api/admin/user-groups') {
    (new AdminController())->userGroups();
}

// POST /api/admin/user-groups (save)
if ($method === 'POST' && $path === '/api/admin/user-groups') {
    (new AdminController())->saveUserGroup();
}

// POST /api/admin/delete-user-group
if ($method === 'POST' && $path === '/api/admin/delete-user-group') {
    (new AdminController())->deleteUserGroup();
}

// POST /api/admin/adjust-balance
if ($method === 'POST' && $path === '/api/admin/adjust-balance') {
    (new AdminController())->adjustBalance();
}

// POST /api/admin/orders/refund
if ($method === 'POST' && $path === '/api/admin/orders/refund') {
    (new AdminController())->refundOrder();
}

// POST /api/admin/clean-orders or POST /api/orders/clean
if (($method === 'POST' && $path === '/api/admin/clean-orders') || ($method === 'POST' && $path === '/api/orders/clean')) {
    (new AdminController())->cleanExpiredOrders();
}

// POST /api/admin/password
if ($method === 'POST' && $path === '/api/admin/password') {
    (new AdminController())->changePassword();
}

// GET /api/admin/orders/export
if ($method === 'GET' && $path === '/api/admin/orders/export') {
    (new AdminController())->exportOrders();
}

// GET /api/admin/products/export
if ($method === 'GET' && $path === '/api/admin/products/export') {
    (new AdminController())->exportProducts();
}

// GET /api/admin/users/export
if ($method === 'GET' && $path === '/api/admin/users/export') {
    (new AdminController())->exportUsers();
}

// GET /api/admin/withdrawals/export
if ($method === 'GET' && $path === '/api/admin/withdrawals/export') {
    (new AdminController())->exportWithdrawals();
}

// ===== 404 Handler =====
http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'code' => 404,
    'msg' => '接口不存在: ' . $path,
    'data' => null,
], JSON_UNESCAPED_UNICODE);
