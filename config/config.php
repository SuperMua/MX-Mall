<?php
/**
 * MX-Mall - 全局配置文件
 *
 * 包含数据库、JWT、站点等核心配置项
 */

return [
    // ===== 数据库配置 =====
    'database' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'dbname'   => 'mx_mall',
        'username' => 'root',
        'password' => '123456',
        'charset'  => 'utf8mb4',
    ],

    // ===== JWT配置 =====
    'jwt' => [
        'secret'     => 'mx_mall_jwt_secret_key_2026',
        'issuer'     => 'mx-mall',
        'expire'     => 86400, // 默认过期时间：24小时（秒）
    ],

    // ===== 站点配置 =====
    'site' => [
        'name'        => 'MX-Mall',
        'url'         => '',
        'description' => '智能收银台商城系统',
    ],

    // ===== 支付配置 =====
    'payment' => [
        // 易支付
        'epay' => [
            'url'        => '',
            'pid'        => '',
            'secret'     => '',
            'notify_url' => '',
            'return_url' => '',
        ],
        // 拉卡拉
        'lakala' => [
            'app_id'       => '',
            'private_key'  => '',
            'public_key'   => '',
            'notify_url'   => '',
            'sandbox'      => false,
        ],
    ],

    // ===== 上传配置 =====
    'upload' => [
        'max_size'  => 5 * 1024 * 1024, // 5MB
        'allowed'   => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'path'      => '/uploads/',
    ],

    // ===== 分页配置 =====
    'page' => [
        'default_size' => 20,
        'max_size'     => 100,
    ],
];
