<?php /** 系统配置 */ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统配置 · 时光邮局后台</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1 style="display:flex;align-items:center;gap:8px;"><span style="width:20px;height:20px;display:inline-flex;"><?= \App\Icons::get('settings') ?></span>系统配置</h1>
            <div class="admin-user"><a href="/" target="_blank">前台</a> · <a href="/admin/logout">退出</a></div>
        </header>
        <div class="admin-content">

            <div class="card">
                <h3>站点配置</h3>
                <div class="form-row">
                    <label>站点名称</label>
                    <input type="text" id="site_name">
                </div>
                <div class="form-row">
                    <label>站点标语</label>
                    <input type="text" id="site_slogan">
                </div>
                <div class="form-row">
                    <label>站点描述</label>
                    <textarea id="site_desc"></textarea>
                </div>
                <div class="form-row">
                    <label>短信签名</label>
                    <input type="text" id="sms_sign">
                </div>
                <div class="form-row">
                    <label>每日信件上限</label>
                    <input type="number" id="letter_per_day">
                </div>
                <div class="form-row">
                    <label>最大重试次数</label>
                    <input type="number" id="max_retry">
                </div>
                <button class="btn-save" onclick="saveSettings()" style="display:inline-flex;align-items:center;gap:6px;"><span style="width:16px;height:16px;display:inline-flex;"><?= \App\Icons::get('save') ?></span>保存配置</button>
            </div>

            <div class="card">
                <h3>修改管理员密码</h3>
                <div class="form-row">
                    <label>原密码</label>
                    <input type="password" id="old_password">
                </div>
                <div class="form-row">
                    <label>新密码</label>
                    <input type="password" id="new_password" placeholder="至少 6 位">
                </div>
                <button class="btn-save" onclick="changePassword()" style="display:inline-flex;align-items:center;gap:6px;"><span style="width:16px;height:16px;display:inline-flex;"><?= \App\Icons::get('key') ?></span>修改密码</button>
            </div>

            <div class="card">
                <h3 style="display:flex;align-items:center;gap:8px;">
                    <span style="width:18px;height:18px;display:inline-flex;color:var(--primary);"><?= \App\Icons::get('mail') ?></span>
                    SMTP 邮件服务器配置
                </h3>
                <p style="color:var(--text-secondary);font-size:13px;margin-bottom:16px;">
                    配置信件投递邮件与注册验证邮件的发件服务器。保存后立即生效，无需重启。
                </p>
                <div class="form-row">
                    <label>SMTP 服务器地址</label>
                    <input type="text" id="smtp_host" placeholder="如 smtp.qq.com / smtp.gmail.com">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-row">
                        <label>端口</label>
                        <input type="number" id="smtp_port" placeholder="465 / 587 / 25" value="465">
                    </div>
                    <div class="form-row">
                        <label>加密方式</label>
                        <select id="smtp_encrypt">
                            <option value="ssl">SSL（465 端口常用）</option>
                            <option value="tls">TLS（587 端口常用）</option>
                            <option value="none">不加密（25 端口）</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <label>用户名</label>
                    <input type="text" id="smtp_username" placeholder="完整邮箱地址">
                </div>
                <div class="form-row">
                    <label>密码 / 授权码</label>
                    <input type="password" id="smtp_password" placeholder="QQ邮箱需填授权码，非登录密码" autocomplete="new-password">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-row">
                        <label>发件邮箱</label>
                        <input type="email" id="smtp_from" placeholder="noreply@example.com">
                    </div>
                    <div class="form-row">
                        <label>发件人名称</label>
                        <input type="text" id="smtp_from_name" placeholder="时光邮局" value="时光邮局">
                    </div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                    <button class="btn-save" onclick="saveSmtp()" style="margin-top:0;">保存 SMTP 配置</button>
                    <span style="width:1px;height:24px;background:var(--border);"></span>
                    <input type="email" id="smtp_test_to" placeholder="测试收件邮箱" style="flex:1;min-width:200px;padding:10px 14px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;">
                    <button class="btn-cancel" onclick="testSmtp()" style="margin-top:0;">发送测试邮件</button>
                </div>
                <p id="smtpStatus" style="margin-top:12px;font-size:13px;color:var(--text-light);"></p>
            </div>

            <div class="card">
                <h3>其他环境变量（.env 文件）</h3>
                <p style="color:var(--text-secondary);font-size:13px;margin-bottom:12px;">
                    以下配置需在服务器上修改 <code style="background:#f5f5f5;padding:2px 6px;border-radius:4px;">.env</code> 文件后生效（安全考虑）。
                </p>
                <table class="table">
                    <thead>
                        <tr><th>配置项</th><th>说明</th><th>当前状态</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>CAIHONG_*</td><td>聚合登录配置</td><td id="st_caihong">检测中</td></tr>
                        <tr><td>SMS_*</td><td>短信配置</td><td id="st_sms">检测中</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div id="toast"></div>
