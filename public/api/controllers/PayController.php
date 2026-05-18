<?php
/**
 * MX-Mall - 支付控制器
 *
 * 处理支付提交、易支付回调、拉卡拉回调
 * 支持两种支付方式: epay（易支付）和 lakala（拉卡拉）
 * 使用 payments/ 目录下的封装类（Epay、Lakala、PayFactory）
 */

require_once __DIR__ . '/BaseController.php';

use NexusMall\Payments\PayFactory;
use NexusMall\Payments\Epay;
use NexusMall\Payments\Lakala;

class PayController extends BaseController
{
    /**
     * 提交支付
     * POST /api/pay/submit
     * 参数: out_trade_no, pay_type(epay/lakala，可选), pay_channel(wxpay/alipay，可选)
     * 如果不传 pay_type，则根据系统配置的 pay_channel 自动选择支付方式
     */
    public function submit(): void
    {
        $db = DB::getInstance();
        $input = $this->allInput();

        $outTradeNo = trim($input['out_trade_no'] ?? $input['trade_no'] ?? '');
        $payType = trim($input['pay_type'] ?? '');
        $payChannel = trim($input['pay_channel'] ?? 'wxpay');

        if (empty($outTradeNo)) {
            $this->jsonError('订单号不能为空');
        }

        // 如果未传 pay_type，根据系统配置自动选择
        if (empty($payType)) {
            $payType = $this->getSystemPayChannel();
            if (empty($payType)) {
                $this->jsonError('系统未配置支付方式，请联系管理员');
            }
        }

        if (!in_array($payType, ['epay', 'lakala', 'lakala_moss', 'wxpay'])) {
            $this->jsonError('支付方式无效');
        }

        if (!in_array($payChannel, ['wxpay', 'alipay'])) {
            $this->jsonError('支付渠道无效');
        }

        // 查询订单
        $order = $db->getRow(
            "SELECT * FROM `orders` WHERE out_trade_no = ?",
            [$outTradeNo]
        );

        if (!$order) {
            $this->jsonError('订单不存在');
        }

        if ($order['status'] != 0) {
            $this->jsonError('订单状态异常，当前状态: ' . $this->getOrderStatusText($order['status']));
        }

        // 检查订单是否过期
        if (!empty($order['expire_time']) && strtotime($order['expire_time']) < time()) {
            $db->update('orders', ['status' => 3], 'id = ?', [$order['id']]);
            $this->jsonError('订单已过期');
        }

        // 获取支付配置
        $payConfig = $this->getPayConfig($payType);

        if (empty($payConfig)) {
            $this->jsonError('支付方式未配置，请联系管理员');
        }

        // 更新订单支付方式
        $db->update('orders', [
            'pay_type'    => $payType,
            'pay_channel' => $payChannel,
        ], 'id = ?', [$order['id']]);

        // 使用 PayFactory 创建支付实例
        try {
            // 构建站点基础URL
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                     || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                     ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            $baseUrl = $protocol . '://' . $host;

            if ($payType === 'epay') {
                // 易支付：使用 Epay SDK 的 getPayLink（MD5签名）
                $epay = new \NexusMall\Payments\Epay($payConfig);
                $payUrl = $epay->getPayLink([
                    'type'         => $payChannel,
                    'out_trade_no' => $order['out_trade_no'],
                    'name'         => $order['product_name'] ?: '商品支付',
                    'money'        => $order['money'],
                    'notify_url'   => $payConfig['notify_url'],
                    'return_url'   => $baseUrl . '/cashier.php?trade_no=' . $order['out_trade_no'] . '&tpl=' . ($order['cashier_tpl'] ?? 'meituan'),
                ]);
            } elseif ($payType === 'lakala') {
                // 拉卡拉：使用 Lakala 类创建订单
                $payInstance = PayFactory::create($payType, $payConfig);
                $result = $payInstance->createOrder([
                    'out_trade_no' => $order['out_trade_no'],
                    'total_amount' => (int) round((float) $order['money'] * 100), // 元转分
                    'subject'      => mb_substr($order['product_name'], 0, 50),
                    'notify_url'   => $payConfig['notify_url'] ?: $this->getNotifyUrl('lakala'),
                ]);
                $payUrl = $result['pay_url'] ?? '';
            } elseif ($payType === 'lakala_moss') {
                // 拉卡拉MOSS：使用 LakalaMoss 类创建订单（收银台模式）
                $payInstance = PayFactory::create($payType, $payConfig);

                // 映射支付渠道到MOSS account_type
                $accountTypeMap = [
                    'wxpay'  => 'WECHAT',
                    'alipay' => 'ALIPAY',
                ];
                $accountType = $accountTypeMap[$payChannel] ?? 'WECHAT';

                $result = $payInstance->createOrder([
                    'out_trade_no'  => $order['out_trade_no'],
                    'total_amount'  => (int) round((float) $order['money'] * 100), // 元转分
                    'subject'       => mb_substr($order['product_name'], 0, 42),
                    'notify_url'    => $payConfig['notify_url'] ?: $this->getNotifyUrl('lakala_moss'),
                    'callback_url'  => $baseUrl . '/cashier.php?trade_no=' . $order['out_trade_no'] . '&tpl=' . ($order['cashier_tpl'] ?? 'meituan'),
                    'account_type'  => $accountType,
                    'order_eff_time'=> 300,
                ]);
                $payUrl = $result['pay_url'] ?? $result['counter_url'] ?? '';
            } elseif ($payType === 'wxpay') {
                $inputOpenid = trim($input['openid'] ?? '');
                $wxpayResult = $this->createWxpayOrder($payConfig, $order, $baseUrl, $inputOpenid);
                $this->logPayment($db, [
                    'order_id'      => $order['id'],
                    'out_trade_no'  => $outTradeNo,
                    'pay_type'      => $payType,
                    'pay_channel'   => $payChannel,
                    'request_data'  => json_encode($wxpayResult, JSON_UNESCAPED_UNICODE),
                    'status'        => 0,
                ]);
                $this->jsonSuccess([
                    'pay_url'      => '',
                    'jsapi_params' => $wxpayResult,
                    'out_trade_no' => $outTradeNo,
                    'money'        => $order['money'],
                    'pay_type'     => $payType,
                    'pay_channel'  => $payChannel,
                ], '支付请求已生成');
            }

            // 记录支付日志
            $this->logPayment($db, [
                'order_id'      => $order['id'],
                'out_trade_no'  => $outTradeNo,
                'pay_type'      => $payType,
                'pay_channel'   => $payChannel,
                'request_data'  => json_encode(['pay_url' => $payUrl], JSON_UNESCAPED_UNICODE),
                'status'        => 0,
            ]);

            $this->jsonSuccess([
                'pay_url'      => $payUrl,
                'out_trade_no' => $outTradeNo,
                'money'        => $order['money'],
                'pay_type'     => $payType,
                'pay_channel'  => $payChannel,
            ], '支付请求已生成');

        } catch (Exception $e) {
            // 记录失败日志
            $this->logPayment($db, [
                'order_id'      => $order['id'],
                'out_trade_no'  => $outTradeNo,
                'pay_type'      => $payType,
                'pay_channel'   => $payChannel,
                'status'        => 2,
                'error_msg'     => $e->getMessage(),
            ]);

            $this->jsonError('支付请求失败: ' . $e->getMessage());
        }
    }

