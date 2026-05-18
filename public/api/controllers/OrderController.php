<?php
/**
 * MX-Mall - 订单控制器
 *
 * 处理前台订单创建和订单详情查询
 */

require_once __DIR__ . '/BaseController.php';

class OrderController extends BaseController
{
    /**
     * 生成加密的收银台URL
     */
    private function buildCashierUrl(string $tradeNo, string $tpl = ''): string
    {
        $params = ['trade_no' => $tradeNo];
        if ($tpl) $params['tpl'] = $tpl;
        $encrypted = Crypto::encrypt(http_build_query($params));
        return '/cashier.php?data=' . urlencode($encrypted);
    }

    /**
     * 创建订单
     * POST /api/cart/checkout
     * 参数:
     *   - product_id: 单商品模式，商品ID
     *   - items: 多商品模式，JSON数组 [{product_id, qty}]
     *   - user_id: 用户ID（可选，0表示游客）
     *   - cashier_tpl: 收银台模版标识（可选）
     *   - share_user_id: 分享者用户ID（代付场景，从当前登录用户获取，可选）
     */
    public function create(): void
    {
        // 检查用户是否登录
        $user = $_REQUEST['current_user'] ?? null;
        if (!$user || !isset($user->id)) {
            $this->jsonError('请先登录', 401);
        }

        $db = DB::getInstance();
        $input = $this->allInput();

        $productId = (int) ($input['product_id'] ?? 0);
        $items = $input['items'] ?? [];
        $userId = (int) ($input['user_id'] ?? 0);
        $cashierTpl = trim($input['cashier_tpl'] ?? $input['template_id'] ?? '');

        // 代付场景：从当前登录用户获取 share_user_id
        $shareUserId = 0;
        $currentUser = $_REQUEST['current_user'] ?? null;
        if ($currentUser && isset($currentUser->id)) {
            $shareUserId = (int) $currentUser->id;
        }
        // 也支持直接传入 share_user_id（兼容场景）
        if (isset($input['share_user_id']) && (int) $input['share_user_id'] > 0) {
            $shareUserId = (int) $input['share_user_id'];
        }

        // 验证至少有一种商品输入方式
        if ($productId <= 0 && empty($items)) {
            $this->jsonError('请选择要购买的商品');
        }

        $db->beginTransaction();

        try {
            $orderItems = [];
            $totalMoney = 0;

            if ($productId > 0) {
                // 单商品模式
                $product = $db->getRow(
                    "SELECT id, name, image, price, status FROM `products` WHERE id = ? AND status = 1",
                    [$productId]
                );

                if (!$product) {
                    $db->rollBack();
                    $this->jsonError('商品不存在或已下架');
                }

                $orderItems[] = [
                    'id'     => $product['id'],
                    'name'   => $product['name'],
                    'image'  => $product['image'],
                    'price'  => (float) $product['price'],
                    'qty'    => 1,
                ];
                $totalMoney = (float) $product['price'];

                // 订单主信息
                $orderData = [
                    'product_id'    => $product['id'],
                    'product_name'  => $product['name'],
                    'product_image' => $product['image'],
                    'product_price' => $product['price'],
                ];
            } else {
                // 多商品模式
                if (!is_array($items)) {
                    $items = json_decode($items, true) ?: [];
                }

                if (empty($items)) {
                    $db->rollBack();
                    $this->jsonError('商品列表不能为空');
                }

                foreach ($items as $item) {
                    $pid = (int) ($item['product_id'] ?? 0);
                    $qty = max(1, (int) ($item['qty'] ?? 1));

                    $product = $db->getRow(
                        "SELECT id, name, image, price, status FROM `products` WHERE id = ? AND status = 1",
                        [$pid]
                    );

                    if (!$product) {
                        $db->rollBack();
                        $this->jsonError("商品ID:{$pid}不存在或已下架");
                    }

                    $orderItems[] = [
                        'id'     => $product['id'],
                        'name'   => $product['name'],
                        'image'  => $product['image'],
                        'price'  => (float) $product['price'],
                        'qty'    => $qty,
                    ];
                    $totalMoney += (float) $product['price'] * $qty;
                }

                // 取第一个商品作为订单主信息
                $firstItem = $orderItems[0];
                $orderData = [
                    'product_id'    => $firstItem['id'],
                    'product_name'  => $firstItem['name'],
                    'product_image' => $firstItem['image'],
                    'product_price' => $firstItem['price'],
                ];
            }

            // 生成商户订单号
            $outTradeNo = $this->generateTradeNo();

            // 订单过期时间（从配置读取，默认15分钟）
            $expireMinutes = $db->getOne("SELECT config_value FROM `system_config` WHERE config_key = 'order_expire_minutes'");
            $expireMinutes = intval($expireMinutes) ?: 15;
            $expireTime = date('Y-m-d H:i:s', time() + $expireMinutes * 60);

            // 组装订单数据
            $orderData = array_merge($orderData, [
                'out_trade_no'  => $outTradeNo,
                'user_id'       => $userId,
                'items'         => json_encode($orderItems, JSON_UNESCAPED_UNICODE),
                'money'         => round($totalMoney, 2),
                'cashier_tpl'   => $cashierTpl ?: null,
                'status'        => 0,
                'expire_time'   => $expireTime,
                'share_user_id' => $shareUserId,
                'is_paid_for'   => $shareUserId > 0 ? 1 : 0,
            ]);

            $orderId = $db->insert('orders', $orderData);

            // 创建订单即算一次销量（不管是否支付）
            try {
                if (is_array($orderItems)) {
                    foreach ($orderItems as $item) {
                        $pid = (int) ($item['id'] ?? 0);
                        if ($pid > 0) {
                            $db->query("UPDATE `products` SET sales_count = sales_count + 1 WHERE id = ?", [$pid]);
                        }
                    }
                } elseif ($productId > 0) {
                    $db->query("UPDATE `products` SET sales_count = sales_count + 1 WHERE id = ?", [$productId]);
                }
            } catch (\Throwable $e) {}

            $db->commit();

            // 获取收银台模版信息（如果指定了模版）
            $template = null;
            if (!empty($cashierTpl)) {
                $template = $db->getRow(
                    "SELECT * FROM `cashier_templates` WHERE tpl_id = ? AND status = 1",
                    [$cashierTpl]
                );
            }

            $this->jsonSuccess([
                'order_id'     => $orderId,
                'out_trade_no' => $outTradeNo,
                'money'        => round($totalMoney, 2),
                'product_name' => $orderData['product_name'],
                'product_image'=> $orderData['product_image'],
                'expire_time'  => $expireTime,
                'cashier_tpl'  => $template,
                'items'        => $orderItems,
                'cashier_url'  => $this->buildCashierUrl($outTradeNo, $cashierTpl),
            ], '订单创建成功');

        } catch (Exception $e) {
            $db->rollBack();
            $this->jsonError('订单创建失败: ' . $e->getMessage());
        }
    }

