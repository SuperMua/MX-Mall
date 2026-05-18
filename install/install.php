<?php
/**
 * MX-Mall - Web安装脚本
 *
 * 功能：环境检测、数据库配置、建表导入、管理员设置、配置生成
 * 安装完成后自动创建lock文件防止重复安装
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// ===== 安装锁检测 =====
$lockFile = __DIR__ . '/install.lock';
if (file_exists($lockFile)) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>安装完成 - MX-Mall</title>';
    echo '<style>*{margin:0;padding:0;box-sizing:border-box}body{background:#0a0a0f;color:#eaeaea;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","PingFang SC","Microsoft YaHei",sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;position:relative;overflow:hidden}';
    echo 'body::before{content:"";position:fixed;top:-200px;left:50%;transform:translateX(-50%);width:600px;height:600px;background:radial-gradient(circle,rgba(108,92,231,0.08) 0%,transparent 70%);pointer-events:none}';
    echo '.box{background:#13131d;border:1px solid #252538;border-radius:12px;padding:40px;text-align:center;max-width:420px;width:90%;box-shadow:0 4px 24px rgba(0,0,0,0.2)}';
    echo '.icon{width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#6c5ce7,#a29bfe);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 8px 32px rgba(108,92,231,0.25)}';
    echo '.icon svg{width:28px;height:28px;fill:#fff}';
    echo '.title{font-size:22px;font-weight:700;color:#fff;margin-bottom:8px}.desc{color:#8888a0;font-size:14px;margin-bottom:24px;line-height:1.6}';
    echo 'a{display:inline-block;background:linear-gradient(135deg,#6c5ce7,#5a4bd1);color:#fff;padding:11px 28px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;box-shadow:0 4px 16px rgba(108,92,231,0.25)}';
    echo 'a:hover{box-shadow:0 6px 24px rgba(108,92,231,0.35);transform:translateY(-1px)}.warn{color:#f39c12;font-size:13px;margin-top:16px;padding:12px;background:rgba(243,156,18,0.08);border:1px solid rgba(243,156,18,0.15);border-radius:8px;line-height:1.6}</style></head><body>';
    echo '<div class="box"><div class="icon"><svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM12 17c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zM9 8V6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9z"/></svg></div><div class="title">系统已安装</div>';
    echo '<div class="desc">MX-Mall已经安装完成，如需重新安装，请先删除 install/install.lock 文件。</div>';
    echo '<a href="/admin.php">进入后台管理</a><div class="warn">警告：重复安装将清空现有数据！</div></div></body></html>';
    exit;
}

// ===== 步骤控制 =====
$step = isset($_POST['step']) ? (int)$_POST['step'] : 1;
if ($step < 1) $step = 1;
if ($step > 5) $step = 5;

// ===== 处理POST请求 =====
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 2:
            // 测试数据库连接
            $dbHost = trim($_POST['db_host'] ?? 'localhost');
            $dbPort = (int)($_POST['db_port'] ?? 3306);
            $dbName = trim($_POST['db_name'] ?? '');
            $dbUser = trim($_POST['db_user'] ?? '');
            $dbPass = trim($_POST['db_pass'] ?? '');

            // 如果没有提交数据库字段，说明是从步骤1跳过来的，直接显示步骤2表单
            if (empty($dbName) && empty($dbUser) && !isset($_POST['db_pass_submitted'])) {
                break;
            }

            if (empty($dbName) || empty($dbUser)) {
                $error = '数据库名称和用户名不能为空';
                $step = 2;
            } else {
                try {
                    $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
                    $pdo = new PDO($dsn, $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 5,
                    ]);
                    // 验证连接是否真实成功（执行简单查询）
                    $stmt = $pdo->query("SELECT 1");
                    if (!$stmt || !$stmt->fetch()) {
                        throw new PDOException('数据库连接验证失败');
                    }
                    $pdo->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->query("USE `{$dbName}`");
                    $success = '数据库连接成功';
                    $step = 3;
                } catch (PDOException $e) {
                    $error = '数据库连接失败: ' . $e->getMessage();
                    $step = 2;
                }
            }
            break;

        case 3:
            // 执行SQL建表
            $dbHost = trim($_POST['db_host'] ?? 'localhost');
            $dbPort = (int)($_POST['db_port'] ?? 3306);
            $dbName = trim($_POST['db_name'] ?? '');
            $dbUser = trim($_POST['db_user'] ?? '');
            $dbPass = trim($_POST['db_pass'] ?? '');

            try {
                $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
                $pdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                // 验证连接是否真实成功
                $stmt = $pdo->query("SELECT 1");
                if (!$stmt || !$stmt->fetch()) {
                    throw new PDOException('数据库连接验证失败');
                }

                $sqlFile = __DIR__ . '/install.sql';
                if (!file_exists($sqlFile)) {
                    $error = 'install.sql 文件不存在';
                    $step = 2;
                } else {
                    $sql = file_get_contents($sqlFile);
                    // 移除 CREATE DATABASE 和 USE 语句（已在步骤2创建）
                    $sql = preg_replace('/CREATE\s+DATABASE\s+IF\s+NOT\s+EXISTS\s+`[^`]+`[^;]*;/i', '', $sql);
                    $sql = preg_replace('/USE\s+`[^`]+`;/i', '', $sql);
                    // 按分号分割执行
                    $pdo->exec($sql);
                    $success = '数据库表创建成功，默认数据已导入';
                    $step = 4;
                }
            } catch (PDOException $e) {
                $error = 'SQL执行失败: ' . $e->getMessage();
                $step = 2;
            }
            break;

        case 4:
            // 合并步骤：设置管理员 + 站点配置 + 写入config.php
            $adminUser = trim($_POST['admin_user'] ?? '');
            $adminPass = trim($_POST['admin_pass'] ?? '');
            $adminPass2 = trim($_POST['admin_pass2'] ?? '');
            $siteName = trim($_POST['site_name'] ?? 'MX-Mall');
            $dbHost = trim($_POST['db_host'] ?? 'localhost');
            $dbPort = (int)($_POST['db_port'] ?? 3306);
            $dbName = trim($_POST['db_name'] ?? '');
            $dbUser = trim($_POST['db_user'] ?? '');
            $dbPass = trim($_POST['db_pass'] ?? '');

            // 验证管理员信息
            if (empty($adminUser) || empty($adminPass)) {
                $error = '管理员用户名和密码不能为空';
                $step = 4;
            } elseif (strlen($adminPass) < 6) {
                $error = '密码长度不能少于6位';
                $step = 4;
            } elseif ($adminPass !== $adminPass2) {
                $error = '两次输入的密码不一致';
                $step = 4;
            } elseif (empty($siteName)) {
                $error = '站点名称不能为空';
                $step = 4;
            } else {
                try {
                    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
                    $pdo = new PDO($dsn, $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    ]);
                    // 验证连接是否真实成功
                    $stmt = $pdo->query("SELECT 1");
                    if (!$stmt || !$stmt->fetch()) {
                        throw new PDOException('数据库连接验证失败');
                    }

                    // 设置管理员
                    $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE `admins` SET `username` = ?, `password` = ? WHERE `id` = 1");
                    $stmt->execute([$adminUser, $hashedPass]);

                    // 写入config.php
                    $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

                    $configContent = <<<'PHP'
<?php
/**
 * MX-Mall - 全局配置文件
 *
 * 包含数据库、JWT、站点等核心配置项
 * 由安装程序自动生成
 */

