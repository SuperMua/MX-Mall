-- ============================================
-- MX-Mall 数据库安装脚本
-- 版本: 1.0.0
-- 创建日期: 2026-04-16
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 创建数据库（如不存在）
-- ----------------------------
CREATE DATABASE IF NOT EXISTS `mx_mall` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mx_mall`;

-- ----------------------------
-- 表: admins（后台管理员）
-- ----------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL COMMENT '管理员用户名',
    `password` VARCHAR(255) NOT NULL COMMENT '密码（password_hash存储）',
    `nickname` VARCHAR(50) DEFAULT NULL COMMENT '昵称',
    `avatar` VARCHAR(255) DEFAULT NULL COMMENT '头像URL',
    `role` ENUM('super','admin') DEFAULT 'admin' COMMENT '角色: super超级管理员, admin普通管理员',
    `status` TINYINT(1) DEFAULT 1 COMMENT '状态: 1启用 0禁用',
    `last_login` DATETIME DEFAULT NULL COMMENT '最后登录时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台管理员表';

-- ----------------------------
-- 表: user_groups（用户分组）
-- ----------------------------
DROP TABLE IF EXISTS `user_groups`;
CREATE TABLE `user_groups` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '分组名称',
    `commission_rate` DECIMAL(5,2) DEFAULT 0.00 COMMENT '佣金比例(%)',
    `price` DECIMAL(10,2) DEFAULT 0.00 COMMENT '购买价格(0为免费/不可购买)',
    `is_default` TINYINT(1) DEFAULT 0 COMMENT '是否默认分组: 0否 1是',
    `sort_order` INT DEFAULT 0 COMMENT '排序（越小越靠前）',
    `status` TINYINT(1) DEFAULT 1 COMMENT '状态: 1启用 0禁用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户分组表';

-- ----------------------------
-- 表: users（前台用户/付款人）
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `openid` VARCHAR(100) DEFAULT NULL COMMENT '微信openid',
    `username` VARCHAR(50) DEFAULT NULL COMMENT '用户名/账号',
    `nickname` VARCHAR(50) DEFAULT NULL COMMENT '昵称',
    `avatar` VARCHAR(255) DEFAULT NULL COMMENT '头像URL',
    `phone` VARCHAR(20) DEFAULT NULL COMMENT '手机号',
    `password` VARCHAR(255) DEFAULT NULL COMMENT '登录密码',
    `status` TINYINT(1) DEFAULT 1 COMMENT '状态: 1正常 0禁用',
    `review_status` TINYINT(1) DEFAULT 0 COMMENT '审核状态: 0待审核 1已通过 2已拒绝',
    `balance` DECIMAL(10,2) DEFAULT 0.00 COMMENT '用户余额',
    `frozen_balance` DECIMAL(10,2) DEFAULT 0.00 COMMENT '冻结余额',
    `group_id` INT DEFAULT 0 COMMENT '用户分组ID',
    `referrer_id` INT DEFAULT 0 COMMENT '推荐人用户ID',
    `is_merchant` TINYINT(1) DEFAULT 0 COMMENT '是否商户: 0否 1是',
    `merchant_status` TINYINT(1) DEFAULT 0 COMMENT '商户审核: 0未申请 1待审核 2已通过 3已拒绝',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='前台用户表';

-- ----------------------------
-- 表: categories（商品分类）
-- ----------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '分类名称',
    `icon` VARCHAR(255) DEFAULT NULL COMMENT '分类图标',
    `sort_order` INT DEFAULT 0 COMMENT '排序（越小越靠前）',
    `status` TINYINT(1) DEFAULT 1 COMMENT '状态: 1启用 0禁用'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商品分类表';

-- ----------------------------
-- 表: products（商品）
-- ----------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL COMMENT '商品名称',
    `image` VARCHAR(500) DEFAULT NULL COMMENT '主图URL',
    `images` TEXT DEFAULT NULL COMMENT '多图JSON数组',
    `price` DECIMAL(10,2) NOT NULL COMMENT '售价',
    `original_price` DECIMAL(10,2) DEFAULT NULL COMMENT '原价',
    `description` TEXT DEFAULT NULL COMMENT '商品描述',
    `category_id` INT DEFAULT 0 COMMENT '分类ID',
    `sort_order` INT DEFAULT 0 COMMENT '排序（越小越靠前）',
    `status` TINYINT(1) DEFAULT 1 COMMENT '状态: 1上架 0下架',
    `sales_count` INT DEFAULT 0 COMMENT '销量',
    `user_id` INT DEFAULT 0 COMMENT '上架用户ID（0表示管理员上架）',
    `shop_name` VARCHAR(200) DEFAULT NULL COMMENT '店铺名称（收银台模板兼容）',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商品表';

-- ----------------------------
-- 表: orders（订单）
-- ----------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `out_trade_no` VARCHAR(64) UNIQUE NOT NULL COMMENT '商户订单号',
    `trade_no` VARCHAR(64) DEFAULT NULL COMMENT '第三方交易号',
    `user_id` INT DEFAULT 0 COMMENT '下单用户ID（0表示游客）',
    `product_id` INT DEFAULT 0 COMMENT '商品ID（单商品订单）',
    `product_name` VARCHAR(200) DEFAULT NULL COMMENT '商品名称',
    `product_image` VARCHAR(500) DEFAULT NULL COMMENT '商品图片',
    `product_price` DECIMAL(10,2) DEFAULT NULL COMMENT '商品单价',
    `items` TEXT DEFAULT NULL COMMENT '多商品JSON: [{id,name,image,price,qty}]',
    `money` DECIMAL(10,2) NOT NULL COMMENT '实付金额',
    `cashier_tpl` VARCHAR(50) DEFAULT NULL COMMENT '收银台模版标识',
    `pay_type` VARCHAR(30) DEFAULT NULL COMMENT '支付方式: epay/lakala',
    `pay_channel` VARCHAR(30) DEFAULT NULL COMMENT '支付渠道: wxpay/alipay',
    `status` TINYINT(1) DEFAULT 0 COMMENT '状态: 0待支付 1已支付 2已退款 3已过期',
    `pay_time` DATETIME DEFAULT NULL COMMENT '支付时间',
    `expire_time` DATETIME DEFAULT NULL COMMENT '过期时间',
    `notify_data` TEXT DEFAULT NULL COMMENT '支付回调原始数据',
    `share_user_id` INT DEFAULT 0 COMMENT '分享者用户ID（代付场景）',
    `is_paid_for` TINYINT(1) DEFAULT 0 COMMENT '是否代付订单: 0否 1是',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX `idx_out_trade_no` (`out_trade_no`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单表';

-- ----------------------------
-- 表: cashier_templates（收银台模版配置）
-- ----------------------------
DROP TABLE IF EXISTS `cashier_templates`;
CREATE TABLE `cashier_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tpl_id` VARCHAR(50) UNIQUE NOT NULL COMMENT '模版标识（如 meituan, jd）',
    `name` VARCHAR(50) NOT NULL COMMENT '显示名称（如 美团外卖）',
    `color` VARCHAR(20) DEFAULT '#6c5ce7' COMMENT '品牌主色',
    `icon` VARCHAR(100) DEFAULT NULL COMMENT '图标class',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '模版描述',
    `slogan` VARCHAR(200) DEFAULT NULL COMMENT '模版标语',
    `nick` VARCHAR(50) DEFAULT NULL COMMENT '默认昵称',
    `status` TINYINT(1) DEFAULT 1 COMMENT '状态: 1启用 0禁用',
    `sort_order` INT DEFAULT 0 COMMENT '排序（越小越靠前）',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='收银台模版配置表';

-- ----------------------------
-- 表: withdrawals（提现记录）
-- ----------------------------
DROP TABLE IF EXISTS `withdrawals`;
CREATE TABLE `withdrawals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL COMMENT '用户ID',
    `out_trade_no` VARCHAR(64) UNIQUE NOT NULL COMMENT '提现单号',
    `amount` DECIMAL(10,2) NOT NULL COMMENT '提现金额',
    `qr_code` VARCHAR(500) DEFAULT NULL COMMENT '收款码图片',
    `real_name` VARCHAR(50) DEFAULT NULL COMMENT '真实姓名',
    `status` TINYINT(1) DEFAULT 0 COMMENT '状态: 0待审核 1已通过 2已拒绝 3已打款',
    `admin_note` VARCHAR(500) DEFAULT NULL COMMENT '管理员备注',
    `review_time` DATETIME DEFAULT NULL COMMENT '审核时间',
    `pay_time` DATETIME DEFAULT NULL COMMENT '打款时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '申请时间',
    `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='提现记录表';

-- ----------------------------
-- 表: system_config（系统配置）
-- ----------------------------
DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `config_key` VARCHAR(50) UNIQUE NOT NULL COMMENT '配置键',
    `config_value` TEXT DEFAULT NULL COMMENT '配置值',
    `config_group` VARCHAR(30) DEFAULT 'general' COMMENT '配置分组: general/pay_epay/pay_lakala/site',
    `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- ----------------------------
