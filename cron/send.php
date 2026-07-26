<?php
// 时光邮局 定时发送脚本
// 用法（crontab）:
//   * * * * * cd /var/www/timepost && php cron/send.php >> storage/logs/cron.log 2>&1
//
// 逻辑：
//   1. 扫描 letters 表：status=0 AND send_time<=NOW()，行锁防并发
//   2. 发送短信/邮件，写 send_logs
//   3. 全部成功 → status=1；任一失败 → status=2，按指数退避设 next_retry_at
//   4. 每 5 分钟扫描失败信件重试（next_retry_at<=NOW() AND retry_count<max_retry）

require_once __DIR__ . '/../config/config.php';
spl_autoload_register(function (string $class) {
    if (!str_starts_with($class, 'App\\')) return;
    $rel = str_replace(['App\\', '\\'], ['', '/'], $class);
    $file = __DIR__ . '/../app/' . $rel . '.php';
    if (is_file($file)) require $file;
});

use App\Libraries\Database;
use App\Libraries\SmsSender;
use App\Libraries\MailSender;

// 防多实例：文件锁
$lockFile = __DIR__ . '/../storage/.cron.lock';
$fp = fopen($lockFile, 'c');
if (!flock($fp, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] 上一次任务还在运行，跳过\n";
    exit(0);
}

$now = date('Y-m-d H:i:s');
$pdo = Database::pdo();
$maxRetry = (int)(Database::one("SELECT svalue FROM settings WHERE skey='max_retry'")['svalue'] ?? 5);

echo "[" . date('Y-m-d H:i:s') . "] 开始扫描待发送信件\n";

