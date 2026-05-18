<?php
/**
 * MX-Mall - JWT工具类
 *
 * 简单的JSON Web Token实现（不依赖第三方库）
 * 使用HS256算法进行签名验证
 */

class JWT
{
    /**
     * 生成JWT Token
     *
     * @param array $payload 载荷数据
     * @param int   $exp     过期时间（秒），默认24小时
     * @return string JWT Token字符串
     */
    public static function encode(array $payload, int $exp = 86400): string
    {
        $config = require __DIR__ . '/config.php';
        $secret = $config['jwt']['secret'];
        $issuer = $config['jwt']['issuer'];

        // 构建Header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        // 构建Payload
        $now = time();
        $payload['iat'] = $now;           // 签发时间
        $payload['exp'] = $now + $exp;    // 过期时间
        $payload['iss'] = $issuer;        // 签发者

        // Base64Url编码
        $headerEncoded  = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        // 生成签名
        $signature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $secret, true);
        $signatureEncoded = self::base64UrlEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    /**
     * 解码并验证JWT Token
     *
     * @param string $token JWT Token字符串
     * @return array|null 验证成功返回payload数组，失败返回null
     */
    public static function decode(string $token): ?array
    {
        $config = require __DIR__ . '/config.php';
        $secret = $config['jwt']['secret'];

        // 分割Token
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // 验证签名
        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $secret, true);

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        // 解码Payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        if (!$payload) {
            return null;
        }

        // 验证过期时间
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    /**
     * Base64Url编码
     *
     * @param string $data 原始数据
     * @return string 编码后的字符串
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64Url解码
     *
     * @param string $data 编码后的字符串
     * @return string 解码后的原始数据
     */
    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
