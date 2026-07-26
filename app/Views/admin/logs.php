<?php /** 发送日志 */ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>发送日志 · 时光邮局后台</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1 style="display:flex;align-items:center;gap:8px;"><span style="width:20px;height:20px;display:inline-flex;"><?= \App\Icons::get('list') ?></span>发送日志</h1>
            <div class="admin-user"><a href="/" target="_blank">前台</a> · <a href="/admin/logout">退出</a></div>
        </header>
        <div class="admin-content">
            <div class="card">
                <div class="filter-bar">
                    <input type="number" id="fLetterId" placeholder="按信件 ID 过滤">
                    <button class="btn-search" onclick="loadList(1)" style="display:inline-flex;align-items:center;gap:6px;"><span style="width:16px;height:16px;display:inline-flex;"><?= \App\Icons::get('search') ?></span>搜索</button>
                </div>
                <table class="table">
                    <thead>
                        <tr><th>ID</th><th>信件ID</th><th>渠道</th><th>状态</th><th>服务商消息ID</th><th>错误信息</th><th>时间</th></tr>
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
        const letterId = document.getElementById('fLetterId').value.trim();
        const data = await api(`/admin/api/logs?page=${curPage}&letter_id=${letterId}`);
        if (data.code !== 0) return toast(data.msg, true);

        const d = data.data;
        document.getElementById('listTable').innerHTML = d.list.map(r => `
            <tr>
                <td>${r.id}</td>
                <td><a href="/admin/letters?id=${r.letter_id}">${r.letter_id}</a></td>
                <td>${r.channel===1?'短信':'邮件'}</td>
                <td><span class="badge ${r.status?'badge-green':'badge-red'}">${r.status?'成功':'失败'}</span></td>
                <td style="font-family:monospace;font-size:12px;">${escapeHtml(r.provider_msg||'-')}</td>
                <td style="color:#e74c3c;font-size:13px;">${escapeHtml(r.error||'-')}</td>
                <td>${r.created_at}</td>
            </tr>
        `).join('') || '<tr><td colspan="7" class="empty">暂无数据</td></tr>';

        const totalPages = Math.ceil(d.total / d.page_size);
        renderPagination('pagination', d.page, totalPages, loadList);
    }

    loadList(1);
</script>
</body>
</html>
