<?php
// 邮件模板渲染器
// 统一邮件视觉风格，与首页（Ink & Accent 风格）保持一致

namespace App\Libraries;

class MailTemplate
{
    /**
     * 渲染通用邮件 HTML
     * @param string $title       邮件标题（hero 大标题）
     * @param string $preHeader   预览摘要（显示在收件箱列表）
     * @param string $contentHtml 正文 HTML
     * @param string $siteName    站点名称
     */
    public static function render(string $title, string $preHeader, string $contentHtml, string $siteName = '时光邮局'): string
    {
        $year = date('Y');
        $url  = rtrim(env('APP_URL') ?: 'https://timepost.cn', '/');

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{$title}</title>
</head>
<body style="margin:0;padding:0;background:#FAFAFA;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif;color:#0A0A0A;line-height:1.6;">
    <!-- 预览文本（收件箱摘要，仅客户端可见） -->
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">{$preHeader}</div>

    <!-- 外层容器 -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#FAFAFA;padding:24px 12px;">
        <tr>
            <td align="center">
                <!-- 邮件主体卡片 -->
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="560" style="max-width:560px;background:#FFFFFF;border-radius:20px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.06);">

                    <!-- 顶部 Hero 区（蓝→深色渐变） -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#3B82F6 0%,#2563EB 60%,#0F172A 100%);padding:48px 40px 40px;text-align:center;">
                            <!-- Logo + 站点名 -->
                            <div style="display:inline-block;margin-bottom:24px;">
                                <div style="display:inline-flex;align-items:center;gap:10px;background:rgba(255,255,255,0.15);padding:8px 16px;border-radius:999px;backdrop-filter:blur(10px);">
                                    <span style="display:inline-flex;width:24px;height:24px;background:#FFFFFF;border-radius:6px;align-items:center;justify-content:center;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/><path d="M9.5 10.5a2.5 2.5 0 0 1 5 0c0 1.5-2.5 3-2.5 3s-2.5-1.5-2.5-3Z"/></svg>
                                    </span>
                                    <span style="color:#FFFFFF;font-size:15px;font-weight:700;letter-spacing:-0.01em;">{$siteName}</span>
                                </div>
                            </div>
                            <h1 style="margin:0 0 12px;color:#FFFFFF;font-size:28px;font-weight:800;letter-spacing:-0.02em;line-height:1.2;">{$title}</h1>
                            <div style="width:40px;height:3px;background:#FF4D8D;margin:0 auto;border-radius:2px;"></div>
                        </td>
                    </tr>

                    <!-- 内容区 -->
                    <tr>
                        <td style="padding:40px 40px 24px;">
                            {$contentHtml}
                        </td>
                    </tr>

                    <!-- 底部 CTA / 版权 -->
                    <tr>
                        <td style="padding:0 40px 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-top:1px dashed #E5E5E5;padding-top:24px;">
                                <tr>
                                    <td align="center" style="color:#A3A3A3;font-size:12px;line-height:1.7;">
                                        <a href="{$url}" style="color:#3B82F6;text-decoration:none;font-weight:600;">前往 {$siteName}</a>
                                        <span style="margin:0 8px;opacity:0.5;">·</span>
                                        <a href="{$url}/write" style="color:#3B82F6;text-decoration:none;">写一封信</a>
                                        <br>
                                        <span style="opacity:0.7;">© Yiyu Creation All Rights Reserved.</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- 备案信息 -->
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="560" style="max-width:560px;margin-top:16px;">
                    <tr>
                        <td align="center" style="color:#A3A3A3;font-size:11px;line-height:1.6;">
                            <a href="https://beian.miit.gov.cn/" style="color:#A3A3A3;text-decoration:none;">蜀ICP备2026005661号</a>
                            <span style="margin:0 6px;opacity:0.5;">·</span>
                            <a href="http://www.beian.gov.cn/portal/registerSystemInfo?recordcode=51052402000123" style="color:#A3A3A3;text-decoration:none;">川公网安备 51052402000123号</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /** 邮箱注册验证邮件 */
    public static function emailVerify(string $verifyUrl, string $email, string $siteName = '时光邮局'): string
    {
        $content = <<<HTML
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
        <td style="font-size:15px;color:#404040;line-height:1.7;">
            你好，<strong style="color:#0A0A0A;">{$email}</strong>
        </td>
    </tr>
    <tr><td style="height:16px;"></td></tr>
    <tr>
        <td style="font-size:15px;color:#404040;line-height:1.7;">
            欢迎加入时光邮局！请点击下方按钮完成邮箱验证，激活你的账号。这封信将带你穿越时光，把此刻的心意寄托给未来的某个人。
        </td>
    </tr>
    <tr><td style="height:28px;"></td></tr>
    <tr>
        <td align="center">
            <a href="{$verifyUrl}" target="_blank" style="display:inline-block;padding:14px 36px;background:#3B82F6;color:#FFFFFF;text-decoration:none;font-size:15px;font-weight:600;border-radius:12px;box-shadow:0 8px 24px rgba(59,130,246,0.25);">立即验证邮箱</a>
        </td>
    </tr>
    <tr><td style="height:28px;"></td></tr>
    <tr>
        <td style="background:#FAFAFA;border-radius:12px;padding:16px 20px;font-size:13px;color:#737373;line-height:1.7;">
            <strong style="color:#404040;">⚠️ 请注意：</strong><br>
            · 此验证链接 <strong style="color:#0A0A0A;">30 分钟内有效</strong>，超时需重新申请<br>
            · 若非本人操作请忽略此邮件，你的账号不会被创建<br>
            · 若按钮无法点击，请复制以下链接到浏览器打开：<br>
            <span style="color:#3B82F6;word-break:break-all;">{$verifyUrl}</span>
        </td>
    </tr>
</table>
HTML;
        return self::render('欢迎加入时光邮局', '完成邮箱验证，开启你的时光之旅', $content, $siteName);
    }

    /** 信件投递通知邮件 */
    public static function letterDelivered(string $recipientName, string $viewUrl, string $excerpt, string $siteName = '时光邮局'): string
    {
        $excerpt = mb_substr($excerpt, 0, 80) . (mb_strlen($excerpt) > 80 ? '…' : '');
        $content = <<<HTML
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
        <td style="font-size:15px;color:#404040;line-height:1.7;">
            亲爱的 <strong style="color:#0A0A0A;">{$recipientName}</strong>：
        </td>
    </tr>
    <tr><td style="height:16px;"></td></tr>
    <tr>
        <td style="font-size:15px;color:#404040;line-height:1.7;">
            你有一封来自时光的信件刚刚送达。有人把心意写下，托付给时间，而此刻，正是它该被你看见的时刻。
        </td>
    </tr>
    <tr><td style="height:20px;"></td></tr>
    <tr>
        <td style="background:linear-gradient(135deg,#EFF6FF 0%,#FFFFFF 100%);border-left:3px solid #3B82F6;border-radius:8px;padding:20px 24px;">
            <div style="font-size:13px;color:#737373;margin-bottom:6px;letter-spacing:0.05em;">信件摘要</div>
            <div style="font-size:15px;color:#0A0A0A;line-height:1.7;font-style:italic;">"{$excerpt}"</div>
        </td>
    </tr>
    <tr><td style="height:28px;"></td></tr>
    <tr>
        <td align="center">
            <a href="{$viewUrl}" target="_blank" style="display:inline-block;padding:14px 36px;background:#0F172A;color:#FFFFFF;text-decoration:none;font-size:15px;font-weight:600;border-radius:12px;box-shadow:0 8px 24px rgba(15,23,42,0.18);">阅读完整信件 →</a>
        </td>
    </tr>
    <tr><td style="height:20px;"></td></tr>
    <tr>
        <td style="font-size:13px;color:#A3A3A3;line-height:1.6;text-align:center;">
            或复制链接到浏览器：<br>
            <span style="color:#3B82F6;word-break:break-all;">{$viewUrl}</span>
        </td>
    </tr>
</table>
HTML;
        return self::render('你有一封时光之信', '一封来自过去的信件已送达，点击查看', $content, $siteName);
    }
}
