<?php
/**
 * MX-Mall - 后台管理控制器
 *
 * 处理管理员登录、仪表盘、用户管理、商品管理、
 * 订单管理、模版管理、系统配置、支付日志等功能
 */

require_once __DIR__ . '/BaseController.php';

class AdminController extends BaseController
{
    /**
     * 管理员登录
     * POST /api/admin/login
     * 参数: username, password
     */
    public function login(): void
    {
        $username = trim($this->input('username', ''));
        $password = $this->input('password', '');

        if (empty($username) || empty($password)) {
            $this->jsonError('用户名和密码不能为空');
        }

        $db = DB::getInstance();
        $admin = $db->getRow(
            "SELECT * FROM `admins` WHERE `username` = ? AND `status` = 1",
            [$username]
        );

        if (!$admin) {
            $this->jsonError('用户名或密码错误');
        }

        // 验证密码
        if (!password_verify($password, $admin['password'])) {
            $this->jsonError('用户名或密码错误');
        }

        // 更新最后登录时间
        $db->update('admins', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$admin['id']]);

        // 生成JWT Token
        $token = JWT::encode([
            'admin_id' => $admin['id'],
            'username' => $admin['username'],
            'nickname' => $admin['nickname'] ?? '',
            'role'     => $admin['role'],
        ]);

