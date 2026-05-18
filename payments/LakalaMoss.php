<?php
/**
 * 拉卡拉MOSS支付封装类
 *
 * 基于MOSS V3 API，RSA加密+签名。
 * 对齐易支付插件 lakalamoss 的实现逻辑。
 * 平台公钥为固定值，用户只需配置 APPID、商户ID、客户私钥。
 */
namespace NexusMall\Payments;

class LakalaMoss
{
    /** @var string MOSS平台固定公钥 */
    private static $platformPublicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQD3E6H3qfgqF7aKypmSuzMIRuL/pRFMzsyqMlSEzzo2aJqN7w8Lb2tfVRfnAUVKMFyDxUzNWER4E/UfR4ymo0YHOaiIJI3AHWdJngJyGgK+SfvYDs9rqC++yisrzYv/TN3fZ93Ru1YWOYi4x4lBSCC9UX2b28hwx32MpJHT7gIrMQIDAQAB';

    /** @var string API网关地址 */
    private static $gatewayUrl = 'https://moss.lakala.com/ord-api/unified/v3';

    /** @var string APPID（业务渠道号/reqId） */
    private $appid;

    /** @var string 商户号 */
    private $merNo;

    /** @var resource 平台公钥资源 */
    private $platformPubKey;

    /** @var resource 客户私钥资源 */
    private $mchPrivateKey;

    /** @var string 异步通知地址 */
    private $notifyUrl;

    /** @var int HTTP超时（秒） */
    private $timeout = 30;

    /**
     * 构造函数
     *
     * @param array $config 配置数组：
     *   - moss_appid     APPID/业务渠道号（必填）
     *   - moss_mer_no    商户号（必填）
     *   - moss_private_key 客户私钥（必填，Base64编码，可含或不含PEM头尾）
     *   - notify_url     异步通知地址（可选）
     */
    public function __construct(array $config)
    {
        $required = ['moss_appid', 'moss_mer_no', 'moss_private_key'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new \InvalidArgumentException("MOSS支付配置缺少必填项：{$key}");
            }
        }

        $this->appid = $config['moss_appid'];
        $this->merNo = $config['moss_mer_no'];
        $this->notifyUrl = $config['notify_url'] ?? '';

        // 加载平台公钥（固定值）
        $this->platformPubKey = $this->loadPublicKey(self::$platformPublicKey);

        // 加载客户私钥
        $this->mchPrivateKey = $this->loadPrivateKey($config['moss_private_key']);

