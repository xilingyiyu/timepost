<?php /** @var array $publicLetters */ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName ?? '时光邮局') ?> · 写给未来的信</title>
    <meta name="description" content="<?= htmlspecialchars($siteSlogan ?? '让时光替你说话') ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect x='2' y='4' width='20' height='16' rx='2'/><path d='m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7'/><path d='M9.5 10.5a2.5 2.5 0 0 1 5 0c0 1.5-2.5 3-2.5 3s-2.5-1.5-2.5-3Z'/></svg>">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<!-- 顶部导航 -->
<nav class="navbar">
    <div class="navbar-inner">
        <a href="/" class="logo">
            <span class="logo-icon"><?= \App\Icons::get('heart_mail') ?></span>
            <span><?= htmlspecialchars($siteName ?? '时光邮局') ?></span>
        </a>
        <button class="nav-toggle" aria-label="菜单"><span></span><span></span><span></span></button>
        <div class="nav-links">
            <a href="/#features">特色</a>
            <a href="/#how">玩法</a>
            <a href="/#public">信件墙</a>
            <a href="/settings">我的信件</a>
            <a href="/login">登录</a>
            <a href="/write" class="btn-primary">开始写信</a>
        </div>
    </div>
</nav>

<!-- Hero 首屏 -->
<section class="hero">
    <span class="float-deco" style="top:15%; left:8%;"><?= \App\Icons::get('mail') ?></span>
    <span class="float-deco" style="top:25%; right:10%; animation-delay:-2s;"><?= \App\Icons::get('postbox') ?></span>
    <span class="float-deco" style="bottom:20%; left:15%; animation-delay:-4s;"><?= \App\Icons::get('heart_mail') ?></span>
    <span class="float-deco" style="bottom:30%; right:18%; animation-delay:-3s;"><?= \App\Icons::get('hourglass') ?></span>

    <div class="hero-content">
        <span class="hero-badge"><span class="dot"></span>让此刻的心意 · 准时抵达未来</span>
        <h1>
            给未来的 TA，<br>
            写一封关于<span class="typewriter-wrap"><span id="typewriter" class="typewriter-text"></span></span><br>
            的<span class="ink">时光之信</span>
        </h1>
        <p class="hero-sub">
            在这里写下你此刻的心情、祝福、约定或秘密。<br>
            设定一个未来的时间，我们会在那时准时把信送到 TA 手里 —— <br>
            短信、邮件，或附带的照片与视频，让时光替你说话。
        </p>
        <div class="hero-actions">
            <a href="/write" class="btn-lg btn-white">立即写信 →</a>
            <a href="/#public" class="btn-lg btn-ghost">看看大家的信</a>
        </div>
    </div>
</section>

<!-- 特色 -->
<section class="section" id="features">
    <div class="container">
        <div style="text-align:center;">
            <span class="section-eyebrow">Features</span>
        </div>
        <h2 class="section-title">为什么选择时光邮局</h2>
        <p class="section-sub">轻量、温暖、可靠 —— 把每一份心意都妥善保管</p>
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon"><?= \App\Icons::get('clock') ?></div>
                <h3>精准定时投递</h3>
                <p>设定任意未来时间，到点自动通过短信和邮件送达。系统带失败重试机制，重要时刻绝不缺席。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= \App\Icons::get('paperclip') ?></div>
                <h3>图文视频附件</h3>
                <p>支持上传图片和视频一起投递。支持超大文件上传，图片 ≤20MB，视频 ≤1GB，让回忆完整保留。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= \App\Icons::get('lock') ?></div>
                <h3>隐私安全保护</h3>
                <p>收件人通过专属链接查看信件，私密信件需密保验证，确保每一封信只有该看的人能看到。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= \App\Icons::get('rainbow') ?></div>
                <h3>多方式登录</h3>
                <p>支持微信、QQ、支付宝等 21 种第三方账号一键登录，无需繁琐注册，30 秒即可开始写信。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= \App\Icons::get('rocket') ?></div>
                <h3>超轻量部署</h3>
                <p>纯 PHP + SQLite 实现，2C2G 服务器即可承载。无 Redis / MySQL 依赖，月成本仅需 25 元。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= \App\Icons::get('bulb') ?></div>
                <h3>极简操作体验</h3>
                <p>三步完成一封信：选择收件人 → 写下内容 → 设定时间。无论老人孩子都能轻松上手。</p>
            </div>
        </div>
    </div>