<script src="/assets/js/app.js"></script>
<script src="/assets/js/admin.js"></script>
<script>
    // 加载配置
    (async () => {
        const data = await api('/admin/api/settings');
        if (data.code !== 0) return toast(data.msg, true);
        const s = data.data;
        ['site_name','site_slogan','site_desc','sms_sign','letter_per_day','max_retry'].forEach(k => {
            const el = document.getElementById(k);
            if (el) el.value = s[k] || '';
        });

        // 加载 SMTP 配置
        ['smtp_host','smtp_port','smtp_username','smtp_password','smtp_from','smtp_from_name','smtp_encrypt'].forEach(k => {
            const el = document.getElementById(k);
            if (el && s[k]) el.value = s[k];
        });

        // 检测环境变量状态（通过 .env 文件状态间接判断）
        document.getElementById('st_caihong').innerHTML = '<span class="badge badge-orange">检查中</span>';
        try {
            const r = await fetch('/api/auth/oauth/url?type=wx');
            const d = await r.json();
            document.getElementById('st_caihong').innerHTML = d.code === 0
                ? '<span class="badge badge-green">已配置</span>'
                : '<span class="badge badge-red">未配置</span>';
        } catch (e) {
            document.getElementById('st_caihong').innerHTML = '<span class="badge badge-red">未配置</span>';
        }
        document.getElementById('st_sms').innerHTML = '<span class="badge badge-orange">在 .env 中配置</span>';

        // SMTP 状态提示
        updateSmtpStatus(s);
    })();

    function updateSmtpStatus(s) {
        const ok = s.smtp_host && s.smtp_username && s.smtp_from;
        const el = document.getElementById('smtpStatus');
        if (ok) {
            el.innerHTML = '<span style="color:#16a34a;">● SMTP 已配置</span> · 服务器 ' + escapeHtml(s.smtp_host) + ':' + escapeHtml(s.smtp_port || '465') + ' · 发件 ' + escapeHtml(s.smtp_from);
        } else {
            el.innerHTML = '<span style="color:#ea580c;">○ SMTP 未配置完整</span> · 邮件将进入开发模式（不真实发送）';
        }
    }

    async function saveSettings() {
        const data = {
            site_name: document.getElementById('site_name').value,
            site_slogan: document.getElementById('site_slogan').value,
            site_desc: document.getElementById('site_desc').value,
            sms_sign: document.getElementById('sms_sign').value,
            letter_per_day: document.getElementById('letter_per_day').value,
            max_retry: document.getElementById('max_retry').value,
        };
        const res = await api('/admin/api/settings', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify(data)
        });
        if (res.code === 0) toast('已保存');
        else toast(res.msg, true);
    }

    async function saveSmtp() {
        const data = {
            smtp_host: document.getElementById('smtp_host').value.trim(),
            smtp_port: document.getElementById('smtp_port').value.trim(),
            smtp_encrypt: document.getElementById('smtp_encrypt').value,
            smtp_username: document.getElementById('smtp_username').value.trim(),
            smtp_password: document.getElementById('smtp_password').value,
            smtp_from: document.getElementById('smtp_from').value.trim(),
            smtp_from_name: document.getElementById('smtp_from_name').value.trim() || '时光邮局',
        };
        const res = await api('/admin/api/settings', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify(data)
        });
        if (res.code === 0) {
            toast('SMTP 配置已保存');
            // 重新拉取以更新状态
            const d = await api('/admin/api/settings');
            if (d.code === 0) updateSmtpStatus(d.data);
        } else toast(res.msg, true);
    }

    async function testSmtp() {
        const to = document.getElementById('smtp_test_to').value.trim();
        if (!to) return toast('请输入测试收件邮箱', true);
        const btn = document.querySelector('button.btn-cancel[onclick="testSmtp()"]');
        const oldText = btn.textContent;
        btn.disabled = true; btn.textContent = '发送中...';
        try {
            const res = await api('/admin/api/smtp/test', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({to})
            });
            if (res.code === 0) toast(res.msg || '测试邮件已发送');
            else toast(res.msg, true);
        } finally {
            btn.disabled = false; btn.textContent = oldText;
        }
    }

    async function changePassword() {
        const old_pwd = document.getElementById('old_password').value;
        const new_pwd = document.getElementById('new_password').value;
        if (new_pwd.length < 6) return toast('新密码至少 6 位', true);
        const res = await api('/admin/api/password', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({old_password: old_pwd, new_password: new_pwd})
        });
        if (res.code === 0) {
            toast('密码已修改，请重新登录');
            setTimeout(() => location.href = '/admin/login', 1500);
        } else toast(res.msg, true);
    }
</script>
</body>
</html>