return [
    // ===== 数据库配置 =====
    'database' => [
        'host'     => '{db_host}',
        'port'     => {db_port},
        'dbname'   => '{db_name}',
        'username' => '{db_user}',
        'password' => '{db_pass}',
        'charset'  => 'utf8mb4',
    ],

    // ===== JWT配置 =====
    'jwt' => [
        'secret'     => '{jwt_secret}',
        'issuer'     => 'mx-mall',
        'expire'     => 86400,
    ],

    // ===== 站点配置 =====
    'site' => [
        'name'        => '{site_name}',
        'url'         => '{site_url}',
        'description' => '智能收银台商城系统',
    ],

    // ===== 支付配置 =====
    'payment' => [
        'epay' => [
            'url'        => '',
            'pid'        => '',
            'secret'     => '',
            'notify_url' => '',
            'return_url' => '',
        ],
        'lakala' => [
            'app_id'       => '',
            'private_key'  => '',
            'public_key'   => '',
            'notify_url'   => '',
            'sandbox'      => false,
        ],
    ],

    // ===== 上传配置 =====
    'upload' => [
        'max_size'  => 5 * 1024 * 1024,
        'allowed'   => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'path'      => '/uploads/',
    ],

    // ===== 分页配置 =====
    'page' => [
        'default_size' => 20,
        'max_size'     => 100,
    ],
];
PHP;

                    $jwtSecret = bin2hex(random_bytes(32));
                    $configContent = str_replace('{db_host}', addslashes($dbHost), $configContent);
                    $configContent = str_replace('{db_port}', $dbPort, $configContent);
                    $configContent = str_replace('{db_name}', addslashes($dbName), $configContent);
                    $configContent = str_replace('{db_user}', addslashes($dbUser), $configContent);
                    $configContent = str_replace('{db_pass}', addslashes($dbPass), $configContent);
                    $configContent = str_replace('{jwt_secret}', $jwtSecret, $configContent);
                    $configContent = str_replace('{site_name}', addslashes($siteName), $configContent);
                    $configContent = str_replace('{site_url}', addslashes($siteUrl), $configContent);

                    $configPath = dirname(__DIR__) . '/config/config.php';
                    if (file_put_contents($configPath, $configContent) === false) {
                        $error = '配置文件写入失败，请检查 config/ 目录是否有写入权限';
                        $step = 4;
                    } else {
                        $success = '安装配置完成';
                        // 直接创建lock文件
                        $lockContent = date('Y-m-d H:i:s') . "\nMX-Mall安装完成\n";
                        @file_put_contents($lockFile, $lockContent);
                        $step = 5;
                    }
                } catch (PDOException $e) {
                    $error = '管理员设置失败: ' . $e->getMessage();
                    $step = 4;
                }
            }
            break;

        case 5:
            // 创建lock文件
            $lockContent = date('Y-m-d H:i:s') . "\nMX-Mall安装完成\n";
            if (file_put_contents($lockFile, $lockContent) === false) {
                $error = 'lock文件创建失败，请手动创建 install/install.lock';
                $step = 4;
            } else {
                $success = '安装完成';
            }
            break;
    }
}

