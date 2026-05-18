<?php
/**
 * Template Base - 通用逻辑（所有收银台模板共享）
 *
 * 此文件由 cashier.php include 后的模板引用。
 * 所有变量已由 cashier.php 提供，此处只做状态计算和辅助函数。
 */

// 防止直接访问
if (empty($order) || !isset($order['id'])) {
    echo '<h3>订单不存在</h3>';
    exit;
}

// === 核心状态判定 ===
$is_paid = ((int)$order['status'] === 1);
$is_expired = (!$is_paid && $remaining_seconds <= 0);
$can_pay = (!$is_paid && !$is_expired);

// === 辅助函数 ===
function format_money($amount) {
    return number_format(floatval($amount), 2, '.', '');
}

// 格式化倒计时为 HH:MM:SS 或 MM:SS
function format_countdown_init($seconds, $showHours = false) {
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    if ($showHours || $h > 0) {
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
    return sprintf('%02d:%02d', $m, $s);
}

// 获取倒计时初始值（用于JS）
function countdown_init_values($seconds) {
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    return ['h' => $h, 'm' => $m, 's' => $s];
}
