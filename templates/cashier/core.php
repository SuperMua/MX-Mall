<?php
/**
 * Core.php Stub for Cashier Templates
 *
 * This file provides the functions and variables that the original cashier templates
 * expect from core.php. It bridges the gap between the original template system
 * and the MX-Mall system.
 */

// Load the MX-Mall database class (skip if already loaded by cashier.php)
if (!class_exists('DB')) {
    require_once __DIR__ . '/../../config/database.php';
}

// Initialize DB singleton (PDO instance for template compatibility)
$DB = DB::getInstance()->getPdo();

// Ensure products table has shop_name column (required by templates)
try {
    $DB->exec("ALTER TABLE `products` ADD COLUMN `shop_name` VARCHAR(200) DEFAULT NULL");
} catch (Exception $e) {
    // Column already exists, ignore
}

/**
 * Get configuration value from system_config table
 */
function get_config($key = null) {
    static $configs = null;
    if ($configs === null) {
        try {
            $db = DB::getInstance();
            $rows = $db->getAll("SELECT config_key, config_value FROM `system_config`");
            $configs = [];
            foreach ($rows as $row) {
                $configs[$row['config_key']] = $row['config_value'];
            }
        } catch (Exception $e) {
            $configs = [];
        }
    }
    if ($key === null) return $configs;
    return $configs[$key] ?? '';
}

/**
 * Get WeChat JS-SDK config
 */
function get_wx_js_config() {
    try {
        $db = DB::getInstance();
        $appid = $db->getOne("SELECT config_value FROM `system_config` WHERE config_key = 'wx_appid'");
        $appsecret = $db->getOne("SELECT config_value FROM `system_config` WHERE config_key = 'wx_appsecret'");
        
        if (empty($appid) || empty($appsecret)) return null;
        
        // 获取access_token（带文件缓存）
        $tokenFile = __DIR__ . '/../../runtime/wx_access_token.json';
        $access_token = '';
        if (file_exists($tokenFile)) {
            $tokenData = json_decode(file_get_contents($tokenFile), true);
            if ($tokenData && $tokenData['expire_time'] > time()) {
                $access_token = $tokenData['access_token'];
            }
        }
        
        if (empty($access_token)) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
            $response = file_get_contents($url);
            $result = json_decode($response, true);
            if (isset($result['access_token'])) {
                $access_token = $result['access_token'];
                @file_put_contents($tokenFile, json_encode([
                    'access_token' => $access_token,
                    'expire_time' => time() + 7000
                ]));
            } else {
                return null;
            }
        }
        
        // 获取jsapi_ticket（带文件缓存）
        $ticketFile = __DIR__ . '/../../runtime/wx_jsapi_ticket.json';
        $jsapi_ticket = '';
        if (file_exists($ticketFile)) {
            $ticketData = json_decode(file_get_contents($ticketFile), true);
            if ($ticketData && $ticketData['expire_time'] > time()) {
                $jsapi_ticket = $ticketData['jsapi_ticket'];
            }
        }
        
        if (empty($jsapi_ticket)) {
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$access_token}&type=jsapi";
            $response = file_get_contents($url);
            $result = json_decode($response, true);
            if (isset($result['ticket'])) {
                $jsapi_ticket = $result['ticket'];
                @file_put_contents($ticketFile, json_encode([
                    'jsapi_ticket' => $jsapi_ticket,
                    'expire_time' => time() + 7000
                ]));
            } else {
                return null;
            }
        }
        
        // 生成签名
        $protocol = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? "https://" : "http://";
        $url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        $timestamp = time();
        $nonceStr = substr(md5(uniqid(mt_rand(), true)), 0, 16);
        
        $signParams = [
            'jsapi_ticket' => $jsapi_ticket,
            'noncestr' => $nonceStr,
            'timestamp' => $timestamp,
            'url' => $url,
        ];
        ksort($signParams);
        $signStr = urldecode(http_build_query($signParams));
        $signature = sha1($signStr);
        
        return [
            'appId' => $appid,
            'timestamp' => $timestamp,
            'nonceStr' => $nonceStr,
            'signature' => $signature,
            'jsApiList' => ['updateAppMessageShareData', 'updateTimelineShareData'],
        ];
    } catch (Exception $e) {
        return null;
    }
}
