<?php
// 后台管理 API

namespace App\Controllers;

use App\Libraries\Database;
use App\Libraries\RateLimiter;

class AdminApi extends BaseController
{
    public function __construct()
    {
        $this->requireAdmin();
    }

    private function requireAdmin(): void
    {
        // SameSite=Lax 防 CSRF（PHP 7.3+ 原生支持）
        $params = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => $params['lifetime'],
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        if (empty($_SESSION['admin_id'])) {
            $this->json(['code' => 401, 'msg' => '未登录'], 401);
        }

        // CSRF：非 GET 请求必须同源（Origin / Referer 校验）
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'GET') {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            $ok = false;
            if ($origin && parse_url($origin, PHP_URL_HOST) === $host) $ok = true;
            if (!$ok && $referer && parse_url($referer, PHP_URL_HOST) === $host) $ok = true;
            // 同源标识头（admin.js 全局注入）
            if (!$ok && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') $ok = true;
            if (!$ok) {
                $this->json(['code' => 403, 'msg' => 'CSRF 校验失败：跨站请求被拒绝'], 403);
            }
        }
    }

    /** 记录管理员操作审计日志 */
    private function audit(string $action, string $targetType, $targetId, string $detail = ''): void
    {
        Database::insert('admin_audit_logs', [
            'admin_id'    => (int)($_SESSION['admin_id'] ?? 0),
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => (string)$targetId,
            'detail'      => $detail,
            'ip'          => RateLimiter::ip(),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /** GET /admin/api/audit?page=&action=  操作审计日志查询 */
    public function auditList()
    {
        $page = max(1, (int)$this->input('page', 1));
        $size = min(100, max(10, (int)$this->input('page_size', 20)));
        $offset = ($page - 1) * $size;
        $action = trim($this->input('action', ''));

        $where = '1=1';
        $params = [];
        if ($action !== '') {
            $where .= ' AND a.action=?';
            $params[] = $action;
        }

        $total = (int)Database::one("SELECT COUNT(*) c FROM admin_audit_logs a WHERE {$where}", $params)['c'];
        $list = Database::all(
            "SELECT a.*, u.username AS admin_name
             FROM admin_audit_logs a LEFT JOIN admins u ON u.id=a.admin_id
             WHERE {$where}
             ORDER BY a.id DESC LIMIT {$size} OFFSET {$offset}",
            $params
        );
        return $this->ok(['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $size]);
    }

    /** GET /admin/api/dashboard  仪表盘统计 */
    public function dashboard()
    {
        $stats = [
            'letters_total'    => (int)Database::one("SELECT COUNT(*) c FROM letters")['c'],
            'letters_pending'  => (int)Database::one("SELECT COUNT(*) c FROM letters WHERE status=0")['c'],
            'letters_sent'     => (int)Database::one("SELECT COUNT(*) c FROM letters WHERE status=1")['c'],
            'letters_failed'   => (int)Database::one("SELECT COUNT(*) c FROM letters WHERE status=2")['c'],
            'letters_canceled' => (int)Database::one("SELECT COUNT(*) c FROM letters WHERE status=3")['c'],
            'letters_audit_pending' => (int)Database::one("SELECT COUNT(*) c FROM letters WHERE is_public=1 AND status=1 AND audit_status=0")['c'],
            'users_total'      => (int)Database::one("SELECT COUNT(*) c FROM users")['c'],
            'attachments_total'=> (int)Database::one("SELECT COUNT(*) c FROM letter_attachments")['c'],
            'attachments_size' => (int)Database::one("SELECT COALESCE(SUM(file_size),0) s FROM letter_attachments")['s'],
            'today_created'    => (int)Database::one("SELECT COUNT(*) c FROM letters WHERE created_at>=datetime('now','start of day','+8 hours')")['c'],
            'today_sent'       => (int)Database::one("SELECT COUNT(*) c FROM letters WHERE sent_at>=datetime('now','start of day','+8 hours')")['c'],
        ];

        // 最近 7 天趋势（按北京时间）
        $trend = Database::all("
            SELECT date(created_at, '+8 hours') AS d, COUNT(*) AS c
            FROM letters WHERE created_at >= datetime('now','-6 days','+8 hours')
            GROUP BY date(created_at, '+8 hours') ORDER BY d ASC
        ");

        // 最近 10 封信件
        $recent = Database::all("SELECT id, title, recipient_name, status, send_time, created_at FROM letters ORDER BY id DESC LIMIT 10");

        return $this->ok([
            'stats' => $stats,
            'trend' => $trend,
            'recent' => $recent,
        ]);
    }

    /** GET /admin/api/letters?page=&status=&keyword= */
    public function letters()
    {
        $page = max(1, (int)$this->input('page', 1));
        $size = min(100, max(10, (int)$this->input('page_size', 20)));
        $status = $this->input('status');
        $audit = $this->input('audit');
        $keyword = trim($this->input('keyword', ''));
        $offset = ($page - 1) * $size;

        $where = '1=1';
        $params = [];
        if ($status !== null && $status !== '') { $where .= ' AND status=?'; $params[] = (int)$status; }
        if ($audit !== null && $audit !== '') { $where .= ' AND audit_status=?'; $params[] = (int)$audit; }
        if ($keyword !== '') { $where .= ' AND (title LIKE ? OR recipient_name LIKE ? OR recipient_phone LIKE ? OR recipient_email LIKE ?)';
            $kw = "%{$keyword}%"; $params = array_merge($params, [$kw, $kw, $kw, $kw]); }

        $total = (int)Database::one("SELECT COUNT(*) c FROM letters WHERE {$where}", $params)['c'];
        $list = Database::all(
            "SELECT id, user_id, title, deliver_channel, recipient_name, recipient_phone, recipient_email,
                    send_time, status, is_public, audit_status, retry_count, error_msg, sent_at, created_at
             FROM letters WHERE {$where}
             ORDER BY id DESC LIMIT {$size} OFFSET {$offset}",
            $params
        );

        return $this->ok(['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $size]);
    }

    /** GET /admin/api/letter/{id} */
    public function letterDetail(int $id)
    {
        $letter = Database::one('SELECT * FROM letters WHERE id=?', [$id]);
        if (!$letter) return $this->fail('信件不存在', 404, 404);
        $attachments = Database::all('SELECT * FROM letter_attachments WHERE letter_id=? ORDER BY id ASC', [$id]);
        $logs = Database::all('SELECT * FROM send_logs WHERE letter_id=? ORDER BY id ASC', [$id]);
        $letter['attachments'] = $attachments;
        $letter['logs'] = $logs;
        return $this->ok($letter);
    }

    /** POST /admin/api/letter/{id}/cancel  强制撤回 */
    public function forceCancel(int $id)
    {
        $letter = Database::one('SELECT status, title FROM letters WHERE id=?', [$id]);
        if (!$letter) return $this->fail('信件不存在', 404, 404);
        if ((int)$letter['status'] === 1) return $this->fail('信件已发送，无法撤回');
        Database::update('letters', ['status' => 3, 'updated_at' => date('Y-m-d H:i:s')], 'id=?', [$id]);
        $this->audit('letter_cancel', 'letter', $id, '强制撤回：' . $letter['title']);
        return $this->ok(null, '已强制撤回');
    }

    /** POST /admin/api/letter/{id}/resend  手动重发失败信件 */
    public function resendLetter(int $id)
    {
        $letter = Database::one('SELECT id, status, title, retry_count FROM letters WHERE id=?', [$id]);
        if (!$letter) return $this->fail('信件不存在', 404, 404);
        if ((int)$letter['status'] !== 2) return $this->fail('仅失败信件可重发');
        // 重置为待发送，立即交给 cron 处理
        Database::update('letters', [
            'status'        => 0,
            'retry_count'   => 0,
            'next_retry_at' => null,
            'error_msg'     => null,
            'send_time'     => date('Y-m-d H:i:s', time() + 5),  // 5 秒后触发
            'updated_at'    => date('Y-m-d H:i:s'),
        ], 'id=?', [$id]);
        $this->audit('letter_resend', 'letter', $id, '手动重发：' . $letter['title']);
        return $this->ok(null, '已加入重发队列');
    }

    /** POST /admin/api/letter/{id}/audit  {status: 1通过 2拒绝}  公开信件审核 */
    public function auditLetter(int $id)
    {
        $status = (int)$this->input('status', 0);
        if (!in_array($status, [1, 2], true)) return $this->fail('审核状态值错误');
        $letter = Database::one('SELECT id, title, is_public FROM letters WHERE id=?', [$id]);
        if (!$letter) return $this->fail('信件不存在', 404, 404);
        if ((int)$letter['is_public'] !== 1) return $this->fail('该信件未申请公开');
        Database::update('letters', ['audit_status' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id=?', [$id]);
        $this->audit('letter_audit', 'letter', $id, ($status === 1 ? '审核通过上墙：' : '审核拒绝上墙：') . $letter['title']);
        return $this->ok(null, $status === 1 ? '已通过，信件将在公开墙展示' : '已拒绝，信件不会公开');
    }

    /** DELETE /admin/api/letter/{id}  彻底删除（连带附件） */
    public function deleteLetter(int $id)
    {
        $letter = Database::one('SELECT title FROM letters WHERE id=?', [$id]);
        Database::q('DELETE FROM letter_attachments WHERE letter_id=?', [$id]);
        Database::q('DELETE FROM send_logs WHERE letter_id=?', [$id]);
        Database::q('DELETE FROM letters WHERE id=?', [$id]);
        $this->audit('letter_delete', 'letter', $id, '删除信件：' . ($letter['title'] ?? ''));
        return $this->ok(null, '已删除');
    }

    /** GET /admin/api/users?page=&keyword= */
    public function users()
    {
        $page = max(1, (int)$this->input('page', 1));
        $size = min(100, max(10, (int)$this->input('page_size', 20)));
        $keyword = trim($this->input('keyword', ''));
        $offset = ($page - 1) * $size;

        $where = '1=1';
        $params = [];
        if ($keyword !== '') {
            $where .= ' AND (phone LIKE ? OR email LIKE ? OR nickname LIKE ?)';
            $kw = "%{$keyword}%"; $params = array_merge($params, [$kw, $kw, $kw]);
        }

        $total = (int)Database::one("SELECT COUNT(*) c FROM users WHERE {$where}", $params)['c'];
        $list = Database::all(
            "SELECT id, phone, email, nickname, avatar, status, union_source, created_at,
                    (SELECT COUNT(*) FROM letters WHERE user_id=users.id) AS letter_count
             FROM users WHERE {$where}
             ORDER BY id DESC LIMIT {$size} OFFSET {$offset}",
            $params
        );
        return $this->ok(['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $size]);
    }

    /** POST /admin/api/user/{id}/status  启用/禁用用户 */
    public function userStatus(int $id)
    {
        $status = (int)$this->input('status', 0);
        if (!in_array($status, [0, 1])) return $this->fail('状态值错误');
        Database::update('users', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id=?', [$id]);
        $this->audit('user_status', 'user', $id, $status === 1 ? '启用用户' : '禁用用户');
        return $this->ok(null, $status === 1 ? '已启用' : '已禁用');
    }

    /** POST /admin/api/user/{id}/password  管理员重置用户密码 {new_password} */
    public function resetUserPassword(int $id)
    {
        $new = $this->input('new_password', '');
        if (strlen($new) < 6) return $this->fail('新密码至少 6 位');
        if (strlen($new) > 64) return $this->fail('新密码不能超过 64 位');

        $user = Database::one('SELECT id, nickname FROM users WHERE id=?', [$id]);
        if (!$user) return $this->fail('用户不存在', 404, 404);

        Database::update('users', [
            'password'   => password_hash($new, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id=?', [$id]);
        $this->audit('user_password_reset', 'user', $id, '重置用户密码：' . ($user['nickname'] ?? ''));
        return $this->ok(null, '密码已重置');
    }

    /** GET /admin/api/export/letters  导出信件 CSV */
    public function exportLetters()
    {
        $status = $this->input('status');
        $where = '1=1';
        $params = [];
        if ($status !== null && $status !== '') { $where .= ' AND status=?'; $params[] = (int)$status; }
        $rows = Database::all(
            "SELECT id, title, recipient_name, recipient_phone, recipient_email,
                    deliver_channel, send_time, status, is_public, audit_status,
                    retry_count, error_msg, sent_at, created_at
             FROM letters WHERE {$where} ORDER BY id DESC",
            $params
        );
        $statusNames = [0 => '待发送', 1 => '已发送', 2 => '失败', 3 => '已撤回'];
        $auditNames  = [0 => '待审核', 1 => '已通过', 2 => '已拒绝', 3 => '私密'];
        $channelNames = [1 => '短信', 2 => '邮件', 3 => '短信+邮件'];

        $this->csvHeaders('letters_' . date('Ymd'));
        $out = fopen('php://output', 'w');
        // BOM 解决 Excel UTF-8 识别
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID', '标题', '收件人', '手机号', '邮箱', '渠道', '投递时间', '状态', '是否公开', '审核状态', '重试次数', '错误信息', '发送时间', '创建时间']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'], $r['title'], $r['recipient_name'], $r['recipient_phone'], $r['recipient_email'],
                $channelNames[$r['deliver_channel']] ?? $r['deliver_channel'],
                $r['send_time'],
                $statusNames[$r['status']] ?? $r['status'],
                $r['is_public'] ? '是' : '否',
                $auditNames[$r['audit_status']] ?? $r['audit_status'],
                $r['retry_count'], $r['error_msg'], $r['sent_at'], $r['created_at'],
            ]);
        }
        fclose($out);
        $this->audit('export', 'letter', '', '导出信件 CSV（' . count($rows) . ' 条）');
        exit;
    }

    /** GET /admin/api/export/users  导出用户 CSV */
    public function exportUsers()
    {
        $rows = Database::all(
            "SELECT id, phone, email, nickname, avatar, union_source, status,
                    (SELECT COUNT(*) FROM letters WHERE user_id=users.id) AS letter_count,
                    created_at, updated_at
             FROM users ORDER BY id DESC"
        );
        $this->csvHeaders('users_' . date('Ymd'));
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID', '手机号', '邮箱', '昵称', '头像', '登录方式', '状态', '信件数', '注册时间', '更新时间']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'], $r['phone'], $r['email'], $r['nickname'], $r['avatar'],
                $r['union_source'] === 'oauth' ? '第三方' : '注册',
                $r['status'] ? '正常' : '禁用',
                $r['letter_count'], $r['created_at'], $r['updated_at'],
            ]);
        }
        fclose($out);
        $this->audit('export', 'user', '', '导出用户 CSV（' . count($rows) . ' 条）');
        exit;
    }

    /** 设置 CSV 下载响应头 */
    private function csvHeaders(string $filename): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
    }

    /** GET /admin/api/settings */
    public function getSettings()
    {
        $rows = Database::all("SELECT skey, svalue FROM settings");
        $settings = [];
        foreach ($rows as $r) $settings[$r['skey']] = $r['svalue'];
        return $this->ok($settings);
    }

    /** POST /admin/api/settings  {key:val,...} */
    public function saveSettings()
    {
        $data = $this->input();
        $allowed = ['site_name','site_slogan','site_desc','sms_sign','max_retry','letter_per_day',
            'smtp_host','smtp_port','smtp_username','smtp_password','smtp_from','smtp_from_name','smtp_encrypt'];
        $changed = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $allowed, true)) {
                $stmt = Database::pdo()->prepare("INSERT INTO settings(skey,svalue) VALUES(?,?) ON CONFLICT(skey) DO UPDATE SET svalue=excluded.svalue");
                $stmt->execute([$k, (string)$v]);
                $changed[] = $k;
            }
        }
        if ($changed) $this->audit('setting_save', 'setting', implode(',', $changed), '修改配置：' . implode(',', $changed));
        return $this->ok(null, '已保存');
    }

    /** POST /admin/api/smtp/test  测试 SMTP 发送 {to} */
    public function testSmtp()
    {
        $to = trim($this->input('to', ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return $this->fail('收件邮箱格式错误');

        $s = $this->settingsMap();
        $mailer = new \App\Libraries\MailSender();
        if (!$mailer->isEnabled()) return $this->fail('SMTP 未配置完整，请先填写服务器地址、用户名、发件邮箱', 400, 400);

        $html = \App\Libraries\MailTemplate::render(
            'SMTP 测试邮件',
            '这是一封来自时光邮局后台的 SMTP 配置测试邮件',
            '<p style="font-size:15px;color:#404040;line-height:1.7;">这是一封来自时光邮局后台的 <strong style="color:#0A0A0A;">SMTP 配置测试邮件</strong>。</p><p style="font-size:15px;color:#404040;line-height:1.7;margin-top:16px;">如果你收到了这封邮件，说明 SMTP 服务器配置正确，可以正常投递信件。</p><p style="font-size:13px;color:#A3A3A3;margin-top:20px;">发送时间：' . date('Y-m-d H:i:s') . '</p>',
            $s['site_name'] ?? '时光邮局'
        );

        $r = $mailer->send($to, '【时光邮局】SMTP 测试邮件', $html);
        if (!$r['ok']) return $this->fail('发送失败：' . $r['error'], 500, 500);
        $this->audit('smtp_test', 'smtp', '', '测试 SMTP 发送到：' . $to);
        return $this->ok(null, $r['mock'] ?? false ? '已模拟发送（开发模式）' : '测试邮件已发送，请查收');
    }

    /** 读取 settings 表为关联数组 */
    private function settingsMap(): array
    {
        $rows = Database::all("SELECT skey, svalue FROM settings");
        $m = [];
        foreach ($rows as $r) $m[$r['skey']] = $r['svalue'];
        return $m;
    }

    /** GET /admin/api/logs?page=&letter_id= */
    public function logs()
    {
        $page = max(1, (int)$this->input('page', 1));
        $size = min(100, max(10, (int)$this->input('page_size', 20)));
        $letterId = $this->input('letter_id');
        $offset = ($page - 1) * $size;

        $where = '1=1';
        $params = [];
        if ($letterId) { $where .= ' AND letter_id=?'; $params[] = (int)$letterId; }

        $total = (int)Database::one("SELECT COUNT(*) c FROM send_logs WHERE {$where}", $params)['c'];
        $list = Database::all(
            "SELECT * FROM send_logs WHERE {$where} ORDER BY id DESC LIMIT {$size} OFFSET {$offset}",
            $params
        );
        return $this->ok(['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $size]);
    }

    /** POST /admin/api/password  修改管理员密码 */
    public function changePassword()
    {
        $aid = $_SESSION['admin_id'];
        $old = $this->input('old_password', '');
        $new = $this->input('new_password', '');
        if (strlen($new) < 6) return $this->fail('新密码至少 6 位');

        $admin = Database::one('SELECT password FROM admins WHERE id=?', [$aid]);
        if (!$admin || !password_verify($old, $admin['password'])) return $this->fail('原密码错误');

        Database::update('admins', ['password' => password_hash($new, PASSWORD_DEFAULT)], 'id=?', [$aid]);
        return $this->ok(null, '密码已修改');
    }
}
