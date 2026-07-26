<?php /** @var array $letter * @var array $attachments * @var string $siteName */
$channelText = [1=>'短信', 2=>'邮件', 3=>'短信+邮件'][$letter['deliver_channel']] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($letter['title']) ?> · <?= htmlspecialchars($siteName) ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect x='2' y='4' width='20' height='16' rx='2'/><path d='m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7'/><path d='M9.5 10.5a2.5 2.5 0 0 1 5 0c0 1.5-2.5 3-2.5 3s-2.5-1.5-2.5-3Z'/></svg>">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        .verify-mask {
            position: fixed; inset: 0; z-index: 999;
            background: rgba(255,255,255,0.95); backdrop-filter: blur(20px);
            display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .verify-box {
            background: #fff; border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg); padding: 40px 36px; max-width: 420px; width: 100%;
            border: 1px solid var(--border-soft); text-align: center;
        }
        .verify-box .ic { width:56px; height:56px; margin:0 auto 16px; color:var(--primary); }
        .verify-box .ic svg { width:100%; height:100%; }
        .verify-box h2 { font-family: 'Plus Jakarta Sans','Inter',sans-serif; font-size: 22px; font-weight: 800; margin-bottom: 8px; letter-spacing: -0.02em; }
        .verify-box p { color: var(--text-light); font-size: 14px; margin-bottom: 24px; }
        .verify-q { background: var(--bg-soft); padding: 14px 16px; border-radius: var(--radius); font-size: 14px; color: var(--text); margin-bottom: 16px; font-weight: 500; }
        .verify-input { width: 100%; padding: 12px 16px; font-size: 15px; border: 1.5px solid var(--border); border-radius: var(--radius); text-align: center; transition: var(--transition); }
        .verify-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-ring); }
        .verify-btn { width: 100%; padding: 14px; margin-top: 14px; border-radius: var(--radius); background: var(--primary); color: #fff; font-weight: 700; font-size: 15px; box-shadow: var(--shadow-primary); transition: var(--transition); }
        .verify-btn:hover { background: var(--primary-dark); }
        .verify-btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .lock-content { filter: blur(8px); pointer-events: none; user-select: none; }
        .view-meta-ic { width:16px; height:16px; display:inline-flex; vertical-align:-3px; margin-right:4px; color:var(--text-light); }
        .view-meta-ic svg { width:100%; height:100%; }
        .view-attach-video { width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:var(--primary-light); color:var(--primary); }
        .view-attach-video svg { width:32px; height:32px; }
    
/* 附件预览 Lightbox */
.lb-mask {
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,0.92);
    align-items:center; justify-content:center;
}
.lb-mask.show { display:flex; }
.lb-mask img { max-width:92vw; max-height:92vh; border-radius:12px; object-fit:contain; }
.lb-mask video { max-width:92vw; max-height:92vh; border-radius:12px; }
.lb-close {
    position:absolute; top:20px; right:24px;
    width:40px; height:40px; border-radius:50%;
    background:rgba(255,255,255,0.15); color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:24px; cursor:pointer; transition:background 0.2s;
}
.lb-close:hover { background:rgba(255,255,255,0.3); }

</style>
</head>
<body>
<nav class="navbar">
    <div class="navbar-inner">
        <a href="/" class="logo"><span class="logo-icon"><?= \App\Icons::get('heart_mail') ?></span><span><?= htmlspecialchars($siteName) ?></span></a>
        <button class="nav-toggle" aria-label="菜单"><span></span><span></span><span></span></button>
        <div class="nav-links"><a href="/">首页</a><a href="/write" class="btn-primary">我也写一封</a></div>
    </div>
</nav>

