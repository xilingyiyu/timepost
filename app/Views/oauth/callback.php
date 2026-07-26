<?php /** @var string $type * @var string $code */ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录中 · 时光邮局</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        .loading-wrap {
            min-height:100vh; display:flex; flex-direction:column;
            align-items:center; justify-content:center;
            background:var(--gradient-soft); padding:40px;
        }
        .spinner {
            width:48px; height:48px; border:4px solid var(--primary-light);
            border-top-color:var(--primary); border-radius:50%;
            animation:spin 0.8s linear infinite; margin-bottom:24px;
        }
        @keyframes spin { to { transform:rotate(360deg); } }
        .loading-text { color:var(--text-secondary); font-size:15px; }
    </style>
</head>
<body>
<div class="loading-wrap">
    <div class="spinner"></div>
    <div class="loading-text">正在完成登录...</div>
</div>
<div id="toast"></div>
<script src="/assets/js/app.js"></script>
<script>
    (async () => {
        const params = new URLSearchParams(location.search);
        const type = '<?= htmlspecialchars($type) ?>';
        const code = '<?= htmlspecialchars($code) ?>';
        try {
            const res = await fetch('/api/auth/oauth/login', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({type, code})
            });
            const data = await res.json();
            if (data.code === 0) {
                localStorage.setItem('token', data.data.token);
                localStorage.setItem('user', JSON.stringify({
                    id: data.data.user_id, nickname: data.data.nickname, avatar: data.data.avatar
                }));
                if (data.data.need_security) {
                    toast('请先设置密保问题');
                    setTimeout(() => location.href = '/settings?tab=security', 600);
                } else {
                    toast('登录成功');
                    setTimeout(() => location.href = '/', 600);
                }
            } else {
                toast(data.msg || '登录失败', true);
                setTimeout(() => location.href = '/login', 1500);
            }
        } catch (e) {
            toast('网络错误', true);
            setTimeout(() => location.href = '/login', 1500);
        }
    })();
</script>
</body>
</html>