        $this->jsonSuccess([
            'token'    => $token,
            'admin_id' => $admin['id'],
            'username' => $admin['username'],
            'nickname' => $admin['nickname'],
            'avatar'   => $admin['avatar'],
            'role'     => $admin['role'],
        ], '登录成功');
    }

    /**
     * 仪表盘统计概览
     * GET /api/admin/dashboard
     */
    public function dashboard(): void
    {
        $db = DB::getInstance();

        // 订单总数
        $totalOrders = $db->count('orders');

        // 已支付订单数
        $paidOrders = $db->count('orders', 'status = 1');

        // 总收入
        $totalIncome = (float) $db->getOne("SELECT COALESCE(SUM(money), 0) FROM `orders` WHERE `status` = 1");

        // 今日订单数
        $todayOrders = $db->count('orders', 'DATE(created_at) = CURDATE()');

        // 今日收入
        $todayIncome = (float) $db->getOne(
            "SELECT COALESCE(SUM(money), 0) FROM `orders` WHERE `status` = 1 AND DATE(created_at) = CURDATE()"
        );

        // 商品总数
        $totalProducts = $db->count('products');

        // 上架商品数
        $onlineProducts = $db->count('products', 'status = 1');

        // 用户总数
        $totalUsers = $db->count('users');

        // 用户总余额
        $totalBalance = (float) $db->getOne("SELECT COALESCE(SUM(balance), 0) FROM `users`");

        // 待提现金额（状态为待审核的提现申请）
        $pendingWithdrawals = (float) $db->getOne("SELECT COALESCE(SUM(amount), 0) FROM `withdrawals` WHERE `status` = 0");

        // 最近7天订单趋势
        $weekTrend = $db->getAll(
            "SELECT DATE(created_at) AS date, COUNT(*) AS order_count, COALESCE(SUM(money), 0) AS amount
             FROM `orders`
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC"
        );

        // 最近10条订单
        $recentOrders = $db->getAll(
            "SELECT id, out_trade_no, product_name, money, status, pay_type, pay_channel, created_at
             FROM `orders`
             ORDER BY id DESC
             LIMIT 10"
        );

        // 支付模板分布（按template_id分组统计已支付订单）
        $templateStats = $db->getAll(
            "SELECT cashier_tpl AS template_id, COUNT(*) AS count
             FROM `orders`
             WHERE status = 1 AND cashier_tpl IS NOT NULL AND cashier_tpl != ''
             GROUP BY cashier_tpl
             ORDER BY count DESC"
        );

        $this->jsonSuccess([
            'total_orders'    => $totalOrders,
            'paid_orders'     => $paidOrders,
            'total_income'    => round($totalIncome, 2),
            'today_orders'    => $todayOrders,
            'today_income'    => round($todayIncome, 2),
            'total_products'  => $totalProducts,
            'online_products' => $onlineProducts,
            'total_users'     => $totalUsers,
            'total_balance'        => round($totalBalance, 2),
            'pending_withdrawals'  => round($pendingWithdrawals, 2),
            'week_trend'      => $weekTrend,
            'recent_orders'   => $recentOrders,
            'template_stats'  => $templateStats,
        ]);
    }

    /**
     * 用户列表（分页）
     * GET /api/admin/users
     */
    public function users(): void
    {
        $db = DB::getInstance();
        [$page, $pageSize] = $this->getPageParams();

        $keyword = trim($this->input('keyword', ''));

        $where = '1=1';
        $params = [];

        if (!empty($keyword)) {
            $where .= ' AND (nickname LIKE ? OR phone LIKE ? OR openid LIKE ? OR username LIKE ?)';
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }

        $result = $db->paginate(
            "SELECT u.id, u.openid, u.username, u.nickname, u.avatar, u.phone, u.status, u.review_status,
                    u.balance, u.frozen_balance, u.group_id, u.is_merchant, u.merchant_status, u.created_at,
                    g.name as group_name, g.commission_rate as group_commission_rate
             FROM `users` u LEFT JOIN `user_groups` g ON u.group_id = g.id
             WHERE {$where} ORDER BY u.id DESC",
            $params, $page, $pageSize
        );

        $this->jsonSuccess($result);
    }

    /**
     * 审核用户注册 / 启用禁用用户
     * POST /api/admin/review-user
     * 参数: id, review_status(1=通过, 2=拒绝), status(可选, 0=禁用, 1=启用)
     */
    public function reviewUser(): void
    {
        $id = (int) ($this->input('id', 0));
        $reviewStatus = $this->input('review_status');
        $status = $this->input('status');
        $groupId = $this->input('group_id');

        if ($id <= 0) {
            $this->jsonError('用户ID无效');
        }

        $db = DB::getInstance();

        $user = $db->getRow("SELECT id, review_status, status FROM `users` WHERE id = ?", [$id]);
        if (!$user) {
            $this->jsonError('用户不存在');
        }

        $updateData = [];

        if ($groupId !== null && $groupId !== '') {
            $updateData['group_id'] = (int)$groupId;
        }

        if ($status !== null && $status !== '') {
            $updateData['status'] = (int) $status;
        }

        if ($reviewStatus !== null && $reviewStatus !== '' && (int)$reviewStatus !== -1) {
            $reviewStatus = (int) $reviewStatus;
            if (in_array($reviewStatus, [1, 2])) {
                if ($user['review_status'] == 0) {
                    $updateData['review_status'] = $reviewStatus;
                }
            }
        }

        if (!empty($updateData)) {
            $db->update('users', $updateData, 'id = ?', [$id]);
        }

        $this->jsonSuccess(null, '操作成功');
    }

    /**
     * 商品列表
     * GET /api/admin/products
     */
    public function products(): void
    {
        $db = DB::getInstance();
        [$page, $pageSize] = $this->getPageParams();

        $keyword = trim($this->input('keyword', ''));
        $categoryId = (int) $this->input('category_id', 0);
        $status = $this->input('status');

        $where = '1=1';
        $params = [];

        if (!empty($keyword)) {
            $where .= ' AND name LIKE ?';
            $params[] = "%{$keyword}%";
        }

        if ($categoryId > 0) {
            $where .= ' AND category_id = ?';
            $params[] = $categoryId;
        }

        if ($status !== null && $status !== '') {
            $where .= ' AND status = ?';
            $params[] = (int) $status;
        }

        $result = $db->paginate(
            "SELECT p.*, c.name AS category_name
             FROM `products` p
             LEFT JOIN `categories` c ON p.category_id = c.id
             WHERE {$where} ORDER BY p.sort_order ASC, p.id DESC",
            $params, $page, $pageSize
        );

        $this->jsonSuccess($result);
    }

    /**
     * 保存商品（新增/编辑）
     * POST /api/admin/products
     * 参数: id(可选), name, image, images, price, original_price, description, category_id, sort_order, status
     */
    public function saveProduct(): void
    {
        $db = DB::getInstance();
        $input = $this->allInput();

        $id = (int) ($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');

        if (empty($name)) {
            $this->jsonError('商品名称不能为空');
        }

        $price = isset($input['price']) ? floatval($input['price']) : 0;
        if ($price <= 0) {
            $this->jsonError('商品价格必须大于0');
        }

        $data = [
            'name'          => $name,
            'image'         => $input['image'] ?? '',
            'images'        => isset($input['images']) ? (is_array($input['images']) ? json_encode($input['images']) : $input['images']) : '',
            'price'         => $price,
            'original_price' => isset($input['original_price']) ? floatval($input['original_price']) : null,
            'description'   => $input['description'] ?? '',
            'category_id'   => (int) ($input['category_id'] ?? 0),
            'sort_order'    => (int) ($input['sort_order'] ?? 0),
            'status'        => (int) ($input['status'] ?? 1),
        ];

        if ($id > 0) {
            // 编辑
            $db->update('products', $data, 'id = ?', [$id]);
            $this->jsonSuccess(['id' => $id], '更新成功');
        } else {
            // 新增
            $newId = $db->insert('products', $data);
            $this->jsonSuccess(['id' => $newId], '添加成功');
        }
    }

    /**
     * 删除用户
     * DELETE /api/admin/users/{id}
     */
    public function deleteUser(): void
    {
        global $routeParams;
        $userId = (int) ($routeParams['id'] ?? 0);
        if ($userId <= 0) {
            $this->jsonError('参数错误');
        }

        $db = DB::getInstance();

        // 不允许删除自己
        $admin = $_REQUEST['current_admin'] ?? null;
        if ($admin && (int) $admin->id === $userId) {
            $this->jsonError('不能删除当前登录的管理员账号');
        }

        $user = $db->getOne("SELECT id, username FROM `users` WHERE id = ?", [$userId]);
        if (!$user) {
            $this->jsonError('用户不存在');
        }

        // 删除用户相关数据
        $db->delete('users', 'id = ?', [$userId]);
        $db->delete('products', 'user_id = ?', [$userId]);
        $db->delete('withdrawals', 'user_id = ?', [$userId]);
        // balance_log表可能不存在，忽略错误
        try { $db->delete('balance_log', 'user_id = ?', [$userId]); } catch (\Throwable $e) {}
        // 订单保留（有财务记录价值），只解除用户关联
        try { $db->query("UPDATE `orders` SET user_id = 0 WHERE user_id = ?", [$userId]); } catch (\Throwable $e) {}

        $this->jsonSuccess(null, '用户已删除');
    }

    /**
     * 删除商品
     * DELETE /api/admin/products/{id}
     */
    public function deleteProduct(): void
    {
        $id = (int) ($this->input('id', 0));

        if ($id <= 0) {
            $this->jsonError('商品ID无效');
        }

        $db = DB::getInstance();
        $db->delete('products', 'id = ?', [$id]);

        $this->jsonSuccess(null, '删除成功');
    }

    /**
     * 订单列表
     * GET /api/admin/orders
     */
    public function orders(): void
    {
        $db = DB::getInstance();

        // 自动将超时的待支付订单标记为已过期
        $db->query("UPDATE `orders` SET status = 3 WHERE status = 0 AND expire_time IS NOT NULL AND expire_time < NOW()");

        [$page, $pageSize] = $this->getPageParams();

        $status = $this->input('status');
        $keyword = trim($this->input('keyword', ''));
        $payType = $this->input('pay_type');

        $where = '1=1';
        $params = [];

        if ($status !== null && $status !== '') {
            // 支持文字和数字两种格式
            $statusMap = [
                'unpaid' => 0, 'paid' => 1, 'refunded' => 2, 'expired' => 3,
            ];
            if (isset($statusMap[$status])) {
                $status = $statusMap[$status];
            } else {
                $status = (int) $status;
            }
            $where .= ' AND o.status = ?';
            $params[] = $status;
        }

        if (!empty($keyword)) {
            $where .= ' AND (o.out_trade_no LIKE ? OR o.product_name LIKE ? OR o.trade_no LIKE ?)';
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }

        if (!empty($payType)) {
            $where .= ' AND o.pay_type = ?';
            $params[] = $payType;
        }

        $result = $db->paginate(
            "SELECT o.*, u.nickname AS user_nickname, u.phone AS user_phone
             FROM `orders` o
             LEFT JOIN `users` u ON o.user_id = u.id
             WHERE {$where} ORDER BY o.id DESC",
            $params, $page, $pageSize
        );

        // 自动将过期未支付的订单标记为已过期(status=3)
        $db->query("UPDATE `orders` SET status = 3 WHERE status = 0 AND expire_time < NOW()");

        $this->jsonSuccess($result);
    }

    /**
     * 收银台模版列表
     * GET /api/admin/templates
     */
    public function templates(): void
    {
        $db = DB::getInstance();

        $list = $db->getAll(
            "SELECT * FROM `cashier_templates` ORDER BY sort_order ASC, id ASC"
        );

        $this->jsonSuccess($list);
    }

    /**
     * 保存收银台模版
     * POST /api/admin/templates
     * 参数: id(可选), tpl_id, name, color, icon, description, slogan, nick, status, sort_order
     */
    public function saveTemplate(): void
    {
        $db = DB::getInstance();
        $input = $this->allInput();

        $id = (int) ($input['id'] ?? 0);
        $tplId = trim($input['tpl_id'] ?? '');
        $name = trim($input['name'] ?? '');

        if (empty($tplId) || empty($name)) {
            $this->jsonError('模版标识和名称不能为空');
        }

        $data = [
            'tpl_id'      => $tplId,
            'name'        => $name,
            'color'       => $input['color'] ?? '#6c5ce7',
            'icon'        => $input['icon'] ?? '',
            'description' => $input['description'] ?? '',
            'slogan'      => $input['slogan'] ?? '',
            'nick'        => $input['nick'] ?? '',
            'status'      => (int) ($input['status'] ?? 1),
            'sort_order'  => (int) ($input['sort_order'] ?? 0),
        ];

        if ($id > 0) {
            $db->update('cashier_templates', $data, 'id = ?', [$id]);
            $this->jsonSuccess(['id' => $id], '更新成功');
        } else {
            // 检查tpl_id唯一性
            $exists = $db->getRow("SELECT id FROM `cashier_templates` WHERE tpl_id = ?", [$tplId]);
            if ($exists) {
                $this->jsonError('模版标识已存在');
            }
            $newId = $db->insert('cashier_templates', $data);
            $this->jsonSuccess(['id' => $newId], '添加成功');
        }
    }

    /**
     * 获取系统配置
     * GET /api/admin/config
     */
    public function getConfig(): void
    {
        $db = DB::getInstance();

        $list = $db->getAll("SELECT config_key, config_value, config_group FROM `system_config` ORDER BY id ASC");

        // 按分组整理
        $config = [];
        foreach ($list as $item) {
            $group = $item['config_group'];
            if (!isset($config[$group])) {
                $config[$group] = [];
            }
            $config[$group][$item['config_key']] = $item['config_value'];
        }

        $this->jsonSuccess($config);
    }

    /**
     * 保存系统配置
     * POST /api/admin/config
     * 参数: configs (关联数组 key => value)
     */
    public function saveConfig(): void
    {
        $db = DB::getInstance();
        $configs = $this->input('configs');

        // 如果没有configs参数，把所有输入当作配置项
        if (empty($configs) || !is_array($configs)) {
            $configs = $this->allInput();
            unset($configs['configs']); // 移除自身
        }

        if (empty($configs)) {
            $this->jsonError('配置数据不能为空');
        }

        $db->beginTransaction();

        try {
            foreach ($configs as $key => $value) {
                // 根据配置键前缀自动分配分组
                $group = 'general';
                if (strpos($key, 'site_') === 0) {
                    $group = 'site';
                } elseif (strpos($key, 'wx_') === 0) {
                    $group = 'wx';
                } elseif (strpos($key, 'epay_') === 0) {
                    $group = 'pay_epay';
                } elseif (strpos($key, 'lakala_') === 0) {
                    $group = 'pay_lakala';
                } elseif (strpos($key, 'moss_') === 0) {
                    $group = 'pay_moss';
                } elseif (strpos($key, 'wxpay_') === 0) {
                    $group = 'pay_wxpay';
                } elseif ($key === 'pay_channel') {
                    $group = 'payment';
                }

                // 检查配置项是否存在
                $exists = $db->getRow("SELECT id FROM `system_config` WHERE config_key = ?", [$key]);
                if ($exists) {
                    $db->update('system_config', ['config_value' => $value], 'config_key = ?', [$key]);
                } else {
                    $db->insert('system_config', [
                        'config_key'   => $key,
                        'config_value' => $value,
                        'config_group' => $group,
                    ]);
                }
            }

            $db->commit();
            $this->jsonSuccess(null, '配置保存成功');
        } catch (Exception $e) {
            $db->rollBack();
            $this->jsonError('配置保存失败: ' . $e->getMessage());
        }
    }

    /**
     * 支付日志列表
     * GET /api/admin/payment-logs
     */
    public function paymentLogs(): void
    {
        $db = DB::getInstance();
        [$page, $pageSize] = $this->getPageParams();

        $status = $this->input('status');
        $payType = $this->input('pay_type');

        $where = '1=1';
        $params = [];

        if ($status !== null && $status !== '') {
            $where .= ' AND status = ?';
            $params[] = (int) $status;
        }

        if (!empty($payType)) {
            $where .= ' AND pay_type = ?';
            $params[] = $payType;
        }

        $result = $db->paginate(
            "SELECT * FROM `payment_logs` WHERE {$where} ORDER BY id DESC",
            $params, $page, $pageSize
        );

        $this->jsonSuccess($result);
    }

    /**
     * 提现管理列表
     * GET /api/admin/withdrawals
     * 返回所有提现记录，支持状态筛选
     */
    public function withdrawList(): void
    {
        $db = DB::getInstance();
        [$page, $pageSize] = $this->getPageParams();

        $status = $this->input('status');
        $keyword = trim($this->input('keyword', ''));

        $where = '1=1';
        $params = [];

        if ($status !== null && $status !== '') {
            $where .= ' AND w.status = ?';
            $params[] = (int) $status;
        }

        if (!empty($keyword)) {
            $where .= ' AND (w.out_trade_no LIKE ? OR w.real_name LIKE ? OR u.username LIKE ?)';
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }

        $result = $db->paginate(
            "SELECT w.*, u.username, u.nickname, u.phone
             FROM `withdrawals` w
             LEFT JOIN `users` u ON w.user_id = u.id
             WHERE {$where} ORDER BY w.id DESC",
            $params, $page, $pageSize
        );

        $this->jsonSuccess($result);
    }

    /**
     * 审核提现
     * POST /api/admin/review-withdraw
     * 参数：id, status(1通过/2拒绝), admin_note
     * 通过时：将冻结余额扣除（不实际打款，管理员手动打款后改为status=3）
     * 拒绝时：将冻结余额退回到可用余额
     */
    public function reviewWithdraw(): void
    {
        $id = (int) ($this->input('id', 0));
        $status = (int) ($this->input('status', 0));
        $adminNote = trim($this->input('admin_note', ''));

        if ($id <= 0) {
            $this->jsonError('提现记录ID无效');
        }

        if (!in_array($status, [1, 2, 3])) {
            $this->jsonError('审核状态无效，1=通过 2=拒绝 3=通过并打款');
        }

        $db = DB::getInstance();

        // 查询提现记录
        $withdraw = $db->getRow(
            "SELECT * FROM `withdrawals` WHERE id = ?",
            [$id]
        );

        if (!$withdraw) {
            $this->jsonError('提现记录不存在');
        }

        if ($withdraw['status'] != 0) {
            $this->jsonError('该提现记录已审核，不能重复操作');
        }

        $db->beginTransaction();

        try {
            if ($status == 1 || $status == 3) {
                // 审核通过：扣除冻结余额
                $db->query(
                    "UPDATE users SET frozen_balance = frozen_balance - ? WHERE id = ? AND frozen_balance >= ?",
                    [$withdraw['amount'], $withdraw['user_id'], $withdraw['amount']]
                );

                $db->update('withdrawals', [
                    'status'      => $status,
                    'admin_note'  => $adminNote,
                    'review_time' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$id]);
            } else {
                // 审核拒绝：冻结余额退回到可用余额
                $db->query(
                    "UPDATE users SET frozen_balance = frozen_balance - ?, balance = balance + ? WHERE id = ? AND frozen_balance >= ?",
                    [$withdraw['amount'], $withdraw['amount'], $withdraw['user_id'], $withdraw['amount']]
                );

                $db->update('withdrawals', [
                    'status'      => 2,
                    'admin_note'  => $adminNote,
                    'review_time' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$id]);
            }

            $db->commit();

            $statusText = $status == 3 ? '已通过并打款' : ($status == 1 ? '已通过' : '已拒绝');
            $this->jsonSuccess(null, "提现审核{$statusText}");

        } catch (Exception $e) {
            $db->rollBack();
            $this->jsonError('审核操作失败: ' . $e->getMessage());
        }
    }

    /**
     * 确认已打款
     * POST /api/admin/complete-withdraw
     * 参数：id
     * 将status改为3已打款
     */
    public function completeWithdraw(): void
    {
        $id = (int) ($this->input('id', 0));

        if ($id <= 0) {
            $this->jsonError('提现记录ID无效');
        }

        $db = DB::getInstance();

        $withdraw = $db->getRow(
            "SELECT * FROM `withdrawals` WHERE id = ?",
            [$id]
        );

        if (!$withdraw) {
            $this->jsonError('提现记录不存在');
        }

        if ($withdraw['status'] != 1) {
            $this->jsonError('该提现记录未通过审核，无法确认打款');
        }

        $db->update('withdrawals', [
            'status'   => 3,
            'pay_time' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->jsonSuccess(null, '已确认打款');
    }

    /**
     * 审核商户申请
     * POST /api/admin/review-merchant
     * 参数：id, merchant_status(2通过/3拒绝)
     */
    public function reviewMerchant(): void
    {
        $id = (int) ($this->input('id', 0));
        $merchantStatus = (int) ($this->input('merchant_status', 0));

        if ($id <= 0) {
            $this->jsonError('用户ID无效');
        }

        if (!in_array($merchantStatus, [2, 3])) {
            $this->jsonError('审核状态无效，2=通过 3=拒绝');
        }

        $db = DB::getInstance();

        $user = $db->getRow(
            "SELECT id, is_merchant, merchant_status FROM `users` WHERE id = ?",
            [$id]
        );

        if (!$user) {
            $this->jsonError('用户不存在');
        }

        if ($user['merchant_status'] != 1) {
            $this->jsonError('该用户未申请商户或已审核，不能重复操作');
        }

        $db->update('users', [
            'is_merchant'     => $merchantStatus == 2 ? 1 : 0,
            'merchant_status' => $merchantStatus,
        ], 'id = ?', [$id]);

        $statusText = $merchantStatus == 2 ? '已通过' : '已拒绝';
        $this->jsonSuccess(null, "商户申请审核{$statusText}");
    }

    /**
     * 分类列表
     * GET /api/admin/categories
     */
    public function categories(): void {
        $db = DB::getInstance();
        $list = $db->getAll("SELECT * FROM categories ORDER BY sort_order ASC, id ASC");
        $this->jsonSuccess($list);
    }

    /**
     * 保存分类（新增或编辑）
     * POST /api/admin/categories
     * 参数: id(可选), name, icon, sort_order, status
     */
    public function saveCategory(): void {
        $id = (int)$this->input('id', 0);
        $name = trim($this->input('name', ''));
        $icon = trim($this->input('icon', ''));
        $sort_order = (int)$this->input('sort_order', 0);
        $status = (int)$this->input('status', 1);

        if (empty($name)) {
            $this->jsonError('分类名称不能为空');
        }

        $db = DB::getInstance();
        $data = ['name' => $name, 'icon' => $icon, 'sort_order' => $sort_order, 'status' => $status];

        if ($id > 0) {
            $db->update('categories', $data, 'id = ?', [$id]);
        } else {
            $id = $db->insert('categories', $data);
        }
        $this->jsonSuccess(['id' => $id], '保存成功');
    }

    /**
     * 删除分类
     * POST /api/admin/delete-category
     * 参数: id
     */
    public function deleteCategory(): void {
        $id = (int)$this->input('id', 0);
        if ($id <= 0) $this->jsonError('参数错误');

        $db = DB::getInstance();
        // 检查是否有商品使用该分类
        $count = $db->count('products', 'category_id = ?', [$id]);
        if ($count > 0) {
            $this->jsonError('该分类下有商品，无法删除');
        }
        $db->delete('categories', 'id = ?', [$id]);
        $this->jsonSuccess(null, '删除成功');
    }

    public function userGroups(): void
    {
        $db = DB::getInstance();
        $list = $db->getAll("SELECT * FROM user_groups ORDER BY sort_order ASC, id ASC");
        $this->jsonSuccess($list);
    }

    public function saveUserGroup(): void
    {
        $id = (int)$this->input('id', 0);
        $name = trim($this->input('name', ''));
        $commissionRate = floatval($this->input('commission_rate', 0));
        $price = floatval($this->input('price', 0));
        $isDefault = (int)$this->input('is_default', 0);
        $sortOrder = (int)$this->input('sort_order', 0);
        $status = (int)$this->input('status', 1);

        if (empty($name)) {
            $this->jsonError('分组名称不能为空');
        }

        if ($commissionRate < 0 || $commissionRate > 100) {
            $this->jsonError('佣金比例范围0-100');
        }

        $db = DB::getInstance();

        if ($isDefault === 1) {
            $db->query("UPDATE user_groups SET is_default = 0");
        }

        $data = [
            'name' => $name,
            'commission_rate' => $commissionRate,
            'price' => $price,
            'is_default' => $isDefault,
            'sort_order' => $sortOrder,
            'status' => $status,
        ];

        if ($id > 0) {
            $db->update('user_groups', $data, 'id = ?', [$id]);
        } else {
            $id = $db->insert('user_groups', $data);
        }
        $this->jsonSuccess(['id' => $id], '保存成功');
    }

    public function deleteUserGroup(): void
    {
        $id = (int)$this->input('id', 0);
        if ($id <= 0) $this->jsonError('参数错误');

        $db = DB::getInstance();
        $group = $db->getRow("SELECT * FROM user_groups WHERE id = ?", [$id]);
        if (!$group) $this->jsonError('分组不存在');

        if ($group['is_default'] == 1) {
            $this->jsonError('默认分组不能删除');
        }

        $defaultGroup = $db->getRow("SELECT id FROM user_groups WHERE is_default = 1 AND id != ?", [$id]);
        if ($defaultGroup) {
            $db->query("UPDATE users SET group_id = ? WHERE group_id = ?", [$defaultGroup['id'], $id]);
        }

        $db->delete('user_groups', 'id = ?', [$id]);
        $this->jsonSuccess(null, '删除成功');
    }

    /**
     * 调整用户余额（手动）
     * POST /api/admin/adjust-balance
     * 参数：user_id, amount(正数加/负数减), note
     */
    public function adjustBalance(): void
    {
        $userId = (int) ($this->input('user_id', 0));
        $amount = floatval($this->input('amount', 0));

        if ($userId <= 0) {
            $this->jsonError('用户ID无效');
        }

        if ($amount == 0) {
            $this->jsonError('调整金额不能为0');
        }

        $db = DB::getInstance();

        $user = $db->getRow(
            "SELECT id, balance FROM `users` WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            $this->jsonError('用户不存在');
        }

        // 减余额时检查是否足够
        if ($amount < 0 && floatval($user['balance']) < abs($amount)) {
            $this->jsonError('用户余额不足，当前余额: ' . round(floatval($user['balance']), 2));
        }

        $db->beginTransaction();

        try {
            $db->query(
                "UPDATE users SET balance = balance + ? WHERE id = ?",
                [$amount, $userId]
            );

            // 记录调整日志到payment_logs（复用日志表）
            $db->insert('payment_logs', [
                'out_trade_no' => 'ADJ' . date('YmdHis') . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
                'pay_type'     => 'manual',
                'pay_channel'  => 'balance_adjust',
                'request_data' => json_encode([
                    'user_id' => $userId,
                    'amount'  => $amount,
                    'admin'   => $_REQUEST['current_admin']->username ?? 'system',
                ], JSON_UNESCAPED_UNICODE),
                'response_data'=> null,
                'status'       => 1,
                'error_msg'    => null,
            ]);

            $db->commit();

            $action = $amount > 0 ? '增加' : '减少';
            $this->jsonSuccess(null, "已{$action}余额: " . abs(round($amount, 2)));

        } catch (Exception $e) {
            $db->rollBack();
            $this->jsonError('余额调整失败: ' . $e->getMessage());
        }
    }

    /**
     * 清理过期订单
     * POST /api/orders/clean
     * 删除所有状态为已过期(status=3)的订单
     */
    public function cleanExpiredOrders(): void
    {
        $db = DB::getInstance();
        $deleted = $db->delete('orders', 'status = 3');
        $this->jsonSuccess(['deleted' => $deleted]);
    }

    /**
     * 修改管理员密码
     * POST /api/admin/password
     * 参数: old_password, new_password, confirm_password
     */
    public function changePassword(): void
    {
        $admin = $_REQUEST['current_admin'] ?? null;
        if (!$admin) {
            $this->jsonError('请先登录');
        }

        $oldPassword = $this->input('old_password', '');
        $newPassword = $this->input('new_password', '');
        $confirmPassword = $this->input('confirm_password', '');

        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->jsonError('请填写所有密码字段');
        }

        if (strlen($newPassword) < 6) {
            $this->jsonError('新密码至少6位');
        }

        if ($newPassword !== $confirmPassword) {
            $this->jsonError('两次输入的新密码不一致');
        }

        $db = DB::getInstance();

        // 查询当前管理员
        $adminRow = $db->getRow(
            "SELECT id, password FROM `admins` WHERE id = ?",
            [$admin->id]
        );

        if (!$adminRow) {
            $this->jsonError('管理员账号不存在');
        }

        // 验证旧密码
        if (!password_verify($oldPassword, $adminRow['password'])) {
            $this->jsonError('旧密码错误');
        }

        // 更新密码
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $affected = $db->update('admins', ['password' => $hashedPassword], 'id = ?', [$admin->id]);

        if ($affected === 0) {
            $this->jsonError('密码更新失败，请重试');
        }

        $this->jsonSuccess(null, '密码修改成功');
    }
}
