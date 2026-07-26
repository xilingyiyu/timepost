<?php /** 信件管理 */ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>信件管理 · 时光邮局后台</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1 style="display:flex;align-items:center;gap:8px;"><span style="width:20px;height:20px;display:inline-flex;"><?= \App\Icons::get('mail') ?></span>信件管理</h1>
            <div class="admin-user"><a href="/" target="_blank">前台</a> · <a href="/admin/logout">退出</a></div>
        </header>
        <div class="admin-content">
            <div class="card">
                <div class="filter-bar">
                    <select id="fStatus">
                        <option value="">全部状态</option>
                        <option value="0">待发送</option>
                        <option value="1">已发送</option>
                        <option value="2">失败</option>
                        <option value="3">已撤回</option>
                    </select>
                    <input type="text" id="fKeyword" placeholder="搜索 标题/收件人/手机/邮箱">
                    <button class="btn-search" onclick="loadList(1)" style="display:inline-flex;align-items:center;gap:6px;"><span style="width:16px;height:16px;display:inline-flex;"><?= \App\Icons::get('search') ?></span>搜索</button>
                    <button class="btn-search" onclick="exportLetters()" style="display:inline-flex;align-items:center;gap:6px;background:#27ae60;"><span style="width:16px;height:16px;display:inline-flex;"><?= \App\Icons::get('download') ?></span>导出CSV</button>
                </div>
                <table class="table">
                    <thead>
                        <tr><th>ID</th><th>标题</th><th>收件人</th><th>渠道</th><th>投递时间</th><th>状态</th><th>公开</th><th>创建时间</th><th>操作</th></tr>
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
    const iconImg = '<span style="width:16px;height:16px;display:inline-flex;vertical-align:-3px;"><?php echo addslashes(\App\Icons::get('image')); ?></span>';
    const iconVid = '<span style="width:16px;height:16px;display:inline-flex;vertical-align:-3px;"><?php echo addslashes(\App\Icons::get('film')); ?></span>';
    let curPage = 1;

    const auditMap = {0:'<span style="color:#f39c12;">待审核</span>', 1:'<span style="color:#27ae60;">已通过</span>', 2:'<span style="color:#e74c3c;">已拒绝</span>', 3:'<span style="color:#999;">私密</span>'};

    async function loadList(page) {
        curPage = page || 1;
        const status = document.getElementById('fStatus').value;
        const keyword = document.getElementById('fKeyword').value.trim();
        const url = `/admin/api/letters?page=${curPage}&status=${status}&keyword=${encodeURIComponent(keyword)}`;
        const data = await api(url);
        if (data.code !== 0) return toast(data.msg, true);

        const d = data.data;
        document.getElementById('listTable').innerHTML = d.list.map(r => `
            <tr>
                <td>${r.id}</td>
                <td><a href="javascript:showDetail(${r.id})">${escapeHtml(r.title)}</a></td>
                <td>${escapeHtml(r.recipient_name)}<br><span style="font-size:12px;color:#999;">${escapeHtml(r.recipient_phone||r.recipient_email||'')}</span></td>
                <td>${channelMap[r.deliver_channel]}</td>
                <td>${r.send_time}</td>
                <td><span class="badge ${statusMap[r.status][1]}">${statusMap[r.status][0]}</span>${r.retry_count>0?`<span style="font-size:11px;color:#999;">(×${r.retry_count})</span>`:''}</td>
                <td>${r.is_public==1?'<span style="color:#27ae60;">公开</span>':'<span style="color:#999;">私密</span>'}</td>
                <td>${r.created_at}</td>
                <td>
                    <button class="btn-action" onclick="showDetail(${r.id})">详情</button>
                    ${r.status===0||r.status===2?`<button class="btn-action" onclick="forceCancel(${r.id})">撤回</button>`:''}
                    ${r.status===2?`<button class="btn-action" style="color:#27ae60;border-color:#27ae60;" onclick="resendLetter(${r.id})">重发</button>`:''}
                    <button class="btn-action danger" onclick="delLetter(${r.id})">删除</button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="9" class="empty">暂无数据</td></tr>';

        const totalPages = Math.ceil(d.total / d.page_size);
        renderPagination('pagination', d.page, totalPages, loadList);
    }

    function exportLetters() {
        const status = document.getElementById('fStatus').value;
        window.location.href = `/admin/api/export/letters?status=${status}`;
    }

    async function showDetail(id) {
        const data = await api(`/admin/api/letter/${id}`);
        if (data.code !== 0) return toast(data.msg, true);
        const r = data.data;
        let html = `
            <div class="drawer-header">
                <h2>信件详情 #${r.id}</h2>
                <button class="drawer-close" onclick="closeDrawer()">×</button>
            </div>
            <div class="detail-row"><div class="label">标题</div><div class="value">${escapeHtml(r.title)}</div></div>
            <div class="detail-row"><div class="label">收件人</div><div class="value">${escapeHtml(r.recipient_name)}</div></div>
            <div class="detail-row"><div class="label">手机号</div><div class="value">${escapeHtml(r.recipient_phone||'-')}</div></div>
            <div class="detail-row"><div class="label">邮箱</div><div class="value">${escapeHtml(r.recipient_email||'-')}</div></div>
            <div class="detail-row"><div class="label">投递渠道</div><div class="value">${channelMap[r.deliver_channel]}</div></div>
            <div class="detail-row"><div class="label">投递时间</div><div class="value">${r.send_time}</div></div>
            <div class="detail-row"><div class="label">状态</div><div class="value"><span class="badge ${statusMap[r.status][1]}">${statusMap[r.status][0]}</span></div></div>
            <div class="detail-row"><div class="label">是否公开</div><div class="value">${r.is_public?'是':'否'}</div></div>
            <div class="detail-row"><div class="label">重试次数</div><div class="value">${r.retry_count}</div></div>
            ${r.error_msg?`<div class="detail-row"><div class="label">错误信息</div><div class="value" style="color:#e74c3c;">${escapeHtml(r.error_msg)}</div></div>`:''}
            <div class="detail-row"><div class="label">创建时间</div><div class="value">${r.created_at}</div></div>
            <div class="detail-row"><div class="label">发送时间</div><div class="value">${r.sent_at||'-'}</div></div>
            <div class="detail-row"><div class="label">查看链接</div><div class="value"><a href="/v/${r.view_token}" target="_blank" style="color:#FF6B6B;">/v/${r.view_token}</a></div></div>
            <div class="detail-row" style="flex-direction:column;"><div class="label">内容</div><div class="value" style="white-space:pre-wrap;margin-top:8px;padding:12px;background:#f8f9fa;border-radius:6px;">${escapeHtml(r.content)}</div></div>
        `;
        if (r.attachments && r.attachments.length) {
            html += `<div class="detail-row" style="flex-direction:column;"><div class="label">附件（${r.attachments.length}）</div><div class="value" style="margin-top:8px;">`;
            r.attachments.forEach(a => {
                html += `<div style="padding:6px 0;border-bottom:1px dashed #eee;">
                    ${a.file_type==='image'?iconImg:iconVid} ${escapeHtml(a.file_name)} (${(a.file_size/1048576).toFixed(2)}MB)
                    <a href="${escapeHtml(a.share_url)}" target="_blank" style="color:#FF6B6B;font-size:12px;">查看</a>
                </div>`;
            });
            html += '</div></div>';
        }
        if (r.logs && r.logs.length) {
            html += `<div class="detail-row" style="flex-direction:column;"><div class="label">发送日志</div><div class="value" style="margin-top:8px;">`;
            r.logs.forEach(l => {
                html += `<div style="padding:4px 0;font-size:13px;">
                    <span class="badge ${l.status?'badge-green':'badge-red'}">${l.channel===1?'短信':'邮件'} ${l.status?'成功':'失败'}</span>
                    <span style="color:#999;margin-left:8px;">${l.created_at}</span>
                    ${l.error?`<div style="color:#e74c3c;margin-top:4px;">${escapeHtml(l.error)}</div>`:''}
                </div>`;
            });
            html += '</div></div>';
        }
        openDrawer(html);
    }

    async function forceCancel(id) {
        if (!confirmAction('确定要强制撤回这封信吗？')) return;
        const data = await api(`/admin/api/letter/${id}/cancel`, {method:'POST'});
        if (data.code === 0) { toast('已撤回'); loadList(curPage); }
        else toast(data.msg, true);
    }

    async function resendLetter(id) {
        if (!confirmAction('确定要手动重发这封失败信件吗？将立即加入发送队列。')) return;
        const data = await api(`/admin/api/letter/${id}/resend`, {method:'POST'});
        if (data.code === 0) { toast('已加入重发队列'); loadList(curPage); }
        else toast(data.msg, true);
    }

    async function delLetter(id) {
        if (!confirmAction('彻底删除信件会同时删除其附件和日志，确定吗？')) return;
        const data = await api(`/admin/api/letter/${id}`, {method:'DELETE'});
        if (data.code === 0) { toast('已删除'); loadList(curPage); }
        else toast(data.msg, true);
    }

    // URL 参数支持?id=xxx 自动打开详情
    const urlParams = new URLSearchParams(location.search);
    if (urlParams.get('id')) showDetail(urlParams.get('id'));
    else loadList(1);
</script>
</body>
</html>
