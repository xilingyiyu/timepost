// 后台 JS 助手

// CSRF 防护：所有 fetch 请求自动带 X-Requested-With 头（后端据此识别同源 AJAX）
(function() {
    const origFetch = window.fetch;
    window.fetch = function(input, init) {
        init = init || {};
        init.headers = init.headers || {};
        // headers 可能是 Headers 对象或普通对象
        if (init.headers instanceof Headers) {
            if (!init.headers.has('X-Requested-With')) {
                init.headers.set('X-Requested-With', 'XMLHttpRequest');
            }
        } else {
            if (!init.headers['X-Requested-With']) {
                init.headers['X-Requested-With'] = 'XMLHttpRequest';
            }
        }
        return origFetch.call(this, input, init);
    };
})();

window.escapeHtml = function(s) {
    if (s === null || s === undefined) return '';
    const d = document.createElement('div');
    d.textContent = String(s);
    return d.innerHTML;
};

window.statusMap = {0:['待发送','badge-gray'],1:['已发送','badge-green'],2:['失败','badge-red'],3:['已撤回','badge-orange']};
window.channelMap = {1:'短信',2:'邮件',3:'短信+邮件'};

// 分页渲染
window.renderPagination = function(containerId, page, totalPages, onClick) {
    const el = document.getElementById(containerId);
    if (!el || totalPages <= 1) { if (el) el.innerHTML = ''; return; }
    let html = '';
    html += `<button ${page<=1?'disabled':''} onclick="window.__pageGo(${page-1})">‹</button>`;
    const start = Math.max(1, page-2);
    const end = Math.min(totalPages, page+2);
    for (let i = start; i <= end; i++) {
        html += `<button class="${i===page?'active':''}" onclick="window.__pageGo(${i})">${i}</button>`;
    }
    html += `<button ${page>=totalPages?'disabled':''} onclick="window.__pageGo(${page+1})">›</button>`;
    el.innerHTML = html;
    window.__pageGo = onClick;
};

// 抽屉
window.openDrawer = function(html) {
    let overlay = document.getElementById('drawerOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'drawerOverlay';
        overlay.className = 'drawer-overlay';
        document.body.appendChild(overlay);
        overlay.onclick = (e) => { if (e.target === overlay) overlay.classList.remove('show'); };
    }
    overlay.innerHTML = `<div class="drawer">${html}</div>`;
    overlay.classList.add('show');
};
window.closeDrawer = function() {
    const overlay = document.getElementById('drawerOverlay');
    if (overlay) overlay.classList.remove('show');
};

// 确认对话框
window.confirmAction = function(msg) {
    return window.confirm(msg);
};

// 移动端：注入汉堡按钮 + 侧边栏切换 + 表格滚动包裹
function initAdminMobile() {
    const header = document.querySelector('.admin-header');
    const sidebar = document.querySelector('.admin-sidebar');
    if (!header || !sidebar) return;

    // 注入遮罩
    const backdrop = document.createElement('div');
    backdrop.className = 'admin-backdrop';
    document.body.appendChild(backdrop);

    // 注入汉堡按钮到 header 最前
    const toggle = document.createElement('button');
    toggle.className = 'admin-toggle';
    toggle.setAttribute('aria-label', '菜单');
    toggle.innerHTML = '<span></span><span></span><span></span>';
    header.insertBefore(toggle, header.firstChild);

    function closeSidebar() {
        sidebar.classList.remove('show');
        backdrop.classList.remove('show');
        toggle.classList.remove('active');
    }
    toggle.addEventListener('click', function() {
        const open = sidebar.classList.toggle('show');
        backdrop.classList.toggle('show', open);
        this.classList.toggle('active', open);
    });
    backdrop.addEventListener('click', closeSidebar);
    // 点击菜单项后收起
    sidebar.querySelectorAll('a').forEach(a => a.addEventListener('click', closeSidebar));

    // 自动为表格包裹滚动容器
    document.querySelectorAll('table.table').forEach(table => {
        if (table.parentElement.classList.contains('table-wrap')) return;
        const wrap = document.createElement('div');
        wrap.className = 'table-wrap';
        table.parentNode.insertBefore(wrap, table);
        wrap.appendChild(table);
    });
}

document.addEventListener('DOMContentLoaded', initAdminMobile);
