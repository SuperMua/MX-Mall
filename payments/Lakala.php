<?php
/**
 * 拉卡拉Moss支付封装类
 *
 * 使用V3风格API（OpenAPI规范），RSA签名。
 * 需要商户号merchant_no、终端号term_no。
 */
namespace NexusMall\Payments;

class Lakala
{
    /** @var string 商户号 */
    private $merchantNo;

    /** @var string 终端号 */
    private $termNo;

    /** @var string 应用ID */
    private $appId;

    /** @var string 商户RSA私钥（PEM格式，不含头尾标记） */
    private $privateKey;

    /** @var string 平台RSA公钥（PEM格式，不含头尾标记） */
    private $publicKey;

    /** @var string API网关地址 */
    private $gateway;

    /** @var string 异步通知地址 */
    private $notifyUrl;

    /** @var int HTTP请求超时时间（秒） */
    private $timeout = 30;

    /**
     * 构造函数
     *
     * @param array $config 配置数组，包含以下键：
     *   - merchant_no  商户号（必填）
     *   - term_no      终端号（必填）
     *   - app_id       应用ID（必填）
     *   - private_key  商户RSA私钥（必填）
     *   - public_key   平台RSA公钥（必填）
     *   - gateway      API网关地址（可选，默认 https://s2.lakala.com/api/v3/labs）
     *   - notify_url   异步通知地址（可选）
     * @throws \InvalidArgumentException 配置缺失时抛出异常
     */
    public function __construct(array $config)
    {
        $required = ['merchant_no', 'term_no', 'app_id', 'private_key', 'public_key'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new \InvalidArgumentException("拉卡拉配置缺少必填项：{$key}");
            }
        }

        $this->merchantNo = $config['merchant_no'];
        $this->termNo     = $config['term_no'];
        $this->appId      = $config['app_id'];
        $this->privateKey = $config['private_key'];
        $this->publicKey  = $config['public_key'];
        $this->gateway    = $config['gateway'] ?? 'https://s2.lakala.com/api/v3/labs';
        $this->notifyUrl  = $config['notify_url'] ?? '';

