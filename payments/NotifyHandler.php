<?php
/**
 * 支付回调处理辅助类
 *
 * 统一处理易支付和拉卡拉的异步回调通知。
 * 职责：验签、更新订单状态、记录支付日志、返回success/fail给支付平台。
 */
namespace NexusMall\Payments;

class NotifyHandler
{
    /**
     * 处理易支付异步回调
     *
     * 流程：
     * 1. 使用Epay实例验证回调签名
     * 2. 检查订单是否已处理（防止重复通知）
     * 3. 更新订单状态为已支付
     * 4. 记录支付日志
     * 5. 输出 "success" 给支付平台
     *
     * @param mixed $db   数据库连接/PDO实例（需支持query和prepare方法）
     * @param Epay  $epay 易支付实例
     * @param array $data 回调数据（$_POST）
     * @return array 处理结果 ['success' => bool, 'message' => string, 'order_no' => string]
     */
    public static function handleEpayNotify($db, Epay $epay, array $data): array
    {
        try {
            // 1. 验签
            if (!$epay->verifyNotify($data)) {
                self::log($db, 'epay', $data['out_trade_no'] ?? '', 2, '签名验证失败', $data, null, null);
                echo 'fail';
                return [
                    'success'  => false,
                    'message'  => '签名验证失败',
                    'order_no' => $data['out_trade_no'] ?? '',
                ];
            }

            // 2. 提取业务参数
            $tradeStatus = $data['trade_status'] ?? '';
            $outTradeNo  = $data['out_trade_no'] ?? '';
            $tradeNo     = $data['trade_no'] ?? '';
            $type        = $data['type'] ?? '';
            $pid         = $data['pid'] ?? '';
            $money       = $data['money'] ?? '';
            $name        = $data['name'] ?? '';

            if (empty($outTradeNo)) {
                self::log($db, 'epay', '', 2, '缺少订单号', $data, null, null);
                echo 'fail';
                return [
                    'success'  => false,
                    'message'  => '缺少订单号',
                    'order_no' => '',
                ];
            }

            // 3. 检查订单是否已处理（防止重复通知）
            $orderId = null;
            if ($db !== null && method_exists($db, 'prepare')) {
                $stmt = $db->prepare("SELECT id, status FROM orders WHERE out_trade_no = :out_trade_no LIMIT 1");
                $stmt->execute([':out_trade_no' => $outTradeNo]);
                $order = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($order) {
                    $orderId = (int) $order['id'];
                    if ((int) $order['status'] === 1) {
                        // 订单已处理，直接返回success防止重复处理
                        self::log($db, 'epay', $outTradeNo, 1, '订单已处理，重复通知', $data, $orderId, $type);
                        echo 'success';
                        return [
                            'success'  => true,
                            'message'  => '订单已处理',
                            'order_no' => $outTradeNo,
                        ];
                    }
                }
            }

            // 4. 检查支付状态（易支付：TRADE_SUCCESS 表示支付成功）
            if ($tradeStatus !== 'TRADE_SUCCESS') {
                self::log($db, 'epay', $outTradeNo, 2, "支付状态非成功：{$tradeStatus}", $data, $orderId, $type);
                echo 'fail';
                return [
                    'success'  => false,
                    'message'  => "支付状态非成功：{$tradeStatus}",
                    'order_no' => $outTradeNo,
                ];
            }

            // 5. 更新订单状态
            if ($db !== null && method_exists($db, 'prepare')) {
                $stmt = $db->prepare(
                    "UPDATE orders SET status = 1, trade_no = :trade_no, pay_type = :pay_type, pay_time = NOW() WHERE out_trade_no = :out_trade_no AND status = 0"
                );
                $stmt->execute([
                    ':trade_no'     => $tradeNo,
                    ':pay_type'     => $type,
                    ':out_trade_no' => $outTradeNo,
                ]);
            }

            // 6. 记录支付日志
            self::log($db, 'epay', $outTradeNo, 1, '支付成功', $data, $orderId, $type);

            // 7. 返回success给支付平台
            echo 'success';

            return [
                'success'  => true,
                'message'  => '支付成功',
                'order_no' => $outTradeNo,
                'trade_no' => $tradeNo,
            ];

        } catch (\Throwable $e) {
            // 异常处理
            self::log($db, 'epay', $data['out_trade_no'] ?? '', 2, '处理异常：' . $e->getMessage(), $data, null, null);
            echo 'fail';

            return [
                'success'  => false,
                'message'  => '处理异常：' . $e->getMessage(),
                'order_no' => $data['out_trade_no'] ?? '',
            ];
        }
    }

