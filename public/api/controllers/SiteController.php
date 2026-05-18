<?php
/**
 * MX-Mall - 站点公开配置控制器
 *
 * 提供无需认证的公开站点配置接口（站点名称、轮播图等）
 */

require_once __DIR__ . '/BaseController.php';

class SiteController extends BaseController
{
    /**
     * 获取公开站点配置
     * GET /api/site/config
     */
    public function publicConfig(): void
    {
        $db = DB::getInstance();
        $rows = $db->getAll(
            "SELECT config_key, config_value FROM system_config WHERE config_key IN ('site_name', 'site_subtitle', 'site_banner_1', 'site_banner_2', 'site_banner_3')"
        );
        $config = [];
        foreach ($rows as $row) {
            $config[$row['config_key']] = $row['config_value'];
        }
        $this->jsonSuccess($config);
    }
}