-- 表: payment_logs（支付日志）
-- ----------------------------
DROP TABLE IF EXISTS `payment_logs`;
CREATE TABLE `payment_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT DEFAULT NULL COMMENT '关联订单ID',
    `out_trade_no` VARCHAR(64) DEFAULT NULL COMMENT '商户订单号',
    `pay_type` VARCHAR(30) DEFAULT NULL COMMENT '支付方式: epay/lakala',
    `pay_channel` VARCHAR(30) DEFAULT NULL COMMENT '支付渠道: wxpay/alipay',
    `request_data` TEXT DEFAULT NULL COMMENT '请求数据',
    `response_data` TEXT DEFAULT NULL COMMENT '响应数据',
    `status` TINYINT(1) DEFAULT 0 COMMENT '状态: 0处理中 1成功 2失败',
    `error_msg` VARCHAR(500) DEFAULT NULL COMMENT '错误信息',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX `idx_out_trade_no` (`out_trade_no`),
    INDEX `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付日志表';

-- ============================================
-- 默认数据插入
-- ============================================

-- ----------------------------
-- 默认用户分组
-- ----------------------------
INSERT INTO `user_groups` (`name`, `commission_rate`, `price`, `is_default`, `sort_order`, `status`) VALUES
('普通用户', 10.00, 0.00, 1, 1, 1),
('黄金用户', 5.00, 99.00, 0, 2, 1),
('钻石用户', 0.00, 299.00, 0, 3, 1);

