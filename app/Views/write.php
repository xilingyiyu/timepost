<?php /** @var string $siteName */ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>写信 · <?= htmlspecialchars($siteName) ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect x='2' y='4' width='20' height='16' rx='2'/><path d='m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7'/><path d='M9.5 10.5a2.5 2.5 0 0 1 5 0c0 1.5-2.5 3-2.5 3s-2.5-1.5-2.5-3Z'/></svg>">
    <link rel="stylesheet" href="/assets/css/app.css">
<style>
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
.attach-progress { width:0; height:3px; background:linear-gradient(90deg,var(--primary),var(--primary-light)); border-radius:2px; transition:width 0.2s; margin-top:4px; }
.attach-item { cursor:pointer; }
</style>
</head>
<body>
<nav class="navbar">
    <div class="navbar-inner">
        <a href="/" class="logo"><span class="logo-icon"><?= \App\Icons::get('heart_mail') ?></span><span><?= htmlspecialchars($siteName) ?></span></a>
        <button class="nav-toggle" aria-label="菜单"><span></span><span></span><span></span></button>
        <div class="nav-links">
            <a href="/" >首页</a>
            <a href="/login" id="loginLink">登录</a>
            <a href="/write" class="btn-primary">写信</a>
        </div>
    </div>
</nav>

<div class="write-wrap">
    <div class="write-card">
        <h1 style="display:flex;align-items:center;gap:10px;"><span style="width:28px;height:28px;color:var(--primary);display:inline-flex;"><?= \App\Icons::get('pen') ?></span>写一封时光之信</h1>
        <p class="sub">写下此刻的心意，让时光替你送达</p>

        <form id="letterForm">
            <!-- 收件人 -->
            <div class="form-group">
                <label class="form-label">收件人称呼 <span class="req">*</span></label>
                <input type="text" class="form-control" id="recipient_name" placeholder="如：小明 / 未来的自己 / 妈妈" maxlength="20">
            </div>

            <!-- 投递方式 -->
            <div class="form-group">
                <label class="form-label">投递方式 <span class="req">*</span></label>
                <div class="channel-grid">
                    <div class="channel-card" data-channel="1">
                        <div class="ic"><?= \App\Icons::get('phone') ?></div><div class="nm">短信</div>
                    </div>
                    <div class="channel-card" data-channel="2">
                        <div class="ic"><?= \App\Icons::get('mail') ?></div><div class="nm">邮件</div>
                    </div>
                    <div class="channel-card" data-channel="3">
                        <div class="ic"><?= \App\Icons::get('heart_mail') ?></div><div class="nm">短信+邮件</div>
                    </div>
                </div>
            </div>

            <!-- 收件信息 -->
            <div class="form-group" id="phoneGroup">
                <label class="form-label">收件人手机号 <span class="req">*</span></label>
                <input type="tel" class="form-control" id="recipient_phone" placeholder="短信将发送到此号码" maxlength="11">
            </div>
            <div class="form-group" id="emailGroup" style="display:none;">
                <label class="form-label">收件人邮箱 <span class="req">*</span></label>
                <input type="email" class="form-control" id="recipient_email" placeholder="邮件将发送到此邮箱">
            </div>

            <!-- 标题 -->
            <div class="form-group">
                <label class="form-label">信件标题 <span class="req">*</span></label>
                <input type="text" class="form-control" id="title" placeholder="如：给三年后的你" maxlength="50">
            </div>

            <!-- 场景模板 -->
            <div class="form-group">
                <label class="form-label">场景模板（可选）</label>
                <div class="tpl-grid" id="tplGrid">
                    <div class="tpl-card" data-tpl="" data-scene="">
                        <div class="ic"><?= \App\Icons::get('pen') ?></div><div class="nm">空白</div>
                    </div>
                </div>
                <div class="hint" style="margin-top:8px;">选择模板后自动填入标题和内容，可继续编辑。也可<a href="#" id="saveAsTpl" style="color:var(--primary);">把当前内容存为我的模板</a></div>
            </div>

            <!-- 内容 -->
            <div class="form-group">
                <label class="form-label">信件内容 <span class="req">*</span></label>
                <textarea class="form-control" id="content" placeholder="此刻你想说什么？把心里话都写下来吧..." maxlength="5000"></textarea>
            </div>

            <!-- 附件 -->
            <div class="form-group">
                <label class="form-label">附件（图片/视频，可选）</label>
                <div class="upload-zone" id="uploadZone">
                    <div class="ic"><?= \App\Icons::get('paperclip') ?></div>
                    <p>点击或拖拽文件到这里上传</p>
                    <div class="hint">支持图片 (jpg/png/gif/webp) 和视频 (mp4/mov/webm)，单个 ≤5MB</div>
                </div>
                <input type="file" id="fileInput" multiple accept="image/*,video/*" style="display:none;">
                <div class="attach-list" id="attachList"></div>
            </div>

            <!-- 投递时间 -->
            <div class="form-group">
                <label class="form-label">投递时间 <span class="req">*</span></label>
                <input type="datetime-local" class="form-control" id="send_time" min="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>">
            </div>

            <!-- 公开 -->
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                    <input type="checkbox" id="is_public" style="width:18px;height:18px;">
                    <span style="font-size:14px;">允许在信件墙公开展示（投递后）</span>
                </label>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn"><span style="width:20px;height:20px;display:inline-flex;vertical-align:middle;margin-right:6px;"><?= \App\Icons::get('send') ?></span>寄出时光之信</button>
        </form>
    </div>