// === 第一步：扫描待发送信件 ===
$letters = $pdo->prepare("
    SELECT * FROM letters
    WHERE status = 0 AND send_time <= ?
    ORDER BY send_time ASC LIMIT 100
");
$letters->execute([$now]);
$pending = $letters->fetchAll();

echo "[" . date('Y-m-d H:i:s') . "] 待发送: " . count($pending) . " 封\n";

foreach ($pending as $letter) {
    sendLetter($letter, $maxRetry);
}

// === 第二步：扫描重试信件 ===
$retries = $pdo->prepare("
    SELECT * FROM letters
    WHERE status = 2 AND next_retry_at <= ? AND retry_count < ?
    ORDER BY next_retry_at ASC LIMIT 50
");
$retries->execute([$now, $maxRetry]);
$retryList = $retries->fetchAll();

echo "[" . date('Y-m-d H:i:s') . "] 待重试: " . count($retryList) . " 封\n";

foreach ($retryList as $letter) {
    sendLetter($letter, $maxRetry);
}

// === 第三步：超最大重试次数的标记为永久失败 + 发送告警 ===
$permanentFails = $pdo->query("SELECT id, title, recipient_name, recipient_phone, recipient_email, error_msg FROM letters WHERE status=2 AND retry_count >= {$maxRetry} AND next_retry_at <= '{$now}'")->fetchAll();
if (!empty($permanentFails)) {
    $ids = array_column($permanentFails, 'id');
    $inClause = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("UPDATE letters SET status=2, error_msg='超过最大重试次数，需人工介入', next_retry_at=NULL WHERE id IN ({$inClause})")->execute($ids);
    echo "[" . date('Y-m-d H:i:s') . "] 永久失败信件: " . count($permanentFails) . " 封\n";

    // 发送告警邮件给管理员
    $alertEmail = env('ALERT_EMAIL', '');
    if ($alertEmail) {
        $mail = new MailSender();
        $html = '<h2 style="color:#DC2626;">⚠️ 时光邮局 - 信件发送永久失败告警</h2>';
        $html .= '<p>以下信件已达最大重试次数（' . $maxRetry . ' 次），需人工介入处理：</p>';
        $html .= '<table style="border-collapse:collapse;font-size:14px;" cellpadding="8" border="1">';
        $html .= '<tr style="background:#f5f5f5;"><th>ID</th><th>标题</th><th>收件人</th><th>联系方式</th><th>最后错误</th></tr>';
        foreach ($permanentFails as $lf) {
            $html .= '<tr>';
            $html .= '<td>' . $lf['id'] . '</td>';
            $html .= '<td>' . htmlspecialchars($lf['title']) . '</td>';
            $html .= '<td>' . htmlspecialchars($lf['recipient_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($lf['recipient_phone'] ?: $lf['recipient_email']) . '</td>';
            $html .= '<td style="color:#DC2626;">' . htmlspecialchars($lf['error_msg'] ?: '未知') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '<p style="margin-top:16px;color:#999;font-size:12px;">后台处理：' . config('app.url') . '/admin/letters?status=2</p>';
        $r = $mail->send($alertEmail, '【时光邮局告警】' . count($permanentFails) . ' 封信件发送永久失败', $html);
        echo "[" . date('Y-m-d H:i:s') . "] 告警邮件: " . ($r['ok'] ? '已发送' : '失败 ' . $r['error']) . "\n";
    }
}

flock($fp, LOCK_UN);
fclose($fp);
@unlink($lockFile);
echo "[" . date('Y-m-d H:i:s') . "] 完成\n\n";

// ============== 函数 ==============

function sendLetter(array $letter, int $maxRetry): void
{
    global $pdo;
    $lid = $letter['id'];
    $channel = (int)$letter['deliver_channel'];
    $now = date('Y-m-d H:i:s');

    echo "  → 处理信件 #{$lid} [渠道={$channel}] 致 {$letter['recipient_name']}\n";

    // 立即占用：标记为处理中（status 临时改为 -1，避免被重复扫描）
    // SQLite 无 SELECT FOR UPDATE，用 UPDATE 抢占
    $stmt = $pdo->prepare("UPDATE letters SET updated_at=? WHERE id=? AND status=?");
    $stmt->execute([$now, $lid, (int)$letter['status']]);
    if ($stmt->rowCount() === 0) {
        echo "    ✗ 已被其他进程处理，跳过\n";
        return;
    }

    // 拼接内容
    $viewUrl = config('app.url') . '/v/' . $letter['view_token'];
    $attachments = $pdo->prepare("SELECT * FROM letter_attachments WHERE letter_id=? ORDER BY id ASC");
    $attachments->execute([$lid]);
    $attachList = $attachments->fetchAll();

    $smsContent = "【时光邮局】{$letter['recipient_name']}，你收到一封时光之信：{$letter['title']}。查看：{$viewUrl}";

    $mailHtml = buildMailHtml($letter, $viewUrl, $attachList);

    $successCount = 0;
    $failCount = 0;
    $errors = [];

    // 短信
    if ($channel & 1) {
        $phone = $letter['recipient_phone'];
        if ($phone) {
            $r = (new SmsSender())->send($phone, $smsContent, $viewUrl);
            logSend($lid, 1, $r);
            if ($r['ok']) { $successCount++; echo "    ✓ 短信发送成功 ({$phone})" . (isset($r['mock']) ? " [MOCK]" : "") . "\n"; }
            else { $failCount++; $errors[] = "短信: {$r['error']}"; echo "    ✗ 短信发送失败: {$r['error']}\n"; }
        } else {
            $failCount++; $errors[] = "收件人手机号为空";
        }
    }

    // 邮件
    if ($channel & 2) {
        $email = $letter['recipient_email'];
        if ($email) {
            $r = (new MailSender())->send($email, "【时光邮局】{$letter['title']}", $mailHtml, $letter['recipient_name']);
            logSend($lid, 2, $r);
            if ($r['ok']) { $successCount++; echo "    ✓ 邮件发送成功 ({$email})" . (isset($r['mock']) ? " [MOCK]" : "") . "\n"; }
            else { $failCount++; $errors[] = "邮件: {$r['error']}"; echo "    ✗ 邮件发送失败: {$r['error']}\n"; }
        } else {
            $failCount++; $errors[] = "收件人邮箱为空";
        }
    }

    // 根据结果更新信件状态
    if ($failCount === 0) {
        // 全部成功：公开信件直接上墙（audit_status=1），私密标记为无需审核（3）
        $auditStatus = (int)$letter['is_public'] === 1 ? 1 : 3;
        $pdo->prepare("UPDATE letters SET status=1, sent_at=?, audit_status=?, error_msg=NULL, updated_at=? WHERE id=?")
            ->execute([$now, $auditStatus, $now, $lid]);
        echo "    ✓ 信件 #{$lid} 状态：已发送\n";
    } elseif ($successCount === 0) {
        // 全部失败 → 进入重试
        $retryCount = (int)$letter['retry_count'] + 1;
        if ($retryCount >= $maxRetry) {
            $pdo->prepare("UPDATE letters SET status=2, retry_count=?, error_msg=?, next_retry_at=NULL, updated_at=? WHERE id=?")
                ->execute([$retryCount, implode(' | ', $errors), $now, $lid]);
            echo "    ✗ 信件 #{$lid} 状态：永久失败（已达最大重试次数）\n";
        } else {
            // 指数退避：1min * (n+1) * 2^(n-1)，封顶 6 小时
            $delay = min(6 * 3600, 60 * ($retryCount + 1) * pow(2, $retryCount - 1));
            $nextRetry = date('Y-m-d H:i:s', time() + $delay);
            $pdo->prepare("UPDATE letters SET status=2, retry_count=?, next_retry_at=?, error_msg=?, updated_at=? WHERE id=?")
                ->execute([$retryCount, $nextRetry, implode(' | ', $errors), $now, $lid]);
            echo "    ⏳ 信件 #{$lid} 状态：失败，{$nextRetry} 重试 (第 {$retryCount}/{$maxRetry} 次)\n";
        }
    } else {
        // 部分成功（罕见）→ 标记已发送但保留错误信息
        $pdo->prepare("UPDATE letters SET status=1, sent_at=?, error_msg=?, updated_at=? WHERE id=?")
            ->execute([$now, '部分渠道失败: ' . implode(' | ', $errors), $now, $lid]);
        echo "    ⚠ 信件 #{$lid} 状态：部分发送成功\n";
    }
}

function logSend(int $letterId, int $channel, array $r): void
{
    Database::insert('send_logs', [
        'letter_id'    => $letterId,
        'channel'      => $channel,
        'status'       => $r['ok'] ? 1 : 0,
        'provider_msg' => $r['msg_id'] ?? '',
        'error'        => $r['error'] ?? '',
        'created_at'   => date('Y-m-d H:i:s'),
    ]);
}

function buildMailHtml(array $letter, string $viewUrl, array $attachments): string
{
    $title = htmlspecialchars($letter['title']);
    $content = nl2br(htmlspecialchars($letter['content']));
    $name = htmlspecialchars($letter['recipient_name']);
    $createTime = htmlspecialchars(date('Y-m-d', strtotime($letter['created_at'])));
    $sendTime = htmlspecialchars(date('Y-m-d H:i', strtotime($letter['send_time'])));

    $attachHtml = '';
    if (!empty($attachments)) {
        $attachHtml .= '<div style="margin-top:24px;padding:16px;background:#f8f9fa;border-radius:8px;">';
        $attachHtml .= '<p style="margin:0 0 12px;font-weight:600;color:#333;">📎 附件 (' . count($attachments) . ')</p>';
        foreach ($attachments as $att) {
            $url = htmlspecialchars($att['share_url']);
            $fn = htmlspecialchars($att['file_name']);
            $pwd = $att['share_password'] ? "（密码：{$att['share_password']}）" : '';
            $icon = $att['file_type'] === 'image' ? '🖼️' : '🎬';
            $attachHtml .= "<p style='margin:6px 0;font-size:14px;'><a href='{$url}' style='color:#FF6B6B;'>{$icon} {$fn}{$pwd}</a></p>";
        }
        $attachHtml .= '</div>';
    }

    return <<<HTML
<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,'PingFang SC','Microsoft YaHei',sans-serif;">
<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;">
  <div style="background:linear-gradient(135deg,#FF6B6B,#6C5CE7);padding:24px 32px;color:#fff;">
    <div style="font-size:24px;font-weight:bold;">💌 时光邮局</div>
    <div style="font-size:13px;opacity:0.9;margin-top:4px;">让此刻的心意 · 准时抵达未来</div>
  </div>
  <div style="padding:32px;">
    <p style="color:#888;font-size:13px;margin:0 0 8px;">致 <strong style="color:#333;">{$name}</strong>：</p>
    <h1 style="font-size:22px;color:#333;margin:0 0 20px;">{$title}</h1>
    <div style="font-size:15px;line-height:1.8;color:#444;padding:20px 0;border-top:1px solid #eee;border-bottom:1px solid #eee;">{$content}</div>
    {$attachHtml}
    <div style="margin-top:32px;padding:16px;background:#fff8f0;border-radius:8px;text-align:center;">
      <p style="margin:0 0 12px;color:#888;font-size:13px;">这封信写于 {$createTime}，于 {$sendTime} 送达</p>
      <a href="{$viewUrl}" style="display:inline-block;padding:10px 24px;background:linear-gradient(135deg,#FF6B6B,#6C5CE7);color:#fff;text-decoration:none;border-radius:24px;font-weight:600;">查看完整信件 →</a>
    </div>
  </div>
  <div style="padding:16px 32px;background:#fafafa;color:#999;font-size:12px;text-align:center;">
    © 时光邮局 · 让时光替你说话
  </div>
</div>
</body></html>
HTML;
}
