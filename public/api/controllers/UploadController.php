<?php
class UploadController extends BaseController {
    public function upload() {
        if (empty($_FILES['file'])) {
            $this->jsonError('请选择要上传的文件');
        }

        $file = $_FILES['file'];

        // 检查错误
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->jsonError('上传失败: ' . $file['error']);
        }

        // 检查类型（微信分享卡片只支持 JPG/PNG）
        $allowedTypes = ['image/jpeg', 'image/png'];
        if (!in_array($file['type'], $allowedTypes)) {
            $this->jsonError('只支持 JPG/PNG 格式');
        }

        // 检查大小（2MB）
        if ($file['size'] > 2 * 1024 * 1024) {
            $this->jsonError('图片大小不能超过2MB');
        }

        // 创建上传目录
        $uploadDir = __DIR__ . '/../../../public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // 生成文件名
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = date('Ymd') . '_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . $filename;

        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            $this->jsonError('文件保存失败');
        }

        $url = '/uploads/' . $filename;
        $this->jsonSuccess(['url' => $url], '上传成功');
    }
}