<div class="view-wrap">
    <div class="view-card" id="letterCard">
        <div class="view-meta">
            <span><span class="view-meta-ic"><?= \App\Icons::get('incoming') ?></span>致 <strong><?= htmlspecialchars($letter['recipient_name']) ?></strong></span>
            <span>·</span>
            <span><?= $channelText ?>送达</span>
            <?php if ($letter['sent_at']): ?>
            <span>·</span>
            <span><?= date('Y-m-d H:i', strtotime($letter['sent_at'])) ?></span>
            <?php endif; ?>
        </div>

        <h1 class="view-title"><?= htmlspecialchars($letter['title']) ?></h1>

        <div class="view-content"><?= htmlspecialchars($letter['content']) ?></div>

        <?php if (!empty($attachments)): ?>
            <div style="margin-bottom:24px;">
                <h3 style="font-size:15px;font-weight:600;margin-bottom:12px;display:flex;align-items:center;gap:6px;"><span style="width:16px;height:16px;display:inline-flex;color:var(--text-secondary);"><?= \App\Icons::get('paperclip') ?></span>附件（<?= count($attachments) ?>）</h3>
                <div class="view-attach-list">
                    <?php foreach ($attachments as $att): ?>
                        <a href="<?= htmlspecialchars($att['share_url']) ?>" target="_blank" class="view-attach-item">
                            <?php if ($att['file_type'] === 'image'): ?>
                                <img src="<?= htmlspecialchars($att['share_url']) ?>" alt="<?= htmlspecialchars($att['file_name']) ?>" loading="lazy" onclick="event.preventDefault();showLightbox('<?= htmlspecialchars($att['share_url'], ENT_QUOTES) ?>')">
                            <?php else: ?>
                                <div class="view-attach-video"><?= \App\Icons::get('film') ?></div>
                            <?php endif; ?>
                            <div class="label"><?= htmlspecialchars($att['file_name']) ?> · <?= round($att['file_size']/1024/1024, 2) ?>MB</div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div style="text-align:center;padding:20px 0 0;border-top:1px dashed var(--border);color:var(--text-light);font-size:13px;">
            这是一封来自 <?= date('Y-m-d', strtotime($letter['created_at'])) ?> 的时光之信
        </div>
    </div>

    <div style="text-align:center;margin-top:24px;">
        <a href="/write" class="btn-lg btn-primary" style="display:inline-flex;align-items:center;gap:8px;color:#fff;"><span style="width:18px;height:18px;display:inline-flex;"><?= \App\Icons::get('pen') ?></span>我也写一封时光之信</a>
    </div>

    <!-- 回复对话区 -->
    <div class="view-card" style="margin-top:20px;">
        <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <span style="width:18px;height:18px;display:inline-flex;color:var(--primary);"><?= \App\Icons::get('sparkles') ?></span>
            回复寄件人
            <span id="replyCount" style="font-size:12px;color:var(--text-light);font-weight:400;"></span>
        </h3>
        <div id="replies" style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px;"></div>
        <div class="reply-form" style="border-top:1px dashed var(--border);padding-top:16px;">
            <input type="text" id="replyAuthor" placeholder="你的署名（可选，默认收件人称呼）" maxlength="20" style="width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:14px;margin-bottom:10px;">
            <textarea id="replyContent" placeholder="想对寄件人说些什么？" maxlength="2000" style="width:100%;padding:12px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:14px;min-height:80px;resize:vertical;font-family:inherit;"></textarea>
            <button id="replyBtn" style="margin-top:10px;padding:10px 22px;background:var(--primary);color:#fff;border-radius:var(--radius-sm);font-weight:600;font-size:14px;">发送回复</button>
        </div>
    </div>
</div>

<!-- 密保验证遮罩 -->
<div id="verifyMask" class="verify-mask" style="display:none;">
    <div class="verify-box">
        <div class="ic"><?= \App\Icons::get('shield') ?></div>
        <h2>身份核验</h2>
        <p>这是一封私密信件，请回答寄件人设置的密保问题</p>
        <div class="verify-q" id="verifyQuestion">加载中...</div>
        <input type="text" id="verifyAnswer" class="verify-input" placeholder="请输入答案（不区分大小写）">
        <button id="verifyBtn" class="verify-btn">查看信件</button>
    </div>
</div>


<!-- Lightbox -->
<div class="lb-mask" id="lightbox" onclick="closeLightbox()">
    <div class="lb-close" onclick="closeLightbox()">&times;</div>
    <div id="lightboxContent"></div>
</div>

<div id="toast"></div>
<script src="/assets/js/app.js"></script>
<script>
const LETTER_ID = <?= (int)$letter['id'] ?>;
const VIEW_TOKEN = '<?= htmlspecialchars($letter['view_token']) ?>';
const RECIPIENT_NAME = <?= json_encode($letter['recipient_name']) ?>;
const card = document.getElementById('letterCard');