</div>


<!-- Lightbox 预览 -->
<div class="lb-mask" id="lightbox" onclick="closeLightbox()">
    <div class="lb-close" onclick="closeLightbox()">&times;</div>
    <div id="lightboxContent"></div>
</div>

<div id="toast"></div>
<script src="/assets/js/app.js"></script>
<script>
    // ===== 模板图标 SVG（PHP 端预渲染，避免 JS 模板字符串嵌套）=====
    const ICON_SVG = {
        sparkles: <?= json_encode(\App\Icons::get('sparkles')) ?>,
        heart:    <?= json_encode(\App\Icons::get('heart')) ?>,
    };

    // ===== 登录态检查 =====
    const token = localStorage.getItem('token');
    if (!token) {
        toast('请先登录', true);
        setTimeout(() => location.href = '/login', 1000);
    }

    // ===== 投递方式切换 =====
    let channel = 1;
    const cards = document.querySelectorAll('.channel-card');
    cards[0].classList.add('active');
    function updateChannelFields() {
        document.getElementById('phoneGroup').style.display = (channel & 1) ? 'block' : 'none';
        document.getElementById('emailGroup').style.display = (channel & 2) ? 'block' : 'none';
    }
    cards.forEach(c => c.onclick = () => {
        cards.forEach(x => x.classList.remove('active'));
        c.classList.add('active');
        channel = parseInt(c.dataset.channel);
        updateChannelFields();
    });

    // ===== 场景模板 =====
    const tplGrid = document.getElementById('tplGrid');
    const SCENE_LABEL = {birthday:'生日',anniversary:'纪念日',graduation:'毕业',newyear:'新年',apology:'道歉',love:'表白',future_self:'给未来的自己',parent:'父母',child:'孩子',farewell:'告别',custom:'自定义'};
    (async function loadTemplates() {
        try {
            const data = await api('/api/templates');
            if (data.code !== 0) return;
            const my = [], builtin = [];
            data.data.list.forEach(t => (t.is_builtin ? builtin : my).push(t));
            const renderCard = (t) => {
                const el = document.createElement('div');
                el.className = 'tpl-card' + (t.is_builtin ? '' : ' mine');
                el.dataset.tpl = t.id;
                el.title = t.content.slice(0, 80) + (t.content.length > 80 ? '…' : '');
                el.innerHTML = `<div class="ic" style="${t.is_builtin?'':'color:var(--primary);'}">${t.is_builtin ? ICON_SVG.sparkles : ICON_SVG.heart}</div><div class="nm">${SCENE_LABEL[t.scene]||'模板'}</div>${t.is_builtin?'':'<span class="del" data-id="'+t.id+'" title="删除">×</span>'}`;
                el.onclick = () => {
                    tplGrid.querySelectorAll('.tpl-card').forEach(x => x.classList.remove('active'));
                    el.classList.add('active');
                    document.getElementById('title').value = t.title;
                    document.getElementById('content').value = t.content;
                };
                el.querySelector('.del')?.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    if (!confirm('删除这个模板？')) return;
                    const r = await api('/api/template/' + t.id, {method:'DELETE'});
                    if (r.code === 0) { el.remove(); toast('已删除'); }
                });
                return el;
            };
            builtin.forEach(t => tplGrid.appendChild(renderCard(t)));
            if (my.length) {
                const sep = document.createElement('div');
                sep.style.cssText = 'grid-column:1/-1;font-size:12px;color:var(--text-light);margin:8px 0 4px;';
                sep.textContent = '我的模板';
                tplGrid.appendChild(sep);
                my.forEach(t => tplGrid.appendChild(renderCard(t)));
            }
        } catch (e) {}
    })();
    // 空白卡片
    tplGrid.querySelector('[data-tpl=""]').onclick = () => {
        tplGrid.querySelectorAll('.tpl-card').forEach(x => x.classList.remove('active'));
        tplGrid.querySelector('[data-tpl=""]').classList.add('active');
        document.getElementById('title').value = '';
        document.getElementById('content').value = '';
    };
    // 存为模板
    document.getElementById('saveAsTpl').onclick = async (e) => {
        e.preventDefault();
        const title = document.getElementById('title').value.trim();
        const content = document.getElementById('content').value.trim();
        if (!title || !content) return toast('请先填写标题和内容');
        const r = await api('/api/template', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({scene:'custom', title, content})});
        if (r.code === 0) toast('已存为我的模板，刷新后可见');
    };

    // ===== 附件上传 =====
    const uploadedAttachments = [];  // {id, file_name, file_type, share_url, thumb}
    const fileInput = document.getElementById('fileInput');
    const uploadZone = document.getElementById('uploadZone');
    const attachList = document.getElementById('attachList');

    uploadZone.onclick = () => fileInput.click();
    ['dragover','dragenter'].forEach(ev => uploadZone.addEventListener(ev, e => { e.preventDefault(); uploadZone.classList.add('dragover'); }));
    ['dragleave','drop'].forEach(ev => uploadZone.addEventListener(ev, e => { e.preventDefault(); uploadZone.classList.remove('dragover'); }));
    uploadZone.addEventListener('drop', e => { if (e.dataTransfer.files.length) handleFiles(e.dataTransfer.files); });
    fileInput.onchange = () => { if (fileInput.files.length) handleFiles(fileInput.files); fileInput.value=''; };

    function isImageFile(name) {
        return /\.(jpg|jpeg|png|gif|webp)$/i.test(name);
    }

    async function handleFiles(files) {
        for (const file of files) {
            if (uploadedAttachments.length >= 9) return toast('\u6700\u591a\u4e0a\u4f20 9 \u4e2a\u9644\u4ef6', true);
            const maxSize = isImageFile(file.name) ? 20971520 : 1073741824;
            const label = isImageFile(file.name) ? '20MB' : '1GB';
            if (file.size > maxSize) { toast(file.name + ' \u8d85\u8fc7\u9650\u5236 (' + label + ')', true); continue; }
            await uploadOne(file);
        }
    }

    function uploadOne(file) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', file);

            const placeholder = document.createElement('div');
            placeholder.className = 'attach-item';
            placeholder.innerHTML = '<div style="color:var(--text-light);font-size:12px;">\u4e0a\u4f20\u4e2d 0%</div><div class="attach-progress"></div>';
            attachList.appendChild(placeholder);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/api/attachment/upload');
            xhr.setRequestHeader('Authorization', 'Bearer ' + token);

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    const pct = Math.round(e.loaded / e.total * 100);
                    const label = placeholder.querySelector('div');
                    const bar = placeholder.querySelector('.attach-progress');
                    label.textContent = '\u4e0a\u4f20\u4e2d ' + pct + '%';
                    bar.style.width = pct + '%';
                }
            };

            xhr.onload = () => {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.code !== 0) {
                        placeholder.remove();
                        toast(data.msg || '\u4e0a\u4f20\u5931\u8d25', true);
                        reject(new Error(data.msg || '\u4e0a\u4f20\u5931\u8d25'));
                        return;
                    }
                    uploadedAttachments.push(data.data);
                    const info = data.data;
                    placeholder.innerHTML = '';
                    if (info.file_type === 'image') {
                        placeholder.innerHTML = '<img src="' + info.share_url + '" alt="' + info.file_name + '">';
                        placeholder.onclick = (e) => { e.stopPropagation(); showLightbox(info.share_url, 'image'); };
                    } else {
                        placeholder.classList.add('video');
                        placeholder.onclick = (e) => { e.stopPropagation(); window.open(info.share_url, '_blank'); };
                    }
                    const del = document.createElement('div');
                    del.className = 'del'; del.textContent = '\u00d7';
                    del.onclick = async (e) => {
                        e.stopPropagation();
                        try {
                            await fetch('/api/attachment/' + info.id, {method:'DELETE', headers:{'Authorization':'Bearer '+token}});
                            placeholder.remove();
                            const idx = uploadedAttachments.findIndex(x => x.id === info.id);
                            if (idx >= 0) uploadedAttachments.splice(idx, 1);
                            toast('\u5df2\u5220\u9664');
                        } catch (e) { toast('\u5220\u9664\u5931\u8d25', true); }
                    };
                    placeholder.appendChild(del);
                    resolve();
                } catch (e) {
                    placeholder.remove();
                    toast('\u89e3\u6790\u54cd\u5e94\u5931\u8d25', true);
                    reject(e);
                }
            };

            xhr.onerror = () => {
                placeholder.remove();
                toast('\u7f51\u7edc\u9519\u8bef\uff0c\u4e0a\u4f20\u5931\u8d25', true);
                reject(new Error('\u7f51\u7edc\u9519\u8bef'));
            };

            xhr.send(formData);
        });
    }

    // ===== 提交 =====
    document.getElementById('letterForm').onsubmit = async (e) => {
        e.preventDefault();
        const name = document.getElementById('recipient_name').value.trim();
        const phone = document.getElementById('recipient_phone').value.trim();
        const email = document.getElementById('recipient_email').value.trim();
        const title = document.getElementById('title').value.trim();
        const content = document.getElementById('content').value.trim();
        const sendTimeEl = document.getElementById('send_time');
        const sendTime = sendTimeEl.value ? sendTimeEl.value.replace('T', ' ') + ':00' : '';
        const isPublic = document.getElementById('is_public').checked ? 1 : 0;

        if (!name) return toast('请填写收件人称呼');
        if (channel & 1 && !/^1[3-9]\d{9}$/.test(phone)) return toast('请填写正确的收件人手机号');
        if (channel & 2 && !/^[^@]+@[^@]+\.[^@]+$/.test(email)) return toast('请填写正确的收件人邮箱');
        if (!title) return toast('请填写信件标题');
        if (!content) return toast('请填写信件内容');
        if (!sendTime) return toast('请选择投递时间');
        if (new Date(sendTime.replace(' ', 'T')) <= new Date()) return toast('投递时间必须晚于当前时间');

        const payload = {
            title, content, deliver_channel: channel,
            recipient_name: name, recipient_phone: phone, recipient_email: email,
            send_time: sendTime, is_public: isPublic,
            attachment_ids: uploadedAttachments.map(a => a.id)
        };

        const btn = document.getElementById('submitBtn');
        btn.disabled = true; btn.textContent = '正在寄出...';
        try {
            const res = await fetch('/api/letter', {
                method:'POST', headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.code === 0) {
                toast('信件已成功寄出，将在 ' + sendTime + ' 送达');
                setTimeout(() => location.href = '/', 1500);
            } else if (data.code === 401) {
                toast('登录已过期，请重新登录', true);
                setTimeout(() => location.href = '/login', 1000);
            } else {
                toast(data.msg || '提交失败', true);
            }
        } catch (err) {
            toast('网络错误', true);
        } finally {
            btn.disabled = false; btn.innerHTML = '<span style="width:20px;height:20px;display:inline-flex;vertical-align:middle;margin-right:6px;"><?= \App\Icons::get('send') ?></span>寄出时光之信';
        }
    };

function showLightbox(url, type) {
    const lb = document.getElementById('lightbox');
    const lbc = document.getElementById('lightboxContent');
    if (type === 'image') {
        lbc.innerHTML = `<img src="${url}" onclick="event.stopPropagation()">`;
    } else {
        lbc.innerHTML = `<video src="${url}" controls autoplay onclick="event.stopPropagation()"></video>`;
    }
    lb.classList.add('show');
}
function closeLightbox() {
    const lb = document.getElementById('lightbox');
    lb.classList.remove('show');
    document.getElementById('lightboxContent').innerHTML = '';
}

</script>
</body>
</html>