    /**
     * 易支付异步回调
     * 回调地址: /api/pay/notify/epay
     * 彩虹易支付通过 GET 方式回调
     */
    public function epayNotify(): void
    {
        $db = DB::getInstance();

        // 获取易支付配置
        $payConfig = $this->getPayConfig('epay');
        if (empty($payConfig)) {
            echo 'fail';
            return;
        }

        // 使用 Epay SDK 验签（读取 $_GET）
        $epay = new \NexusMall\Payments\Epay($payConfig);
        $verifyResult = $epay->verifyNotify();

        if (!$verifyResult) {
            $this->logPayment($db, [
                'out_trade_no' => $_GET['out_trade_no'] ?? '',
                'pay_type'     => 'epay',
                'response_data'=> json_encode($_GET, JSON_UNESCAPED_UNICODE),
                'status'       => 2,
                'error_msg'    => '签名验证失败',
            ]);
            echo 'fail';
            return;
        }

        // 获取回调参数（全部从 $_GET 读取）
        $outTradeNo = $_GET['out_trade_no'] ?? '';
        $tradeNo = $_GET['trade_no'] ?? '';
        $money = $_GET['money'] ?? '';
        $tradeStatus = $_GET['trade_status'] ?? '';
        $type = $_GET['type'] ?? '';

        if (empty($outTradeNo) || empty($tradeNo)) {
            echo 'fail';
            return;
        }

        // 检查交易状态
        if ($tradeStatus !== 'TRADE_SUCCESS') {
            echo 'success';
            return;
        }

        // 查询订单
        $order = $db->getRow(
            "SELECT * FROM `orders` WHERE out_trade_no = ?",
            [$outTradeNo]
        );

        if (!$order) {
            echo 'fail';
            return;
        }

        // 防止重复处理
        if ($order['status'] == 1) {
            echo 'success';
            return;
        }

        // 验证金额
        if (floatval($money) != floatval($order['money'])) {
            $this->logPayment($db, [
                'order_id'     => $order['id'],
                'out_trade_no' => $outTradeNo,
                'pay_type'     => 'epay',
                'response_data'=> json_encode($_GET, JSON_UNESCAPED_UNICODE),
                'status'       => 2,
                'error_msg'    => '金额不匹配: 回调' . $money . ' vs 订单' . $order['money'],
            ]);
            echo 'fail';
            return;
        }

        // 更新订单状态
        $db->update('orders', [
            'trade_no'    => $tradeNo,
            'status'      => 1,
            'pay_time'    => date('Y-m-d H:i:s'),
            'pay_type'    => 'epay',
            'pay_channel' => $type,
            'notify_data' => json_encode($_GET, JSON_UNESCAPED_UNICODE),
        ], 'id = ?', [$order['id']]);

        // 代付逻辑：给分享者加余额
        $this->handleShareBalance($db, $order);

        // 购买身份逻辑
        $this->handleGroupPurchase($db, $order);

        // 记录成功日志
        $this->logPayment($db, [
            'order_id'     => $order['id'],
            'out_trade_no' => $outTradeNo,
            'pay_type'     => 'epay',
            'pay_channel'  => $type,
            'response_data'=> json_encode($_GET, JSON_UNESCAPED_UNICODE),
            'status'       => 1,
        ]);

        echo 'success';
    }

