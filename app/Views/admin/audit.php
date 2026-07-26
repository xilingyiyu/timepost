<?php /** 操作审计日志 */ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>审计日志 · 时光邮局后台</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1 style="display:flex;align-items:center;gap:8px;"><span style="width:20px;height:20px;display:inline-flex;"><?= \App\Icons::get('shield') ?></span>操作审计日志</h1>
            <div class="admin-user"><a href="/" target="_blank">前台</a> · <a href="/admin/logout">退出</a></div>
        </header>
        <div class="admin-content">
            <div class="card">
                <div class="filter-bar">
                    <input type="text" id="fAction" placeholder="按操作类型过滤（如 export/audit/user_status）">
                    <button class="btn-search" onclick="loadList(1)" style="display:inline-flex;align-items:center;gap:6px;"><span style="width:16px;height:16px;display:inline-flex;"><?= \App\Icons::get('search') ?></span>搜索</button>
                </div>
                <table class="table">
                    <thead>
                        <tr><th>ID</th><th>管理员</th><th>操作</th><th>对象类型</th><th>对象ID</th><th>详情</th><th>IP</th><th>时间</th></tr>
                    </thead>
                    <tbody id="listTable"></tbody>
                </table>
                <div class="pagination" id="pagination"></div>
            </div>
        </div>
    </main>
</div>

<div id="toast"></div>
<script src="/assets/js/app.js"></script>
<script src="/assets/js/admin.js"></script>
<script>
    let curPage = 1;

    async function loadList(page) {
        curPage = page || 1;
        const action = document.getElementById('fAction').value.trim();
        const data = await api(`/admin/api/audit?page=${curPage}&action=${encodeURIComponent(action)}`);
        if (data.code !== 0) return toast(data.msg, true);

        const d = data.data;
        document.getElementById('listTable').innerHTML = d.list.map(r => `
            <tr>
                <td>${r.id}</td>
                <td>${escapeHtml(r.admin_name || ('#' + r.admin_id))}</td>
                <td><span class="badge badge-blue">${escapeHtml(r.action)}</span></td>
                <td>${escapeHtml(r.target_type)}</td>
                <td>${escapeHtml(r.target_id || '-')}</td>
                <td style="font-size:13px;">${escapeHtml(r.detail || '-')}</td>
                <td style="font-family:monospace;font-size:12px;">${escapeHtml(r.ip || '-')}</td>
                <td>${r.created_at}</td>
            </tr>
        `).join('') || '<tr><td colspan="8" class="empty">暂无数据</td></tr>';

        const totalPages = Math.ceil(d.total / d.page_size);
        renderPagination('pagination', d.page, totalPages, loadList);
    }

    loadList(1);
</script>
</body>
</html>
