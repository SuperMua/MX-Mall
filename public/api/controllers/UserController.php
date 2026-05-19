<?php
/**
 * MX-Mall - 前台用户控制器
 *
 * 处理用户注册、登录、个人信息、商户申请、
 * 商品上架、订单查询、提现等操作
 */

class UserController extends BaseController
{
    /**
     * 用户注册（用户名模式）
     * 注册即通过审核（review_status=1），商户权限需单独申请
     */
    public function register()
    {
        $username = trim($this->input('username', ''));
        $password = $this->input('password', '');
        $ref = intval($this->input('ref', '0'));

        if (empty($username) || mb_strlen($username) < 2 || mb_strlen($username) > 20) {
            return $this->jsonError('用户名长度2-20个字符');
        }
        if (strlen($password) < 6) {
            return $this->jsonError('密码至少6位');
        }

        $db = DB::getInstance();

        $exists = $db->getRow("SELECT id FROM users WHERE username = ?", [$username]);
        if ($exists) {
            return $this->jsonError('该用户名已被注册');
        }

        // 验证推荐人
        if ($ref > 0) {
            $referrer = $db->getRow("SELECT id FROM users WHERE id = ? AND status = 1", [$ref]);
            if (!$referrer) $ref = 0;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $defaultGroup = $db->getRow("SELECT id FROM user_groups WHERE is_default = 1 AND status = 1");
        $groupId = $defaultGroup ? intval($defaultGroup['id']) : 0;

        $userId = $db->insert('users', [
            'username'       => $username,
            'nickname'       => $username,
            'password'       => $hashedPassword,
            'avatar'         => '',
            'status'         => 1,
            'review_status'  => 0,
            'balance'        => 0.00,
            'frozen_balance' => 0.00,
            'group_id'       => $groupId,
            'referrer_id'    => $ref,
            'is_merchant'    => 0,
            'merchant_status'=> 0,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        if ($userId) {
            return $this->jsonSuccess(null, '注册成功，请等待管理员审核');
        }

        return $this->jsonError('注册失败，请重试');
    }

    /**
     * 用户登录（支持用户名或手机号）
     * 返回信息增加 balance, is_merchant, merchant_status
     */
    public function login()
    {
        $username = trim($this->input('username', ''));
        $password = $this->input('password', '');

        if (empty($username) || empty($password)) {
            return $this->jsonError('用户名和密码不能为空');
        }

        $db = DB::getInstance();
        $user = $db->getRow("SELECT u.*, g.name as group_name, g.commission_rate as group_commission_rate FROM users u LEFT JOIN user_groups g ON u.group_id = g.id WHERE u.username = ? OR u.phone = ?", [$username, $username]);

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->jsonError('用户名或密码错误');
        }

        if ($user['status'] != 1) {
            return $this->jsonError('账号已被禁用');
        }

        // 检查审核状态
        if ($user['review_status'] == 0) {
            return $this->jsonError('账号正在审核中，请耐心等待');
        }
        if ($user['review_status'] == 2) {
            return $this->jsonError('账号审核未通过，请联系管理员');
        }

        // 生成JWT token（30天有效）
        $jwt = new JWT();
        $token = $jwt->encode([
            'user_id'  => $user['id'],
            'username' => $user['username'] ?? $user['phone'],
            'nickname' => $user['nickname'],
        ], 86400 * 30);

        return $this->jsonSuccess([
            'token' => $token,
            'user'  => [
                'id'             => $user['id'],
                'nickname'       => $user['nickname'],
                'username'       => $user['username'] ?? '',
                'phone'          => $user['phone'] ?? '',
                'avatar'         => $user['avatar'] ?? '',
                'balance'        => round(floatval($user['balance']), 2),
                'frozen_balance' => round(floatval($user['frozen_balance']), 2),
                'is_merchant'    => (int) $user['is_merchant'],
                'merchant_status'=> (int) $user['merchant_status'],
                'group_id'       => (int) $user['group_id'],
                'group_name'     => $user['group_name'] ?? '',
                'group_commission_rate' => floatval($user['group_commission_rate'] ?? 0),
            ],
        ], '登录成功');
    }

    /**
     * 获取个人信息
     * 增加返回 balance, frozen_balance, is_merchant, merchant_status
     */
    public function getProfile()
    {
        $user = $_REQUEST['current_user'] ?? null;
        if (!$user) {
            return $this->jsonError('请先登录');
        }

        $db = DB::getInstance();
        $userInfo = $db->getRow(
            "SELECT u.id, u.username, u.nickname, u.phone, u.avatar, u.status, u.review_status,
                    u.balance, u.frozen_balance, u.group_id, u.is_merchant, u.merchant_status, u.created_at,
                    g.name as group_name, g.commission_rate as group_commission_rate
             FROM users u LEFT JOIN user_groups g ON u.group_id = g.id
             WHERE u.id = ?",
            [$user->id]
        );

        if (!$userInfo) {
            return $this->jsonError('用户不存在');
        }

        // 格式化金额字段
        $userInfo['balance'] = round(floatval($userInfo['balance']), 2);
        $userInfo['frozen_balance'] = round(floatval($userInfo['frozen_balance']), 2);
        $userInfo['is_merchant'] = (int) $userInfo['is_merchant'];
        $userInfo['merchant_status'] = (int) $userInfo['merchant_status'];

        return $this->jsonSuccess($userInfo);
    }

    /**
     * 更新个人信息
     */
    public function updateProfile()
    {
        $user = $_REQUEST['current_user'] ?? null;
        if (!$user) {
            return $this->jsonError('请先登录');
        }

        $db       = DB::getInstance();
        $nickname = $this->input('nickname', '');
        $avatar   = $this->input('avatar', '');

        $data = [];
        if ($nickname !== '') {
            $data['nickname'] = $nickname;
        }
        if ($avatar !== '') {
            $data['avatar'] = $avatar;
        }

        if (empty($data)) {
            return $this->jsonError('没有要修改的信息');
        }

        $db->update('users', $data, "id = ?", [$user->id]);

        return $this->jsonSuccess(null, '修改成功');
    }

    /**
     * 申请商户权限
     * POST /api/user/apply-merchant（需UserAuth认证）
     * 检查是否已申请，设置 merchant_status=1（待审核）
     */
    public function applyMerchant()
    {
        $user = $_REQUEST['current_user'] ?? null;
        if (!$user) {
            return $this->jsonError('请先登录');
        }

        $db = DB::getInstance();
        $userId = $user->id;

        // 查询当前用户状态
        $userInfo = $db->getRow(
            "SELECT id, is_merchant, merchant_status FROM users WHERE id = ?",
            [$userId]
        );

        if (!$userInfo) {
            return $this->jsonError('用户不存在');
        }

        // 已经是商户
        if ($userInfo['is_merchant'] == 1 && $userInfo['merchant_status'] == 2) {
            return $this->jsonError('您已经是商户，无需重复申请');
        }

        // 正在审核中
        if ($userInfo['merchant_status'] == 1) {
            return $this->jsonError('商户申请正在审核中，请耐心等待');
        }

        // 被拒绝后可以重新申请
        $db->update('users', [
            'is_merchant'     => 1,
            'merchant_status' => 1,  // 待审核
        ], 'id = ?', [$userId]);

        return $this->jsonSuccess(null, '商户申请已提交，请等待管理员审核');
    }

    /**
     * 获取我上架的商品
     * GET /api/user/products（需UserAuth认证）
     * 返回当前用户上架的所有商品
     */
    public function getMyProducts()
    {
        $user = $_REQUEST['current_user'] ?? null;
        if (!$user) {
            return $this->jsonError('请先登录');
        }

        $db = DB::getInstance();
        [$page, $pageSize] = $this->getPageParams();
        $userId = $user->id;

        $result = $db->paginate(
            "SELECT p.*, c.name AS category_name
             FROM `products` p
             LEFT JOIN `categories` c ON p.category_id = c.id
             WHERE p.user_id = ?
             ORDER BY p.sort_order ASC, p.id DESC",
            [$userId], $page, $pageSize
        );

        return $this->jsonSuccess($result);
    }

    /**
     * 用户上架商品（需要is_merchant=1且merchant_status=2）
     * POST /api/user/products（需UserAuth认证）
     * 参数：name, image, price, original_price, description, category_id
     * user_id自动设为当前用户，status默认1
     */
    public function createProduct()
    {
        $user = $_REQUEST['current_user'] ?? null;
        if (!$user) {
            return $this->jsonError('请先登录');
        }

        $db = DB::getInstance();
        $userId = $user->id;

        // 检查商户权限
        $userInfo = $db->getRow(
            "SELECT id, is_merchant, merchant_status FROM users WHERE id = ?",
            [$userId]
        );

        if (!$userInfo) {
            return $this->jsonError('用户不存在');
        }

        if ($userInfo['is_merchant'] != 1 || $userInfo['merchant_status'] != 2) {
            return $this->jsonError('您还没有商户权限，请先申请并等待审核通过');
        }

        $input = $this->allInput();
        $name = trim($input['name'] ?? '');

        if (empty($name)) {
            return $this->jsonError('商品名称不能为空');
        }

        $price = isset($input['price']) ? floatval($input['price']) : 0;
        if ($price <= 0) {
            return $this->jsonError('商品价格必须大于0');
        }

        $data = [
            'name'          => $name,
            'image'         => $input['image'] ?? '',
            'images'        => isset($input['images']) ? (is_array($input['images']) ? json_encode($input['images']) : $input['images']) : '',
            'price'         => $price,
            'original_price'=> isset($input['original_price']) ? floatval($input['original_price']) : null,
            'description'   => $input['description'] ?? '',
            'category_id'   => (int) ($input['category_id'] ?? 0),
            'sort_order'    => (int) ($input['sort_order'] ?? 0),
            'status'        => 1,
            'user_id'       => $userId,
        ];

        $newId = $db->insert('products', $data);

        if ($newId) {
            return $this->jsonSuccess(['id' => $newId], '商品上架成功');
        }

        return $this->jsonError('商品上架失败，请重试');
    }

    /**
     * 切换商品上下架状态
     * POST /api/user/products/status（需UserAuth认证）
     * 参数：id, status(0下架/1上架)
     */
    public function updateProductStatus()
    {
        $user = $_REQUEST['current_user'] ?? null;
        if (!$user) {
            return $this->jsonError('请先登录');
        }

        $id = (int) $this->input('id', 0);
        $status = (int) $this->input('status', 0);

        if ($id <= 0) {
            return $this->jsonError('商品ID无效');
        }

        if (!in_array($status, [0, 1])) {
            return $this->jsonError('状态无效');
        }

        $db = DB::getInstance();

        // 只能操作自己的商品
        $product = $db->getRow("SELECT id, user_id FROM products WHERE id = ?", [$id]);
        if (!$product) {
            return $this->jsonError('商品不存在');
        }

        if ($product['user_id'] != $user->id) {
            return $this->jsonError('无权操作此商品');
        }

        $db->update('products', ['status' => $status], 'id = ?', [$id]);

        return $this->jsonSuccess(null, $status == 1 ? '已上架' : '已下架');
    }

    /**
     * 获取我的订单（我分享出去的代付订单）
     * GET /api/user/orders（需UserAuth认证）
     * 返回share_user_id=当前用户的订单
     */
    public function getMyOrders()
    {
        $user = $_REQUEST['current_user'] ?? null;
        if (!$user) {
            return $this->jsonError('请先登录');
        }

        $db = DB::getInstance();
        // 自动将超时的待支付订单标记为已过期
        $db->query("UPDATE `orders` SET status = 3 WHERE status = 0 AND user_id = ? AND expire_time IS NOT NULL AND expire_time < NOW()", [$user->id]);

        [$page, $pageSize] = $this->getPageParams();
        $userId = $user->id;

        $status = $this->input('status');

        $where = 'share_user_id = ?';
        $params = [$userId];

        if ($status !== null && $status !== '') {
            $where .= ' AND status = ?';
            $params[] = (int) $status;
        }

        $result = $db->paginate(
            "SELECT * FROM `orders`
             WHERE {$where}
             ORDER BY id DESC",
            $params, $page, $pageSize
        );

        return $this->jsonSuccess($result);
    }

    /**
     * 申请提现
     * POST /api/user/withdraw（需UserAuth认证）
     * 参数：amount, qr_code(收款码图片URL), real_name
     * 检查余额是否足够，冻结余额，创建提现记录
     */
    public function requestWithdraw()
    {
        $user = $_REQUEST['current_user'] ?? null;
        if (!$user) {
            return $this->jsonError('请先登录');
        }

        $db = DB::getInstance();
        $userId = $user->id;

        $amount = floatval($this->input('amount', 0));
        $qrCode = trim($this->input('qr_code', ''));
        $realName = trim($this->input('real_name', ''));

        if ($amount <= 0) {
            return $this->jsonError('提现金额必须大于0');
        }

        if (empty($qrCode)) {
            return $this->jsonError('请上传收款码');
        }

        if (empty($realName)) {
            return $this->jsonError('请填写真实姓名');
        }

        // 查询用户余额
        $userInfo = $db->getRow(
            "SELECT id, balance, frozen_balance FROM users WHERE id = ? FOR UPDATE",
            [$userId]
        );

        if (!$userInfo) {
            return $this->jsonError('用户不存在');
        }

        $balance = floatval($userInfo['balance']);
        $frozenBalance = floatval($userInfo['frozen_balance']);

        if ($balance < $amount) {
            return $this->jsonError('可用余额不足，当前可用余额: ' . round($balance, 2));
        }

        // 生成提现单号
        $outTradeNo = 'WD' . date('YmdHis') . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $db->beginTransaction();

        try {
            // 冻结余额：可用余额减少，冻结余额增加
            $db->query(
                "UPDATE users SET balance = balance - ?, frozen_balance = frozen_balance + ? WHERE id = ?",
                [$amount, $amount, $userId]
            );

            // 创建提现记录
            $withdrawId = $db->insert('withdrawals', [
                'user_id'     => $userId,
                'out_trade_no'=> $outTradeNo,
                'amount'      => $amount,
                'qr_code'     => $qrCode,
                'real_name'   => $realName,
                'status'      => 0,  // 待审核
                'created_at'  => date('Y-m-d H:i:s'),
            ]);

            if (!$withdrawId) {
                throw new Exception('提现记录创建失败');
            }

            $db->commit();

            return $this->jsonSuccess([
                'id'           => $withdrawId,
                'out_trade_no' => $outTradeNo,
                'amount'       => round($amount, 2),
            ], '提现申请已提交，请等待审核');

        } catch (Exception $e) {
            $db->rollBack();
            return $this->jsonError('提现申请失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取我的提现记录
     * GET /api/user/withdrawals（需UserAuth认证）
     */
    public function getWithdrawals()
    {
        $user = $_REQUEST['current_user'] ?? null;
        if (!$user) {
            return $this->jsonError('请先登录');
        }

        $db = DB::getInstance();
        [$page, $pageSize] = $this->getPageParams();
        $userId = $user->id;

        $status = $this->input('status');

        $where = 'user_id = ?';
        $params = [$userId];

        if ($status !== null && $status !== '') {
            $where .= ' AND status = ?';
            $params[] = (int) $status;
        }

        $result = $db->paginate(
            "SELECT * FROM `withdrawals`
             WHERE {$where}
             ORDER BY id DESC",
            $params, $page, $pageSize
        );

        return $this->jsonSuccess($result);
    }

    /**
     * 获取余额变动记录（从已支付的代付订单中查询）
     * GET /api/user/balance-log（需UserAuth认证）
     */
    public function getBalanceLog()
    {
        $user = $_REQUEST['current_user'] ?? null;
        if (!$user) {
            return $this->jsonError('请先登录');
        }

        $db = DB::getInstance();
        [$page, $pageSize] = $this->getPageParams();
        $userId = $user->id;

        // 查询分享者获得的收入记录（已支付的代付订单）
        $result = $db->paginate(
            "SELECT id, out_trade_no, product_name, money, pay_time, created_at
             FROM `orders`
             WHERE share_user_id = ? AND status = 1
             ORDER BY id DESC",
            [$userId], $page, $pageSize
        );

        return $this->jsonSuccess($result);
    }

    public function getGroupInfo()
    {
        $user = $_REQUEST['current_user'] ?? null;
        if (!$user) {
            return $this->jsonError('请先登录');
        }

        $db = DB::getInstance();
        $groups = $db->getAll("SELECT * FROM user_groups WHERE status = 1 ORDER BY sort_order ASC, id ASC");

        $userGroup = $db->getRow(
            "SELECT g.* FROM user_groups g INNER JOIN users u ON u.group_id = g.id WHERE u.id = ?",
            [$user->id]
        );

        return $this->jsonSuccess([
            'groups' => $groups,
            'current_group' => $userGroup ? [
                'id' => $userGroup['id'],
                'name' => $userGroup['name'],
                'commission_rate' => floatval($userGroup['commission_rate']),
            ] : null,
        ]);
    }

    public function purchaseGroup()
    {
        $user = $_REQUEST['current_user'] ?? null;
        if (!$user) {
            return $this->jsonError('请先登录');
        }

        $groupId = (int)$this->input('group_id', 0);
        if ($groupId <= 0) {
            return $this->jsonError('请选择要购买的身份');
        }

        $db = DB::getInstance();
        $group = $db->getRow("SELECT * FROM user_groups WHERE id = ? AND status = 1", [$groupId]);
        if (!$group) {
            return $this->jsonError('该身份不存在或已禁用');
        }

        $price = floatval($group['price']);
        if ($price <= 0) {
            return $this->jsonError('该身份无法购买');
        }

        $currentUser = $db->getRow("SELECT id, group_id, balance FROM users WHERE id = ?", [$user->id]);
        if (!$currentUser) {
            return $this->jsonError('用户不存在');
        }

        if (intval($currentUser['group_id']) === $groupId) {
            return $this->jsonError('您已经是该身份');
        }

        $currentGroupId = intval($currentUser['group_id']);
        if ($currentGroupId > 0) {
            $currentGroup = $db->getRow("SELECT commission_rate, sort_order FROM user_groups WHERE id = ?", [$currentGroupId]);
            if ($currentGroup) {
                $currentRate = floatval($currentGroup['commission_rate']);
                $newRate = floatval($group['commission_rate']);
                if ($newRate >= $currentRate) {
                    return $this->jsonError('不能购买同级或更低级的身份');
                }
            }
        }

        $balance = floatval($currentUser['balance']);
        if ($balance < $price) {
            return $this->jsonError('余额不足，当前余额¥' . number_format($balance, 2) . '，需要¥' . number_format($price, 2));
        }

        $newBalance = $balance - $price;
        $db->update('users', [
            'balance' => $newBalance,
            'group_id' => $groupId,
        ], 'id = ?', [$user->id]);

        return $this->jsonSuccess([
            'group_name' => $group['name'],
            'new_balance' => round($newBalance, 2),
        ], '购买成功');
    }
}
