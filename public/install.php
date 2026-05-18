<?php
/**
 * MX-Mall - 安装入口
 *
 * 从 public/ 目录可访问的安装脚本
 * 实际逻辑在上级目录的 install/install.php
 */

// 安装锁检测 - 已安装则跳转到后台
$lockFile = __DIR__ . '/../install/install.lock';
if (file_exists($lockFile)) {
    // 不返回403，直接跳转到后台
    header('Location: /admin.php');
    exit;
}

// 加载实际的安装脚本
require __DIR__ . '/../install/install.php';