    /**
     * 拉卡拉异步回调
     * POST /api/pay/notify/lakala
     */
    public function lakalaNotify(): void
    {
        $db = DB::getInstance();

        // 获取回调数据（JSON Body）
        $rawInput = file_get_contents('php://input');
        $params = json_decode($rawInput, true) ?: [];

        $outTradeNo = $params['out_trade_no'] ?? $params['orderNo'] ?? '';
        $tradeNo = $params['trade_no'] ?? $params['transactionNo'] ?? '';
        $tradeStatus = $params['trade_status'] ?? $params['status'] ?? '';

        if (empty($outTradeNo) || empty($tradeNo)) {
            $this->notifyResponse('fail', 'lakala');
        }

        // 获取拉卡拉配置
        $payConfig = $this->getPayConfig('lakala');
        if (empty($payConfig) || empty($payConfig['public_key'])) {
            $this->notifyResponse('fail', 'lakala');
        }

        // 使用 Lakala 类验签
        try {
            $lakala = PayFactory::create('lakala', $payConfig);
        } catch (Exception $e) {
            $this->notifyResponse('fail', 'lakala');
        }

        if (!$lakala->verifyNotify($params)) {
            $this->logPayment($db, [
                'out_trade_no' => $outTradeNo,
                'pay_type'     => 'lakala',
                'response_data'=> $rawInput,
                'status'       => 2,
                'error_msg'    => '签名验证失败',
            ]);
            $this->notifyResponse('fail', 'lakala');
        }

        // 查询订单
        $order = $db->getRow(
            "SELECT * FROM `orders` WHERE out_trade_no = ?",
            [$outTradeNo]
        );

        if (!$order) {
            $this->notifyResponse('fail', 'lakala');
        }

        // 防止重复处理
        if ($order['status'] == 1) {
            $this->notifyResponse('success', 'lakala');
        }

        // 更新订单状态
        $db->update('orders', [
            'trade_no'    => $tradeNo,
            'status'      => 1,
            'pay_time'    => date('Y-m-d H:i:s'),
            'notify_data' => $rawInput,
        ], 'id = ?', [$order['id']]);

        // 代付逻辑：如果订单有 share_user_id，给分享者加余额
        $this->handleShareBalance($db, $order);

        // 购买身份逻辑
        $this->handleGroupPurchase($db, $order);

        // 记录成功日志
        $this->logPayment($db, [
            'order_id'     => $order['id'],
            'out_trade_no' => $outTradeNo,
            'pay_type'     => 'lakala',
            'pay_channel'  => $order['pay_channel'],
            'response_data'=> $rawInput,
            'status'       => 1,
        ]);

        $this->notifyResponse('success', 'lakala');
    }

