<?php
// 时光邮局 全局配置

$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if ($v !== '' && !getenv($k)) putenv("$k=$v");
    }
}

function env(string $key, $default = null) {
    $v = getenv($key);
    return $v === false ? $default : $v;
}

function config(string $key, $default = null) {
    static $cfg = null;
    if ($cfg === null) {
        $cfg = [
            'app' => [
                'name'    => env('APP_NAME', '时光邮局'),
                'url'     => env('APP_URL', 'http://localhost:8080'),
                'debug'   => env('APP_DEBUG', 'true') === 'true',
                'timezone'=> 'Asia/Shanghai',
            ],
            'db' => [
                'driver' => 'sqlite',
                'path'   => dirname(__DIR__) . '/storage/timepost.db',
            ],
            'jwt' => [
                'secret' => env('JWT_SECRET', 'change-me-please-' . md5(__FILE__)),
                'ttl'    => (int)env('JWT_TTL', 604800),
            ],
            'storage' => [
                'local_path' => env('STORAGE_LOCAL_PATH', '/mnt/timepost-storage'),
            ],
            'caihong' => [
                'base'      => env('CAIHONG_API_BASE', 'https://u.arsn.cn/connect.php'),
                'appid'     => env('CAIHONG_APPID', ''),
                'appkey'    => env('CAIHONG_APPKEY', ''),
                'redirect'  => env('CAIHONG_REDIRECT_URI', ''),
            ],
            'sms' => [
                'provider' => env('SMS_PROVIDER', 'aliyun'),
            ],
            'mail' => [
                'host'     => env('MAIL_HOST', ''),
                'port'     => (int)env('MAIL_PORT', 465),
                'username' => env('MAIL_USERNAME', ''),
                'password' => env('MAIL_PASSWORD', ''),
                'from'     => env('MAIL_FROM', ''),
                'from_name'=> env('MAIL_FROM_NAME', '时光邮局'),
            ],
            'attachment' => [
                'max_size'   => (int)env('ATTACHMENT_MAX_SIZE', 104857600),
                'max_count'  => (int)env('ATTACHMENT_MAX_COUNT', 9),
                'allow_ext'  => explode(',', env('ATTACHMENT_ALLOW_EXT', 'jpg,jpeg,png,gif,webp,mp4,mov,avi,webm')),
            ],
        ];
    }
    $keys = explode('.', $key);
    $val = $cfg;
    foreach ($keys as $k) {
        $val = $val[$k] ?? null;
        if ($val === null) return $default;
    }
    return $val;
}

date_default_timezone_set(config('app.timezone'));
error_reporting(config('app.debug') ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', config('app.debug') ? '1' : '0');