        if (!empty($config['timeout'])) {
            $this->timeout = (int) $config['timeout'];
        }
    }

    /**
     * 统一下单
     *
     * 创建支付订单，返回支付参数数组用于前端调起支付。
     *
     * @param array $params 下单参数：
     *   - out_trade_no  商户订单号（必填）
     *   - total_amount  金额，单位：分（必填）
     *   - subject       商品标题（必填）
     *   - body          商品描述（可选）
     *   - notify_url    异步通知地址（可选，优先使用构造时的值）
     * @return array 支付参数数组（含跳转URL或调起支付所需参数）
     * @throws \InvalidArgumentException 参数缺失时抛出异常
     * @throws \RuntimeException 下单失败时抛出异常
     */
    public function createOrder(array $params): array
    {
        $required = ['out_trade_no', 'total_amount', 'subject'];
        foreach ($required as $key) {
            if (!isset($params[$key]) || $params[$key] === '') {
                throw new \InvalidArgumentException("创建订单缺少必填参数：{$key}");
            }
        }

        $notifyUrl = $params['notify_url'] ?? $this->notifyUrl;
        if (empty($notifyUrl)) {
            throw new \InvalidArgumentException("异步通知地址(notify_url)不能为空");
        }

        // 构建请求体
        $reqParams = [
            'merchant_no'   => $this->merchantNo,
            'term_no'       => $this->termNo,
            'app_id'        => $this->appId,
            'out_trade_no'  => $params['out_trade_no'],
            'total_amount'  => (string) $params['total_amount'],
            'subject'       => $params['subject'],
            'notify_url'    => $notifyUrl,
        ];

        // 可选参数
        if (!empty($params['body'])) {
            $reqParams['body'] = $params['body'];
        }

        // 生成签名
        $sign = $this->generateSign($reqParams);

        // 构建完整请求体（V3风格：业务参数 + 签名信息）
        $requestBody = [
            'req_data' => json_encode($reqParams, JSON_UNESCAPED_UNICODE),
            'sign'     => $sign,
        ];

        // 发送请求
        $url = rtrim($this->gateway, '/') . '/trade/pay';
        $response = $this->httpPost($url, $requestBody);

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('响应JSON解析失败：' . json_last_error_msg());
        }

        if (!isset($result['ret_code']) || $result['ret_code'] !== 'SUCCESS') {
            $errMsg = $result['ret_msg'] ?? '未知错误';
            throw new \RuntimeException("拉卡拉下单失败：{$errMsg}");
        }

        // 验签响应
        if (isset($result['sign'])) {
            $respData = $result['resp_data'] ?? '';
            if (!$this->rsaVerify($respData, $result['sign'])) {
                throw new \RuntimeException('拉卡拉响应验签失败');
            }
        }

        // 解析返回的支付参数
        $respData = json_decode($result['resp_data'] ?? '{}', true);

        return [
            'order_no'     => $params['out_trade_no'],
            'trade_no'     => $respData['trade_no'] ?? '',
            'pay_params'   => $respData['pay_params'] ?? [],
            'pay_url'      => $respData['pay_url'] ?? '',
            'qr_code'      => $respData['qr_code'] ?? '',
            'raw_response' => $respData,
        ];
    }

    /**
     * 查询订单
     *
     * @param string $outTradeNo 商户订单号
     * @return array|null 查询结果数组，失败返回null
     * @throws \RuntimeException 查询失败时抛出异常
     */
    public function queryOrder(string $outTradeNo): ?array
    {
        try {
            $reqParams = [
                'merchant_no'  => $this->merchantNo,
                'term_no'      => $this->termNo,
                'app_id'       => $this->appId,
                'out_trade_no' => $outTradeNo,
            ];

            $sign = $this->generateSign($reqParams);

            $requestBody = [
                'req_data' => json_encode($reqParams, JSON_UNESCAPED_UNICODE),
                'sign'     => $sign,
            ];

            $url = rtrim($this->gateway, '/') . '/trade/query';
            $response = $this->httpPost($url, $requestBody);

            $result = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            if (!isset($result['ret_code']) || $result['ret_code'] !== 'SUCCESS') {
                return null;
            }

            // 验签响应
            if (isset($result['sign'])) {
                $respData = $result['resp_data'] ?? '';
                if (!$this->rsaVerify($respData, $result['sign'])) {
                    return null;
                }
            }

            return json_decode($result['resp_data'] ?? '{}', true);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 申请退款
     *
     * @param string $outTradeNo   商户订单号
     * @param int    $refundAmount 退款金额，单位：分
     * @return array 退款结果数组
     * @throws \InvalidArgumentException 参数缺失时抛出异常
     * @throws \RuntimeException 退款失败时抛出异常
     */
    public function refund(string $outTradeNo, int $refundAmount): array
    {
        if ($refundAmount <= 0) {
            throw new \InvalidArgumentException('退款金额必须大于0');
        }

        $reqParams = [
            'merchant_no'   => $this->merchantNo,
            'term_no'       => $this->termNo,
            'app_id'        => $this->appId,
            'out_trade_no'  => $outTradeNo,
            'refund_amount' => (string) $refundAmount,
        ];

        $sign = $this->generateSign($reqParams);

        $requestBody = [
            'req_data' => json_encode($reqParams, JSON_UNESCAPED_UNICODE),
            'sign'     => $sign,
        ];

        $url = rtrim($this->gateway, '/') . '/trade/refund';
        $response = $this->httpPost($url, $requestBody);

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('响应JSON解析失败：' . json_last_error_msg());
        }

        if (!isset($result['ret_code']) || $result['ret_code'] !== 'SUCCESS') {
            $errMsg = $result['ret_msg'] ?? '未知错误';
            throw new \RuntimeException("拉卡拉退款失败：{$errMsg}");
        }

        // 验签响应
        if (isset($result['sign'])) {
            $respData = $result['resp_data'] ?? '';
            if (!$this->rsaVerify($respData, $result['sign'])) {
                throw new \RuntimeException('拉卡拉退款响应验签失败');
            }
        }

        return json_decode($result['resp_data'] ?? '{}', true);
    }

    /**
     * 验证异步回调签名
     *
     * @param array $data 回调数据（通常为 $_POST 或 JSON body）
     * @return bool 验签是否通过
     */
    public function verifyNotify(array $data): bool
    {
        if (empty($data['sign'])) {
            return false;
        }

        $sign = $data['sign'];

        // 回调中的业务数据
        $respData = $data['resp_data'] ?? $data['req_data'] ?? '';

        // 如果resp_data是JSON字符串，直接对字符串验签
        if (is_string($respData) && !empty($respData)) {
            return $this->rsaVerify($respData, $sign);
        }

        // 如果是数组，先JSON编码再验签
        if (is_array($respData)) {
            $jsonStr = json_encode($respData, JSON_UNESCAPED_UNICODE);
            return $this->rsaVerify($jsonStr, $sign);
        }

        return false;
    }

    /**
     * 生成签名
     *
     * 步骤：
     * 1. 过滤空值参数
     * 2. 按key的ASCII升序排列
     * 3. 拼接成 key=value&key=value 格式
     * 4. 使用商户私钥进行RSA签名
     *
     * @param array $params 待签名参数
     * @return string Base64编码的签名值
     * @throws \RuntimeException 签名失败时抛出异常
     */
    public function generateSign(array $params): string
    {
        // 过滤空值
        $filtered = [];
        foreach ($params as $key => $value) {
            if ($value !== '' && $value !== null) {
                $filtered[$key] = $value;
            }
        }

        // 按key的ASCII升序排列
        ksort($filtered);

        // 拼接成 key=value&key=value 格式
        $stringToSign = '';
        foreach ($filtered as $key => $value) {
            if ($stringToSign !== '') {
                $stringToSign .= '&';
            }
            $stringToSign .= $key . '=' . $value;
        }

        return $this->rsaSign($stringToSign);
    }

    /**
     * 验证签名
     *
     * @param array  $params 业务参数
     * @param string $sign   待验证的签名值
     * @return bool 验签是否通过
     */
    public function verifySign(array $params, string $sign): bool
    {
        // 过滤空值
        $filtered = [];
        foreach ($params as $key => $value) {
            if ($value !== '' && $value !== null && $key !== 'sign') {
                $filtered[$key] = $value;
            }
        }

        // 按key的ASCII升序排列
        ksort($filtered);

        // 拼接成 key=value&key=value 格式
        $stringToSign = '';
        foreach ($filtered as $key => $value) {
            if ($stringToSign !== '') {
                $stringToSign .= '&';
            }
            $stringToSign .= $key . '=' . $value;
        }

        return $this->rsaVerify($stringToSign, $sign);
    }

    /**
     * RSA签名（SHA256WithRSA）
     *
     * @param string $data 待签名字符串
     * @return string Base64编码的签名值
     * @throws \RuntimeException 签名失败时抛出异常
     */
    public function rsaSign(string $data): string
    {
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($this->privateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        $result = openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$result) {
            throw new \RuntimeException('RSA签名失败：' . openssl_error_string());
        }

        return base64_encode($signature);
    }

    /**
     * RSA验签（SHA256WithRSA）
     *
     * @param string $data 待验签字符串
     * @param string $sign Base64编码的签名值
     * @return bool 验签是否通过
     */
    public function rsaVerify(string $data, string $sign): bool
    {
        $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($this->publicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

        $signature = base64_decode($sign);

        if ($signature === false) {
            return false;
        }

        $result = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256);

        if ($result === -1) {
            return false;
        }

        return $result === 1;
    }

    /**
     * 发送HTTP POST请求
     *
     * @param string $url    请求地址
     * @param array  $data   POST数据（将以JSON格式发送）
     * @param int    $timeout 超时时间（秒），默认使用构造时的配置
     * @return string 响应内容
     * @throws \RuntimeException cURL请求失败时抛出异常
     */
    public function httpPost(string $url, array $data, int $timeout = 0): string
    {
        if ($timeout <= 0) {
            $timeout = $this->timeout;
        }

        $ch = curl_init();

        try {
            $jsonPayload = json_encode($data, JSON_UNESCAPED_UNICODE);

            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $jsonPayload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HEADER         => false,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new \RuntimeException('cURL请求失败：' . curl_error($ch));
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode !== 200) {
                throw new \RuntimeException("HTTP请求失败，状态码：{$httpCode}，响应：{$response}");
            }

            return $response;
        } finally {
            curl_close($ch);
        }
    }
}
