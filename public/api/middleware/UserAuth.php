<?php
/**
 * MX-Mall - 前台用户JWT认证中间件
 *
 * 从Authorization header中获取Bearer Token并验证
 * 验证通过后将用户信息存入请求上下文
 */

require_once __DIR__ . '/../../../config/jwt.php';

class UserAuth
{
    /**
     * 当前认证的用户信息
     *
     * @var array|null
     */
    private static ?array $user = null;

    /**
     * 处理认证中间件
     *
     * @return bool 认证成功返回true，失败则终止请求并返回401
     */
    public static function handle(): bool
    {
        // 获取Authorization头（兼容多种写法）
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($authHeader)) {
            self::unauthorized('请先登录');
            return false;
        }

        // 解析Bearer Token
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            self::unauthorized('Authorization格式错误');
            return false;
        }

        $token = $matches[1];

        // 解码并验证Token
        $payload = JWT::decode($token);
        if ($payload === null) {
            self::unauthorized('登录已过期');
            return false;
        }

        // 检查必要字段（前台用户使用user_id）
        if (!isset($payload['user_id'])) {
            self::unauthorized('Token数据不完整');
            return false;
        }

        // 存储用户信息
        self::$user = [
            'id'       => $payload['user_id'],
            'phone'    => $payload['phone'] ?? '',
            'nickname' => $payload['nickname'] ?? '',
        ];

        // 将用户信息存入全局，方便控制器获取
        $_REQUEST['current_user'] = (object) self::$user;

        return true;
    }

    /**
     * 获取当前认证的用户信息
     *
     * @return array|null
     */
    public static function user(): ?array
    {
        return self::$user;
    }

    /**
     * 获取当前用户ID
     *
     * @return int
     */
    public static function userId(): int
    {
        return self::$user['id'] ?? 0;
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
            'code' => 1,
            'msg'  => $msg,
            'data' => null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
