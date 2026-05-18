<?php
/**
 * MX-Mall - 基础控制器
 *
 * 所有控制器的父类，提供统一的JSON响应方法
 * 统一响应格式: {code: 0/1, msg: '', data: {}}
 */

class BaseController
{
    /**
     * 返回成功响应
     *
     * @param mixed  $data 返回数据
     * @param string $msg  提示信息
     * @return void
     */
    protected function jsonSuccess($data = null, string $msg = 'success'): void
    {
        $this->outputJson(0, $msg, $data);
    }

    /**
     * 返回错误响应
     *
     * @param string $msg  错误信息
     * @param int    $code 错误码（默认1）
     * @param mixed  $data 附加数据
     * @return void
     */
    protected function jsonError(string $msg = 'error', int $code = 1, $data = null): void
    {
        $this->outputJson($code, $msg, $data);
    }

    /**
     * 输出JSON响应
     *
     * @param int    $code 状态码（0成功，非0失败）
     * @param string $msg  提示信息
     * @param mixed  $data 返回数据
     * @return void
     */
    private function outputJson(int $code, string $msg, $data): void
    {
        // 清理所有输出缓冲，防止PHP警告等污染JSON响应
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    /**
     * 获取请求参数（支持GET/POST/JSON Body）
     *
     * @param string $key     参数名
     * @param mixed  $default 默认值
     * @return mixed
     */
    protected function input(string $key, $default = null)
    {
        // 优先从POST获取
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }

        // 从GET获取
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }

        // 从JSON Body获取
        $json = $this->getJsonInput();
        if ($json !== null && isset($json[$key])) {
            return $json[$key];
        }

        return $default;
    }

    /**
     * 获取所有输入参数（合并GET/POST/JSON Body）
     *
     * @return array
     */
    protected function allInput(): array
    {
        $json = $this->getJsonInput();
        return array_merge($_GET, $_POST, $json ?: []);
    }

    /**
     * 获取JSON请求体
     *
     * @return array|null
     */
    protected function getJsonInput(): ?array
    {
        static $json = null;
        if ($json === null) {
            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            if (!is_array($json)) {
                $json = null;
            }
        }
        return $json;
    }

    /**
     * 获取分页参数
     *
     * @return array [page, pageSize]
     */
    protected function getPageParams(): array
    {
        $config = require __DIR__ . '/../../../config/config.php';
        $defaultSize = $config['page']['default_size'];
        $maxSize = $config['page']['max_size'];

        $page = max(1, (int)($this->input('page', 1)));
        $pageSize = max(1, min((int)($this->input('page_size', $defaultSize)), $maxSize));

        return [$page, $pageSize];
    }
}
