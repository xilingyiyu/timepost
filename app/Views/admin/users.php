<?php /** 用户管理 */ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 · 时光邮局后台</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1 style="display:flex;align-items:center;gap:8px;"><span style="width:20px;height:20px;display:inline-flex;"><?= \App\Icons::get('users') ?></span>用户管理</h1>
            <div class="admin-user"><a href="/" target="_blank">前台</a> · <a href="/admin/logout">退出</a></div>
        </header>
        <div class="admin-content">
            <div class="card">
                <div class="filter-bar">
                    <input type="text" id="fKeyword" placeholder="搜索 手机号/邮箱/昵称">
                    <button class="btn-search" onclick="loadList(1)" style="display:inline-flex;align-items:center;gap:6px;"><span style="width:16px;height:16px;display:inline-flex;"><?= \App\Icons::get('search') ?></span>搜索</button>
                    <button class="btn-search" onclick="window.location.href='/admin/api/export/users'" style="display:inline-flex;align-items:center;gap:6px;background:#27ae60;"><span style="width:16px;height:16px;display:inline-flex;"><?= \App\Icons::get('download') ?></span>导出CSV</button>
                </div>
                <table class="table">
                    <thead>
                        <tr><th>ID</th><th>昵称</th><th>手机号</th><th>邮箱</th><th>来源</th><th>信件数</th><th>状态</th><th>注册时间</th><th>操作</th></tr>
                    </thead>
                    <tbody id="listTable"></tbody>
                </table>
                <div class="pagination" id="pagination"></div>
            </div>
        </div>
    </main>
</div>

<div id="toast"></div>

<!-- 重置密码模态框 -->
<div id="pwdModal" class="modal-mask" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3>重置用户密码</h3>
            <button class="modal-close" onclick="closePwdModal()">×</button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-secondary);font-size:13px;margin-bottom:16px;">
                正在为用户 <strong id="pwdUserName" style="color:var(--text);"></strong>（ID: <span id="pwdUserId"></span>）重置密码
            </p>
            <div class="form-row">
                <label>新密码</label>
                <input type="password" id="pwdNew" placeholder="至少 6 位" autocomplete="new-password">
            </div>
            <div class="form-row">
                <label>确认新密码</label>
                <input type="password" id="pwdConfirm" placeholder="再次输入新密码" autocomplete="new-password">
            </div>
            <p style="color:var(--text-light);font-size:12px;margin-top:8px;">⚠️ 重置后原密码立即失效，建议告知用户新密码并提醒尽快修改。</p>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closePwdModal()">取消</button>
            <button class="btn-save" id="pwdSubmitBtn" onclick="submitResetPassword()">确认重置</button>
        </div>
    </div>
</div>

<script src="/assets/js/app.js"></script>
<script src="/assets/js/admin.js"></script>
<script>
    let curPage = 1;
    let pwdTargetUserId = 0;

    async function loadList(page) {
        curPage = page || 1;
        const keyword = document.getElementById('fKeyword').value.trim();
        const data = await api(`/admin/api/users?page=${curPage}&keyword=${encodeURIComponent(keyword)}`);
        if (data.code !== 0) return toast(data.msg, true);

        const d = data.data;
        document.getElementById('listTable').innerHTML = d.list.map(r => `
            <tr>
                <td>${r.id}</td>
                <td>${r.avatar?`<img src="${escapeHtml(r.avatar)}" style="width:24px;height:24px;border-radius:50%;vertical-align:middle;"> `:''}${escapeHtml(r.nickname)}</td>
                <td>${escapeHtml(r.phone||'-')}</td>
                <td>${escapeHtml(r.email||'-')}</td>
                <td><span class="badge ${r.union_source==='oauth'?'badge-blue':'badge-gray'}">${r.union_source==='oauth'?'第三方':'注册'}</span></td>
                <td>${r.letter_count}</td>
                <td><span class="badge ${r.status?'badge-green':'badge-red'}">${r.status?'正常':'禁用'}</span></td>
                <td>${r.created_at}</td>
                <td>
                    <button class="btn-action" onclick="resetPassword(${r.id}, escapeHtml(r.nickname))">改密码</button>
                    <button class="btn-action" onclick="toggleStatus(${r.id}, ${r.status?0:1})">${r.status?'禁用':'启用'}</button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="9" class="empty">暂无数据</td></tr>';

        const totalPages = Math.ceil(d.total / d.page_size);
        renderPagination('pagination', d.page, totalPages, loadList);
    }

    async function toggleStatus(id, status) {
        const action = status ? '启用' : '禁用';
        if (!confirmAction(`确定${action}该用户吗？`)) return;
        const data = await api(`/admin/api/user/${id}/status`, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({status})
        });
        if (data.code === 0) { toast('已' + action); loadList(curPage); }
        else toast(data.msg, true);
    }

    // 重置密码模态框
    function resetPassword(id, name) {
        pwdTargetUserId = id;
        document.getElementById('pwdUserId').textContent = id;
        document.getElementById('pwdUserName').textContent = name;
        document.getElementById('pwdNew').value = '';
        document.getElementById('pwdConfirm').value = '';
        document.getElementById('pwdModal').style.display = 'flex';
        setTimeout(() => document.getElementById('pwdNew').focus(), 100);
    }

    function closePwdModal() {
        document.getElementById('pwdModal').style.display = 'none';
    }

    async function submitResetPassword() {
        const newPwd = document.getElementById('pwdNew').value;
        const confirmPwd = document.getElementById('pwdConfirm').value;
        if (newPwd.length < 6) return toast('新密码至少 6 位', true);
        if (newPwd !== confirmPwd) return toast('两次输入的密码不一致', true);

        const btn = document.getElementById('pwdSubmitBtn');
        btn.disabled = true; btn.textContent = '提交中...';
        try {
            const data = await api(`/admin/api/user/${pwdTargetUserId}/password`, {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({new_password: newPwd})
            });
            if (data.code === 0) {
                toast('密码已重置');
                closePwdModal();
            } else {
                toast(data.msg, true);
            }
        } finally {
            btn.disabled = false; btn.textContent = '确认重置';
        }
    }

    // ESC 关闭模态框
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closePwdModal();
    });

    loadList(1);
</script>
</body>
</html>
