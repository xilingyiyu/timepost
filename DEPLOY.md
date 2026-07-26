# 时光邮局 部署文档

基于 PHP 8 + SQLite，2C2G 服务器即可承载日活 500 用户。

## 一、环境要求

| 组件 | 最低版本 | 说明 |
| --- | --- | --- |
| PHP | 8.0+ | 需启用扩展：`curl`、`pdo_sqlite`、`mbstring`、`openssl`、`fileinfo` |
| SQLite | 3.x | PHP 自带，无需单独安装 |
| Nginx | 1.18+ | 或 Apache 2.4+ |
| 服务器 | 2C2G | 推荐腾讯云/阿里云轻量，约 25 元/月 |

**无需安装：** MySQL、Redis、Node.js、Composer、Docker

## 二、服务器环境初始化

以下以 Ubuntu 20.04/22.04 和 CentOS 7/8 为例。

### Ubuntu

```bash
apt update
apt install -y nginx php8.1-fpm php8.1-sqlite3 php8.1-curl php8.1-mbstring php8.1-fileinfo
```

### CentOS / OpenCloudOS

```bash
yum install -y nginx php83 php83-php-fpm php83-php-pdo php83-php-mbstring
```

### 安装宝塔面板（可选，方便可视化管理）

```bash
curl -sSO https://download.bt.cn/install/install_panel.sh && bash install_panel.sh
```

## 三、目录结构

```
timepost/
├── public/                  # Web 根目录（Nginx 指向这里）
│   ├── index.php            # 入口文件
│   └── assets/              # 静态资源（CSS/JS/图片）
│       ├── css/app.css
│       ├── css/admin.css
│       └── js/app.js
├── app/
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   ├── Home.php         # 前台页面
│   │   ├── Admin.php        # 后台页面
│   │   ├── AdminApi.php     # 后台 API
│   │   └── Api/             # 前台 API
│   │       ├── Auth.php     # 认证
│   │       ├── OauthLogin.php # 彩虹聚合登录
│   │       ├── Attachment.php # 附件上传
│   │       └── Letter.php   # 信件管理
│   ├── Libraries/
│   │   ├── Database.php
│   │   ├── JwtHelper.php
│   │   ├── CaihongOauth.php
│   │   ├── SmsSender.php
│   │   ├── MailSender.php
│   │   └── RateLimiter.php
│   └── Views/               # 视图模板
│       ├── home.php
│       ├── write.php
│       ├── login.php
│       ├── view_letter.php
│       ├── oauth/callback.php
│       └── admin/
├── config/
│   ├── config.php           # 全局配置 + env 加载
│   └── routes.php           # 路由表
├── cron/
│   ├── send.php             # 定时发送脚本
│   └── backup_db.php        # 数据库备份脚本
├── storage/
│   ├── init_db.php          # 数据库初始化
│   ├── timepost.db          # SQLite 数据库（自动生成）
│   └── logs/                # 日志目录
├── .env.example             # 环境变量模板
└── DEPLOY.md                # 本文件
```

## 四、部署步骤

### 步骤 1：上传代码

将整个 `timepost` 目录上传到服务器，例如 `/var/www/timepost`。

### 步骤 2：初始化数据库

```bash
cd /var/www/timepost
php storage/init_db.php
```

输出：
```
数据库初始化完成: /var/www/timepost/storage/timepost.db
默认管理员: admin / admin123（请尽快修改）
```

### 步骤 3：复制并配置 .env

```bash
cp .env.example .env
vim .env
```

按下方"五、配置说明"逐项填写。

### 步骤 4：设置目录权限

```bash
# 应用和 storage 目录需要 www 用户权限
chown -R www:www /var/www/timepost
chmod -R 755 /var/www/timepost

# storage 需要可写
chmod -R 775 /var/www/timepost/storage

# .env 文件保护（只 root 和 www 组可读）
chown root:www /var/www/timepost/.env
chmod 640 /var/www/timepost/.env
```

### 步骤 5：配置文件存储

附件直接存储在服务器本地文件系统。

```bash
# 创建上传目录
mkdir -p /var/www/timepost/storage/uploads
chown www:www /var/www/timepost/storage/uploads

# 或使用外部挂载存储（如 NFS / SSHFS）
mkdir -p /mnt/timepost-storage
# 在 .env 中设置: STORAGE_LOCAL_PATH=/mnt/timepost-storage
```

### 步骤 6：配置 Nginx

```nginx
server {
    listen 80;
    server_name timepost.yourdomain.com;
    root /var/www/timepost/public;
    index index.php;

    client_max_body_size 1024m;

    # 禁止访问敏感目录
    location ~ ^/(app|config|storage|cron|\.) {
        deny all;
        return 404;
    }

    # 路由转发（单入口模式）
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 静态资源缓存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

重新加载 Nginx：

```bash
nginx -t && systemctl reload nginx
```

### 步骤 7：配置 Crontab 定时任务

```bash
crontab -e
```

添加以下两行：

```
* * * * * cd /var/www/timepost && php cron/send.php >> storage/logs/send.log 2>&1
0 3 * * * cd /var/www/timepost && php cron/backup_db.php >> storage/logs/backup.log 2>&1
```

### 步骤 8：配置 HTTPS（可选但推荐）

使用 Certbot 获取免费 SSL 证书：

```bash
# Ubuntu
snap install certbot --classic
certbot --nginx -d timepost.yourdomain.com