    /**
     * 订单详情（收银台页面展示）
     * GET /api/order/{trade_no}
     */
    public function detail(): void
    {
        $tradeNo = trim($this->input('trade_no', ''));

        if (empty($tradeNo)) {
            $this->jsonError('订单号不能为空');
        }

        $db = DB::getInstance();

        $order = $db->getRow(
            "SELECT * FROM `orders` WHERE out_trade_no = ?",
            [$tradeNo]
        );

        if (!$order) {
            $this->jsonError('订单不存在');
        }

        // 解析多图JSON
        if (!empty($order['items'])) {
            $order['items'] = json_decode($order['items'], true) ?: [];
        } else {
            $order['items'] = [];
        }

        // 获取收银台模版信息
        $template = null;
        if (!empty($order['cashier_tpl'])) {
            $template = $db->getRow(
                "SELECT * FROM `cashier_templates` WHERE tpl_id = ? AND status = 1",
                [$order['cashier_tpl']]
            );
        }

        // 获取站点配置
        $siteConfig = $this->getSiteConfig();

        $this->jsonSuccess([
            'order'    => $order,
            'template' => $template,
            'site'     => $siteConfig,
        ]);
    }

    /**
     * 生成商户订单号
     * 格式: NX + 年月日时分秒 + 6位随机数
     *
     * @return string
     */
    private function generateTradeNo(): string
    {
        return 'NX' . date('YmdHis') . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * 更新订单状态（后台管理）
     * POST /api/order/status
     * 参数: order_id, status(0=待支付, 1=已支付, 2=已退款, 3=已过期)
     */
    public function updateStatus(): void
    {
        $input = $this->allInput();
        if (empty($input)) {
            $this->jsonError('请求数据为空');
        }

        $orderId = intval($input['order_id'] ?? 0);
        $status = intval($input['status'] ?? 0);

        if (!$orderId || !in_array($status, [0, 1, 2, 3])) {
            $this->jsonError('参数错误');
        }

        $db = DB::getInstance();
        $order = $db->getRow("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$order) {
            $this->jsonError('订单不存在');
        }

        $db->update('orders', ['status' => $status], 'id = ?', [$orderId]);
        $this->jsonSuccess();
    }

    /**
     * 获取站点配置
     *
     * @return array
     */
    private function getSiteConfig(): array
    {
        $db = DB::getInstance();
        $list = $db->getAll(
            "SELECT config_key, config_value FROM `system_config` WHERE config_group = 'site'"
        );

        $config = [];
        foreach ($list as $item) {
            $config[$item['config_key']] = $item['config_value'];
        }

        return $config;
    }
}