</section>

<!-- 玩法 -->
<section class="section" id="how" style="background:var(--bg-soft);">
    <div class="container">
        <div style="text-align:center;">
            <span class="section-eyebrow">How it works</span>
        </div>
        <h2 class="section-title">三步寄出你的时光之信</h2>
        <p class="section-sub">从想法到寄出，只需 3 分钟</p>
        <div class="steps">
            <div class="step">
                <div class="step-num"></div>
                <h4>写下心意</h4>
                <p>记录此刻的心情、祝福或承诺，可附上照片和视频让回忆更立体</p>
            </div>
            <div class="step">
                <div class="step-num"></div>
                <h4>选择时间</h4>
                <p>设定未来的某一天，生日、纪念日、毕业季，或遥远的某年</p>
            </div>
            <div class="step">
                <div class="step-num"></div>
                <h4>静待时光</h4>
                <p>系统会在那个时刻准时投递，让 TA 在对的时间收到你的心意</p>
            </div>
        </div>
        <div style="text-align:center; margin-top:48px;">
            <a href="/write" class="btn-lg btn-primary" style="display:inline-flex; color:#fff;">开始我的第一封信 →</a>
        </div>
    </div>
</section>

<!-- 公开信件墙 -->
<section class="section wall-section" id="public">
    <div class="container">
        <div style="text-align:center;">
            <span class="section-eyebrow">Public Wall</span>
        </div>
        <h2 class="section-title">时光信件墙</h2>
        <p class="section-sub">那些已被时光送达的善意与温暖</p>
        <?php if (empty($publicLetters)): ?>
            <div style="text-align:center; padding:60px 20px; color:var(--text-light);">
                <div class="empty-ic"><?= \App\Icons::get('sprout') ?></div>
                <p>还没有公开的信件，成为第一个让信件上墙的人吧</p>
                <a href="/write" class="btn-lg btn-primary" style="display:inline-flex; margin-top:20px; color:#fff;">写一封</a>
            </div>
        <?php else: ?>
            <div class="wall-stats">
                <div class="wall-stat-item">
                    <span class="num"><?= count($publicLetters) ?></span>
                    <span class="lbl">封公开信件</span>
                </div>
                <div class="wall-stat-divider"></div>
                <div class="wall-stat-item">
                    <span class="num"><?= array_sum(array_map(fn($l) => (int)($l['content_length'] ?? 0), $publicLetters)) ?></span>
                    <span class="lbl">字心意已寄出</span>
                </div>
                <div class="wall-stat-divider"></div>
                <div class="wall-stat-item">
                    <span class="num"><?= count(array_filter($publicLetters, fn($l) => !empty($l['is_featured']))) ?></span>
                    <span class="lbl">封精选佳作</span>
                </div>
            </div>
            <div class="letters-grid">
                <?php foreach ($publicLetters as $idx => $letter): ?>
                    <article class="letter-card<?= !empty($letter['is_featured']) ? ' letter-card-featured' : '' ?>"
                             style="animation-delay: <?= $idx * 60 ?>ms"
                             onclick="location.href='/v/<?= htmlspecialchars($letter['view_token'] ?? '') ?>'">
                        <?php if (!empty($letter['cover_url'])): ?>
                            <div class="letter-card-cover">
                                <img src="<?= htmlspecialchars($letter['cover_url']) ?>" alt="<?= htmlspecialchars($letter['title']) ?>" loading="lazy">
                                <div class="letter-card-cover-overlay"></div>
                                <?php if (!empty($letter['is_featured'])): ?>
                                    <span class="letter-card-badge">
                                        <span class="badge-dot"></span>精选
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="letter-card-body">
                            <div class="letter-card-title"><?= htmlspecialchars($letter['title']) ?></div>
                            <div class="letter-card-excerpt"><?= htmlspecialchars($letter['excerpt'] ?? '') ?></div>
                            <div class="letter-card-meta">
                                <span class="tag">致 <?= htmlspecialchars($letter['recipient_name']) ?></span>
                                <span class="meta-dot">·</span>
                                <span><?= htmlspecialchars(date('Y-m-d', strtotime($letter['sent_at']))) ?></span>
                                <span class="meta-dot">·</span>
                                <span><?= (int)($letter['content_length'] ?? 0) ?> 字</span>
                                <span class="meta-arrow">阅读 →</span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA -->
