<?php
// 通用限流器（基于 rate_limits 表，滑动窗口）
// 用法：
//   if (!RateLimiter::hit('login', $phone, 5, 60)) { /* 超限 */ }
//   RateLimiter::check('login', $phone, 5, 60);  // 仅检查不写入，超限抛异常

namespace App\Libraries;

class RateLimiter
{
    /** 命中一次限流计数
     * @param string $action  动作名（如 login/register/letter_create/sms）
     * @param string $identifier 标识（手机号/IP/用户ID）
     * @param int $max      窗口内最大次数
     * @param int $window   窗口秒数
     * @return bool true=允许 false=超限
     */
    public static function hit(string $action, string $identifier, int $max, int $window): bool
    {
        self::gc($action, $identifier, $window);
        $now = date('Y-m-d H:i:s');
        $count = self::count($action, $identifier, $window);
        if ($count >= $max) return false;
        Database::insert('rate_limits', [
            'action' => $action, 'identifier' => $identifier, 'created_at' => $now,
        ]);
        return true;
    }

    /** 仅检查是否超限，不写入 */
    public static function check(string $action, string $identifier, int $max, int $window): bool
    {
        return self::count($action, $identifier, $window) < $max;
    }

    /** 当前窗口已命中次数 */
    public static function count(string $action, string $identifier, int $window): int
    {
        $since = date('Y-m-d H:i:s', time() - $window);
        return (int)Database::one(
            "SELECT COUNT(*) AS c FROM rate_limits WHERE action=? AND identifier=? AND created_at>=?",
            [$action, $identifier, $since]
        )['c'];
    }

    /** 清理过期记录（仅当前 action+identifier，避免全表扫描） */
    private static function gc(string $action, string $identifier, int $window): void
    {
        // 随机清理（10% 概率清理全表，避免每次写入都清）
        if (mt_rand(1, 100) <= 10) {
            $cutoff = date('Y-m-d H:i:s', time() - 3600);  // 清理 1 小时前的
            Database::pdo()->exec("DELETE FROM rate_limits WHERE created_at < '{$cutoff}'");
        }
    }

    /** 获取客户端 IP */
    public static function ip(): string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $k) {
            if (!empty($_SERVER[$k])) {
                $ip = trim(explode(',', $_SERVER[$k])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '0.0.0.0';
    }
}