        if (!empty($config['timeout'])) {
            $this->timeout = (int) $config['timeout'];
        }
    }

    /**
     * 统一下单（收银台模式）
     *
     * @param array $params 下单参数：
     *   - out_trade_no  商户订单号（必填）
     *   - total_amount  金额，单位：分（必填）
     *   - subject       商品标题（必填）
     *   - notify_url    异步通知地址（可选）
     *   - callback_url  支付完成跳转地址（可选）
     *   - account_type  支付方式（可选，如 ALIPAY,WECHAT,UQRCODEPAY）
     *   - order_eff_time 订单有效时间-秒（可选）
     * @return array 含 pay_url/counter_url 的数组
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

        // 构建业务参数
        $bizParams = [
            'mer_no'       => $this->merNo,
            'order_no'     => $params['out_trade_no'],
            'total_amount' => strval(round((float) $params['total_amount'])),
            'pay_scene'    => '0',
            'subject'      => $params['subject'],
            'notify_url'   => $notifyUrl,
        ];

        // 支付完成跳转地址
        if (!empty($params['callback_url'])) {
            $bizParams['callback_url'] = $params['callback_url'];
        }

        // 指定收银台内展示的支付方式（可选）
        if (!empty($params['account_type'])) {
            $bizParams['account_type'] = $params['account_type'];
        }

        // 订单有效时间
        if (!empty($params['order_eff_time'])) {
            $bizParams['order_eff_time'] = (string) $params['order_eff_time'];
        }

        // 发送请求
        $result = $this->execute('lfops.moss.order.pay', $bizParams);

        return [
            'order_no'     => $params['out_trade_no'],
            'pay_serial'   => $result['pay_serial'] ?? '',
            'counter_url'  => $result['counter_url'] ?? '',
            'pay_url'      => $result['counter_url'] ?? '',
            'raw_response' => $result,
        ];
    }

    /**
     * 查询订单
     *
     * @param string $outTradeNo 商户订单号
     * @return array|null
     */
    public function queryOrder(string $outTradeNo): ?array
    {
        try {
            return $this->execute('lfops.moss.order.qry', [
                'order_no' => $outTradeNo,
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 验证异步回调通知
     *
     * @param string $rawBody 原始请求体（JSON字符串）
     * @return array|null 解密后的通知数据，验签失败返回null
     */
    public function verifyNotify(string $rawBody): ?array
    {
        $request = json_decode($rawBody, true);
        if (!$request || empty($request['sign'])) {
            return null;
        }

        // 验签
        if (!$this->verifySign($request)) {
            return null;
        }

        // 解密
        $decrypted = $this->rsaPrivateDecrypt($request['requestEncrypted'] ?? '');
        if ($decrypted === false) {
            return null;
        }

        return json_decode($decrypted, true);
    }

    /**
     * 构建回调成功响应（只需签名，不需要加密）
     *
     * @param array $head 原始回调请求中的head
     * @param bool  $success 是否成功
     * @param string $message 失败时的消息
     * @return string JSON响应字符串
     */
    public function buildNotifyResponse(array $head, bool $success = true, string $message = null): string
    {
        $params = [
            'head' => [
                'code'        => $success ? '000000' : '000001',
                'desc'        => $success ? 'success' : ($message ?: 'failure'),
                'serviceTime' => date('YmdHis'),
                'serviceSn'   => $head['serviceSn'] ?? '',
                'serviceId'   => $head['serviceId'] ?? '',
            ]
        ];

        $params['sign'] = $this->generateSign($params);

        return json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * 发起API请求（加密+签名+解密+验签）
     *
     * @param string $service 服务ID
     * @param array  $data    业务参数
     * @return array 解密后的响应数据
     */
    private function execute(string $service, array $data): array
    {
        $request = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $params = [
            'head' => [
                'versionId'       => '1.0',
                'serviceId'       => $service,
                'channelId'       => 'API',
                'requestTime'     => date('YmdHis'),
                'serviceSn'       => $this->generateSid(),
                'businessChannel' => $this->appid,
            ],
            'requestEncrypted' => $this->rsaPublicEncrypt($request),
        ];

        $params['sign'] = $this->generateSign($params);

        $body = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $response = $this->httpPost(self::$gatewayUrl, $body);

        $result = json_decode($response, true);

        if (isset($result['head']['code']) && $result['head']['code'] == '000000') {
            if (!$this->verifySign($result)) {
                throw new \RuntimeException('响应报文验签失败');
            }
            $decrypted = $this->rsaPrivateDecrypt($result['responseEncrypted'] ?? '');
            if ($decrypted === false) {
                throw new \RuntimeException('响应报文解密失败');
            }
            return json_decode($decrypted, true);
        } elseif (isset($result['head']['code'])) {
            throw new \RuntimeException('[' . $result['head']['code'] . '] ' . $result['head']['desc']);
        } else {
            throw new \RuntimeException('请求失败: ' . $response);
        }
    }

    /**
     * 构造签名内容
     */
    private function getSignContent(array $params): string
    {
        ksort($params);
        $signStr = '';
        foreach ($params as $k => $v) {
            if ($k !== 'sign' && $v !== null) {
                if (is_array($v)) {
                    ksort($v);
                    $v = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
                $signStr .= ($signStr ? '&' : '') . $k . '=' . $v;
            }
        }
        return $signStr;
    }

    /**
     * 生成签名
     */
    private function generateSign(array $params): string
    {
        return $this->rsaPrivateSign($this->getSignContent($params));
    }

    /**
     * 验证签名
     */
    private function verifySign(array $params): bool
    {
        if (empty($params['sign'])) return false;
        return $this->rsaPublicVerify($this->getSignContent($params), $params['sign']);
    }

    /**
     * 客户私钥签名
     */
    private function rsaPrivateSign(string $data): string
    {
        openssl_sign($data, $signature, $this->mchPrivateKey);
        return base64_encode($signature);
    }

    /**
     * 平台公钥验签
     */
    private function rsaPublicVerify(string $data, string $signature): bool
    {
        $result = openssl_verify($data, base64_decode($signature), $this->platformPubKey);
        return $result === 1;
    }

    /**
     * 平台公钥加密（分块）
     */
    private function rsaPublicEncrypt(string $data): string
    {
        $encrypted = '';
        $partLen = (int) (openssl_pkey_get_details($this->platformPubKey)['bits'] / 8) - 11;
        $plainData = str_split($data, $partLen);
        foreach ($plainData as $chunk) {
            $partial = '';
            if (!openssl_public_encrypt($chunk, $partial, $this->platformPubKey)) {
                throw new \RuntimeException('RSA加密失败');
            }
            $encrypted .= $partial;
        }
        return base64_encode($encrypted);
    }

    /**
     * 客户私钥解密（分块）
     */
    private function rsaPrivateDecrypt(string $data)
    {
        $decrypted = '';
        $partLen = (int) (openssl_pkey_get_details($this->mchPrivateKey)['bits'] / 8);
        $chunks = str_split(base64_decode($data), $partLen);
        foreach ($chunks as $chunk) {
            $partial = '';
            if (!openssl_private_decrypt($chunk, $partial, $this->mchPrivateKey)) {
                return false;
            }
            $decrypted .= $partial;
        }
        return $decrypted;
    }

    /**
     * 加载平台公钥
     */
    private function loadPublicKey(string $publicKeyStr)
    {
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($publicKeyStr, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        $key = openssl_pkey_get_public($res);
        if (!$key) {
            throw new \InvalidArgumentException('平台公钥不正确');
        }
        return $key;
    }

    /**
     * 加载客户私钥（兼容含/不含PEM头尾）
     */
    private function loadPrivateKey(string $privateKeyStr)
    {
        if (strpos($privateKeyStr, '-----BEGIN') === false) {
            $privateKeyStr = str_replace(["\n", "\r"], '', $privateKeyStr);
            $privateKeyStr = "-----BEGIN PRIVATE KEY-----\n" .
                wordwrap($privateKeyStr, 64, "\n", true) .
                "\n-----END PRIVATE KEY-----";
        }
        $key = openssl_pkey_get_private($privateKeyStr);
        if (!$key) {
            throw new \InvalidArgumentException('客户私钥不正确');
        }
        return $key;
    }

    /**
     * HTTP POST请求
     */
    private function httpPost(string $url, string $body): string
    {
        $ch = curl_init();
        try {
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HEADER         => false,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json; charset=utf-8',
                ],
            ]);
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new \RuntimeException('HTTP请求失败：' . curl_error($ch));
            }
            return $response ?: '';
        } finally {
            curl_close($ch);
        }
    }

    /**
     * 生成32位随机流水号
     */
    private function generateSid(): string
    {
        return sprintf(
            '%04x%04x%04x%04x%04x%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
