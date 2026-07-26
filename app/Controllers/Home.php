<?php
// 页面控制器

namespace App\Controllers;

use App\Libraries\CaihongOauth;
use App\Libraries\Database;

class Home extends BaseController
{
    /** 首页 */
    public function index()
    {
        $siteName = Database::one("SELECT svalue FROM settings WHERE skey='site_name'")['svalue'] ?? '时光邮局';
        $siteSlogan = Database::one("SELECT svalue FROM settings WHERE skey='site_slogan'")['svalue'] ?? '';
        $publicLetters = Database::all(
            "SELECT id, title, content, recipient_name, sent_at, view_token
             FROM letters WHERE is_public=1 AND status=1 AND audit_status=1
             ORDER BY CASE WHEN title IN ('致我永远的盛夏','时光里的我们','星空下的约定') THEN 0 ELSE 1 END,
                      sent_at DESC LIMIT 12"
        );
        // 前台隐藏完整正文，只展示摘要；并附带首张配图
        $letterIds = array_column($publicLetters, 'id');
        $attachMap = [];
        if (!empty($letterIds)) {
            $in = implode(',', array_fill(0, count($letterIds), '?'));
            $rows = Database::all(
                "SELECT letter_id, COALESCE(local_url, share_url) as share_url FROM letter_attachments
                 WHERE file_type='image' AND letter_id IN ({$in})
                 ORDER BY id ASC",
                $letterIds
            );
            foreach ($rows as $r) {
                if (!isset($attachMap[$r['letter_id']])) {
                    $attachMap[$r['letter_id']] = $r['share_url'];
                }
            }
        }
        foreach ($publicLetters as &$l) {
            $full = $l['content'] ?? '';
            $l['excerpt'] = mb_substr($full, 0, 60) . (mb_strlen($full) > 60 ? '…' : '');
            $l['content_length'] = mb_strlen($full);
            $l['cover_url'] = $attachMap[$l['id']] ?? '';
            $l['is_featured'] = in_array($l['title'], ['致我永远的盛夏','时光里的我们','星空下的约定'], true) ? 1 : 0;
            unset($l['content']);
        }
        $this->view('home', [
            'siteName' => $siteName,
            'siteSlogan' => $siteSlogan,
            'publicLetters' => $publicLetters,
        ]);
    }

    /** 写信页 */
    public function write()
    {
        $siteName = Database::one("SELECT svalue FROM settings WHERE skey='site_name'")['svalue'] ?? '时光邮局';
        $this->view('write', ['siteName' => $siteName]);
    }

    /** 登录页 */
    public function login()
    {
        $oauth = new CaihongOauth();
        $oauthTypes = $oauth->isConfigured() ? CaihongOauth::TYPES : [];
        // 展示这 8 种官方品牌登录
        $showTypes = array_intersect_key($oauthTypes, array_flip(['wx', 'alipay', 'qq', 'dingtalk', 'twitter', 'google', 'github', 'gitee']));
        $this->view('login', [
            'siteName' => Database::one("SELECT svalue FROM settings WHERE skey='site_name'")['svalue'] ?? '时光邮局',
            'oauthEnabled' => $oauth->isConfigured(),
            'oauthTypes' => $showTypes,
        ]);
    }

    /** 我的信件（已整合进用户中心，重定向到 /settings） */
    public function letters()
    {
        header('Location: /settings');
        exit;
    }

    /** 我的信件（已整合进用户中心，重定向到 /settings） */
    public function myLetters()
    {
        header('Location: /settings');
        exit;
    }

    /** 用户设置页（密保问题等） */
    public function settings()
    {
        $siteName = Database::one("SELECT svalue FROM settings WHERE skey='site_name'")['svalue'] ?? '时光邮局';
        $this->view('settings', ['siteName' => $siteName]);
    }

    /** H5 查看信件 */
    public function viewLetter(string $token)
    {
        $letter = Database::one('SELECT * FROM letters WHERE view_token=?', [$token]);
        if (!$letter) {
            http_response_code(404);
            echo '<h1 style="text-align:center;padding:60px;">信件不存在或已被撤回</h1>';
            return;
        }
        $attachments = Database::all(
            'SELECT id, file_name, file_size, file_type, mime_type, COALESCE(local_url, share_url) as share_url, share_password
             FROM letter_attachments WHERE letter_id=? ORDER BY id ASC',
            [$letter['id']]
        );
        $siteName = Database::one("SELECT svalue FROM settings WHERE skey='site_name'")['svalue'] ?? '时光邮局';
        $this->view('view_letter', ['letter' => $letter, 'attachments' => $attachments, 'siteName' => $siteName]);
    }
}
