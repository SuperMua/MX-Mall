<?php
/**
 * MX-Mall - 管理员JWT认证中间件
 *
 * 从Authorization header中获取Bearer Token并验证
 * 验证通过后将管理员信息存入请求上下文
 */

require_once __DIR__ . '/../../../config/jwt.php';

class Auth
{
    /**
     * 当前认证的管理员信息
     *
     * @var array|null
     */
    private static ?array $admin = null;

    /**
     * 处理认证中间件
     *
     * @return bool 认证成功返回true，失败则终止请求并返回401
     */
    public static function handle(): bool
    {
        // 获取Authorization头
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($authHeader)) {
            self::unauthorized('缺少Authorization头');
            return false;
        }

        // 解析Bearer Token
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            self::unauthorized('Authorization格式错误，应为: Bearer {token}');
            return false;
        }

        $token = $matches[1];

        // 解码并验证Token
        $payload = JWT::decode($token);
        if ($payload === null) {
            self::unauthorized('Token无效或已过期');
            return false;
        }

        // 检查必要字段
        if (!isset($payload['admin_id']) || !isset($payload['username'])) {
            self::unauthorized('Token数据不完整');
            return false;
        }

        // 存储管理员信息
        self::$admin = [
            'id'       => $payload['admin_id'],
            'username' => $payload['username'],
            'nickname' => $payload['nickname'] ?? '',
            'role'     => $payload['role'] ?? 'admin',
        ];

        return true;
    }

    /**
     * 获取当前认证的管理员信息
     *
     * @return array|null
     */
    public static function admin(): ?array
    {
        return self::$admin;
    }

    /**
     * 获取当前管理员ID
     *
     * @return int
     */
    public static function adminId(): int
    {
        return self::$admin['id'] ?? 0;
    }

    /**
     * 返回401未授权响应
     *
     * @param string $msg 错误信息
     */
    private static function unauthorized(string $msg): void
    {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'code' => 401,
            'msg'  => $msg,
            'data' => null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
