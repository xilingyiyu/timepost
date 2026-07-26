<?php
// 第三方聚合登录（彩虹 u.arsn.cn）

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\CaihongOauth;
use App\Libraries\Database;
use App\Libraries\JwtHelper;

class OauthLogin extends BaseController
{
    /** GET /api/auth/oauth/url?type=wx */
    public function getUrl()
    {
        $type = $this->input('type', '');
        $oauth = new CaihongOauth();
        if (!$oauth->isConfigured()) return $this->fail('聚合登录未配置', 500, 500);
        try {
            $data = $oauth->getLoginUrl($type);
            return $this->ok(['url' => $data['url'], 'qrcode' => $data['qrcode'] ?? null]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 500, 500);
        }
    }

    /** GET /api/auth/oauth/callback?type=wx&code=XXX  彩虹回调入口 */
    public function callback()
    {
        $type = $this->input('type', '');
        $code = $this->input('code', '');
        if (!$type || !$code) return $this->fail('缺少参数');
        // 渲染中转页：把 type+code 传给前端 JS 完成登录
        $this->view('oauth/callback', ['type' => $type, 'code' => $code]);
    }

    /** POST /api/auth/oauth/login  {type, code} */
    public function login()
    {
        $type = $this->input('type', '');
        $code = $this->input('code', '');
        if (!$type || !$code) return $this->fail('缺少参数');

        $oauth = new CaihongOauth();
        if (!$oauth->isConfigured()) return $this->fail('聚合登录未配置', 500, 500);
        try {
            $info = $oauth->getUserInfo($type, $code);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 500, 500);
        }

        $socialUid = $info['social_uid'];
        $nickname  = $info['nickname'] ?? ('用户' . substr($socialUid, 0, 6));
        $avatar    = $info['faceimg'] ?? '';
        $token     = $info['access_token'] ?? '';

        // 查找已绑定记录
        $binding = Database::one('SELECT * FROM user_oauths WHERE platform=? AND social_uid=?', [$type, $socialUid]);
        if ($binding) {
            $userId = (int)$binding['user_id'];
            // 更新昵称/头像/token
            Database::update('user_oauths',
                ['access_token' => $token, 'nickname' => $nickname, 'avatar' => $avatar, 'updated_at' => date('Y-m-d H:i:s')],
                'id=?', [$binding['id']]
            );
        } else {
            // 自动创建新用户（无密码）
            $now = date('Y-m-d H:i:s');
            $userId = Database::insert('users', [
                'nickname' => $nickname, 'avatar' => $avatar,
                'status' => 1, 'union_source' => 'oauth', 'created_at' => $now,
            ]);
            Database::insert('user_oauths', [
                'user_id' => $userId, 'platform' => $type, 'social_uid' => $socialUid,
                'access_token' => $token, 'nickname' => $nickname, 'avatar' => $avatar,
                'created_at' => $now,
            ]);
        }

        $needSecurity = empty(Database::one('SELECT sec_question FROM users WHERE id=?', [$userId])['sec_question']);
        return $this->ok([
            'token' => JwtHelper::issue($userId),
            'user_id' => $userId,
            'nickname' => $nickname,
            'avatar' => $avatar,
            'need_security' => $needSecurity,
        ]);
    }
}