<section class="section" style="background:var(--accent); color:#fff; text-align:center; padding:100px 24px; position:relative; overflow:hidden;">
    <div style="position:absolute; top:-100px; left:50%; transform:translateX(-50%); width:600px; height:300px; background:radial-gradient(ellipse at center, rgba(59,130,246,0.4) 0%, transparent 60%); pointer-events:none;"></div>
    <div style="position:relative;">
        <h2 style="font-family:'Plus Jakarta Sans','Inter',sans-serif; font-size:clamp(28px,5vw,40px); font-weight:800; margin-bottom:16px; letter-spacing:-0.02em;">准备好写下你的时光之信了吗？</h2>
        <p style="font-size:18px; opacity:0.75; margin-bottom:36px;">让此刻的心意 · 准时抵达未来</p>
        <a href="/write" class="btn-lg btn-primary-lg">立即开始写信 →</a>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="footer-inner">
        <div class="logo">
            <span class="logo-icon"><?= \App\Icons::get('heart_mail') ?></span>
            <span><?= htmlspecialchars($siteName ?? '时光邮局') ?></span>
        </div>
        <div class="footer-links">
            <a href="/#features">特色</a>
            <a href="/#how">玩法</a>
            <a href="/#public">信件墙</a>
            <a href="/write">写信</a>
            <a href="/login">登录</a>
        </div>
        <div class="footer-copy">
            © Yiyu Creation All Rights Reserved.
        </div>
        <div class="footer-filing">
            <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer" class="filing-link filing-icp">
                <img src="https://beian.miit.gov.cn/img/icons/favicon.ico" alt="ICP" class="filing-icn" onerror="this.style.display='none'">
                <span>蜀ICP备2026005661号</span>
            </a>
            <a href="http://www.beian.gov.cn/portal/registerSystemInfo?recordcode=51052402000123" target="_blank" rel="noopener noreferrer" class="filing-link filing-mps">
                <img src="https://www.beian.gov.cn/img/new/gongan.png" alt="公安备案" class="filing-icn" onerror="this.style.display='none'">
                <span>川公网安备 51052402000123号</span>
            </a>
        </div>
    </div>
</footer>

<div id="toast"></div>
<script src="/assets/js/app.js"></script>
<script>
// 关键词打字机效果 - 循环切换关键词
(function() {
    const words = ['心情', '祝福', '约定', '秘密', '思念', '告白', '梦想', '感谢'];
    const el = document.getElementById('typewriter');
    if (!el) return;
    let wordIdx = 0;
    let charIdx = 0;
    let isDeleting = false;

    function tick() {
        const word = words[wordIdx];
        if (isDeleting) {
            charIdx--;
            el.textContent = word.substring(0, charIdx);
            if (charIdx === 0) {
                isDeleting = false;
                wordIdx = (wordIdx + 1) % words.length;
                setTimeout(tick, 300);
                return;
            }
            setTimeout(tick, 60);
        } else {
            charIdx++;
            el.textContent = word.substring(0, charIdx);
            if (charIdx === word.length) {
                isDeleting = true;
                setTimeout(tick, 1600);
                return;
            }
            setTimeout(tick, 130);
        }
    }
    tick();
})();

// 滚动渐入动画
(function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.feature-card, .step, .letter-card, .section-title, .section-sub').forEach(el => {
        el.classList.add('fade-in');
        observer.observe(el);
    });
})();
</script>
</body>
</html>
