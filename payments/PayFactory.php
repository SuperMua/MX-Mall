<?php
/**
 * 支付工厂类
 *
 * 根据支付类型创建对应的支付实例，实现简单的工厂模式。
 * 支持的支付类型：epay（易支付）、lakala（拉卡拉）
 */
namespace NexusMall\Payments;

class PayFactory
{
    /**
     * 支付类型与类名的映射
     */
    private static $map = [
        'epay'      => Epay::class,
        'lakala'    => Lakala::class,
        'lakala_moss' => LakalaMoss::class,
    ];

    /**
     * 创建支付实例
     *
     * @param string $type   支付类型：'epay' 或 'lakala'
     * @param array  $config 对应支付渠道的配置数组
     * @return Epay|Lakala 支付实例
     * @throws \InvalidArgumentException 不支持的支付类型时抛出异常
     */
    public static function create(string $type, array $config)
    {
        $type = strtolower(trim($type));

        if (!isset(self::$map[$type])) {
            $supported = implode(', ', array_keys(self::$map));
            throw new \InvalidArgumentException("不支持的支付类型：{$type}，支持的类型：{$supported}");
        }

        $className = self::$map[$type];

        return new $className($config);
    }

    /**
     * 注册自定义支付类型
     *
     * 允许扩展新的支付渠道。
     *
     * @param string $type      支付类型标识
     * @param string $className 类名（必须实现构造函数接收config数组）
     * @throws \InvalidArgumentException 类不存在时抛出异常
     */
    public static function register(string $type, string $className): void
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("支付类不存在：{$className}");
        }

        self::$map[strtolower(trim($type))] = $className;
    }

    /**
     * 获取所有已注册的支付类型
     *
     * @return array 支付类型列表
     */
    public static function getSupportedTypes(): array
    {
        return array_keys(self::$map);
    }
}