    /**
     * 拉卡拉MOSS异步回调
     * POST /api/pay/notify/lakala_moss
     */
    public function mossNotify(): void
    {
        $db = DB::getInstance();

        // 获取原始请求体
        $rawInput = file_get_contents('php://input');

        if (empty($rawInput)) {
            $this->mossNotifyFail($rawInput, '请求体为空');
            return;
        }

        // 获取MOSS配置
        $payConfig = $this->getPayConfig('lakala_moss');
        if (empty($payConfig)) {
            $this->mossNotifyFail($rawInput, 'MOSS支付未配置');
            return;
        }

        // 使用 LakalaMoss 类验签+解密
        try {
            $moss = PayFactory::create('lakala_moss', $payConfig);
        } catch (Exception $e) {
            $this->mossNotifyFail($rawInput, 'MOSS实例创建失败: ' . $e->getMessage());
            return;
        }

        $notifyData = $moss->verifyNotify($rawInput);
        if (!$notifyData) {
            $this->mossNotifyFail($rawInput, '签名验证或解密失败');
            return;
        }

        // 解析通知数据
        $orderNo       = $notifyData['order_no'] ?? '';
        $tradeState    = $notifyData['trade_state'] ?? '';
        $paySerial     = $notifyData['pay_serial'] ?? '';

        if (empty($orderNo)) {
            $this->mossNotifyFail($rawInput, '订单号为空');
            return;
        }

        // 只处理支付成功通知
        if ($tradeState !== 'SUCCESS') {
            $request = json_decode($rawInput, true) ?: [];
            echo $moss->buildNotifyResponse($request['head'] ?? []);
            return;
        }

        // 查询订单
        try {
            $order = $db->getRow(
                "SELECT * FROM `orders` WHERE out_trade_no = ?",
                [$orderNo]
            );
        } catch (\Throwable $e) {
            $this->mossNotifyFail($rawInput, '查询订单异常: ' . $e->getMessage());
            return;
        }

        if (!$order) {
            $this->mossNotifyFail($rawInput, '订单不存在: ' . $orderNo);
            return;
        }

        // 防重复处理
        if ($order['status'] == 1) {
            $request = json_decode($rawInput, true) ?: [];
            echo $moss->buildNotifyResponse($request['head'] ?? []);
            return;
        }

        // 更新订单状态
        $db->update('orders', [
            'status'    => 1,
            'pay_time'  => date('Y-m-d H:i:s'),
            'trade_no'  => $paySerial,
        ], 'id = ?', [$order['id']]);

        // 代付逻辑：给分享者加余额
        $this->handleShareBalance($db, $order);

        // 购买身份逻辑
        $this->handleGroupPurchase($db, $order);

        // 记录成功日志
        $this->logPayment($db, [
            'order_id'     => $order['id'],
            'out_trade_no' => $orderNo,
            'pay_type'     => 'lakala_moss',
            'pay_channel'  => $order['pay_channel'],
            'response_data'=> $rawInput,
            'status'       => 1,
        ]);

        // 返回签名的成功响应
        $request = json_decode($rawInput, true) ?: [];
        echo $moss->buildNotifyResponse($request['head'] ?? []);
    }