// ===== 环境检测函数 =====
function checkEnv()
{
    $results = [];
    $phpVersion = PHP_VERSION;
    $results[] = [
        'name'  => 'PHP版本',
        'value' => $phpVersion,
        'pass'  => version_compare($phpVersion, '7.4.0', '>='),
        'required' => '>= 7.4.0',
    ];

    $extensions = [
        'PDO'           => ['pdo', 'PDO扩展'],
        'pdo_mysql'     => ['pdo_mysql', 'PDO MySQL驱动'],
        'openssl'       => ['openssl', 'OpenSSL扩展'],
        'curl'          => ['curl', 'cURL扩展'],
        'json'          => ['json', 'JSON扩展'],
        'mbstring'      => ['mbstring', 'MBString扩展'],
        'simplexml'     => ['simplexml', 'SimpleXML扩展'],
    ];

    foreach ($extensions as $ext => $info) {
        $loaded = extension_loaded($ext);
        $results[] = [
            'name'     => $info[1],
            'value'    => $loaded ? '已安装' : '未安装',
            'pass'     => $loaded,
            'required' => '必须',
        ];
    }

    // 检查目录写入权限
    $dirs = [
        'config/' => dirname(__DIR__) . '/config',
        'uploads/' => dirname(__DIR__) . '/public/uploads',
    ];
    foreach ($dirs as $label => $dir) {
        $writable = is_writable($dir) || (!file_exists($dir) && is_writable(dirname($dir)));
        $results[] = [
            'name'     => $label . ' 写入权限',
            'value'    => $writable ? '可写' : '不可写',
            'pass'     => $writable,
            'required' => '必须',
        ];
    }

    return $results;
}