-- ----------------------------
-- 默认管理员（用户名: admin, 密码: admin123）
-- password_hash('admin123', PASSWORD_DEFAULT) 的结果
-- ----------------------------
INSERT INTO `admins` (`username`, `password`, `nickname`, `role`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '超级管理员', 'super', 1);

-- ----------------------------
-- 默认系统配置
-- ----------------------------
INSERT INTO `system_config` (`config_key`, `config_value`, `config_group`) VALUES
('site_name', '刀客源码网', 'site'),
('site_logo', '', 'site'),
('site_description', '智能收银台商城系统', 'site'),
('epay_url', '', 'pay_epay'),
('epay_pid', '', 'pay_epay'),
('epay_secret', '', 'pay_epay'),
('epay_notify_url', '', 'pay_epay'),
('epay_return_url', '', 'pay_epay'),
('lakala_app_id', '', 'pay_lakala'),
('lakala_private_key', '', 'pay_lakala'),
('lakala_public_key', '', 'pay_lakala'),
('lakala_notify_url', '', 'pay_lakala'),
('lakala_sandbox', '0', 'pay_lakala'),
('pay_channel', 'epay', 'general'),
('epay_api', '', 'pay_epay'),
('epay_id', '', 'pay_epay'),
('epay_key', '', 'pay_epay'),
('wxpay_appid', '', 'pay_wxpay'),
('wxpay_secret', '', 'pay_wxpay'),
('wxpay_mchid', '', 'pay_wxpay'),
('wxpay_key', '', 'pay_wxpay'),
('ysm_api', 'https://www.yishoumi.cn/u/payment', 'pay_ysm'),
('ysm_id', '', 'pay_ysm'),
('ysm_key', '', 'pay_ysm'),
('ysm_pay_type', '0', 'pay_ysm'),
('pay_manual_qrcode', '', 'pay_manual');

-- ----------------------------
-- 14个收银台模版
-- ----------------------------
INSERT INTO `cashier_templates` (`tpl_id`, `name`, `color`, `icon`, `description`, `slogan`, `nick`, `status`, `sort_order`) VALUES
('meituan', '美团外卖', '#FFD100', 'icon-meituan', '美团外卖风格收银台', '美团外卖，送啥都快', '美团用户', 1, 1),
('jd', '京东', '#E2231A', 'icon-jd', '京东风格收银台', '多快好省，只为品质生活', '京东用户', 1, 2),
('ctrip-flight', '携程机票', '#003580', 'icon-ctrip', '携程机票风格收银台', '携程在手，说走就走', '携程用户', 1, 3),
('didi', '滴滴出行', '#FF6B00', 'icon-didi', '滴滴出行风格收银台', '滴滴一下，美好出行', '滴滴用户', 1, 4),
('pdd', '拼多多', '#E02E24', 'icon-pdd', '拼多多风格收银台', '拼得多，省得多', '拼多多用户', 1, 5),
('taobao', '淘宝', '#FF5000', 'icon-taobao', '淘宝风格收银台', '太好逛了吧', '淘宝用户', 1, 6),
('ctrip-hotel', '携程酒店', '#003580', 'icon-ctrip-hotel', '携程酒店风格收银台', '全球精选酒店，低价有保障', '携程用户', 1, 7),
('fliggy', '飞猪', '#FF6600', 'icon-fliggy', '飞猪风格收银台', '享受大不同', '飞猪用户', 1, 8),
('dewu', '得物', '#000000', 'icon-dewu', '得物风格收银台', '有物，有生活', '得物用户', 1, 9),
('maoyan', '猫眼', '#E4393C', 'icon-maoyan', '猫眼风格收银台', '看电影，上猫眼', '猫眼用户', 1, 10),
('taobao2', '淘宝好物', '#FF5000', 'icon-taobao2', '淘宝好物风格收银台', '发现好物，品质生活', '淘宝用户', 1, 11),
('douyin', '抖音', '#161823', 'icon-douyin', '抖音风格收银台', '记录美好生活', '抖音用户', 1, 12),
('didi2', '滴滴Pro', '#FF6B00', 'icon-didi2', '滴滴Pro风格收银台', '品质出行，滴滴Pro', '滴滴用户', 1, 13),
('xianyu', '闲鱼', '#FFE000', 'icon-xianyu', '闲鱼风格收银台', '闲不住？上闲鱼！', '闲鱼用户', 1, 14);

-- ----------------------------
-- 默认分类
-- ----------------------------
INSERT INTO `categories` (`name`, `icon`, `sort_order`, `status`) VALUES
('数码电子', 'icon-digital', 1, 1),
('生活服务', 'icon-life', 2, 1),
('出行旅游', 'icon-travel', 3, 1),
('影视娱乐', 'icon-entertainment', 4, 1);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- 安装完成
-- ============================================
