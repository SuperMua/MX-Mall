# MX-Mall

智能收银台商城系统，内置14种收银台风格，支持彩虹易支付和拉卡拉MOSS双支付通道。

## 环境要求

| 项目     | 要求                                    |
| ------ | ------------------------------------- |
| PHP    | 7.4                                   |
| MySQL  | 5.7+                                  |
| Web服务器 | Nginx                                 |
| PHP扩展  | openssl、curl、pdo\_mysql、json、mbstring |

## 安装

1. 宝塔创建站点，运行目录设为 `/public`
2. 上传 `mx-mall` 目录内容到网站根目录
3. 访问 `https://你的域名/install/install.php`，按提示配置数据库和管理员账号
4. 宝塔网站设置 → 伪静态，粘贴以下内容：

```nginx
location /api/ {
    try_files $uri /api/index.php$is_args$args;
}

location ~ \.php$ {
    fastcgi_pass unix:/tmp/php-cgi.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

1. 安装完成后删除 `install/` 目录

后台地址：`https://你的域名/admin.php/`
