<?php
/**
 * MX-Mall - 前台商品控制器
 *
 * 处理前台商品列表和商品详情展示
 * 只返回已上架（status=1）的商品
 */

require_once __DIR__ . '/BaseController.php';

class ProductController extends BaseController
{
    /**
     * 商品列表
     * GET /api/products
     * 参数: category_id, keyword, page, page_size
     */
    public function index(): void
    {
        $db = DB::getInstance();
        [$page, $pageSize] = $this->getPageParams();

        $categoryId = (int) $this->input('category_id', 0);
        $keyword = trim($this->input('keyword', ''));

        $where = 'p.status = 1';
        $params = [];

        // 分类筛选
        if ($categoryId > 0) {
            $where .= ' AND p.category_id = ?';
            $params[] = $categoryId;
        }

        // 关键词搜索
        if (!empty($keyword)) {
            $where .= ' AND p.name LIKE ?';
            $params[] = "%{$keyword}%";
        }

        $result = $db->paginate(
            "SELECT p.id, p.name, p.image, p.price, p.original_price, p.description,
                    p.category_id, p.sales_count, p.created_at,
                    c.name AS category_name
             FROM `products` p
             LEFT JOIN `categories` c ON p.category_id = c.id
             WHERE {$where}
             ORDER BY p.sort_order ASC, p.id DESC",
            $params, $page, $pageSize
        );

        // 获取所有启用的分类（用于前台筛选）
        $categories = $db->getAll(
            "SELECT id, name, icon FROM `categories` WHERE status = 1 ORDER BY sort_order ASC"
        );

        $this->jsonSuccess([
            'list'       => $result['list'],
            'total'      => $result['total'],
            'page'       => $result['page'],
            'page_size'  => $result['page_size'],
            'total_page' => $result['total_page'],
            'categories' => $categories,
        ]);
    }

    /**
     * 商品详情
     * GET /api/products/{id}
     */
    public function detail(): void
    {
        $id = (int) $this->input('id', 0);

        if ($id <= 0) {
            $this->jsonError('商品ID无效');
        }

        $db = DB::getInstance();

        $product = $db->getRow(
            "SELECT p.*, c.name AS category_name
             FROM `products` p
             LEFT JOIN `categories` c ON p.category_id = c.id
             WHERE p.id = ? AND p.status = 1",
            [$id]
        );

        if (!$product) {
            $this->jsonError('商品不存在或已下架');
        }

        // 解析多图JSON
        if (!empty($product['images'])) {
            $product['images'] = json_decode($product['images'], true) ?: [];
        } else {
            $product['images'] = [];
        }

        $this->jsonSuccess($product);
    }
}