// ===== 获取POST值 =====
function post($key, $default = '')
{
    return isset($_POST[$key]) ? htmlspecialchars($_POST[$key], ENT_QUOTES, 'UTF-8') : $default;
}

$envResults = checkEnv();
$allPass = array_reduce($envResults, function ($carry, $item) {
    return $carry && $item['pass'];
}, true);

// ===== 确定当前显示步骤 =====
$displayStep = $step;
if ($error) {
    // 出错时回退到上一步
    $displayStep = $step;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MX-Mall - 安装向导</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #6c5ce7;
            --primary-light: #a29bfe;
            --primary-dark: #5a4bd1;
            --bg-deep: #0a0a0f;
            --bg-card: #13131d;
            --bg-input: #1a1a2e;
            --border: #252538;
            --border-focus: #6c5ce7;
            --text-primary: #eaeaea;
            --text-secondary: #8888a0;
            --text-muted: #555568;
            --success: #00b894;
            --danger: #ff6b6b;
            --warning: #f39c12;
            --radius: 12px;
            --font: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif;
        }

        body {
            background: var(--bg-deep);
            color: var(--text-primary);
            font-family: var(--font);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 48px 16px;
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient background glow */
        body::before {
            content: '';
            position: fixed;
            top: -200px;
            left: 50%;
            transform: translateX(-50%);
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(108,92,231,0.08) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .installer {
            width: 100%;
            max-width: 620px;
            position: relative;
            z-index: 1;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .header .logo-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            margin-bottom: 16px;
            box-shadow: 0 8px 32px rgba(108,92,231,0.25);
        }
        .header .logo-mark svg {
            width: 28px;
            height: 28px;
            fill: #fff;
        }
        .header h1 {
            font-size: 26px;
            font-weight: 800;
            color: #fff;
            letter-spacing: 3px;
            margin-bottom: 6px;
        }
        .header h1 span { color: var(--primary-light); }
        .header p { color: var(--text-muted); font-size: 13px; letter-spacing: 0.5px; }

        /* Steps */
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 36px;
            position: relative;
            padding: 0 4px;
        }
        .steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 36px;
            right: 36px;
            height: 2px;
            background: var(--border);
            border-radius: 1px;
        }
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        .step-num {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--bg-card);
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 8px;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .step-item.active .step-num {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            box-shadow: 0 0 0 4px rgba(108,92,231,0.15), 0 0 20px rgba(108,92,231,0.3);
        }
        .step-item.done .step-num {
            background: var(--success);
            border-color: var(--success);
            color: #fff;
            box-shadow: 0 0 0 4px rgba(0,184,148,0.12);
        }
        .step-label {
            font-size: 11px;
            color: var(--text-muted);
            white-space: nowrap;
            transition: color 0.3s;
        }
        .step-item.active .step-label { color: var(--primary-light); }
        .step-item.done .step-label { color: var(--success); }

        /* Card */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
            margin-bottom: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.2);
        }
        .card-title {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-title .title-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(108,92,231,0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        /* Env table */
        .env-table {
            width: 100%;
            border-collapse: collapse;
        }
        .env-table th, .env-table td {
            padding: 11px 12px;
            text-align: left;
            border-bottom: 1px solid rgba(37,37,56,0.6);
            font-size: 13px;
        }
        .env-table th { color: var(--text-muted); font-weight: 500; }
        .env-table td { color: var(--text-secondary); }
        .env-table tr:last-child td { border-bottom: none; }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .badge-ok { background: rgba(0,184,148,0.12); color: var(--success); }
        .badge-fail { background: rgba(255,107,107,0.12); color: var(--danger); }

        /* Form */
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 6px;
            font-weight: 500;
        }
        .form-group label .required {
            color: var(--danger);
            margin-left: 2px;
        }
        .form-row {
            display: flex;
            gap: 12px;
        }
        .form-row .form-group { flex: 1; }
        input[type="text"], input[type="password"], input[type="number"], input[type="url"] {
            width: 100%;
            padding: 11px 14px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 14px;
            font-family: var(--font);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108,92,231,0.1);
        }
        input::placeholder { color: var(--text-muted); }

        /* Buttons */
        .btn-row {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            font-family: var(--font);
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            box-shadow: 0 4px 16px rgba(108,92,231,0.25);
        }
        .btn-primary:hover {
            box-shadow: 0 6px 24px rgba(108,92,231,0.35);
            transform: translateY(-1px);
        }
        .btn-primary:active { transform: translateY(0); }
        .btn-primary:disabled {
            background: #2a2a3e;
            color: var(--text-muted);
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }
        .btn-secondary {
            background: var(--bg-input);
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover { background: #222236; }

        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .alert-error {
            background: rgba(255,107,107,0.08);
            border: 1px solid rgba(255,107,107,0.15);
            color: var(--danger);
        }
        .alert-success {
            background: rgba(0,184,148,0.08);
            border: 1px solid rgba(0,184,148,0.15);
            color: var(--success);
        }
        .alert-info {
            background: rgba(108,92,231,0.08);
            border: 1px solid rgba(108,92,231,0.15);
            color: var(--primary-light);
        }

        /* Success page */
        .success-box {
            text-align: center;
            padding: 24px 0;
        }
        .success-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--success), #00d2a0);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 32px rgba(0,184,148,0.25);
            animation: successPulse 2s ease-in-out infinite;
        }
        .success-icon svg {
            width: 36px;
            height: 36px;
            stroke: #fff;
            fill: none;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        @keyframes successPulse {
            0%, 100% { box-shadow: 0 8px 32px rgba(0,184,148,0.25); }
            50% { box-shadow: 0 8px 40px rgba(0,184,148,0.4); }
        }
        .success-title {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
        }
        .success-desc {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 28px;
            line-height: 1.7;
        }
        .success-links {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .success-links a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .link-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            box-shadow: 0 4px 16px rgba(108,92,231,0.25);
        }
        .link-primary:hover {
            box-shadow: 0 6px 24px rgba(108,92,231,0.35);
            transform: translateY(-1px);
        }
        .link-secondary {
            background: var(--bg-input);
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        .link-secondary:hover { background: #222236; }
        .success-warn {
            margin-top: 28px;
            padding: 12px 16px;
            background: rgba(243,156,18,0.08);
            border: 1px solid rgba(243,156,18,0.15);
            border-radius: 8px;
            color: var(--warning);
            font-size: 12px;
            line-height: 1.6;
        }

        /* Admin section box */
        .admin-section {
            margin: 20px 0;
            padding: 20px;
            background: var(--bg-input);
            border-radius: 10px;
            border: 1px solid var(--border);
        }
        .admin-section-title {
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .admin-section-title .section-icon {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: rgba(108,92,231,0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .hidden { display: none !important; }

        /* Responsive */
        @media (max-width: 480px) {
            body { padding: 24px 12px; }
            .card { padding: 20px; }
            .form-row { flex-direction: column; gap: 0; }
            .steps { padding: 0; }
            .step-label { font-size: 10px; }
            .success-links { flex-direction: column; }
            .success-links a { justify-content: center; }
        }
    </style>
</head>
<body>
<div class="installer">
    <!-- 标题 -->
    <div class="header">
        <div class="logo-mark">
            <svg viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <h1><span>MX</span>-Mall</h1>
        <p>安装向导 - 按照步骤完成系统安装</p>
    </div>

    <!-- 步骤条 -->
    <div class="steps">
        <div class="step-item <?php echo $displayStep >= 1 ? ($displayStep > 1 ? 'done' : 'active') : ''; ?>">
            <div class="step-num"><?php echo $displayStep > 1 ? '&#10003;' : '1'; ?></div>
            <div class="step-label">环境检测</div>
        </div>
        <div class="step-item <?php echo $displayStep >= 2 ? ($displayStep > 2 ? 'done' : 'active') : ''; ?>">
            <div class="step-num"><?php echo $displayStep > 2 ? '&#10003;' : '2'; ?></div>
            <div class="step-label">数据库</div>
        </div>
        <div class="step-item <?php echo $displayStep >= 3 ? ($displayStep > 3 ? 'done' : 'active') : ''; ?>">
            <div class="step-num"><?php echo $displayStep > 3 ? '&#10003;' : '3'; ?></div>
            <div class="step-label">建表</div>
        </div>
        <div class="step-item <?php echo $displayStep >= 4 ? ($displayStep > 4 ? 'done' : 'active') : ''; ?>">
            <div class="step-num"><?php echo $displayStep > 4 ? '&#10003;' : '4'; ?></div>
            <div class="step-label">站点与管理员</div>
        </div>
        <div class="step-item <?php echo $displayStep >= 5 ? 'active' : ''; ?>">
            <div class="step-num">5</div>
            <div class="step-label">完成</div>
        </div>
    </div>

    <!-- 错误提示 -->
    <?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($success && !$error): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <!-- ===== 步骤1：环境检测 ===== -->
    <?php if ($displayStep === 1): ?>
    <div class="card">
        <div class="card-title"><div class="title-icon">&#128269;</div> 环境检测</div>
        <table class="env-table">
            <thead>
                <tr><th>检测项</th><th>当前值</th><th>要求</th><th>状态</th></tr>
            </thead>
            <tbody>
                <?php foreach ($envResults as $item): ?>
                <tr>
                    <td><?php echo $item['name']; ?></td>
                    <td><?php echo $item['value']; ?></td>
                    <td><?php echo $item['required']; ?></td>
                    <td><span class="badge <?php echo $item['pass'] ? 'badge-ok' : 'badge-fail'; ?>"><?php echo $item['pass'] ? '通过' : '不通过'; ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="POST">
        <input type="hidden" name="step" value="2">
        <div class="btn-row">
            <button type="submit" class="btn btn-primary" <?php echo !$allPass ? 'disabled' : ''; ?>>
                <?php echo $allPass ? '环境检测通过，下一步' : '请先解决环境问题'; ?>
            </button>
        </div>
        <?php if (!$allPass): ?>
        <div class="alert alert-error" style="margin-top:12px;">部分检测项未通过，请先修复后再继续安装。</div>
        <?php endif; ?>
    </form>

    <!-- ===== 步骤2：数据库配置 ===== -->
    <?php elseif ($displayStep === 2): ?>
    <div class="card">
        <div class="card-title"><div class="title-icon">&#128451;</div> 数据库配置</div>
        <form method="POST" id="dbForm">
            <input type="hidden" name="step" value="2">
            <input type="hidden" name="db_pass_submitted" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label>数据库主机</label>
                    <input type="text" name="db_host" value="<?php echo post('db_host', extension_loaded('pdo_mysql') ? 'localhost' : ''); ?>" placeholder="localhost">
                </div>
                <div class="form-group">
                    <label>端口</label>
                    <input type="number" name="db_port" value="<?php echo post('db_port', '3306'); ?>" placeholder="3306">
                </div>
            </div>
            <div class="form-group">
                <label>数据库名称 <span class="required">*</span></label>
                <input type="text" name="db_name" value="<?php echo post('db_name', 'mx_mall'); ?>" placeholder="mx_mall">
            </div>
            <div class="form-group">
                <label>数据库用户名 <span class="required">*</span></label>
                <input type="text" name="db_user" value="<?php echo post('db_user'); ?>" placeholder="root">
            </div>
            <div class="form-group">
                <label>数据库密码</label>
                <input type="password" name="db_pass" value="<?php echo post('db_pass'); ?>" placeholder="请输入数据库密码">
            </div>
            <div class="btn-row">
                <button type="submit" class="btn btn-primary">测试连接并下一步</button>
            </div>
        </form>
    </div>

    <!-- ===== 步骤3：建表导入 ===== -->
    <?php elseif ($displayStep === 3): ?>
    <div class="card">
        <div class="card-title"><div class="title-icon">&#128451;</div> 初始化数据库</div>
        <div class="alert alert-info">
            即将执行 install.sql 创建数据表并导入默认数据（管理员账号、系统配置、收银台模版等）。
        </div>
        <form method="POST">
            <input type="hidden" name="step" value="3">
            <?php
            // 传递数据库信息
            foreach (['db_host', 'db_port', 'db_name', 'db_user', 'db_pass'] as $field) {
                echo '<input type="hidden" name="' . $field . '" value="' . post($field) . '">';
            }
            ?>
            <div class="btn-row">
                <button type="submit" class="btn btn-primary">执行建表并导入数据</button>
            </div>
        </form>
    </div>

    <!-- ===== 步骤4：站点与管理员配置（合并） ===== -->
    <?php elseif ($displayStep === 4): ?>
    <div class="card">
        <div class="card-title"><div class="title-icon">&#9881;</div> 站点与管理员配置</div>
        <form method="POST">
            <input type="hidden" name="step" value="4">
            <?php
            foreach (['db_host', 'db_port', 'db_name', 'db_user', 'db_pass'] as $field) {
                echo '<input type="hidden" name="' . $field . '" value="' . post($field) . '">';
            }
            ?>
            <div class="form-group">
                <label>站点名称 <span class="required">*</span></label>
                <input type="text" name="site_name" value="<?php echo post('site_name', 'MX-Mall'); ?>" placeholder="请输入站点名称">
            </div>
            <div class="admin-section">
                <div class="admin-section-title"><div class="section-icon">&#128100;</div> 管理员账号设置</div>
                <div class="form-group">
                    <label>管理员用户名 <span class="required">*</span></label>
                    <input type="text" name="admin_user" value="<?php echo post('admin_user', 'admin'); ?>" placeholder="admin">
                </div>
                <div class="form-group">
                    <label>管理员密码 <span class="required">*</span></label>
                    <input type="password" name="admin_pass" placeholder="至少6位密码">
                </div>
                <div class="form-group">
                    <label>确认密码 <span class="required">*</span></label>
                    <input type="password" name="admin_pass2" placeholder="再次输入密码">
                </div>
            </div>
            <div class="alert alert-info">
                系统将自动生成 config/config.php 配置文件，包含数据库连接、JWT密钥等配置。<br>
                站点URL将自动检测，无需手动填写。安装完成后可在后台管理面板中修改支付配置。
            </div>
            <div class="btn-row">
                <button type="submit" class="btn btn-primary">保存配置并完成安装</button>
            </div>
        </form>
    </div>

    <!-- ===== 步骤5：安装完成 ===== -->
    <?php elseif ($displayStep === 5): ?>
    <div class="card">
        <div class="success-box">
            <div class="success-icon">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="success-title">安装完成</div>
            <div class="success-desc">
                MX-Mall已成功安装！<br>
                您可以使用管理员账号登录后台管理系统。
            </div>
            <div class="success-links">
                <a href="/admin.php" class="link-primary">&#128272; 进入后台管理</a>
                <a href="/" class="link-secondary">&#127968; 查看前台首页</a>
            </div>
            <div class="success-warn">
                &#9888; 安全提示：安装完成后，建议删除 install 目录或确保 install.lock 文件存在，防止他人重新安装。
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 底部信息 -->
    <div style="text-align:center;margin-top:24px;color:var(--text-muted);font-size:12px;">
        MX-Mall v1.0.0 &copy; <?php echo date('Y'); ?>
    </div>
</div>
</body>
</html>
