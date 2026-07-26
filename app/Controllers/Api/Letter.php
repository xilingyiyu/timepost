<?php
// 信件 API

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\Database;
use App\Libraries\RateLimiter;

class Letter extends BaseController
{
    /** POST /api/letter
     *  {title, content, deliver_channel, recipient_name, recipient_phone?, recipient_email?,
     *   send_time, is_public?, attachment_ids?}
     */
    public function create()
    {
        $uid = $this->requireLogin();

        // 限流：单用户每小时最多 20 封信
        if (!RateLimiter::hit('letter_create', (string)$uid, 20, 3600)) {
            return $this->fail('写信过于频繁，请稍后再试', 429, 429);
        }

        $title    = trim($this->input('title', ''));
        $content  = trim($this->input('content', ''));
        $channel  = (int)$this->input('deliver_channel', 0);
        $rName    = trim($this->input('recipient_name', ''));
        $rPhone   = trim($this->input('recipient_phone', ''));
        $rEmail   = trim($this->input('recipient_email', ''));
        $sendTime = trim($this->input('send_time', ''));
        $isPublic = (int)$this->input('is_public', 0);
        $attIds   = $this->input('attachment_ids', []);

        if ($title === '' || $content === '') return $this->fail('标题和内容不能为空');
        if (!in_array($channel, [1, 2, 3])) return $this->fail('投递渠道错误');
        if ($rName === '') return $this->fail('收件人姓名不能为空');
        if ($channel & 1 && !preg_match('/^1[3-9]\d{9}$/', $rPhone)) return $this->fail('收件人手机号错误');
        if ($channel & 2 && !filter_var($rEmail, FILTER_VALIDATE_EMAIL)) return $this->fail('收件人邮箱错误');
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $sendTime)) return $this->fail('投递时间格式错误');
        if (strtotime($sendTime) <= time()) return $this->fail('投递时间必须晚于当前时间');

        $now = date('Y-m-d H:i:s');
        $viewToken = bin2hex(random_bytes(16));

        $lid = Database::insert('letters', [
            'user_id'         => $uid,
            'title'           => $title,
            'content'         => $content,
            'deliver_channel' => $channel,
            'recipient_name'  => $rName,
            'recipient_phone' => $rPhone ?: null,
            'recipient_email' => $rEmail ?: null,
            'send_time'       => $sendTime,
            'status'          => 0,
            'is_public'       => $isPublic,
            'view_token'      => $viewToken,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        // 绑定附件
        if (!empty($attIds) && is_array($attIds)) {
            foreach ($attIds as $aid) {
                Database::update('letter_attachments', ['letter_id' => $lid], 'id=? AND user_id=?', [(int)$aid, $uid]);
            }
        }

        return $this->ok(['id' => $lid, 'view_token' => $viewToken], '信件已保存');
    }

    /** GET /api/letter?page=1&status=  我的信件列表 */
    public function list()
    {
        $uid = $this->requireLogin();
        $page = max(1, (int)$this->input('page', 1));
        $size = min(50, max(10, (int)$this->input('page_size', 20)));
        $status = $this->input('status');
        $offset = ($page - 1) * $size;

        $where = 'user_id=?';
        $params = [$uid];
        if ($status !== null && $status !== '') {
            $where .= ' AND status=?';
            $params[] = (int)$status;
        }

        $total = (int)Database::one("SELECT COUNT(*) AS c FROM letters WHERE {$where}", $params)['c'];
        $rows = Database::all(
            "SELECT id, title, deliver_channel, recipient_name, send_time, status, is_public, attachment_count, created_at
             FROM (SELECT l.*, (SELECT COUNT(*) FROM letter_attachments a WHERE a.letter_id=l.id) AS attachment_count
                   FROM letters l WHERE {$where}
                   ORDER BY created_at DESC LIMIT {$size} OFFSET {$offset})",
            $params
        );

        return $this->ok(['list' => $rows, 'total' => $total, 'page' => $page, 'page_size' => $size]);
    }

    /** GET /api/letter/{id}  信件详情 */
    public function show(int $id)
    {
        $uid = $this->requireLogin();
        $letter = Database::one('SELECT * FROM letters WHERE id=? AND user_id=?', [$id, $uid]);
        if (!$letter) return $this->fail('信件不存在', 404, 404);

        $attachments = Database::all(
            'SELECT id, file_name, file_size, file_type, mime_type, COALESCE(local_url, share_url) as share_url, share_password, created_at
             FROM letter_attachments WHERE letter_id=? ORDER BY id ASC',
            [$id]
        );
        $letter['attachments'] = $attachments;
        // 附带回复列表
        $letter['replies'] = Database::all(
            'SELECT id, from_role, author_name, content, created_at FROM letter_replies WHERE letter_id=? ORDER BY id ASC',
            [$id]
        );
        return $this->ok($letter);
    }

    /** POST /api/letter/{id}/reply  寄件人回复（需登录） {content} */
    public function reply(int $id)
    {
        $uid = $this->requireLogin();
        $letter = Database::one('SELECT id, user_id, recipient_name FROM letters WHERE id=?', [$id]);
        if (!$letter) return $this->fail('信件不存在', 404, 404);
        if ((int)$letter['user_id'] !== $uid) return $this->fail('无权回复此信件', 403, 403);
        $content = trim($this->input('content', ''));
        if ($content === '') return $this->fail('回复内容不能为空');
        if (mb_strlen($content) > 2000) return $this->fail('回复内容不能超过 2000 字');

        $user = Database::one('SELECT nickname FROM users WHERE id=?', [$uid]);
        Database::insert('letter_replies', [
            'letter_id'   => $id,
            'from_role'   => 'sender',
            'author_name' => $user['nickname'] ?? '寄件人',
            'content'     => $content,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        return $this->ok(null, '回复已发送');
    }

    /** POST /v/{token}/reply  收件人在 H5 查看页回复（无需登录） {author, content} */
    public function recipientReply(string $token)
    {
        $letter = Database::one('SELECT id, recipient_name FROM letters WHERE view_token=?', [$token]);
        if (!$letter) return $this->fail('信件不存在', 404, 404);
        $author = trim($this->input('author', '')) ?: $letter['recipient_name'];
        $content = trim($this->input('content', ''));
        if ($content === '') return $this->fail('回复内容不能为空');
        if (mb_strlen($author) > 20) return $this->fail('署名不能超过 20 字');
        if (mb_strlen($content) > 2000) return $this->fail('回复内容不能超过 2000 字');

        Database::insert('letter_replies', [
            'letter_id'   => $letter['id'],
            'from_role'   => 'recipient',
            'author_name' => $author,
            'content'     => $content,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        return $this->ok(null, '回复已送达给寄件人');
    }

    /** GET /v/{token}/replies  收件人在 H5 查看页获取对话记录 */
    public function publicReplies(string $token)
    {
        $letter = Database::one('SELECT id FROM letters WHERE view_token=?', [$token]);
        if (!$letter) return $this->fail('信件不存在', 404, 404);
        $list = Database::all(
            'SELECT id, from_role, author_name, content, created_at FROM letter_replies WHERE letter_id=? ORDER BY id ASC',
            [$letter['id']]
        );
        return $this->ok(['list' => $list]);
    }

    /** POST /api/letter/{id}/cancel  撤回（仅未发送可撤回） */
    public function cancel(int $id)
    {
        $uid = $this->requireLogin();
        $letter = Database::one('SELECT status FROM letters WHERE id=? AND user_id=?', [$id, $uid]);
        if (!$letter) return $this->fail('信件不存在', 404, 404);
        if ((int)$letter['status'] !== 0) return $this->fail('当前状态不可撤回');

        Database::update('letters', ['status' => 3, 'updated_at' => date('Y-m-d H:i:s')], 'id=?', [$id]);
        return $this->ok(null, '已撤回');
    }

    /** GET /api/letter/{id}/security-question  获取信件寄件人的密保问题（无需登录，收件人可见）
     *  只返回是否有密保 + 问题内容，绝不返回答案 */
    public function securityQuestion(int $id)
    {
        $letter = Database::one('SELECT user_id FROM letters WHERE id=?', [$id]);
        if (!$letter) return $this->fail('信件不存在', 404, 404);

        $user = Database::one('SELECT sec_question FROM users WHERE id=?', [$letter['user_id']]);
        $q = $user['sec_question'] ?? '';
        return $this->ok([
            'has_question' => !empty($q),
            'question'     => $q,
        ]);
    }

    /** GET /api/letter/public?page=1  公开信件（已发送）
     *  前台只返回元信息和内容摘要，不返回完整正文 */
    public function publicList()
    {
        $page = max(1, (int)$this->input('page', 1));
        $size = min(50, max(10, (int)$this->input('page_size', 20)));
        $offset = ($page - 1) * $size;

        $total = (int)Database::one("SELECT COUNT(*) AS c FROM letters WHERE is_public=1 AND status=1")['c'];
        $rows = Database::all(
            "SELECT id, title, content, recipient_name, sent_at, view_token
             FROM letters WHERE is_public=1 AND status=1
             ORDER BY sent_at DESC LIMIT {$size} OFFSET {$offset}"
        );
        // 前台隐藏完整正文，只保留摘要 + 内容长度
        foreach ($rows as &$r) {
            $full = $r['content'] ?? '';
            $r['content_length'] = mb_strlen($full);
            $r['excerpt'] = mb_substr($full, 0, 50) . (mb_strlen($full) > 50 ? '…' : '');
            unset($r['content']);
        }
        return $this->ok(['list' => $rows, 'total' => $total, 'page' => $page, 'page_size' => $size]);
    }
}
