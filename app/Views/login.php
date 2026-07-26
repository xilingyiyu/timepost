<?php /** @var string $siteName * @var bool $oauthEnabled * @var array $oauthTypes */ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 · <?= htmlspecialchars($siteName) ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect x='2' y='4' width='20' height='16' rx='2'/><path d='m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7'/><path d='M9.5 10.5a2.5 2.5 0 0 1 5 0c0 1.5-2.5 3-2.5 3s-2.5-1.5-2.5-3Z'/></svg>">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        .login-wrap {
            min-height:calc(100vh - 64px); display:flex; align-items:center; justify-content:center;
            padding:40px 20px; background:var(--gradient-soft);
        }
        .login-card {
            background:#fff; border-radius:var(--radius-lg); box-shadow:var(--shadow-lg);
            padding:40px 36px; width:100%; max-width:400px;
        }
        .login-card h1 { font-size:24px; font-weight:800; margin-bottom:6px; text-align:center; }
        .login-card .sub { color:var(--text-secondary); font-size:14px; text-align:center; margin-bottom:28px; }
        .tabs { display:flex; gap:8px; margin-bottom:24px; background:var(--bg); padding:4px; border-radius:var(--radius); }
        .tab {
            flex:1; padding:10px; text-align:center; border-radius:var(--radius-sm);
            font-size:14px; font-weight:600; color:var(--text-secondary); cursor:pointer; transition:var(--transition);
        }
        .tab.active { background:#fff; color:var(--primary); box-shadow:var(--shadow-sm); }
        .mini-tabs { display:flex; gap:6px; background:var(--bg); padding:3px; border-radius:var(--radius-sm); }
        .mini-tab {
            flex:1; padding:8px; text-align:center; border-radius:6px;
            font-size:13px; font-weight:500; color:var(--text-secondary); cursor:pointer; transition:var(--transition);
        }
        .mini-tab.active { background:#fff; color:var(--primary); box-shadow:var(--shadow-sm); }
        .form-label .opt { color:var(--text-muted); font-weight:400; font-size:12px; }
        .email-tip { margin-top:12px; padding:10px 14px; background:var(--primary-light); border-radius:var(--radius-sm); font-size:12px; color:var(--primary); line-height:1.6; }
        .oauth-divider {
            display:flex; align-items:center; gap:12px; margin:24px 0;
            color:var(--text-light); font-size:13px;
        }
        .oauth-divider::before, .oauth-divider::after {
            content:''; flex:1; height:1px; background:var(--border);
        }
        .oauth-buttons { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; padding-bottom:28px; }
        .oauth-btn {
            width:48px; height:48px; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            cursor:pointer; transition:all .2s ease;
            border:1.5px solid var(--border); background:#fff;
            position:relative;
        }
        .oauth-btn:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(15,23,42,0.12); border-color:var(--bc, var(--border)); }
        .oauth-btn svg { width:22px; height:22px; display:block; }
        .oauth-btn .tip {
            position:absolute; bottom:-26px; left:50%; transform:translateX(-50%);
            background:var(--text); color:#fff; font-size:11px; padding:3px 8px;
            border-radius:5px; white-space:nowrap; opacity:0; pointer-events:none;
            transition:opacity .2s ease; z-index:10;
        }
        .oauth-btn:hover .tip { opacity:1; }
        .oauth-label { text-align:center; color:var(--text-light); font-size:12px; margin-top:8px; }
        @media (max-width:480px) {
            .oauth-btn { width:52px; height:52px; }
            .oauth-btn svg { width:24px; height:24px; }
            .oauth-buttons { gap:12px; }
            .login-card { padding:32px 22px; }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="navbar-inner">
        <a href="/" class="logo"><span class="logo-icon"><?= \App\Icons::get('heart_mail') ?></span><span><?= htmlspecialchars($siteName) ?></span></a>
        <button class="nav-toggle" aria-label="菜单"><span></span><span></span><span></span></button>
        <div class="nav-links"><a href="/" >返回首页</a></div>
    </div>
</nav>

<div class="login-wrap">
    <div class="login-card">
        <h1>欢迎回来</h1>
        <p class="sub">登录后即可管理你的时光信件</p>

        <div class="tabs">
            <div class="tab active" data-tab="login">登录</div>
            <div class="tab" data-tab="register">注册</div>
        </div>

        <form id="authForm">
            <!-- 注册模式下的注册方式切换 -->
            <div class="form-group reg-only" id="regTypeGroup" style="display:none;">
                <div class="mini-tabs">
                    <div class="mini-tab active" data-reg="phone">手机号</div>
                    <div class="mini-tab" data-reg="email">邮箱</div>
                </div>
            </div>
            <div class="form-group" id="phoneGroup">
                <label class="form-label">手机号 <span class="req">*</span></label>
                <input type="tel" class="form-control" id="phone" placeholder="请输入手机号" maxlength="11">
            </div>
            <div class="form-group" id="emailGroup" style="display:none;">
                <label class="form-label">邮箱 <span class="req">*</span></label>
                <input type="email" class="form-control" id="email" placeholder="请输入邮箱地址">
            </div>
            <div class="form-group" id="accountGroup" style="display:none;">
                <label class="form-label">账号 <span class="req">*</span></label>
                <input type="text" class="form-control" id="account" placeholder="手机号或邮箱">
            </div>
            <div class="form-group reg-only" id="nicknameGroup" style="display:none;">
                <label class="form-label">昵称 <span class="opt">选填</span></label>
                <input type="text" class="form-control" id="nickname" placeholder="默认从邮箱前缀生成" maxlength="20">
            </div>
            <div class="form-group">
                <label class="form-label">密码 <span class="req">*</span></label>
                <input type="password" class="form-control" id="password" placeholder="至少 6 位">
            </div>
            <button type="submit" class="btn-submit" id="submitBtn">登录</button>
            <p id="emailTip" class="email-tip" style="display:none;">邮箱注册将发送验证链接，请在 30 分钟内点击邮件中的链接完成激活。</p>
        </form>

        <?php if ($oauthEnabled && !empty($oauthTypes)):
            // 官方品牌 SVG 图标（来源：Simple Icons + Tabler Icons，均 CC0/MIT 授权）
            $oauthIcons = [
                'wx' => '<svg viewBox="0 0 24 24" fill="#07C160"><path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 0 0 .167-.054l1.903-1.114a.864.864 0 0 1 .717-.098 10.16 10.16 0 0 0 2.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.596-6.348zM5.785 5.991c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178A1.17 1.17 0 0 1 4.623 7.17c0-.651.52-1.18 1.162-1.18zm5.813 0c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178 1.17 1.17 0 0 1-1.162-1.178c0-.651.52-1.18 1.162-1.18zm5.34 2.867c-1.797-.052-3.746.512-5.28 1.786-1.72 1.428-2.687 3.72-1.78 6.22.942 2.453 3.666 4.229 6.884 4.229.826 0 1.622-.12 2.361-.336a.722.722 0 0 1 .598.082l1.584.926a.272.272 0 0 0 .14.047c.134 0 .24-.111.24-.247 0-.06-.023-.12-.038-.177l-.327-1.233a.582.582 0 0 1-.023-.156.49.49 0 0 1 .201-.398C23.024 18.48 24 16.82 24 14.98c0-3.21-2.931-5.837-6.656-6.088V8.89c-.135-.01-.27-.027-.407-.03zm-2.53 3.274c.535 0 .969.44.969.982a.976.976 0 0 1-.969.983.976.976 0 0 1-.969-.983c0-.542.434-.982.97-.982zm4.844 0c.535 0 .969.44.969.982a.976.976 0 0 1-.969.983.976.976 0 0 1-.969-.983c0-.542.434-.982.969-.982z"/></svg>',
                'alipay' => '<svg viewBox="0 0 24 24" fill="#1677FF"><path d="M19.695 15.07c3.426 1.158 4.203 1.22 4.203 1.22V3.846c0-2.124-1.705-3.845-3.81-3.845H3.914C1.808.001.102 1.722.102 3.846v16.31c0 2.123 1.706 3.845 3.813 3.845h16.173c2.105 0 3.81-1.722 3.81-3.845v-.157s-6.19-2.602-9.315-4.119c-2.096 2.602-4.8 4.181-7.607 4.181-4.75 0-6.361-4.19-4.112-6.949.49-.602 1.324-1.175 2.617-1.497 2.025-.502 5.247.313 8.266 1.317a16.796 16.796 0 0 0 1.341-3.302H5.781v-.952h4.799V6.975H4.77v-.953h5.81V3.591s0-.409.411-.409h2.347v2.84h5.744v.951h-5.744v1.704h4.69a19.453 19.453 0 0 1-1.986 5.06c1.424.52 2.702 1.011 3.654 1.333m-13.81-2.032c-.596.06-1.71.325-2.321.869-1.83 1.608-.735 4.55 2.968 4.55 2.151 0 4.301-1.388 5.99-3.61-2.403-1.182-4.438-2.028-6.637-1.809"/></svg>',
                'qq' => '<svg viewBox="0 0 24 24" fill="#12B7F5"><path d="M21.395 15.035a40 40 0 0 0-.803-2.264l-1.079-2.695c.001-.032.014-.562.014-.836C19.526 4.632 17.351 0 12 0S4.474 4.632 4.474 9.241c0 .274.013.804.014.836l-1.08 2.695a39 39 0 0 0-.802 2.264c-1.021 3.283-.69 4.643-.438 4.673.54.065 2.103-2.472 2.103-2.472 0 1.469.756 3.387 2.394 4.771-.612.188-1.363.479-1.845.835-.434.32-.379.646-.301.778.343.578 5.883.369 7.482.189 1.6.18 7.14.389 7.483-.189.078-.132.132-.458-.301-.778-.483-.356-1.233-.646-1.846-.836 1.637-1.384 2.393-3.302 2.393-4.771 0 0 1.563 2.537 2.103 2.472.251-.03.581-1.39-.438-4.673"/></svg>',
                'dingtalk' => '<svg viewBox="0 0 24 24" fill="none" stroke="#007FFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1 -18 0a9 9 0 0 1 18 0z"/><path d="M8 7.5l7.02 2.632a1 1 0 0 1 .567 1.33l-1.087 2.538h1.5l-5 4l1 -4c-3.1 .03 -3.114 -3.139 -4 -6.5z"/></svg>',
                'twitter' => '<svg viewBox="0 0 24 24" fill="#000000"><path d="M14.234 10.162 22.977 0h-2.072l-7.591 8.824L7.251 0H.258l9.168 13.343L.258 24H2.33l8.016-9.318L16.749 24h6.993zm-2.837 3.299-.929-1.329L3.076 1.56h3.182l5.965 8.532.929 1.329 7.754 11.09h-3.182z"/></svg>',
                'google' => '<svg viewBox="0 0 24 24" fill="#4285F4"><path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/></svg>',
                'github' => '<svg viewBox="0 0 24 24" fill="#181717"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>',
                'gitee' => '<svg viewBox="0 0 24 24" fill="#C71D23"><path d="M11.984 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.016 0zm6.09 5.333c.328 0 .593.266.592.593v1.482a.594.594 0 0 1-.593.592H9.777c-.982 0-1.778.796-1.778 1.778v5.63c0 .327.266.592.593.592h5.63c.982 0 1.778-.796 1.778-1.778v-.296a.593.593 0 0 0-.592-.593h-4.15a.592.592 0 0 1-.592-.592v-1.482a.593.593 0 0 1 .593-.592h6.815c.327 0 .593.265.593.592v3.408a4 4 0 0 1-4 4H5.926a.593.593 0 0 1-.593-.593V9.778a4.444 4.444 0 0 1 4.445-4.444h8.296Z"/></svg>',
            ];
            // 显示名覆盖（仅登录页展示，不影响后台 type 参数）
            $displayNames = ['twitter' => 'X'];
            // 按用户配置的顺序渲染
            $order = ['wx','alipay','qq','dingtalk','twitter','google','github','gitee'];
            $sorted = [];
            foreach ($order as $k) { if (isset($oauthTypes[$k])) $sorted[$k] = $oauthTypes[$k]; }
            foreach ($oauthTypes as $k => $v) { if (!isset($sorted[$k])) $sorted[$k] = $v; }
        ?>
            <div class="oauth-divider">其他登录方式</div>
            <div class="oauth-buttons">
                <?php foreach ($sorted as $type => $name):
                    $icon = $oauthIcons[$type] ?? '<svg viewBox="0 0 24 24" fill="#64748B"><circle cx="12" cy="12" r="9"/></svg>';
                    $color = ['wx'=>'#07C160','alipay'=>'#1677FF','qq'=>'#12B7F5','dingtalk'=>'#007FFF','twitter'=>'#000','google'=>'#4285F4','github'=>'#181717','gitee'=>'#C71D23'][$type] ?? '#64748B';
                    $label = $displayNames[$type] ?? $name;
                ?>
                    <div class="oauth-btn" title="<?= $label ?>登录" style="--bc:<?= $color ?>" onclick="oauthLogin('<?= $type ?>')">
                        <?= $icon ?>
                        <span class="tip"><?= $label ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="oauth-label">点击图标使用第三方账号登录</div>
        <?php endif; ?>
    </div>
</div>

<div id="toast"></div>
<script src="/assets/js/app.js"></script>
<script>
    let mode = 'login';
    let regType = 'phone';

    // 登录/注册 大 tab 切换
    document.querySelectorAll('.tab').forEach(t => {
        t.onclick = () => {
            document.querySelectorAll('.tab').forEach(x => x.classList.remove('active'));
            t.classList.add('active');
            mode = t.dataset.tab;
            const isReg = mode === 'register';
            document.querySelectorAll('.reg-only').forEach(el => el.style.display = isReg ? 'block' : 'none');
            document.getElementById('phoneGroup').style.display = isReg ? 'block' : 'none';
            document.getElementById('accountGroup').style.display = isReg ? 'none' : 'block';
            document.getElementById('emailGroup').style.display = 'none';
            document.getElementById('emailTip').style.display = 'none';
            document.getElementById('submitBtn').textContent = isReg ? '注册' : '登录';
            regType = 'phone';
            document.querySelectorAll('.mini-tab').forEach(x => x.classList.toggle('active', x.dataset.reg === 'phone'));
        };
    });

    // 手机/邮箱 mini tab 切换
    document.querySelectorAll('.mini-tab').forEach(t => {
        t.onclick = () => {
            document.querySelectorAll('.mini-tab').forEach(x => x.classList.remove('active'));
            t.classList.add('active');
            regType = t.dataset.reg;
            document.getElementById('phoneGroup').style.display = regType === 'phone' ? 'block' : 'none';
            document.getElementById('emailGroup').style.display = regType === 'email' ? 'block' : 'none';
            document.getElementById('emailTip').style.display = regType === 'email' ? 'block' : 'none';
        };
    });

    document.getElementById('authForm').onsubmit = async (e) => {
        e.preventDefault();
        const password = document.getElementById('password').value;
        if (!password || password.length < 6) return toast('密码至少 6 位');

        const btn = document.getElementById('submitBtn');
        btn.disabled = true; btn.textContent = '处理中...';

        try {
            let url, payload, successMsg;
            if (mode === 'login') {
                const account = document.getElementById('account').value.trim();
                if (!account) { toast('请输入账号'); return; }
                url = '/api/auth/login';
                payload = { account, password };
                successMsg = '登录成功，正在跳转...';
            } else if (regType === 'phone') {
                const phone = document.getElementById('phone').value.trim();
                if (!phone) { toast('请输入手机号'); return; }
                url = '/api/auth/register';
                payload = { phone, password };
                successMsg = '注册成功，正在跳转...';
            } else {
                const email = document.getElementById('email').value.trim();
                if (!email) { toast('请输入邮箱'); return; }
                const nickname = document.getElementById('nickname').value.trim();
                url = '/api/auth/register/email';
                payload = { email, password, nickname };
            }

            const res = await fetch(url, {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.code === 0) {
                if (mode === 'login') {
                    localStorage.setItem('token', data.data.token);
                    localStorage.setItem('user', JSON.stringify({id:data.data.user_id, nickname:data.data.nickname, avatar:data.data.avatar}));
                    if (data.data.need_security) {
                        toast('请先设置密保问题');
                        setTimeout(() => location.href = '/settings?tab=security', 800);
                    } else {
                        toast('登录成功，正在跳转...');
                        setTimeout(() => location.href = '/write', 800);
                    }
                } else if (regType === 'phone') {
                    localStorage.setItem('token', data.data.token);
                    localStorage.setItem('user', JSON.stringify({id:data.data.user_id, nickname:data.data.nickname}));
                    if (data.data.need_security) {
                        toast('注册成功，请设置密保问题');
                        setTimeout(() => location.href = '/settings?tab=security', 800);
                    } else {
                        toast('注册成功，正在跳转...');
                        setTimeout(() => location.href = '/write', 800);
                    }
                } else {
                    // 邮箱注册：显示成功提示但不跳转
                    toast('验证邮件已发送，请前往邮箱点击验证链接完成注册');
                    btn.textContent = '已发送';
                    setTimeout(() => { btn.textContent = '重新发送'; btn.disabled = false; }, 3000);
                    return;
                }
            } else {
                toast(data.msg || '操作失败', true);
            }
        } catch (err) {
            toast('网络错误', true);
        } finally {
            if (mode === 'login' || regType === 'phone') {
                btn.disabled = false;
                btn.textContent = mode === 'login' ? '登录' : '注册';
            }
        }
    };

    // URL 参数：邮箱验证成功跳转回来
    const params = new URLSearchParams(location.search);
    if (params.get('verified') === '1') {
        const t = params.get('token');
        if (t) {
            localStorage.setItem('token', t);
            toast('邮箱验证成功，已自动登录');
            setTimeout(() => location.href = '/settings?tab=security', 1000);
        } else {
            toast('邮箱验证成功，请登录');
        }
    }

    async function oauthLogin(type) {
        try {
            const res = await fetch('/api/auth/oauth/url?type=' + type);
            const data = await res.json();
            if (data.code === 0 && data.data.url) {
                location.href = data.data.url;
            } else {
                toast(data.msg || '获取登录链接失败', true);
            }
        } catch (e) { toast('网络错误', true); }
    }
</script>
</body>
</html>
