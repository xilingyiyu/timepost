<?php
// 时光邮局 入口文件
// 所有请求经此转发到对应 Controller

// PHP 内置开发服务器：直接返回静态文件（生产环境由 Nginx/Apache 处理）
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;
    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

require_once __DIR__ . '/../config/config.php';

// 自动加载：App\xxx → app/xxx.php
spl_autoload_register(function (string $class) {
    if (!str_starts_with($class, 'App\\')) return;
    $rel = str_replace(['App\\', '\\'], ['', '/'], $class);
    $file = __DIR__ . '/../app/' . $rel . '.php';
    if (is_file($file)) require $file;
});

// 简单路由器
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

$routes = require __DIR__ . '/../config/routes.php';

$matched = false;
foreach ($routes as [$rMethod, $rPath, $rHandler]) {
    if ($rMethod !== $method) continue;
    // 把 {xxx} 转为正则
    $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $rPath);
    $pattern = '#^' . $pattern . '$#';
    if (preg_match($pattern, $uri, $m)) {
        array_shift($m);
        [$class, $method] = explode('@', $rHandler);
        $class = 'App\\Controllers\\' . $class;
        $controller = new $class();
        $controller->$method(...$m);
        $matched = true;
        break;
    }
}

if (!$matched) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 404, 'msg' => 'Not Found', 'path' => $uri], JSON_UNESCAPED_UNICODE);
}
