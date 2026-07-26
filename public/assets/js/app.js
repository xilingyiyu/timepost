// 时光邮局 前端公共脚本

// 用户图标 SVG（用于导航栏登录态）
const ICON_USER_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';

// Toast 助手
function toast(msg, isError = false) {
    const el = document.getElementById('toast');
    if (!el) { alert(msg); return; }
    el.textContent = msg;
    el.className = 'show' + (isError ? ' error' : '');
    clearTimeout(window.__toastT);
    window.__toastT = setTimeout(() => el.className = '', 2500);
}

// 导航栏登录态更新：已登录时把"登录"链接替换为"用户中心"入口
function updateNavbar() {
    const token = localStorage.getItem('token');
    const loginLink = document.querySelector('nav.navbar a[href="/login"]');
    if (!loginLink) return;
    if (token) {
        let user = {};
        try { user = JSON.parse(localStorage.getItem('user') || '{}'); } catch (e) {}
        const name = user.nickname || '用户中心';
        loginLink.href = '/settings';
        loginLink.innerHTML = '<span style="width:16px;height:16px;display:inline-flex;vertical-align:-3px;margin-right:4px;">' + ICON_USER_SVG + '</span>' + name;
        loginLink.style.fontWeight = '600';
    }
}

// 带鉴权的 fetch
async function api(url, options = {}) {
    const token = localStorage.getItem('token');
    if (token) {
        options.headers = options.headers || {};
        options.headers['Authorization'] = 'Bearer ' + token;
    }
    const res = await fetch(url, options);
    if (res.status === 401) {
        toast('登录已过期，请重新登录', true);
        setTimeout(() => location.href = '/login', 1000);
        throw new Error('unauthorized');
    }
    return res.json();
}

// 全局未捕获错误提示
window.addEventListener('unhandledrejection', (e) => {
    console.error(e);
    if (e.reason && e.reason.message !== 'unauthorized') {
        toast('操作失败，请重试', true);
    }
});

// 移动端汉堡菜单切换
function initNavToggle() {
    const toggle = document.querySelector('.nav-toggle');
    const links = document.querySelector('.nav-links');
    if (!toggle || !links) return;
    toggle.addEventListener('click', function() {
        this.classList.toggle('active');
        links.classList.toggle('show');
    });
    // 点击链接后自动收起菜单
    links.querySelectorAll('a').forEach(a => {
        a.addEventListener('click', () => {
            toggle.classList.remove('active');
            links.classList.remove('show');
        });
    });
}

// DOM 加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    updateNavbar();
    initNavToggle();
});
