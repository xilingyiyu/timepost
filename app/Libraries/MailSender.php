<?php
// 邮件发送器（纯 socket SMTP，无 PHPMailer 依赖）
// 支持 SMTPS (465) / SMTP (25/587)

namespace App\Libraries;

class MailSender
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $from;
    private string $fromName;
    private bool $enabled;

    public function __construct()
    {
        // 优先读取数据库 settings 表（后台可配置），其次读取 .env
        $dbSettings = self::loadDbSettings();

        $this->host     = $dbSettings['smtp_host']      ?? env('MAIL_HOST', '');
        $this->port     = (int)($dbSettings['smtp_port'] ?? env('MAIL_PORT', 465));
        $this->username = $dbSettings['smtp_username']  ?? env('MAIL_USERNAME', '');
        $this->password = $dbSettings['smtp_password']  ?? env('MAIL_PASSWORD', '');
        $this->from     = $dbSettings['smtp_from']      ?? env('MAIL_FROM', '');
        $this->fromName = $dbSettings['smtp_from_name'] ?? env('MAIL_FROM_NAME', '时光邮局');
        $this->enabled = $this->host !== '' && $this->username !== '' && $this->from !== '';
    }

    /** 从 settings 表加载 SMTP 配置（容错：表不存在或无配置时返回空数组） */
    private static function loadDbSettings(): array
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->query("SELECT skey, svalue FROM settings WHERE skey LIKE 'smtp_%'");
            if (!$stmt) return [];
            $m = [];
            foreach ($stmt->fetchAll() as $r) $m[$r['skey']] = $r['svalue'];
            return $m;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 发送 HTML 邮件
     * @return array ['ok'=>bool, 'msg_id'=>string, 'error'=>string]
     */
    public function send(string $to, string $subject, string $htmlBody, string $toName = ''): array
    {
        if (!$this->enabled) {
            // 模拟模式
            return ['ok' => true, 'msg_id' => 'MOCK_' . time(), 'error' => '', 'mock' => true];
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg_id' => '', 'error' => '邮箱格式错误'];
        }

        try {
            $msgId = $this->smtpSend($to, $subject, $htmlBody, $toName);
            return ['ok' => true, 'msg_id' => $msgId, 'error' => ''];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg_id' => '', 'error' => $e->getMessage()];
        }
    }

    public function isEnabled(): bool { return $this->enabled; }

    private function smtpSend(string $to, string $subject, string $htmlBody, string $toName): string
    {
        $useSSL = $this->port == 465;
        $remote = ($useSSL ? 'ssl://' : '') . $this->host . ':' . $this->port;

        $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
        $fp = stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) throw new \RuntimeException("SMTP 连接失败: {$errstr} ({$errno})");

        $msgId = 'tp' . time() . '.' . bin2hex(random_bytes(4)) . '@timepost';
        $this->smtpRead($fp, 220);

        $this->smtpCmd($fp, "EHLO timepost.cn", 250);
        $this->smtpCmd($fp, "AUTH LOGIN", 334);
        $this->smtpCmd($fp, base64_encode($this->username), 334);
        $this->smtpCmd($fp, base64_encode($this->password), 235);

        $this->smtpCmd($fp, "MAIL FROM:<{$this->from}>", 250);
        $this->smtpCmd($fp, "RCPT TO:<{$to}>", 250);
        $this->smtpCmd($fp, "DATA", 354);

        $boundary = 'b' . bin2hex(random_bytes(8));
        $headers = [
            'From: ' . $this->encodeHeader($this->fromName) . " <{$this->from}>",
            'To: ' . ($toName ? $this->encodeHeader($toName) . " <{$to}>" : $to),
            'Subject: ' . $this->encodeHeader($subject),
            'Date: ' . gmdate('D, d M Y H:i:s T'),
            'Message-ID: <' . $msgId . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode(strip_tags($htmlBody))) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $body .= "--{$boundary}--\r\n";

        $msg = implode("\r\n", $headers) . "\r\n\r\n" . $body;
        fputs($fp, $msg . "\r\n.\r\n");
        $this->smtpRead($fp, 250);

        $this->smtpCmd($fp, "QUIT", 221);
        fclose($fp);
        return $msgId;
    }

    private function smtpCmd($fp, string $cmd, int $expect): void
    {
        fputs($fp, $cmd . "\r\n");
        $this->smtpRead($fp, $expect);
    }

    private function smtpRead($fp, int $expectCode): void
    {
        $resp = '';
        while (is_resource($fp) && !feof($fp)) {
            $line = fgets($fp, 515);
            if ($line === false) break;
            $resp .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;  // 末行
        }
        $code = (int)substr($resp, 0, 3);
        if ($code !== $expectCode) {
            throw new \RuntimeException("SMTP 期望 {$expectCode}，实际: {$resp}");
        }
    }

    private function encodeHeader(string $s): string
    {
        return '=?UTF-8?B?' . base64_encode($s) . '?=';
    }
}
