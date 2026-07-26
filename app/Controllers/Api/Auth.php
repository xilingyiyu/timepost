<?php
// 用户认证 API

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\Database;
use App\Libraries\JwtHelper;
use App\Libraries\MailSender;
use App\Libraries\MailTemplate;
use App\Libraries\RateLimiter;

class Auth extends BaseController
{
    /** POST /api/auth/register  {phone, password, nickname?} */
    public function register()
    {
        $phone = trim($this->input('phone', ''));
        $password = $this->input('password', '');
        $nickname = trim($this->input('nickname', '')) ?: ('用户' . substr($phone, -4));
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) return $this->fail('手机号格式错误');
        if (strlen($password) < 6) return $this->fail('密码至少 6 位');

        // 限流：单 IP 每小时最多 5 次注册
        $ip = RateLimiter::ip();
        if (!RateLimiter::hit('register', $ip, 5, 3600)) {
            return $this->fail('注册过于频繁，请稍后再试', 429, 429);
        }

        $exists = Database::one('SELECT id FROM users WHERE phone=?', [$phone]);
        if ($exists) return $this->fail('该手机号已注册');

        $now = date('Y-m-d H:i:s');
        $uid = Database::insert('users', [
            'phone' => $phone, 'password' => password_hash($password, PASSWORD_DEFAULT),
            'nickname' => $nickname, 'status' => 1, 'union_source' => 'local', 'created_at' => $now,
        ]);
        return $this->ok(['token' => JwtHelper::issue($uid), 'user_id' => $uid, 'nickname' => $nickname, 'need_security' => true]);
    }

    /** POST /api/auth/register/email  {email, password, nickname?}
     *  申请邮箱注册：发送 30 分钟有效的验证链接到邮箱 */
    public function registerByEmail()
    {
        $email    = trim($this->input('email', ''));
        $password = $this->input('password', '');
        $nickname = trim($this->input('nickname', '')) ?: ('用户' . explode('@', $email)[0]);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return $this->fail('邮箱格式错误');
        if (strlen($password) < 6) return $this->fail('密码至少 6 位');
        if (strlen($password) > 64) return $this->fail('密码不能超过 64 位');
        if (mb_strlen($nickname) > 20) return $this->fail('昵称不能超过 20 字');

        $ip = RateLimiter::ip();
        if (!RateLimiter::hit('register_email', $ip . '|' . $email, 3, 3600)) {
            return $this->fail('该邮箱申请过于频繁，请 1 小时后再试', 429, 429);
        }

        $exists = Database::one('SELECT id FROM users WHERE email=?', [$email]);
        if ($exists) return $this->fail('该邮箱已注册');

        // 同一邮箱 5 分钟内已有待验证记录则复用
        $recent = Database::one(
            "SELECT id, token FROM email_verifications WHERE email=? AND status=0 AND expires_at>?",
            [$email, date('Y-m-d H:i:s', time() - 300)]
        );

        $token = $recent['token'] ?? bin2hex(random_bytes(24));
        $now   = date('Y-m-d H:i:s');
        $exp   = date('Y-m-d H:i:s', time() + 1800);  // 30 分钟

        if ($recent) {
            Database::update('email_verifications', [
                'password'   => password_hash($password, PASSWORD_DEFAULT),
                'nickname'   => $nickname,
                'expires_at' => $exp,
            ], 'id=?', [$recent['id']]);
        } else {
            Database::insert('email_verifications', [
                'email'      => $email,
                'token'      => $token,
                'password'   => password_hash($password, PASSWORD_DEFAULT),
                'nickname'   => $nickname,
                'status'     => 0,
                'expires_at' => $exp,
                'created_at' => $now,
            ]);
        }

        $baseUrl = rtrim(env('APP_URL') ?: 'https://timepost.cn', '/');
        $verifyUrl = $baseUrl . '/api/auth/email/verify?token=' . $token;

        $mailer = new MailSender();
        $html = MailTemplate::emailVerify($verifyUrl, $email, env('APP_NAME', '时光邮局'));
        $result = $mailer->send($email, '【时光邮局】邮箱验证 - 完成你的注册', $html);

        if (!$result['ok']) {
            return $this->fail('验证邮件发送失败：' . $result['error'], 500, 500);
        }

        $msg = $result['mock'] ?? false
            ? '已发送验证邮件（开发模式：未真实发送，请检查日志）'
            : '验证邮件已发送，请在 30 分钟内点击邮件中的链接完成注册';
        return $this->ok(['email' => $email, 'expires_in' => 1800], $msg);
    }

    /** GET /api/auth/email/verify?token=xxx
     *  验证邮箱并创建账号 */
    public function verifyEmail()
    {
        $token = trim($this->input('token', ''));
        if (strlen($token) < 32) return $this->fail('验证链接无效', 400, 400);

        $row = Database::one('SELECT * FROM email_verifications WHERE token=?', [$token]);
        if (!$row) return $this->fail('验证链接无效', 400, 400);
        if ((int)$row['status'] === 1) return $this->fail('该链接已被使用，请直接登录');
        if (strtotime($row['expires_at']) < time()) {
            Database::update('email_verifications', ['status' => 2], 'id=?', [$row['id']]);
            return $this->fail('验证链接已过期，请重新申请注册');
        }

        // 二次校验，避免期间被注册
        $exists = Database::one('SELECT id FROM users WHERE email=?', [$row['email']]);
        if ($exists) {
            Database::update('email_verifications', ['status' => 1, 'used_at' => date('Y-m-d H:i:s')], 'id=?', [$row['id']]);
            return $this->fail('该邮箱已注册，请直接登录');
        }

        $now = date('Y-m-d H:i:s');
        $nickname = $row['nickname'] ?: ('用户' . substr($row['email'], 0, 4));
        $uid = Database::insert('users', [
            'email'        => $row['email'],
            'password'     => $row['password'],
            'nickname'     => $nickname,
            'status'       => 1,
            'union_source' => 'local',
            'created_at'   => $now,
        ]);
        Database::update('email_verifications', [
            'status' => 1, 'used_at' => $now,
        ], 'id=?', [$row['id']]);

        // 验证成功后返回登录页（带成功提示）
        $token = JwtHelper::issue($uid);
        $loginUrl = rtrim(env('APP_URL') ?: '', '/') . '/login?verified=1&token=' . urlencode($token);
        header('Location: ' . $loginUrl);
        exit;
    }

    /** POST /api/auth/login  {account, password}  account 支持手机号/邮箱 */
    public function login()
    {
        $account = trim($this->input('account', ''));
        $password = $this->input('password', '');
        if (!$account || !$password) return $this->fail('账号或密码不能为空');

        // 限流：单账号 10 分钟内最多 5 次登录失败尝试
        $ip = RateLimiter::ip();
        if (!RateLimiter::hit('login', $account . '|' . $ip, 5, 600)) {
            return $this->fail('登录尝试过于频繁，请 10 分钟后再试', 429, 429);
        }

        $user = Database::one('SELECT * FROM users WHERE phone=? OR email=?', [$account, $account]);
        if (!$user || !password_verify($password, $user['password'])) {
            return $this->fail('账号或密码错误');
        }
                $needSecurity = empty($user['sec_question']);
        if ($user['status'] != 1) return $this->fail('账号已被禁用');

        return $this->ok([
            'token' => JwtHelper::issue($user['id']),
            'user_id' => (int)$user['id'],
            'nickname' => $user['nickname'],
            'avatar' => $user['avatar'],
            'need_security' => $needSecurity,
        ]);
    }

    /** POST /api/auth/logout */
    public function logout()
    {
        // JWT 无状态，前端丢弃 token 即可
        return $this->ok(null, '已退出');
    }

    /** GET /api/user/profile */
    public function profile()
    {
        $uid = $this->requireLogin();
        $user = Database::one('SELECT id, phone, email, nickname, avatar, union_source, password, sec_question, sec_answer, created_at FROM users WHERE id=?', [$uid]);
        if (!$user) return $this->fail('用户不存在', 404, 404);
        $user['has_sec_question'] = !empty($user['sec_question']);
        $user['has_password']     = !empty($user['password']);
        unset($user['sec_answer'], $user['password']);
        return $this->ok($user);
    }

    /** POST /api/user/profile  更新个人信息 {nickname, avatar?} */
    public function updateProfile()
    {
        $uid = $this->requireLogin();
        $nickname = trim($this->input('nickname', ''));
        $avatar   = trim($this->input('avatar', ''));
        if (mb_strlen($nickname) < 1 || mb_strlen($nickname) > 20) return $this->fail('昵称长度需在 1-20 字之间');

        $update = ['nickname' => $nickname, 'updated_at' => date('Y-m-d H:i:s')];
        if ($avatar !== '') $update['avatar'] = $avatar;
        Database::update('users', $update, 'id=?', [$uid]);
        return $this->ok(['nickname' => $nickname], '已保存');
    }

    /** POST /api/user/password  修改密码 {old_password, new_password}  仅本地账号 */
    public function changePassword()
    {
        $uid = $this->requireLogin();
        $old = $this->input('old_password', '');
        $new = $this->input('new_password', '');
        if (strlen($new) < 6) return $this->fail('新密码至少 6 位');
        if (strlen($new) > 64) return $this->fail('新密码不能超过 64 位');

        $user = Database::one('SELECT password, union_source FROM users WHERE id=?', [$uid]);
        if (!$user) return $this->fail('用户不存在', 404, 404);
        if (($user['union_source'] ?? 'local') !== 'local' || empty($user['password'])) {
            return $this->fail('第三方登录账号不支持修改密码');
        }
        if (!password_verify($old, $user['password'])) return $this->fail('原密码不正确');

        Database::update('users', [
            'password' => password_hash($new, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id=?', [$uid]);
        return $this->ok(null, '密码已修改');
    }

    /** POST /api/user/security  设置/修改密保问题  {question, answer} */
    public function setSecurity()
    {
        $uid = $this->requireLogin();
        $question = trim($this->input('question', ''));
        $answer   = trim($this->input('answer', ''));
        if (mb_strlen($question) < 2 || mb_strlen($question) > 60) return $this->fail('问题长度需在 2-60 字之间');
        if (mb_strlen($answer) < 1 || mb_strlen($answer) > 60) return $this->fail('答案长度需在 1-60 字之间');

        Database::update('users', [
            'sec_question' => $question,
            'sec_answer'   => password_hash(strtolower($answer), PASSWORD_DEFAULT),
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id=?', [$uid]);

        return $this->ok(null, '密保问题已保存');
    }

    /** GET /api/user/security  获取当前用户的密保问题 */
    public function getSecurity()
    {
        $uid = $this->requireLogin();
        $user = Database::one('SELECT sec_question FROM users WHERE id=?', [$uid]);
        return $this->ok([
            'has_question' => !empty($user['sec_question']),
            'question'     => $user['sec_question'] ?? '',
        ]);
    }

    /** POST /api/user/security/verify  验证密保答案  {answer}
     *  用于在 H5 查看私密信件前进行身份核验 */
    public function verifySecurity()
    {
        $answer = trim($this->input('answer', ''));
        $letterId = (int)$this->input('letter_id', 0);
        if ($answer === '' || $letterId <= 0) return $this->fail('参数错误');

        $letter = Database::one('SELECT user_id FROM letters WHERE id=?', [$letterId]);
        if (!$letter) return $this->fail('信件不存在', 404, 404);

        $user = Database::one('SELECT sec_question, sec_answer FROM users WHERE id=?', [$letter['user_id']]);
        if (!$user || empty($user['sec_question']) || empty($user['sec_answer'])) {
            return $this->fail('该用户未设置密保问题，无法验证');
        }
        if (!password_verify(strtolower($answer), $user['sec_answer'])) {
            return $this->fail('密保答案不正确');
        }
        return $this->ok(null, '验证通过');
    }
}