    /**
     * 处理拉卡拉异步回调
     *
     * 流程：
     * 1. 使用Lakala实例验证回调签名
     * 2. 解析回调数据
     * 3. 检查订单是否已处理（防止重复通知）
     * 4. 更新订单状态为已支付
     * 5. 记录支付日志
     * 6. 返回JSON格式的成功/失败响应给支付平台
     *
     * @param mixed  $db     数据库连接/PDO实例
     * @param Lakala $lakala 拉卡拉支付实例
     * @param array  $data   回调数据（$_POST 或 JSON body 解析后的数组）
     * @return array 处理结果 ['success' => bool, 'message' => string, 'order_no' => string]
     */
    public static function handleLakalaNotify($db, Lakala $lakala, array $data): array
    {
        try {
            // 1. 验签
            if (!$lakala->verifyNotify($data)) {
                self::log($db, 'lakala', '', 2, '签名验证失败', $data, null, null);
                echo json_encode(['ret_code' => 'FAIL', 'ret_msg' => '签名验证失败']);
                return [
                    'success'  => false,
                    'message'  => '签名验证失败',
                    'order_no' => '',
                ];
            }

            // 2. 解析回调中的业务数据
            $respData = $data['resp_data'] ?? $data['req_data'] ?? '';
            if (is_string($respData)) {
                $respData = json_decode($respData, true);
            }

            if (!is_array($respData)) {
                self::log($db, 'lakala', '', 2, '回调数据解析失败', $data, null, null);
                echo json_encode(['ret_code' => 'FAIL', 'ret_msg' => '数据解析失败']);
                return [
                    'success'  => false,
                    'message'  => '回调数据解析失败',
                    'order_no' => '',
                ];
            }

            // 3. 提取业务参数
            $outTradeNo  = $respData['out_trade_no'] ?? '';
            $tradeNo     = $respData['trade_no'] ?? '';
            $tradeStatus = $respData['trade_status'] ?? '';
            $totalAmount = $respData['total_amount'] ?? '';

            if (empty($outTradeNo)) {
                self::log($db, 'lakala', '', 2, '缺少订单号', $data, null, null);
                echo json_encode(['ret_code' => 'FAIL', 'ret_msg' => '缺少订单号']);
                return [
                    'success'  => false,
                    'message'  => '缺少订单号',
                    'order_no' => '',
                ];
            }

            // 4. 检查订单是否已处理（防止重复通知）
            $orderId = null;
            if ($db !== null && method_exists($db, 'prepare')) {
                $stmt = $db->prepare("SELECT id, status FROM orders WHERE out_trade_no = :out_trade_no LIMIT 1");
                $stmt->execute([':out_trade_no' => $outTradeNo]);
                $order = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($order) {
                    $orderId = (int) $order['id'];
                    if ((int) $order['status'] === 1) {
                        self::log($db, 'lakala', $outTradeNo, 1, '订单已处理，重复通知', $data, $orderId, 'lakala');
                        echo json_encode(['ret_code' => 'SUCCESS', 'ret_msg' => '处理成功']);
                        return [
                            'success'  => true,
                            'message'  => '订单已处理',
                            'order_no' => $outTradeNo,
                        ];
                    }
                }
            }

            // 5. 检查支付状态（拉卡拉：SUCCESS / TRADE_SUCCESS 表示支付成功）
            $successStatuses = ['SUCCESS', 'TRADE_SUCCESS', 'PAY_SUCCESS'];
            if (!in_array($tradeStatus, $successStatuses, true)) {
                self::log($db, 'lakala', $outTradeNo, 2, "支付状态非成功：{$tradeStatus}", $data, $orderId, 'lakala');
                echo json_encode(['ret_code' => 'FAIL', 'ret_msg' => '支付状态非成功']);
                return [
                    'success'  => false,
                    'message'  => "支付状态非成功：{$tradeStatus}",
                    'order_no' => $outTradeNo,
                ];
            }

            // 6. 更新订单状态
            if ($db !== null && method_exists($db, 'prepare')) {
                $stmt = $db->prepare(
                    "UPDATE orders SET status = 1, trade_no = :trade_no, pay_type = 'lakala', pay_time = NOW() WHERE out_trade_no = :out_trade_no AND status = 0"
                );
                $stmt->execute([
                    ':trade_no'     => $tradeNo,
                    ':out_trade_no' => $outTradeNo,
                ]);
            }

            // 7. 记录支付日志
            self::log($db, 'lakala', $outTradeNo, 1, '支付成功', $data, $orderId, 'lakala');

            // 8. 返回成功响应给支付平台
            echo json_encode(['ret_code' => 'SUCCESS', 'ret_msg' => '处理成功']);

            return [
                'success'      => true,
                'message'      => '支付成功',
                'order_no'     => $outTradeNo,
                'trade_no'     => $tradeNo,
                'total_amount' => $totalAmount,
            ];

        } catch (\Throwable $e) {
            // 异常处理
            self::log($db, 'lakala', '', 2, '处理异常：' . $e->getMessage(), $data, null, null);
            echo json_encode(['ret_code' => 'FAIL', 'ret_msg' => '处理异常']);

            return [
                'success'  => false,
                'message'  => '处理异常：' . $e->getMessage(),
                'order_no' => '',
            ];
        }
    }

