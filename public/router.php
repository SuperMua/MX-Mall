<?php
/**
 * PHP Built-in Server Router
 * Mimics the .htaccess rewrite rules for local development
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static files directly
if (file_exists(__DIR__ . $uri) && is_file(__DIR__ . $uri)) {
    // Skip PHP files — they need to be processed
    if (pathinfo($uri, PATHINFO_EXTENSION) !== 'php') {
        return false;
    }
}

// API routes → api/index.php
if (preg_match('#^/api/#', $uri)) {
    $_SERVER['SCRIPT_NAME'] = '/api/index.php';
    require __DIR__ . '/api/index.php';
    return;
}

// Admin routes → admin.php
if ($uri === '/admin' || preg_match('#^/admin/(.*)$#', $uri, $m)) {
    if (isset($m[1])) $_GET['page'] = $m[1];
    require __DIR__ . '/admin.php';
    return;
}

// Install route
if ($uri === '/install') {
    require __DIR__ . '/install.php';
    return;
}

// Frontend page routes: [regex, file, param_map]
$pageRoutes = [
    ['#^/$#',                            'index.php',             []],
    ['#^/product/(\d+)$#',            'product.php',           ['id' => 1]],
    ['#^/cart$#',                      'cart.php',              []],
    ['#^/checkout$#',                  'checkout.php',          []],
    ['#^/cashier$#',                   'cashier.php',           []],
    ['#^/pay-result$#',                'pay-result.php',        []],
    ['#^/user$#',                      'user.php',              []],
    ['#^/user-products$#',             'user-products.php',     []],
    ['#^/user-orders$#',               'user-orders.php',       []],
    ['#^/user-withdrawals$#',          'user-withdrawals.php',  []],
    ['#^/user-groups$#',               'user-groups.php',       []],
];

foreach ($pageRoutes as [$pattern, $file, $params]) {
    if (preg_match($pattern, $uri, $m)) {
        foreach ($params as $key => $idx) {
            $_GET[$key] = $m[$idx];
        }
        $filePath = __DIR__ . '/' . $file;
        if (file_exists($filePath)) {
            require $filePath;
            return;
        }
    }
}

// Fallback: serve as static file or 404
if (file_exists(__DIR__ . $uri)) {
    return false;
}

http_response_code(404);
echo '404 Not Found';