    /**
     * MOSS回调失败处理
     */
    private function mossNotifyFail(string $rawInput, string $errorMsg): void
    {
        $db = DB::getInstance();
        $this->logPayment($db, [
            'out_trade_no' => '',
            'pay_type'     => 'lakala_moss',
            'response_data'=> $rawInput,
            'status'       => 2,
            'error_msg'    => $errorMsg,
        ]);

        // MOSS回调失败也需要返回签名响应
        $payConfig = $this->getPayConfig('lakala_moss');
        if ($payConfig) {
            try {
                $moss = PayFactory::create('lakala_moss', $payConfig);
                $request = json_decode($rawInput, true) ?: [];
                echo $moss->buildNotifyResponse($request['head'] ?? [], false, $errorMsg);
                return;
            } catch (\Throwable $e) {}
        }

        // 兜底返回
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['result' => 'fail']);
    }

    // ===== 私有方法 =====

    /**
     * 创建微信JSAPI支付订单
     * 调用统一下单接口，返回JSAPI调起支付所需的参数
     */
    private function createWxpayOrder(array $payConfig, array $order, string $baseUrl, string $inputOpenid = ''): array
    {
        $appid = $payConfig['appid'];
        $mchid = $payConfig['mchid'];
        $key = $payConfig['key'];
        $notifyUrl = $payConfig['notify_url'];

        $nonceStr = $this->getNonceStr();
        $outTradeNo = $order['out_trade_no'];
        $totalFee = (int)round((float)$order['money'] * 100);
        $body = mb_substr($order['product_name'] ?: '商品支付', 0, 128);
        $spbillCreateIp = $_SERVER['REMOTE_ADDR'] ?: '127.0.0.1';
        $tradeType = 'JSAPI';

        $openid = '';
        if (!empty($inputOpenid)) {
            $openid = $inputOpenid;
        }
        if (empty($openid)) {
            $userId = (int)($order['user_id'] ?? 0);
            if ($userId > 0) {
                $db = DB::getInstance();
                $userRow = $db->getRow("SELECT openid FROM users WHERE id = ?", [$userId]);
                if ($userRow && !empty($userRow['openid'])) {
                    $openid = $userRow['openid'];
                }
            }
        }
        if (empty($openid) && !empty($_COOKIE['wx_openid'])) {
            $openid = $_COOKIE['wx_openid'];
        }
        if (empty($openid)) {
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            $inWechat = (strpos($ua, 'MicroMessenger') !== false);
            if ($inWechat) {
                throw new Exception('微信授权获取openid失败，请关闭页面重新打开，或清除浏览器缓存后重试');
            } else {
                throw new Exception('微信JSAPI支付需要在微信中打开，请将链接发送到微信中访问');
            }
        }

        $params = [
            'appid'            => $appid,
            'mch_id'           => $mchid,
            'nonce_str'        => $nonceStr,
            'body'             => $body,
            'out_trade_no'     => $outTradeNo,
            'total_fee'        => $totalFee,
            'spbill_create_ip' => $spbillCreateIp,
            'notify_url'       => $notifyUrl,
            'trade_type'       => $tradeType,
            'openid'           => $openid,
        ];

        $params['sign'] = $this->makeWxSign($params, $key);

        $xml = $this->arrayToXml($params);

        $response = $this->postXmlCurl('https://api.mch.weixin.qq.com/pay/unifiedorder', $xml);
        $result = $this->xmlToArray($response);

        if (!isset($result['return_code']) || $result['return_code'] !== 'SUCCESS') {
            $errMsg = isset($result['return_msg']) ? $result['return_msg'] : '统一下单失败';
            throw new Exception($errMsg);
        }
        if (!isset($result['result_code']) || $result['result_code'] !== 'SUCCESS') {
            $errMsg = isset($result['err_code_des']) ? $result['err_code_des'] : '下单失败';
            throw new Exception($errMsg);
        }

        $prepayId = $result['prepay_id'];
        $jsapiParams = $this->buildJsapiParams($appid, $prepayId, $key);

        return $jsapiParams;
    }

    /**
     * 构建JSAPI调起支付所需的参数
     */
    private function buildJsapiParams(string $appId, string $prepayId, string $key): array
    {
        $params = [
            'appId'     => $appId,
            'timeStamp' => (string)time(),
            'nonceStr'  => $this->getNonceStr(),
            'package'   => 'prepay_id=' . $prepayId,
            'signType'  => 'MD5',
        ];
        $params['paySign'] = $this->makeWxSign($params, $key);
        return $params;
    }

    /**
     * 通过网页授权获取用户openid
     */
    private function getWxOpenid(array $payConfig): string
    {
        $appid = $payConfig['appid'];
        $appsecret = $payConfig['appsecret'] ?? '';

        if (!empty($_GET['code'])) {
            $code = trim($_GET['code']);
            $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$appsecret}&code={$code}&grant_type=authorization_code";
            $res = $this->curlGet($url);
            if ($res) {
                $data = json_decode($res, true);
                if (!empty($data['openid'])) {
                    $openid = $data['openid'];
                    setcookie('wx_openid', $openid, time() + 86400 * 30, '/', '', false, true);
                    return $openid;
                }
            }
        }

        if (!empty($_COOKIE['wx_openid'])) {
            return $_COOKIE['wx_openid'];
        }

        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if (strpos($ua, 'MicroMessenger') !== false) {
            $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                . ($_SERVER['REQUEST_URI'] ?? '/');
            $redirectUri = urlencode($currentUrl);
            $authUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirectUri}&response_type=code&scope=snsapi_base&state=wxpay#wechat_redirect";
            header('Location: ' . $authUrl);
            exit;
        }

        return '';
    }

    private function curlGet(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res !== false ? $res : '';
    }

    /**
     * 微信支付异步回调
     * POST /api/pay/notify/wxpay
     */
    public function wxpayNotify(): void
    {
        $db = DB::getInstance();
        $rawInput = file_get_contents('php://input');

        if (empty($rawInput)) {
            echo $this->wxpayFailXml('请求体为空');
            return;
        }

        $data = $this->xmlToArray($rawInput);
        if (!$data) {
            echo $this->wxpayFailXml('XML解析失败');
            return;
        }

        $payConfig = $this->getPayConfig('wxpay');
        if (empty($payConfig)) {
            echo $this->wxpayFailXml('微信支付未配置');
            return;
        }

        if (!isset($data['return_code']) || $data['return_code'] !== 'SUCCESS') {
            echo $this->wxpayFailXml('通信失败');
            return;
        }

        $sign = $data['sign'] ?? '';
        unset($data['sign']);
        $expectedSign = $this->makeWxSign($data, $payConfig['key']);
        if (strtolower($sign) !== strtolower($expectedSign)) {
            $this->logPayment($db, [
                'out_trade_no' => $data['out_trade_no'] ?? '',
                'pay_type'     => 'wxpay',
                'response_data'=> $rawInput,
                'status'       => 2,
                'error_msg'    => '签名验证失败',
            ]);
            echo $this->wxpayFailXml('签名验证失败');
            return;
        }

        if (!isset($data['result_code']) || $data['result_code'] !== 'SUCCESS') {
            echo $this->wxpaySuccessXml();
            return;
        }

        $outTradeNo = $data['out_trade_no'] ?? '';
        $tradeNo = $data['transaction_id'] ?? '';
        $totalFee = $data['total_fee'] ?? 0;

        if (empty($outTradeNo)) {
            echo $this->wxpayFailXml('订单号为空');
            return;
        }

        $order = $db->getRow(
            "SELECT * FROM `orders` WHERE out_trade_no = ?",
            [$outTradeNo]
        );

        if (!$order) {
            echo $this->wxpayFailXml('订单不存在');
            return;
        }

        if ($order['status'] == 1) {
            echo $this->wxpaySuccessXml();
            return;
        }

        $orderTotalFee = (int)round((float)$order['money'] * 100);
        if ($totalFee != $orderTotalFee) {
            $this->logPayment($db, [
                'order_id'     => $order['id'],
                'out_trade_no' => $outTradeNo,
                'pay_type'     => 'wxpay',
                'response_data'=> $rawInput,
                'status'       => 2,
                'error_msg'    => '金额不匹配: 回调' . $totalFee . ' vs 订单' . $orderTotalFee,
            ]);
            echo $this->wxpayFailXml('金额不匹配');
            return;
        }

        $db->update('orders', [
            'trade_no'    => $tradeNo,
            'status'      => 1,
            'pay_time'    => date('Y-m-d H:i:s'),
            'pay_type'    => 'wxpay',
            'pay_channel' => 'wxpay',
            'notify_data' => $rawInput,
        ], 'id = ?', [$order['id']]);

        $this->handleShareBalance($db, $order);

        $this->handleGroupPurchase($db, $order);

        $this->logPayment($db, [
            'order_id'     => $order['id'],
            'out_trade_no' => $outTradeNo,
            'pay_type'     => 'wxpay',
            'pay_channel'  => 'wxpay',
            'response_data'=> $rawInput,
            'status'       => 1,
        ]);

        echo $this->wxpaySuccessXml();
    }

    private function wxpaySuccessXml(): string
    {
        return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    }

    private function wxpayFailXml(string $msg): string
    {
        return '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[' . htmlspecialchars($msg) . ']]></return_msg></xml>';
    }

    private function makeWxSign(array $params, string $key): string
    {
        ksort($params);
        $string = '';
        foreach ($params as $k => $v) {
            if ($k === 'sign' || $v === '' || $v === null) continue;
            $string .= $k . '=' . $v . '&';
        }
        $string .= 'key=' . $key;
        return strtoupper(md5($string));
    }

    private function getNonceStr(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }

    private function arrayToXml(array $data): string
    {
        $xml = '<xml>';
        foreach ($data as $k => $v) {
            if (is_numeric($v)) {
                $xml .= '<' . $k . '>' . $v . '</' . $k . '>';
            } else {
                $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
            }
        }
        $xml .= '</xml>';
        return $xml;
    }

    private function xmlToArray(string $xml): array
    {
        libxml_disable_entity_loader(true);
        $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$obj) return [];
        $json = json_encode($obj);
        return json_decode($json, true);
    }

    private function postXmlCurl(string $url, string $xml, int $timeout = 30): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml; charset=utf-8']);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($errno) {
            throw new Exception('微信支付请求失败: ' . $error);
        }
        if (empty($response)) {
            throw new Exception('微信支付返回为空');
        }
        return $response;
    }

    /**
     * 处理代付逻辑：支付成功后给分享者加余额
     *
     * @param DB    $db    数据库实例
     * @param array $order 订单信息
     */
    private function handleShareBalance(DB $db, array $order): void
    {
        try {
            $shareUserId = (int) ($order['share_user_id'] ?? 0);
            if ($shareUserId <= 0) {
                return;
            }

            $money = floatval($order['money']);
            if ($money <= 0) {
                return;
            }

            $commissionRate = $this->getCommissionRate($db, $shareUserId);
            $actualMoney = $money;
            if ($commissionRate > 0) {
                $commission = round($money * $commissionRate / 100, 2);
                $actualMoney = $money - $commission;
                if ($actualMoney < 0) {
                    $actualMoney = 0;
                }
            }

            if ($actualMoney > 0) {
                $db->query(
                    "UPDATE users SET balance = balance + ? WHERE id = ?",
                    [$actualMoney, $shareUserId]
                );
            }
        } catch (Exception $e) {
        }
    }

    private function getCommissionRate(DB $db, int $shareUserId): float
    {
        try {
            $user = $db->getRow("SELECT group_id FROM users WHERE id = ?", [$shareUserId]);
            if ($user && intval($user['group_id']) > 0) {
                $group = $db->getRow("SELECT commission_rate FROM user_groups WHERE id = ? AND status = 1", [intval($user['group_id'])]);
                if ($group && isset($group['commission_rate'])) {
                    $rate = floatval($group['commission_rate']);
                    if ($rate >= 0 && $rate <= 100) {
                        return $rate;
                    }
                }
            }
            $defaultGroup = $db->getRow("SELECT commission_rate FROM user_groups WHERE is_default = 1 AND status = 1");
            if ($defaultGroup && isset($defaultGroup['commission_rate'])) {
                $rate = floatval($defaultGroup['commission_rate']);
                if ($rate >= 0 && $rate <= 100) {
                    return $rate;
                }
            }
        } catch (Exception $e) {}
        return 0;
    }

    private function handleGroupPurchase(DB $db, array $order): void
    {
        try {
            $productName = isset($order['product_name']) ? $order['product_name'] : '';
            if (strpos($productName, '购买身份:') !== 0) {
                return;
            }

            $userId = (int) ($order['user_id'] ?? 0);
            if ($userId <= 0) {
                return;
            }

            $outTradeNo = $order['out_trade_no'] ?? '';
            if (strpos($outTradeNo, 'GRP') !== 0) {
                return;
            }

            $group = $db->getRow("SELECT id FROM user_groups WHERE status = 1 AND name = ?", [trim(str_replace('购买身份:', '', $productName))]);
            if ($group) {
                $db->update('users', ['group_id' => (int)$group['id']], 'id = ?', [$userId]);
            }
        } catch (Exception $e) {}
    }

    /**
     * 获取系统配置的支付渠道
     * 从 system_config 中读取 pay_channel 配置项
     *
     * @return string 支付方式（epay/lakala/ysm等）
     */
    private function getSystemPayChannel(): string
    {
        $db = DB::getInstance();
        $payChannel = $db->getOne(
            "SELECT config_value FROM `system_config` WHERE config_key = 'pay_channel'"
        );
        return $payChannel ?: 'epay';
    }

    /**
     * 获取支付回调URL
     *
     * @param string $payType 支付方式
     * @return string
     */
    private function getNotifyUrl(string $payType): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

        return "{$scheme}://{$host}{$basePath}/api/pay/notify/{$payType}";
    }

    /**
     * 获取支付配置
     *
     * @param string $payType 支付方式（epay/lakala）
     * @return array|null
     */
    private function getPayConfig(string $payType): ?array
    {
        $db = DB::getInstance();
        $rows = $db->getAll("SELECT config_key, config_value FROM system_config");
        $config = [];
        foreach ($rows as $row) {
            $config[$row['config_key']] = $row['config_value'];
        }

        // 自动生成通知地址
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                 || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                 ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $baseUrl = $protocol . '://' . $host;

        if ($payType === 'epay') {
            if (empty($config['epay_api']) || empty($config['epay_id']) || empty($config['epay_key'])) {
                return null;
            }

            return [
                'apiurl'     => $config['epay_api'] ?? '',
                'pid'        => $config['epay_id'] ?? '',
                'key'        => $config['epay_key'] ?? '',
                'notify_url' => $baseUrl . '/api/pay/notify/epay',
                'return_url' => $baseUrl . '/cashier.php?trade_no=DEFAULT&tpl=meituan',
            ];
        } elseif ($payType === 'lakala') {
            if (empty($config['lakala_app_id'])) {
                return null;
            }
            return $config;
        } elseif ($payType === 'lakala_moss') {
            if (empty($config['moss_mer_no']) || empty($config['moss_private_key'])) {
                return null;
            }
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                     || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                     ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            $baseUrl = $protocol . '://' . $host;
            $config['notify_url'] = $baseUrl . '/api/pay/notify/lakala_moss';
            return $config;
        } elseif ($payType === 'wxpay') {
            if (empty($config['wxpay_mchid']) || empty($config['wxpay_key'])) {
                return null;
            }
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                     || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                     ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            $baseUrl = $protocol . '://' . $host;
            $wxAppid = $config['wx_appid'] ?? '';
            $wxAppsecret = $config['wx_appsecret'] ?? '';
            return [
                'appid'      => $wxAppid,
                'mchid'      => $config['wxpay_mchid'],
                'key'        => $config['wxpay_key'],
                'appsecret'  => $wxAppsecret,
                'notify_url' => $baseUrl . '/api/pay/notify/wxpay',
            ];
        }

        return null;
    }

    /**
     * 记录支付日志
     *
     * @param DB    $db   数据库实例
     * @param array $data 日志数据
     */
    private function logPayment(DB $db, array $data): void
    {
        try {
            $db->insert('payment_logs', [
                'order_id'      => $data['order_id'] ?? null,
                'out_trade_no'  => $data['out_trade_no'] ?? '',
                'pay_type'      => $data['pay_type'] ?? '',
                'pay_channel'   => $data['pay_channel'] ?? '',
                'request_data'  => $data['request_data'] ?? null,
                'response_data' => $data['response_data'] ?? null,
                'status'        => $data['status'] ?? 0,
                'error_msg'     => $data['error_msg'] ?? null,
            ]);
        } catch (Exception $e) {
            // 日志记录失败不影响主流程
        }
    }

    /**
     * 更新商品销量
     *
     * @param DB    $db    数据库实例
     * @param array $order 订单信息
     */
    private function updateSalesCount(DB $db, array $order): void
    {
        try {
            // 解析商品列表
            $items = json_decode($order['items'], true);
            if (is_array($items)) {
                foreach ($items as $item) {
                    $pid = (int) ($item['id'] ?? 0);
                    $qty = (int) ($item['qty'] ?? 1);
                    if ($pid > 0) {
                        $db->query(
                            "UPDATE `products` SET sales_count = sales_count + ? WHERE id = ?",
                            [$qty, $pid]
                        );
                    }
                }
            } elseif ($order['product_id'] > 0) {
                // 单商品订单
                $db->query(
                    "UPDATE `products` SET sales_count = sales_count + 1 WHERE id = ?",
                    [$order['product_id']]
                );
            }
        } catch (Exception $e) {
            // 销量更新失败不影响主流程
        }
    }

    /**
     * 获取订单状态文本
     *
     * @param int $status 状态码
     * @return string
     */
    private function getOrderStatusText(int $status): string
    {
        $map = [
            0 => '待支付',
            1 => '已支付',
            2 => '已退款',
            3 => '已过期',
        ];
        return $map[$status] ?? '未知';
    }

    /**
     * 输出回调响应
     *
     * @param string $result   success/fail
     * @param string $payType  支付方式
     */
    private function notifyResponse(string $result, string $payType): void
    {
        if ($payType === 'epay') {
            // 易支付回调响应
            echo $result;
        } else {
            // 拉卡拉回调响应（JSON格式）
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'result_code' => $result === 'success' ? 'SUCCESS' : 'FAIL',
                'result_msg'  => $result === 'success' ? 'OK' : 'ERROR',
            ]);
        }
        exit;
    }
}
