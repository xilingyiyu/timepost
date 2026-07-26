<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户中心 · <?= htmlspecialchars($siteName) ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect x='2' y='4' width='20' height='16' rx='2'/><path d='m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7'/><path d='M9.5 10.5a2.5 2.5 0 0 1 5 0c0 1.5-2.5 3-2.5 3s-2.5-1.5-2.5-3Z'/></svg>">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        /* 用户中心 - 现代化布局 */
        .uc-wrap { max-width: 1100px; margin: 36px auto 60px; padding: 0 20px; display: grid; grid-template-columns: 260px 1fr; gap: 28px; }
        @media (max-width: 820px) { .uc-wrap { grid-template-columns: 1fr; gap: 16px; } .uc-side { position: static !important; } }
        .uc-side { position: sticky; top: 90px; align-self: start; }

        /* 左侧卡片：渐变头像区 + 菜单 */
        .uc-card { background: #fff; border-radius: 20px; box-shadow: 0 1px 3px rgba(15,23,42,0.04), 0 12px 32px -8px rgba(15,23,42,0.08); border: 1px solid var(--border-soft); overflow: hidden; }
        .uc-profile { padding: 28px 20px 24px; text-align: center; position: relative; background: linear-gradient(180deg, #F8FAFC 0%, #fff 100%); border-bottom: 1px solid var(--border-soft); }
        .uc-avatar { width: 72px; height: 72px; border-radius: 22px; margin: 0 auto 14px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 30px; font-family: 'Plus Jakarta Sans','Inter',sans-serif; box-shadow: 0 8px 20px rgba(59,130,246,0.35); position: relative; }
        .uc-avatar::after { content:''; position:absolute; inset:0; border-radius:22px; border:1px solid rgba(255,255,255,0.3); pointer-events:none; }
        .uc-name { font-size: 16px; font-weight: 700; color: var(--text); letter-spacing: -0.01em; }
        .uc-account { font-size: 12px; color: var(--text-muted); margin-top: 4px; word-break: break-all; }
        .uc-menu { padding: 10px; }
        .uc-menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 12px; cursor: pointer; color: var(--text-secondary); font-size: 14px; font-weight: 500; transition: all .2s ease; margin-bottom: 3px; position: relative; }
        .uc-menu-item:hover { background: var(--bg-soft); color: var(--text); }
        .uc-menu-item.active { background: var(--primary-light); color: var(--primary); font-weight: 600; }
        .uc-menu-item.active::before { content:''; position:absolute; left:-10px; top:50%; transform:translateY(-50%); width:3px; height:18px; border-radius:0 3px 3px 0; background:var(--primary); }
        .uc-menu-item .ic { width: 20px; height: 20px; display:flex; align-items:center; justify-content:center; }
        .uc-menu-item .ic svg { width: 18px; height: 18px; stroke-width: 2; }
        .uc-menu-item.danger { color: #DC2626; }
        .uc-menu-item.danger:hover { background: #FEE2E2; }
        .uc-menu-item.danger.active { background: #FEE2E2; color: #DC2626; }
        .uc-menu-item.danger.active::before { background: #DC2626; }

        /* 右侧主区 */
        .uc-main { background: #fff; border-radius: 20px; box-shadow: 0 1px 3px rgba(15,23,42,0.04), 0 12px 32px -8px rgba(15,23,42,0.08); border: 1px solid var(--border-soft); padding: 36px 40px; min-height: 480px; position: relative; overflow: hidden; }
        .uc-main::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, var(--primary) 0%, transparent 50%); opacity:.5; }
        @media (max-width: 820px) { .uc-main { padding: 28px 22px; } }
        .uc-section { display: none; }
        .uc-section.active { display: block; animation: fadeUp .35s cubic-bezier(.2,.8,.2,1); }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        .uc-section h2 { font-family: 'Plus Jakarta Sans','Inter',sans-serif; font-size: 24px; font-weight: 800; letter-spacing: -0.025em; margin-bottom: 6px; color: var(--text); }
        .uc-section .desc { color: var(--text-light); font-size: 13px; margin-bottom: 32px; }

        /* 表单 */
        .uc-form .form-group { margin-bottom: 22px; }
        .uc-form .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; letter-spacing: -0.005em; }
        .uc-form .form-control { width: 100%; padding: 12px 14px; border: 1.5px solid var(--border); border-radius: 12px; font-size: 14px; font-family: inherit; transition: all .2s ease; box-sizing: border-box; background: #fff; }
        .uc-form .form-control:hover:not(:focus) { border-color: var(--text-light); }
        .uc-form .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-ring); }
        .uc-form .form-control:disabled { background: var(--bg-soft); color: var(--text-muted); cursor: not-allowed; }
        .uc-form .hint { font-size: 12px; color: var(--text-muted); margin-top: 6px; line-height: 1.5; }

        /* 按钮 */
        .uc-btn { padding: 12px 24px; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all .2s ease; border: none; display: inline-flex; align-items: center; gap: 6px; }
        .uc-btn.primary { background: var(--primary); color: #fff; box-shadow: 0 4px 12px rgba(59,130,246,0.3), 0 2px 4px rgba(59,130,246,0.2); }
        .uc-btn.primary:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 6px 16px rgba(59,130,246,0.4); }
        .uc-btn.primary:active { transform: translateY(0); }
        .uc-btn.primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .uc-btn.ghost { background: #fff; color: var(--text-secondary); border: 1.5px solid var(--border); }
        .uc-btn.ghost:hover { background: var(--bg-soft); border-color: var(--text-light); }

        /* 徽章 */
        .uc-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; margin-left: 10px; vertical-align: middle; letter-spacing: 0.01em; }
        .uc-badge::before { content:''; width:6px; height:6px; border-radius:50%; }
        .uc-badge.ok { background: #D1FAE5; color: #065F46; }
        .uc-badge.ok::before { background: #10B981; }
        .uc-badge.no { background: #FEF3C7; color: #92400E; }
        .uc-badge.no::before { background: #F59E0B; }
        .uc-badge.oauth { background: var(--primary-light); color: var(--primary); }
        .uc-badge.oauth::before { background: var(--primary); }

        /* 信息行 */
        .info-list { background: #FAFBFC; border-radius: 14px; padding: 4px 18px; border: 1px solid var(--border-soft); }
        .info-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid var(--border-soft); font-size: 14px; }
        .info-row:last-child { border-bottom: none; }
        .info-row .k { color: var(--text-muted); }
        .info-row .v { color: var(--text); font-weight: 500; }

        /* 空状态盒 */
        .empty-box { padding: 40px 32px; text-align: center; color: var(--text-light); background: linear-gradient(180deg, #FAFBFC 0%, #F8FAFC 100%); border-radius: 14px; border: 1px dashed var(--border); }
        .empty-box .em-ic { font-size: 36px; margin-bottom: 14px; opacity: 0.7; }
        .empty-box .em-ic svg { width:36px; height:36px; }

        /* 头像预览块 */
        .avatar-edit { display: flex; align-items: center; gap: 18px; padding: 18px; background: linear-gradient(135deg, #FAFBFC 0%, #F8FAFC 100%); border-radius: 14px; border: 1px solid var(--border-soft); }

        /* 信件列表 */
        .lt-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 12px; }
        .lt-filters { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 18px; }
        .lt-filter { padding: 6px 14px; border-radius: 999px; font-size: 13px; font-weight: 500; background: #fff; border: 1px solid var(--border); color: var(--text-secondary); cursor: pointer; transition: all .2s ease; }
        .lt-filter:hover { border-color: var(--text-light); }
        .lt-filter.active { background: var(--text); color: #fff; border-color: var(--text); }
        .lt-list { display: grid; gap: 12px; }
        .lt-card { background: #FAFBFC; border-radius: 14px; border: 1px solid var(--border-soft); padding: 18px 20px; transition: all .2s ease; cursor: pointer; position: relative; }
        .lt-card:hover { background: #fff; box-shadow: 0 8px 20px rgba(15,23,42,0.08); border-color: var(--border); transform: translateY(-2px); }
        .lt-card-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 10px; }
        .lt-card-title { font-family: 'Plus Jakarta Sans','Inter',sans-serif; font-size: 16px; font-weight: 700; color: var(--text); letter-spacing: -0.01em; }
        .lt-status { padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; white-space: nowrap; }
        .lt-status.s0 { background: #FEF3C7; color: #92400E; }
        .lt-status.s1 { background: #D1FAE5; color: #065F46; }
        .lt-status.s2 { background: #FEE2E2; color: #991B1B; }
        .lt-status.s3 { background: #F3F4F6; color: #6B7280; }
        .lt-card-row { display: flex; gap: 16px; color: var(--text-light); font-size: 13px; margin-bottom: 5px; }
        .lt-card-row .k { width: 64px; color: var(--text-muted); }
        .lt-card-row .v { color: var(--text-secondary); }
        .lt-card-foot { display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-soft); }
        .lt-card-foot .time { color: var(--text-muted); font-size: 12px; display:inline-flex; align-items:center; gap:3px; }
        .lt-card-foot .time svg { width:12px; height:12px; }
        .lt-card-actions { display: flex; gap: 8px; }
        .lt-btn { padding: 6px 12px; border-radius: 7px; font-size: 12px; font-weight: 500; cursor: pointer; transition: all .2s ease; border: none; }
        .lt-btn.view { background: var(--primary-light); color: var(--primary); }
        .lt-btn.view:hover { background: var(--primary); color: #fff; }
        .lt-btn.cancel { background: #FEE2E2; color: #991B1B; }
        .lt-btn.cancel:hover { background: #DC2626; color: #fff; }
        .lt-empty { text-align: center; padding: 56px 20px; color: var(--text-light); }
        .lt-empty .em-ic { font-size: 40px; margin-bottom: 12px; opacity: 0.5; color: var(--text-light); }
        .lt-empty .em-ic svg { width:40px; height:40px; }
        .lt-empty a { color: var(--primary); font-weight: 600; }
        .lt-pager { display: flex; justify-content: center; gap: 6px; margin-top: 24px; }
        .lt-pager button { padding: 7px 13px; border-radius: 8px; border: 1px solid var(--border); background: #fff; cursor: pointer; font-size: 13px; transition: all .2s ease; }
        .lt-pager button:hover:not(:disabled) { border-color: var(--primary); color: var(--primary); }
        .lt-pager button:disabled { opacity: 0.4; cursor: not-allowed; }
        .lt-pager .cur { background: var(--text); color: #fff; border-color: var(--text); }

        /* 详情弹窗 */
        .lt-modal-mask { position: fixed; inset: 0; background: rgba(15,23,42,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(2px); }
        .lt-modal-mask.show { display: flex; }
        .lt-modal { background: #fff; border-radius: 18px; max-width: 560px; width: 100%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(15,23,42,0.25); }
        .lt-modal-head { padding: 20px 24px; border-bottom: 1px solid var(--border-soft); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; background: #fff; }
        .lt-modal-head h3 { font-family: 'Plus Jakarta Sans','Inter',sans-serif; font-size: 18px; font-weight: 700; }
        .lt-modal-close { width: 30px; height: 30px; border-radius: 50%; background: var(--bg-soft); display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 18px; border: none; color: var(--text-secondary); }
        .lt-modal-close:hover { background: var(--border-soft); }
        .lt-modal-body { padding: 24px; }
        .lt-modal-body .row { margin-bottom: 14px; }
        .lt-modal-body .row .k { color: var(--text-muted); font-size: 12px; margin-bottom: 4px; }
        .lt-modal-body .row .v { color: var(--text); font-size: 14px; }
        .lt-modal-body .content { background: var(--bg-soft); border-radius: 12px; padding: 16px; font-size: 14px; line-height: 1.8; color: var(--text); white-space: pre-wrap; word-break: break-word; margin-top: 8px; }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="navbar-inner">
        <a href="/" class="logo"><span class="logo-icon"><?= \App\Icons::get('heart_mail') ?></span><span><?= htmlspecialchars($siteName) ?></span></a>
        <button class="nav-toggle" aria-label="菜单"><span></span><span></span><span></span></button>
        <div class="nav-links">
            <a href="/">首页</a>
            <a href="/write" class="btn-primary">写信</a>
        </div>
    </div>
</nav>

<div class="uc-wrap">
    <!-- 左侧菜单 -->
    <aside class="uc-side">
        <div class="uc-card">
            <div class="uc-profile">
                <div id="avatar" class="uc-avatar">?</div>
                <div id="nickname" class="uc-name">加载中...</div>
                <div id="account" class="uc-account"></div>
            </div>
            <div class="uc-menu">
                <div class="uc-menu-item active" data-tab="letters">
                    <span class="ic"><?= \App\Icons::get('mail') ?></span>
                    我的信件
                </div>
                <div class="uc-menu-item" data-tab="profile">
                    <span class="ic"><?= \App\Icons::get('user') ?></span>
                    个人信息
                </div>
                <div class="uc-menu-item" data-tab="password">
                    <span class="ic"><?= \App\Icons::get('key') ?></span>
                    修改密码
                </div>
                <div class="uc-menu-item" data-tab="security">
                    <span class="ic"><?= \App\Icons::get('shield') ?></span>
                    密保问题
                </div>
                <div class="uc-menu-item danger" data-tab="logout">
                    <span class="ic"><?= \App\Icons::get('logout') ?></span>
                    退出登录
                </div>
            </div>
        </div>
    </aside>

    <!-- 右侧内容 -->
    <main class="uc-main">
        <!-- 我的信件 -->
        <section class="uc-section active" id="tab-letters">
            <h2>我的信件</h2>
            <p class="desc">管理你写给未来的时光信件</p>
            <div class="lt-filters">
                <div class="lt-filter active" data-status="">全部</div>
                <div class="lt-filter" data-status="0">待发送</div>
                <div class="lt-filter" data-status="1">已发送</div>
                <div class="lt-filter" data-status="2">失败</div>
                <div class="lt-filter" data-status="3">已撤回</div>
            </div>
            <div id="ltList" class="lt-list">
                <div class="lt-empty"><div class="em-ic"><?= \App\Icons::get('inbox') ?></div>加载中...</div>
            </div>
            <div id="ltPager" class="lt-pager" style="display:none;"></div>
        </section>

        <!-- 个人信息 -->
        <section class="uc-section" id="tab-profile">
            <h2>个人信息</h2>
            <p class="desc">管理你的昵称，昵称首字将作为头像展示</p>
            <div class="uc-form">
                <div class="form-group">
                    <label class="form-label">昵称</label>
                    <div class="avatar-edit">
                        <div id="avatarPreview" class="uc-avatar" style="margin:0;width:56px;height:56px;border-radius:16px;font-size:24px;">U</div>
                        <div style="flex:1;">
                            <input type="text" id="f_nickname" class="form-control" maxlength="20" placeholder="输入昵称（1-20 字）">
                            <div class="hint">头像会自动取昵称首字并应用品牌渐变</div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">账号信息</label>
                    <div class="info-list">
                        <div class="info-row"><span class="k">注册方式</span><span class="v" id="i_source">-</span></div>
                        <div class="info-row"><span class="k">手机号</span><span class="v" id="i_phone">-</span></div>
                        <div class="info-row"><span class="k">邮箱</span><span class="v" id="i_email">-</span></div>
                        <div class="info-row"><span class="k">注册时间</span><span class="v" id="i_created">-</span></div>
                    </div>
                </div>
                <button id="btnSaveProfile" class="uc-btn primary">
                    <span style="width:16px;height:16px;display:inline-flex;"><?= \App\Icons::get('save') ?></span>
                    保存修改
                </button>
            </div>
        </section>

        <!-- 修改密码 -->
        <section class="uc-section" id="tab-password">
            <h2>修改密码</h2>
            <p class="desc">仅本地注册账号可修改密码，建议定期更换</p>
            <div class="uc-form" id="pwForm">
                <div class="form-group">
                    <label class="form-label">原密码</label>
                    <input type="password" id="f_oldpw" class="form-control" maxlength="64" placeholder="输入当前密码">
                </div>
                <div class="form-group">
                    <label class="form-label">新密码</label>
                    <input type="password" id="f_newpw" class="form-control" maxlength="64" placeholder="至少 6 位">
                    <div class="hint">建议字母 + 数字组合，6-64 位</div>
                </div>
                <div class="form-group">
                    <label class="form-label">确认新密码</label>
                    <input type="password" id="f_newpw2" class="form-control" maxlength="64" placeholder="再次输入新密码">
                </div>
                <button id="btnSavePw" class="uc-btn primary">
                    <span style="width:16px;height:16px;display:inline-flex;"><?= \App\Icons::get('key') ?></span>
                    修改密码
                </button>
            </div>
            <div id="pwDisabled" class="empty-box" style="display:none;">
                <div class="em-ic" style="width:40px;height:40px;margin:0 auto 14px;color:var(--text-light);">
                    <?= \App\Icons::get('link') ?>
                </div>
                <div style="color:var(--text);font-size:15px;font-weight:600;margin-bottom:6px;">第三方登录账号</div>
                <div style="font-size:13px;">当前账号通过微信/QQ 等登录，不支持修改密码<br>如需设置密码，请联系管理员</div>
            </div>
        </section>

        <!-- 密保问题 -->
        <section class="uc-section" id="tab-security">
            <h2>密保问题 <span id="secBadge" class="uc-badge no">未设置</span></h2>
            <p class="desc">用于私密信件的身份核验，每账号仅一个，请妥善设置</p>
            <div class="uc-form">
                <div class="form-group">
                    <label class="form-label">问题 <span style="color:#DC2626;">*</span></label>
                    <input type="text" id="f_question" class="form-control" maxlength="60" placeholder="自定义问题，例如：你小学最好的朋友叫什么名字？">
                    <div class="hint">2-60 字，建议选择只有自己知道答案的问题</div>
                </div>
                <div class="form-group">
                    <label class="form-label">答案 <span style="color:#DC2626;">*</span></label>
                    <input type="text" id="f_answer" class="form-control" maxlength="60" placeholder="输入答案（不区分大小写）">
                    <div class="hint">答案以加密形式存储，仅用于核验，无法找回，1-60 字</div>
                </div>
                <button id="btnSaveSec" class="uc-btn primary">
                    <span style="width:16px;height:16px;display:inline-flex;"><?= \App\Icons::get('save') ?></span>
                    保存密保问题
                </button>
            </div>
        </section>

        <!-- 退出登录 -->
        <section class="uc-section" id="tab-logout">
            <h2>退出登录</h2>
            <p class="desc">退出后需要重新登录才能管理信件</p>
            <div class="empty-box">
                <div class="em-ic" style="width:44px;height:44px;margin:0 auto 14px;color:var(--text-light);">
                    <?= \App\Icons::get('logout') ?>
                </div>
                <div style="color:var(--text);font-size:15px;font-weight:600;margin-bottom:20px;">确定要退出当前账号吗？</div>
                <div style="display:flex;gap:10px;justify-content:center;">
                    <button id="btnLogout" class="uc-btn primary" style="background:#DC2626;box-shadow:0 4px 12px rgba(220,38,38,0.3);">确认退出</button>
                    <a href="/settings" class="uc-btn ghost" style="text-decoration:none;">取消</a>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- 信件详情弹窗 -->
<div id="ltModalMask" class="lt-modal-mask">
    <div class="lt-modal">
        <div class="lt-modal-head">
            <h3 id="ltModalTitle">信件详情</h3>
            <button class="lt-modal-close" onclick="closeLtModal()">×</button>
        </div>
        <div class="lt-modal-body" id="ltModalBody"></div>
    </div>
</div>

<div id="toast"></div>
<script src="/assets/js/app.js"></script>
<script>
const token = localStorage.getItem('token') || '';
if (!token) { location.href = '/login'; }

// 图标（来自 PHP 图标库）
const icInbox = '<?= addslashes(\App\Icons::get('inbox')) ?>';
const icPaperclip = '<?= addslashes(\App\Icons::get('paperclip')) ?>';

let userData = null;

// ===== 菜单切换 =====
document.querySelectorAll('.uc-menu-item').forEach(item => {
    item.addEventListener('click', () => {
        const tab = item.dataset.tab;
        if (tab === 'logout') {
            // 退出 tab 直接切换显示
        }
        document.querySelectorAll('.uc-menu-item').forEach(x => x.classList.remove('active'));
        item.classList.add('active');
        document.querySelectorAll('.uc-section').forEach(s => s.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
    });
});

// ===== 信件列表 =====
let ltStatus = '';
let ltPage = 1;

document.querySelectorAll('.lt-filter').forEach(f => {
    f.addEventListener('click', () => {
        document.querySelectorAll('.lt-filter').forEach(x => x.classList.remove('active'));
        f.classList.add('active');
        ltStatus = f.dataset.status;
        ltPage = 1;
        loadLtList();
    });
});

function loadLtList() {
    document.getElementById('ltList').innerHTML = '<div class="lt-empty">加载中...</div>';
    let url = '/api/letter?page=' + ltPage + '&page_size=20';
    if (ltStatus !== '') url += '&status=' + ltStatus;
    fetch(url, { headers: { 'Authorization': 'Bearer ' + token }})
        .then(r => r.json())
        .then(res => {
            if (res.code === 401) { location.href = '/login'; return; }
            if (res.code !== 0) { toast(res.msg, 'error'); return; }
            renderLtList(res.data);
        });
}

function ltStatusText(s) { return {0:'待发送',1:'已发送',2:'发送失败',3:'已撤回'}[s] || '未知'; }
function ltChannelText(c) {
    const t = [];
    if (c & 1) t.push('短信');
    if (c & 2) t.push('邮件');
    return t.join('+');
}

function renderLtList(data) {
    const list = data.list || [];
    if (!list.length) {
        document.getElementById('ltList').innerHTML = '<div class="lt-empty"><div class="em-ic">'+icInbox+'</div>还没有信件<br><a href="/write">去写第一封 →</a></div>';
        document.getElementById('ltPager').style.display = 'none';
        return;
    }
    const html = list.map(l => `
        <div class="lt-card" onclick="showLtDetail(${l.id})">
            <div class="lt-card-top">
                <div class="lt-card-title">${escapeHtml(l.title)}</div>
                <span class="lt-status s${l.status}">${ltStatusText(l.status)}</span>
            </div>
            <div class="lt-card-row"><span class="k">收件人</span><span class="v">${escapeHtml(l.recipient_name || '-')}</span></div>
            <div class="lt-card-row"><span class="k">渠道</span><span class="v">${ltChannelText(l.deliver_channel)}</span></div>
            <div class="lt-card-row"><span class="k">投递于</span><span class="v">${l.send_time}</span></div>
            <div class="lt-card-foot">
                <span class="time">创建于 ${l.created_at}${l.attachment_count ? ' · '+icPaperclip+' '+l.attachment_count : ''}</span>
                <div class="lt-card-actions" onclick="event.stopPropagation()">
                    <button class="lt-btn view" onclick="showLtDetail(${l.id})">查看</button>
                    ${l.status === 0 ? `<button class="lt-btn cancel" onclick="cancelLt(${l.id})">撤回</button>` : ''}
                </div>
            </div>
        </div>
    `).join('');
    document.getElementById('ltList').innerHTML = html;

    const totalPages = Math.ceil(data.total / data.page_size);
    const pager = document.getElementById('ltPager');
    if (totalPages <= 1) { pager.style.display = 'none'; return; }
    pager.style.display = 'flex';
    let ph = '';
    ph += `<button ${ltPage<=1?'disabled':''} onclick="ltGoPage(${ltPage-1})">上一页</button>`;
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || Math.abs(i - ltPage) <= 1) {
            ph += `<button class="${i===ltPage?'cur':''}" onclick="ltGoPage(${i})">${i}</button>`;
        } else if (Math.abs(i - ltPage) === 2) {
            ph += `<button disabled>…</button>`;
        }
    }
    ph += `<button ${ltPage>=totalPages?'disabled':''} onclick="ltGoPage(${ltPage+1})">下一页</button>`;
    pager.innerHTML = ph;
}

function ltGoPage(p) { ltPage = p; loadLtList(); }

function showLtDetail(id) {
    fetch('/api/letter/' + id, { headers: { 'Authorization': 'Bearer ' + token }})
        .then(r => r.json())
        .then(res => {
            if (res.code !== 0) { toast(res.msg, 'error'); return; }
            const l = res.data;
            document.getElementById('ltModalTitle').textContent = l.title;
            const attachHtml = (l.attachments && l.attachments.length) ? `
                <div class="row">
                    <div class="k">附件 (${l.attachments.length})</div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
                        ${l.attachments.map(a => `<a href="${a.share_url||'#'}" target="_blank" class="lt-btn view">${a.file_name}</a>`).join('')}
                    </div>
                </div>` : '';
            document.getElementById('ltModalBody').innerHTML = `
                <div class="row"><div class="k">收件人</div><div class="v">${escapeHtml(l.recipient_name||'-')}</div></div>
                <div class="row"><div class="k">投递渠道</div><div class="v">${ltChannelText(l.deliver_channel)}</div></div>
                <div class="row"><div class="k">投递时间</div><div class="v">${l.send_time}</div></div>
                <div class="row"><div class="k">状态</div><div class="v">${ltStatusText(l.status)}${l.sent_at?' · 已于 '+l.sent_at+' 送达':''}</div></div>
                <div class="row"><div class="k">正文</div><div class="content">${escapeHtml(l.content)}</div></div>
                ${attachHtml}
            `;
            document.getElementById('ltModalMask').classList.add('show');
        });
}

function closeLtModal() { document.getElementById('ltModalMask').classList.remove('show'); }
document.getElementById('ltModalMask').addEventListener('click', e => { if (e.target.id === 'ltModalMask') closeLtModal(); });

function cancelLt(id) {
    if (!confirm('确定撤回这封信件？撤回后无法恢复。')) return;
    fetch('/api/letter/' + id + '/cancel', { method: 'POST', headers: { 'Authorization': 'Bearer ' + token }})
        .then(r => r.json())
        .then(res => {
            if (res.code === 0) { toast('已撤回'); loadLtList(); }
            else toast(res.msg, 'error');
        });
}

function escapeHtml(s) {
    return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// ===== 加载用户信息 =====
function loadProfile() {
    return fetch('/api/user/profile', { headers: { 'Authorization': 'Bearer ' + token }})
        .then(r => r.json())
        .then(res => {
            if (res.code === 401) { location.href = '/login'; return; }
            if (res.code !== 0) { toast(res.msg, 'error'); return; }
            const u = res.data;
            userData = u;
            const first = (u.nickname || 'U').charAt(0).toUpperCase();
            document.getElementById('nickname').textContent = u.nickname || '用户';
            document.getElementById('avatar').textContent = first;
            document.getElementById('avatarPreview').textContent = first;
            document.getElementById('account').textContent = [u.phone, u.email].filter(Boolean).join(' · ') || '第三方登录账号';
            document.getElementById('f_nickname').value = u.nickname || '';

            const sourceText = u.union_source === 'local' ? '本地注册' : '第三方登录';
            document.getElementById('i_source').innerHTML = sourceText + (u.union_source !== 'local' ? ' <span class="uc-badge oauth">OAuth</span>' : '');
            document.getElementById('i_phone').textContent = u.phone || '未绑定';
            document.getElementById('i_email').textContent = u.email || '未绑定';
            document.getElementById('i_created').textContent = u.created_at ? u.created_at.substring(0, 16) : '-';

            // 密码设置区块显隐
            if (!u.has_password) {
                document.getElementById('pwForm').style.display = 'none';
                document.getElementById('pwDisabled').style.display = 'block';
            }

            // 密保 badge
            const badge = document.getElementById('secBadge');
            if (u.has_sec_question) {
                badge.textContent = '已设置';
                badge.className = 'uc-badge ok';
            }
        });
}

// ===== 加载密保问题 =====
function loadSecurity() {
    return fetch('/api/user/security', { headers: { 'Authorization': 'Bearer ' + token }})
        .then(r => r.json())
        .then(res => {
            if (res.code === 0 && res.data.has_question) {
                document.getElementById('f_question').value = res.data.question;
                document.getElementById('btnSaveSec').textContent = '更新密保问题';
            }
        });
}

// ===== 保存个人信息 =====
document.getElementById('btnSaveProfile').addEventListener('click', () => {
    const nickname = document.getElementById('f_nickname').value.trim();
    if (!nickname) { toast('请输入昵称', 'error'); return; }
    const btn = document.getElementById('btnSaveProfile');
    btn.disabled = true; btn.textContent = '保存中...';
    fetch('/api/user/profile', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify({ nickname })
    }).then(r => r.json()).then(res => {
        if (res.code === 0) {
            toast('已保存');
            const first = nickname.charAt(0).toUpperCase();
            document.getElementById('avatar').textContent = first;
            document.getElementById('avatarPreview').textContent = first;
            document.getElementById('nickname').textContent = nickname;
        } else toast(res.msg || '保存失败', 'error');
    }).catch(() => toast('网络错误', 'error'))
    .finally(() => { btn.disabled = false; btn.textContent = '保存修改'; });
});

// ===== 修改密码 =====
document.getElementById('btnSavePw').addEventListener('click', () => {
    const oldpw = document.getElementById('f_oldpw').value;
    const newpw = document.getElementById('f_newpw').value;
    const newpw2 = document.getElementById('f_newpw2').value;
    if (!oldpw || !newpw) { toast('请填写完整', 'error'); return; }
    if (newpw.length < 6) { toast('新密码至少 6 位', 'error'); return; }
    if (newpw !== newpw2) { toast('两次新密码不一致', 'error'); return; }
    const btn = document.getElementById('btnSavePw');
    btn.disabled = true; btn.textContent = '修改中...';
    fetch('/api/user/password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify({ old_password: oldpw, new_password: newpw })
    }).then(r => r.json()).then(res => {
        if (res.code === 0) {
            toast('密码已修改');
            document.getElementById('f_oldpw').value = '';
            document.getElementById('f_newpw').value = '';
            document.getElementById('f_newpw2').value = '';
        } else toast(res.msg || '修改失败', 'error');
    }).catch(() => toast('网络错误', 'error'))
    .finally(() => { btn.disabled = false; btn.textContent = '修改密码'; });
});

// ===== 保存密保问题 =====
document.getElementById('btnSaveSec').addEventListener('click', () => {
    const question = document.getElementById('f_question').value.trim();
    const answer = document.getElementById('f_answer').value.trim();
    if (!question || !answer) { toast('请填写问题和答案', 'error'); return; }
    const btn = document.getElementById('btnSaveSec');
    btn.disabled = true; btn.textContent = '保存中...';
    fetch('/api/user/security', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify({ question, answer })
    }).then(r => r.json()).then(res => {
        if (res.code === 0) {
            toast('密保问题已保存');
            document.getElementById('f_answer').value = '';
            const badge = document.getElementById('secBadge');
            badge.textContent = '已设置';
            badge.className = 'uc-badge ok';
            document.getElementById('btnSaveSec').textContent = '更新密保问题';
        } else toast(res.msg || '保存失败', 'error');
    }).catch(() => toast('网络错误', 'error'))
    .finally(() => { btn.disabled = false; btn.textContent = '保存密保问题'; });
});

// ===== 退出登录 =====
document.getElementById('btnLogout').addEventListener('click', () => {
    fetch('/api/auth/logout', { method: 'POST', headers: { 'Authorization': 'Bearer ' + token }})
        .finally(() => {
            localStorage.removeItem('token');
            location.href = '/login';
        });
});

// 初始化
// ===== URL param tab support =====
const urlParams = new URLSearchParams(location.search);
const targetTab = urlParams.get('tab');
if (targetTab && document.getElementById('tab-' + targetTab)) {
    document.querySelectorAll('.uc-menu-item').forEach(x => x.classList.remove('active'));
    const menuItem = document.querySelector('.uc-menu-item[data-tab="' + targetTab + '"]');
    if (menuItem) menuItem.classList.add('active');
    document.querySelectorAll('.uc-section').forEach(s => s.classList.remove('active'));
    document.getElementById('tab-' + targetTab).classList.add('active');
}

loadProfile().then(() => loadSecurity());
loadLtList();
</script>
</body>
</html>
