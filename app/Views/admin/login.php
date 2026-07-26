<?php /** 后台登录页占位 */ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台登录 · 时光邮局</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        .admin-login { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; background:linear-gradient(135deg,#2D3436,#1F1F2E); }
        .admin-card { background:#fff; padding:40px; border-radius:14px; width:100%; max-width:360px; box-shadow:0 20px 60px rgba(0,0,0,0.3); }
        .admin-card h1 { font-size:22px; margin-bottom:6px; }
        .admin-card .sub { color:var(--text-secondary); font-size:13px; margin-bottom:24px; }
        @media (max-width:480px) { .admin-card { padding:28px 22px; } }
    </style>
</head>
<body>
<div class="admin-login">
    <div class="admin-card">
        <h1 style="display:flex;align-items:center;gap:8px;"><span style="width:22px;height:22px;display:inline-flex;"><?= \App\Icons::get('wrench') ?></span>后台管理</h1>
        <p class="sub">默认账号 admin / admin123</p>
        <form id="loginForm">
            <div class="form-group">
                <input type="text" class="form-control" id="username" placeholder="用户名" value="admin">
            </div>
            <div class="form-group">
                <input type="password" class="form-control" id="password" placeholder="密码">
            </div>
            <button type="submit" class="btn-submit">登录</button>
        </form>
    </div>
</div>
<div id="toast"></div>
<script src="/assets/js/app.js"></script>
<script>
    document.getElementById('loginForm').onsubmit = async (e) => {
        e.preventDefault();
        const res = await fetch('/admin/login', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
                username: document.getElementById('username').value,
                password: document.getElementById('password').value
            })
        });
        const data = await res.json();
        if (data.code === 0) location.href = data.data.redirect;
        else toast(data.msg, true);
    };
</script>
</body>
</html>