# 证书自动续期
certbot renew --dry-run
```

## 五、配置说明

### 5.1 必填项

| 变量 | 说明 | 示例 |
| --- | --- | --- |
| `APP_URL` | 站点完整 URL | `https://timepost.yourdomain.com` |
| `JWT_SECRET` | JWT 签名密钥 | 运行 `php -r 'echo bin2hex(random_bytes(32));'` 生成 |
| `STORAGE_LOCAL_PATH` | 附件存储目录 | `/var/www/timepost/storage/uploads` |

### 5.2 短信配置（阿里云）

1. 开通阿里云短信服务
2. 创建签名和模板
3. 获取 AccessKey

```env
SMS_PROVIDER=aliyun
SMS_ACCESS_KEY=你的AccessKey
SMS_SECRET_KEY=你的SecretKey
SMS_SIGN=时光邮局
SMS_TEMPLATE_CODE=SMS_123456789
```

### 5.3 邮件配置（SMTP）

以 QQ 邮箱为例：

```env
MAIL_HOST=smtp.qq.com
MAIL_PORT=465
MAIL_USERNAME=你的邮箱@qq.com
MAIL_PASSWORD=你的SMTP授权码
MAIL_FROM=你的邮箱@qq.com
MAIL_FROM_NAME=时光邮局
```

> QQ 邮箱需在设置中开启 SMTP 服务并获取授权码。

### 5.4 彩虹聚合登录（OAuth）

1. 前往 [https://u.arsn.cn](https://u.arsn.cn) 注册并创建应用
2. 获取 AppID 和 AppKey
3. 填写回调地址

```env
CAIHONG_API_BASE=https://u.arsn.cn/connect.php
CAIHONG_APPID=你的AppID
CAIHONG_APPKEY=你的AppKey
CAIHONG_REDIRECT_URI=https://你的域名/api/auth/oauth/callback
```

> 留空则不启用 OAuth，仅支持手机号注册。

### 5.5 运维告警

```env
# 发送失败告警接收邮箱（可选，留空不告警）
ALERT_EMAIL=admin@yourdomain.com

# 备份保留天数
BACKUP_RETENTION_DAYS=30
```

### 5.6 附件限制

```env
ATTACHMENT_MAX_COUNT=9
ATTACHMENT_ALLOW_EXT=jpg,jpeg,png,gif,webp,mp4,mov,avi,webm
```

> 图片限制 20MB，视频限制 1GB（后端自动判断）。

## 六、常见问题

### 6.1 上传附件失败

1. 确认 PHP 已启用 `fileinfo` 扩展
2. 确认 `STORAGE_LOCAL_PATH` 路径存在且 `www` 用户有写权限
3. 确认 Nginx `client_max_body_size` 足够大
4. 确认 PHP `upload_max_filesize` 和 `post_max_size` 足够大

```ini
# php.ini
upload_max_filesize = 1024M
post_max_size = 1024M
```

### 6.2 短信/邮件发送失败

1. 确认发送脚本正常运行：`php cron/send.php`
2. 查看发送日志：`tail -f storage/logs/send.log`
3. 检查阿里云短信余额和模板审核状态
4. 检查 SMTP 账号密码是否正确

### 6.3 页面显示 403 / 404

1. 确认 Nginx 配置的 `root` 指向 `public/` 目录
2. 确认文件权限正确：`chown -R www:www /var/www/timepost`
3. 检查 `.env` 文件权限为 640

### 6.4 OAuth 登录回调失败

1. 确认 `.env` 中 `CAIHONG_REDIRECT_URI` 与彩虹聚合后台配置一致
2. 确认域名已正确解析并配置了 HTTPS
3. 检查回调地址末尾路径：`/api/auth/oauth/callback`

### 6.5 数据库损坏

SQLite 一般不会损坏，若遇到问题可恢复最近备份：

```bash
# 查看备份
ls -lt storage/backups/

# 恢复（替换当前数据库）
cp storage/backups/timepost_YYYYMMDD_HHMMSS.db storage/timepost.db
```

## 七、安全检查清单

- [ ] 默认管理员密码已修改
- [ ] `.env` 文件权限为 640（非公开可读）
- [ ] Nginx 已配置禁止访问 `.env`、`storage/`、`app/` 目录
- [ ] `JWT_SECRET` 已替换为随机字符串
- [ ] HTTPS 已启用
- [ ] 目录权限：`public/uploads` 不对外暴露目录索引

## 八、性能参考

| 指标 | 数值 |
| --- | --- |
| 日活用户 | 500 |
| 并发请求 | 50 QPS |
| 单封附件 | ≤9 个文件 |
| 图片上限 | 20MB |
| 视频上限 | 1GB |
| 定时扫描频率 | 每分钟 1 次 |
| 数据库大小 | ~10MB（万封信用量） |
