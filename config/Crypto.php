<?php
/**
 * AES-256-CBC 对称加密工具
 * 密钥从 config.php 中读取，无需数据库
 */
class Crypto
{
    private static function getKeys(): array
    {
        static $keys = null;
        if ($keys === null) {
            // 从配置文件读取密钥
            $configFile = __DIR__ . '/config.php';
            $config = file_exists($configFile) ? include $configFile : [];
            $secret = $config['app_secret'] ?? 'mx_mall_default_secret_key_2024';
            // 派生 key(32字节) 和 iv(16字节)
            $keys = [
                'key' => substr(hash('sha256', $secret . '_aes_key', true), 0, 32),
                'iv'  => substr(hash('md5', $secret . '_aes_iv', true), 0, 16),
            ];
        }
        return $keys;
    }

    public static function encrypt(string $data): string
    {
        $k = self::getKeys();
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $k['key'], OPENSSL_RAW_DATA, $k['iv']);
        if ($encrypted === false) return '';
        return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
    }

    public static function decrypt(string $data): ?string
    {
        $k = self::getKeys();
        $decoded = base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
        if ($decoded === false) return null;
        $decrypted = openssl_decrypt($decoded, 'AES-256-CBC', $k['key'], OPENSSL_RAW_DATA, $k['iv']);
        return $decrypted !== false ? $decrypted : null;
    }
}
