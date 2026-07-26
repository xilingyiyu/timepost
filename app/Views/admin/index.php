<?php /** 后台仪表盘 */ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>仪表盘 · 时光邮局后台</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1 style="display:flex;align-items:center;gap:8px;"><span style="width:20px;height:20px;display:inline-flex;"><?= \App\Icons::get('dashboard') ?></span>仪表盘</h1>
            <div class="admin-user">
                <a href="/" target="_blank">前台</a> ·
                <a href="/admin/logout">退出</a>
            </div>
        </header>

        <div class="admin-content">
            <!-- 统计卡片 -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card stat-blue">
                    <div class="stat-num" id="letters_total">-</div>
                    <div class="stat-label">信件总数</div>
                </div>
                <div class="stat-card stat-orange">
                    <div class="stat-num" id="letters_pending">-</div>
                    <div class="stat-label">待发送</div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-num" id="letters_sent">-</div>
                    <div class="stat-label">已发送</div>
                </div>
                <div class="stat-card stat-red">
                    <div class="stat-num" id="letters_failed">-</div>
                    <div class="stat-label">发送失败</div>
                </div>
                <div class="stat-card stat-purple">
                    <div class="stat-num" id="users_total">-</div>
                    <div class="stat-label">用户数</div>
                </div>
                <div class="stat-card stat-cyan">
                    <div class="stat-num" id="today_created">-</div>
                    <div class="stat-label">今日新建</div>
                </div>
                <div class="stat-card stat-pink">
                    <div class="stat-num" id="today_sent">-</div>
                    <div class="stat-label">今日发送</div>
                </div>
                <div class="stat-card stat-gray">
                    <div class="stat-num" id="attachments_size_fmt">-</div>
                    <div class="stat-label">附件总大小</div>
                </div>
            </div>

            <!-- 趋势 -->
            <div class="card">
                <h3 style="display:flex;align-items:center;gap:8px;"><span style="width:18px;height:18px;display:inline-flex;"><?= \App\Icons::get('trend') ?></span>最近 7 天信件创建趋势</h3>
                <div id="trendChart" class="trend-chart"></div>
            </div>

            <!-- 最近信件 -->
            <div class="card">
                <div class="card-header">
                    <h3 style="display:flex;align-items:center;gap:8px;"><span style="width:18px;height:18px;display:inline-flex;"><?= \App\Icons::get('history') ?></span>最近信件</h3>
                    <a href="/admin/letters" class="btn-link">查看全部 →</a>
                </div>
                <table class="table">
                    <thead>
                        <tr><th>ID</th><th>标题</th><th>收件人</th><th>状态</th><th>投递时间</th><th>创建时间</th></tr>
                    </thead>
                    <tbody id="recentTable"></tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div id="toast"></div>
<script src="/assets/js/app.js"></script>
<script src="/assets/js/admin.js"></script>
<script>
    const statusMap = {0:['待发送','badge-gray'],1:['已发送','badge-green'],2:['失败','badge-red'],3:['已撤回','badge-orange']};

    (async () => {
        const data = await api('/admin/api/dashboard');
        if (data.code !== 0) return toast(data.msg, true);

        const s = data.data.stats;
        Object.keys(s).forEach(k => {
            const el = document.getElementById(k);
            if (el) el.textContent = s[k];
        });
        // 附件大小格式化
        const size = s.attachments_size || 0;
        document.getElementById('attachments_size_fmt').textContent =
            size > 1073741824 ? (size/1073741824).toFixed(2)+'GB' :
            size > 1048576 ? (size/1048576).toFixed(2)+'MB' :
            (size/1024).toFixed(2)+'KB';

        // 趋势图
        const trend = data.data.trend;
        const max = Math.max(1, ...trend.map(t => t.c));
        document.getElementById('trendChart').innerHTML = trend.map(t => `
            <div class="trend-bar">
                <div class="bar" style="height:${(t.c/max*100)}%" title="${t.c} 封"></div>
                <div class="bar-label">${t.c}</div>
                <div class="bar-date">${t.d.slice(5)}</div>
            </div>
        `).join('');

        // 最近信件
        document.getElementById('recentTable').innerHTML = data.data.recent.map(r => `
            <tr>
                <td>${r.id}</td>
                <td><a href="/admin/letters?id=${r.id}">${escapeHtml(r.title)}</a></td>
                <td>${escapeHtml(r.recipient_name)}</td>
                <td><span class="badge ${statusMap[r.status][1]}">${statusMap[r.status][0]}</span></td>
                <td>${r.send_time}</td>
                <td>${r.created_at}</td>
            </tr>
        `).join('') || '<tr><td colspan="6" class="empty">暂无数据</td></tr>';
    })();

    function escapeHtml(s) { const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
</script>
</body>
</html>
