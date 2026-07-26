<?php
// 彩虹聚合登录客户端（u.arsn.cn）
// 文档: https://u.arsn.cn/doc.php

namespace App\Libraries;

class CaihongOauth
{
    private string $base;
    private string $appid;
    private string $appkey;
    private string $redirect;

    // u.arsn.cn 支持的 21 种登录方式
    public const TYPES = [
        'qq'        => 'QQ',
        'wx'        => '微信',
        'wxmp'      => '公众号',
        'alipay'    => '支付宝',
        'sina'      => '微博',
        'baidu'     => '百度',
        'huawei'    => '华为',
        'xiaomi'    => '小米',
        'google'    => '谷歌',
        'aliyun'    => '阿里云',
        'microsoft' => '微软',
        'gitea'     => 'Gitea',
        'facebook'  => 'Facebook',
        'gitlab'    => 'Gitlab',
        'twitter'   => 'Twitter',
        'telegram'  => 'Telegram',
        'feishu'    => '飞书',
        'wework'    => '企业微信',
        'dingtalk'  => '钉钉',
        'gitee'     => 'Gitee',
        'github'    => 'GitHub',
    ];

    public function __construct()
    {
        $this->base    = config('caihong.base');
        $this->appid   = config('caihong.appid');
        $this->appkey  = config('caihong.appkey');
        $this->redirect = config('caihong.redirect');
    }

    public function isConfigured(): bool
    {
        return $this->appid !== '' && $this->appkey !== '' && $this->redirect !== '';
    }

    /** Step1: 获取登录跳转 URL */
    public function getLoginUrl(string $type): array
    {
        $this->validateType($type);
        $url = $this->base . '?' . http_build_query([
            'act'          => 'login',
            'appid'        => $this->appid,
            'appkey'       => $this->appkey,
            'type'         => $type,
            'redirect_uri' => $this->redirect,
        ]);
        $res = $this->http($url);
        if (($res['code'] ?? -1) !== 0) {
            throw new \RuntimeException($res['msg'] ?? '获取登录URL失败');
        }
        return $res;
    }

    /** Step4: 用 code 换用户信息 */
    public function getUserInfo(string $type, string $code): array
    {
        $this->validateType($type);
        $url = $this->base . '?' . http_build_query([
            'act'    => 'callback',
            'appid'  => $this->appid,
            'appkey' => $this->appkey,
            'type'   => $type,
            'code'   => $code,
        ]);
        $res = $this->http($url);
        if (($res['code'] ?? -1) !== 0) {
            throw new \RuntimeException($res['msg'] ?? '获取用户信息失败');
        }
        return $res;
    }

    /** 用 social_uid 查询用户信息（用于刷新） */
    public function queryUser(string $type, string $socialUid): array
    {
        $this->validateType($type);
        $url = $this->base . '?' . http_build_query([
            'act'        => 'query',
            'appid'      => $this->appid,
            'appkey'     => $this->appkey,
            'type'       => $type,
            'social_uid' => $socialUid,
        ]);
        $res = $this->http($url);
        if (($res['code'] ?? -1) !== 0) {
            throw new \RuntimeException($res['msg'] ?? '查询用户失败');
        }
        return $res;
    }

    private function validateType(string $type): void
    {
        if (!isset(self::TYPES[$type])) {
            throw new \RuntimeException('不支持的登录方式: ' . $type);
        }
    }

    private function http(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($body === false) throw new \RuntimeException('聚合登录请求失败: ' . $err);
        return json_decode($body, true) ?: [];
    }
}
