<?php
// 基础控制器

namespace App\Controllers;

use App\Libraries\Database;
use App\Libraries\JwtHelper;

abstract class BaseController
{
    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function ok($data = null, string $msg = 'ok'): void
    {
        $this->json(['code' => 0, 'msg' => $msg, 'data' => $data]);
    }

    protected function fail(string $msg, int $code = 1, int $status = 400): void
    {
        $this->json(['code' => $code, 'msg' => $msg, 'data' => null], $status);
    }

    protected function input(?string $key = null, $default = null)
    {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true) ?: [];
        $merged = array_merge($_GET, $_POST, $json);
        if ($key === null) return $merged;
        return $merged[$key] ?? $default;
    }

    /** 从 Authorization Header 获取当前用户 ID，无则返回 0 */
    protected function userId(): int
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\s+(.+)/', $header, $m)) return 0;
        try {
            $payload = JwtHelper::verify(trim($m[1]));
            return (int)($payload['sub'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** 要求登录，否则返回 401 */
    protected function requireLogin(): int
    {
        $uid = $this->userId();
        if ($uid <= 0) $this->fail('请先登录', 401, 401);
        return $uid;
    }

    protected function db(): \PDO
    {
        return Database::pdo();
    }

    protected function view(string $tpl, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../Views/' . $tpl . '.php';
        exit;
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