    /**
     * 记录支付日志
     *
     * 将回调处理结果写入支付日志表，便于排查问题。
     * 字段与 install.sql 中 payment_logs 表一致：
     *   order_id, out_trade_no, pay_type, pay_channel,
     *   request_data, response_data, status, error_msg, created_at
     *
     * @param mixed       $db         数据库连接/PDO实例（可为null，null时不记录）
     * @param string      $payType    支付方式：epay / lakala
     * @param string      $orderNo    商户订单号
     * @param int         $status     状态：0处理中 1成功 2失败
     * @param string      $errorMsg   错误信息（仅在出错时写入）
     * @param array       $rawData    原始回调数据（写入response_data）
     * @param int|null    $orderId    关联订单ID
     * @param string|null $payChannel 支付渠道：wxpay/alipay
     */
    private static function log($db, string $payType, string $orderNo, int $status, string $errorMsg, array $rawData, ?int $orderId, ?string $payChannel): void
    {
        // 如果没有数据库连接，使用 error_log 记录
        if ($db === null || !method_exists($db, 'prepare')) {
            error_log("[PayNotify][{$payType}][{$status}] order={$orderNo} msg={$errorMsg}");
            return;
        }

        try {
            $stmt = $db->prepare(
                "INSERT INTO payment_logs (order_id, out_trade_no, pay_type, pay_channel, response_data, status, error_msg, created_at)
                 VALUES (:order_id, :out_trade_no, :pay_type, :pay_channel, :response_data, :status, :error_msg, NOW())"
            );
            $stmt->execute([
                ':order_id'      => $orderId,
                ':out_trade_no'  => $orderNo,
                ':pay_type'      => $payType,
                ':pay_channel'   => $payChannel,
                ':response_data' => json_encode($rawData, JSON_UNESCAPED_UNICODE),
                ':status'        => $status,
                ':error_msg'     => $status === 2 ? $errorMsg : null,
            ]);
        } catch (\Throwable $e) {
            // 日志记录失败不应影响主流程
            error_log("[PayNotify][LogError] " . $e->getMessage());
        }
    }
}
