<?php
/**
 * MX-Mall - 数据库操作类
 *
 * 基于PDO的数据库单例模式实现
 * 提供query/insert/update/delete/getRow/getAll等快捷方法
 * 所有操作使用预处理语句防止SQL注入
 */

class DB
{
    /**
     * @var DB|null 单例实例
     */
    private static ?DB $instance = null;

    /**
     * @var PDO PDO连接实例
     */
    private PDO $pdo;

    /**
     * 私有构造函数，防止外部实例化
     */
    private function __construct()
    {
        // 加载配置
        $config = require __DIR__ . '/config.php';
        $db = $config['database'];

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['dbname'],
            $db['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $db['username'], $db['password'], $options);
            // 统一时区为 Asia/Shanghai，确保 CURRENT_TIMESTAMP 与 PHP time() 一致
            $this->pdo->exec("SET time_zone = '+08:00'");
        } catch (PDOException $e) {
            // 开发环境输出错误，生产环境记录日志
            http_response_code(500);
            echo json_encode([
                'code' => 1,
                'msg'  => '数据库连接失败: ' . $e->getMessage(),
            ]);
            exit;
        }
    }

    /**
     * 获取单例实例
     *
     * @return DB
     */
    public static function getInstance(): DB
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取PDO实例
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * 执行预处理查询
     *
     * @param string $sql    SQL语句（可含占位符 ?）
     * @param array  $params 绑定参数
     * @return PDOStatement
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * 查询所有记录
     *
     * @param string $sql    SQL语句
     * @param array  $params 绑定参数
     * @return array
     */
    public function getAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * 查询单条记录
     *
     * @param string $sql    SQL语句
     * @param array  $params 绑定参数
     * @return array|null
     */
    public function getRow(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row ?: null;
    }

    /**
     * 查询单个字段值
     *
     * @param string $sql    SQL语句
     * @param array  $params 绑定参数
     * @return mixed
     */
    public function getOne(string $sql, array $params = [])
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * 插入数据
     *
     * @param string $table 表名
     * @param array  $data  关联数组 [字段名 => 值]
     * @return int 最后插入的ID
     */
    public function insert(string $table, array $data): int
    {
        $fields = implode(', ', array_map(fn($f) => "`{$f}`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `{$table}` ({$fields}) VALUES ({$placeholders})";

        $this->query($sql, array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * 更新数据
     *
     * @param string $table       表名
     * @param array  $data        关联数组 [字段名 => 值]
     * @param string $where       WHERE条件（可含占位符 ?）
     * @param array  $whereParams WHERE绑定参数
     * @return int 受影响的行数
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($f) => "`{$f}` = ?", array_keys($data)));
        $sql = "UPDATE `{$table}` SET {$set} WHERE {$where}";
        $params = array_merge(array_values($data), $whereParams);

        return $this->query($sql, $params)->rowCount();
    }

    /**
     * 删除数据
     *
     * @param string $table       表名
     * @param string $where       WHERE条件（可含占位符 ?）
     * @param array  $params      WHERE绑定参数
     * @return int 受影响的行数
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * 获取记录总数（用于分页）
     *
     * @param string $table       表名
     * @param string $where       WHERE条件
     * @param array  $params      绑定参数
     * @return int
     */
    public function count(string $table, string $where = '1=1', array $params = []): int
    {
        return (int) $this->getOne("SELECT COUNT(*) FROM `{$table}` WHERE {$where}", $params);
    }

    /**
     * 分页查询
     *
     * @param string $sql       基础SQL（不含LIMIT）
     * @param array  $params    绑定参数
     * @param int    $page      当前页码
     * @param int    $pageSize  每页数量
     * @return array ['list' => [], 'total' => int, 'page' => int, 'page_size' => int, 'total_page' => int]
     */
    public function paginate(string $sql, array $params = [], int $page = 1, int $pageSize = 20): array
    {
        $page = max(1, $page);
        $pageSize = max(1, min($pageSize, 100));
        $offset = ($page - 1) * $pageSize;

        // 获取总数
        $countSql = preg_replace('/SELECT\s+.*?\s+FROM/i', 'SELECT COUNT(*) FROM', $sql, 1);
        $total = (int) $this->getOne($countSql, $params);

        // 获取分页数据
        $list = $this->getAll($sql . " LIMIT {$offset}, {$pageSize}", $params);

        return [
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'page_size'  => $pageSize,
            'total_page' => (int) ceil($total / $pageSize),
        ];
    }

    /**
     * 开启事务
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * 回滚事务
     */
    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * 防止克隆
     */
    private function __clone() {}

    /**
     * 防止反序列化
     */
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton');
    }
}
