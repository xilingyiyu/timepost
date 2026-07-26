<?php
// JWT 签发与校验（HS256，纯 PHP 实现，无依赖）

namespace App\Libraries;

class JwtHelper
{
    private static function secret(): string
    {
        $secret = config('jwt.secret');
        // 生产环境拒绝弱默认值，防止 token 被伪造
        $debug = config('app.debug');
        $isWeak =
            $secret === '' ||
            str_starts_with($secret, 'change-me-please') ||
            $secret === '请改成你自己的随机字符串_至少32位' ||
            strlen($secret) < 32;
        if ($isWeak && !$debug) {
            throw new \RuntimeException(
                'JWT_SECRET 未正确配置：生产环境必须设置 ≥32 位随机字符串。' .
                '请修改 .env 中的 JWT_SECRET 后重试。'
            );
        }
        return $secret;
    }

    private static function ttl(): int
    {
        return (int)config('jwt.ttl', 604800);
    }

    /** 签发 token */
    public static function issue(int $userId, array $extra = []): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $now = time();
        $payload = array_merge([
            'sub'  => $userId,
            'iat'  => $now,
            'exp'  => $now + self::ttl(),
        ], $extra);
        return self::encode($header, $payload);
    }

    /** 校验并返回 payload，失败抛异常 */
    public static function verify(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) throw new \RuntimeException('Token 格式错误');
        [$h, $p, $s] = $parts;
        $expected = self::sign($h . '.' . $p);
        if (!hash_equals($expected, $s)) throw new \RuntimeException('Token 签名无效');
        $payload = json_decode(self::base64UrlDecode($p), true);
        if (!is_array($payload)) throw new \RuntimeException('Token 载荷无效');
        if (($payload['exp'] ?? 0) < time()) throw new \RuntimeException('Token 已过期');
        return $payload;
    }

    private static function encode(array $header, array $payload): string
    {
        $h = self::base64UrlEncode(json_encode($header));
        $p = self::base64UrlEncode(json_encode($payload));
        $s = self::sign($h . '.' . $p);
        return $h . '.' . $p . '.' . $s;
    }

    private static function sign(string $data): string
    {
        return self::base64UrlEncode(hash_hmac('sha256', $data, self::secret(), true));
    }

    private static function base64UrlEncode(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $s): string
    {
        return base64_decode(strtr($s, '-_', '+/'));
    }
}