// 简易 HTML 转义
function escapeHtml(s) {
    if (s == null) return '';
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// ===== 回复对话 =====
const repliesEl = document.getElementById('replies');
const replyCountEl = document.getElementById('replyCount');

async function loadReplies() {
    try {
        const res = await fetch('/v/' + VIEW_TOKEN + '/replies').then(r => r.json());
        if (res.code !== 0) return;
        const list = res.data.list || [];
        replyCountEl.textContent = list.length ? `(${list.length} 条)` : '';
        repliesEl.innerHTML = list.map(r => {
            const isSender = r.from_role === 'sender';
            const bubbleStyle = isSender
                ? 'background:var(--primary-light);border-color:var(--primary-light);align-self:flex-end;'
                : 'background:#fff;align-self:flex-start;';
            const tagColor = isSender ? 'var(--primary)' : 'var(--text-light)';
            return `<div style="max-width:80%;padding:10px 14px;border:1px solid var(--border-soft);border-radius:var(--radius);${bubbleStyle}">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;font-size:12px;color:${tagColor};">
                    <strong>${escapeHtml(r.author_name)}</strong>
                    <span style="color:var(--text-muted);">${isSender ? '· 寄件人' : '· 收件人'}</span>
                </div>
                <div style="font-size:14px;line-height:1.6;color:var(--text);white-space:pre-wrap;">${escapeHtml(r.content)}</div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">${r.created_at}</div>
            </div>`;
        }).join('') || '<div style="text-align:center;color:var(--text-muted);font-size:13px;padding:16px;">还没有回复，成为第一个回复的人吧</div>';
    } catch (e) {}
}

document.getElementById('replyBtn').addEventListener('click', async () => {
    const author = document.getElementById('replyAuthor').value.trim();
    const content = document.getElementById('replyContent').value.trim();
    if (!content) return toast('请输入回复内容', true);
    const btn = document.getElementById('replyBtn');
    btn.disabled = true; btn.textContent = '发送中...';
    try {
        const res = await fetch('/v/' + VIEW_TOKEN + '/reply', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({author, content})
        }).then(r => r.json());
        if (res.code === 0) {
            document.getElementById('replyContent').value = '';
            toast('回复已送达给寄件人');
            loadReplies();
        } else {
            toast(res.msg || '发送失败', true);
        }
    } catch (e) {
        toast('网络错误', true);
    } finally {
        btn.disabled = false; btn.textContent = '发送回复';
    }
});

loadReplies();

// 先检查寄件人是否设置了密保问题
fetch('/api/letter/' + LETTER_ID + '/security-question', { method: 'GET' })
    .then(r => r.json())
    .then(res => {
        if (res.code === 0 && res.data.has_question) {
            // 设置了密保，先锁定内容并要求验证
            card.classList.add('lock-content');
            document.getElementById('verifyQuestion').textContent = res.data.question;
            document.getElementById('verifyMask').style.display = 'flex';
        }
        // 没设置密保则直接展示（默认就是可见的）
    })
    .catch(() => { /* 接口异常默认可见，不阻断用户 */ });

document.getElementById('verifyBtn').addEventListener('click', () => {
    const answer = document.getElementById('verifyAnswer').value.trim();
    if (!answer) { toast('请输入答案', 'error'); return; }

    const btn = document.getElementById('verifyBtn');
    btn.disabled = true;
    btn.textContent = '验证中...';

    fetch('/api/user/security/verify', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ letter_id: LETTER_ID, answer: answer })
    })
    .then(r => r.json())
    .then(res => {
        if (res.code === 0) {
            card.classList.remove('lock-content');
            document.getElementById('verifyMask').style.display = 'none';
            toast('验证通过');
        } else {
            toast(res.msg || '答案不正确', 'error');
        }
    })
    .catch(() => toast('网络错误', 'error'))
    .finally(() => {
        btn.disabled = false;
        btn.textContent = '查看信件';
    });
});

function showLightbox(url) {
    document.getElementById('lightboxContent').innerHTML = '<img src="' + url + '" onclick="event.stopPropagation()">';
    document.getElementById('lightbox').classList.add('show');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('show');
    document.getElementById('lightboxContent').innerHTML = '';
}

</script>
</body>
</html>
