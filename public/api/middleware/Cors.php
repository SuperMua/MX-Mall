<?php
/**
 * MX-Mall - CORS跨域中间件
 *
 * 处理跨域请求，设置响应头
 * 支持预检请求（OPTIONS）
 */

class Cors
{
    /**
     * 允许的来源（* 表示允许所有来源）
     * 生产环境建议设置为具体域名
     *
     * @var string
     */
    private static string $allowOrigin = '*';

    /**
     * 允许的HTTP方法
     *
     * @var string
     */
    private static string $allowMethods = 'GET, POST, PUT, DELETE, OPTIONS';

    /**
     * 允许的请求头
     *
     * @var string
     */
    private static string $allowHeaders = 'Content-Type, Authorization, X-Requested-With, Accept, Origin';

    /**
     * 预检请求缓存时间（秒）
     *
     * @var int
     */
    private static int $maxAge = 86400;

    /**
     * 是否允许携带凭证
     *
     * @var bool
     */
    private static bool $allowCredentials = false;

    /**
     * 处理CORS中间件
     * 设置跨域响应头，处理OPTIONS预检请求
     */
    public static function handle(): void
    {
        // 设置允许的来源
        if (self::$allowOrigin === '*') {
            header('Access-Control-Allow-Origin: *');
        } else {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if (in_array($origin, explode(',', self::$allowOrigin))) {
                header('Access-Control-Allow-Origin: ' . $origin);
            }
        }

        // 设置允许携带凭证
        if (self::$allowCredentials) {
            header('Access-Control-Allow-Credentials: true');
        }

        // 设置允许的方法
        header('Access-Control-Allow-Methods: ' . self::$allowMethods);

        // 设置允许的头
        header('Access-Control-Allow-Headers: ' . self::$allowHeaders);

        // 设置预检缓存时间
        header('Access-Control-Max-Age: ' . self::$maxAge);

        // 设置暴露的响应头（前端可以访问的自定义头）
        header('Access-Control-Expose-Headers: Authorization, Content-Disposition');

        // 处理OPTIONS预检请求
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
