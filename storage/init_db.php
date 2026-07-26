<?php
// 时光邮局 数据库初始化脚本
// 用法: php storage/init_db.php

require_once __DIR__ . '/../config/config.php';

$dbPath = config('db.path');
if (!is_dir(dirname($dbPath))) mkdir(dirname($dbPath), 0755, true);

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 启用 WAL 模式提升并发
$pdo->exec('PRAGMA journal_mode=WAL');
$pdo->exec('PRAGMA foreign_keys=ON');

$sqls = [
    // 用户表
    "CREATE TABLE IF NOT EXISTS users (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        phone        TEXT UNIQUE,
        email        TEXT UNIQUE,
        password     TEXT,
        nickname     TEXT NOT NULL DEFAULT '',
        avatar       TEXT DEFAULT '',
        status       INTEGER NOT NULL DEFAULT 1,        -- 1正常 0禁用
        union_source TEXT NOT NULL DEFAULT 'local',     -- local/oauth
        sec_question TEXT NOT NULL DEFAULT '',          -- 自定义密保问题
        sec_answer   TEXT NOT NULL DEFAULT '',          -- 密保答案哈希(password_hash)
        created_at   TEXT NOT NULL,
        updated_at   TEXT
    )",

    // 第三方账号绑定表（彩虹聚合登录）
    "CREATE TABLE IF NOT EXISTS user_oauths (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id      INTEGER NOT NULL,
        platform     TEXT NOT NULL,   -- wx/qq/alipay/sina/baidu 等
        social_uid   TEXT NOT NULL,
        access_token TEXT,
        nickname     TEXT,
        avatar       TEXT,
        created_at   TEXT NOT NULL,
        updated_at   TEXT,
        UNIQUE(platform, social_uid)
    )",
    "CREATE INDEX IF NOT EXISTS idx_user_oauths_user ON user_oauths(user_id)",
    "CREATE INDEX IF NOT EXISTS idx_user_oauths_puid ON user_oauths(platform, social_uid)",

    // 信件表
    "CREATE TABLE IF NOT EXISTS letters (
        id               INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id          INTEGER NOT NULL,
        title            TEXT NOT NULL,
        content          TEXT NOT NULL,
        deliver_channel  INTEGER NOT NULL,   -- 1短信 2邮件 3短信+邮件
        recipient_name   TEXT NOT NULL DEFAULT '',
        recipient_phone  TEXT,
        recipient_email  TEXT,
        send_time        TEXT NOT NULL,      -- 投递时间 YYYY-MM-DD HH:MM:SS
        status           INTEGER NOT NULL DEFAULT 0,  -- 0待发送 1已发送 2失败 3已撤回
        is_public        INTEGER NOT NULL DEFAULT 0,  -- 0私密 1公开
        audit_status     INTEGER NOT NULL DEFAULT 0,  -- 公开审核：0未审核(默认) 1通过 2拒绝 3无需审核(私密)
        view_token       TEXT NOT NULL,      -- H5 查看凭证
        retry_count      INTEGER NOT NULL DEFAULT 0,
        next_retry_at    TEXT,
        sent_at          TEXT,
        error_msg        TEXT,
        created_at       TEXT NOT NULL,
        updated_at       TEXT
    )",
    "CREATE INDEX IF NOT EXISTS idx_letters_user ON letters(user_id)",
    "CREATE INDEX IF NOT EXISTS idx_letters_status_sendtime ON letters(status, send_time)",
    "CREATE INDEX IF NOT EXISTS idx_letters_view_token ON letters(view_token)",
    "CREATE INDEX IF NOT EXISTS idx_letters_public ON letters(is_public, status)",

    // 附件表（本地存储）
    "CREATE TABLE IF NOT EXISTS letter_attachments (
        id                INTEGER PRIMARY KEY AUTOINCREMENT,
        letter_id         INTEGER NOT NULL DEFAULT 0,  -- 0 表示尚未绑定信件
        user_id           INTEGER NOT NULL,
        cloudreve_path    TEXT NOT NULL,    -- 文件相对路径（兼容旧字段名）
        cloudreve_file_id TEXT,
        file_name         TEXT NOT NULL,
        file_size         INTEGER NOT NULL DEFAULT 0,
        file_type         TEXT NOT NULL,    -- image/video
        mime_type         TEXT,
        share_url         TEXT,             -- 公开/私有分享链
        share_password    TEXT,             -- 私有分享密码
        thumb_url         TEXT,
        duration          INTEGER DEFAULT 0, -- 视频时长(秒)
        width             INTEGER DEFAULT 0,
        height            INTEGER DEFAULT 0,
        created_at        TEXT NOT NULL
    )",
    "CREATE INDEX IF NOT EXISTS idx_attach_letter ON letter_attachments(letter_id)",
    "CREATE INDEX IF NOT EXISTS idx_attach_user ON letter_attachments(user_id)",

    // 发送日志
    "CREATE TABLE IF NOT EXISTS send_logs (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        letter_id    INTEGER NOT NULL,
        channel      INTEGER NOT NULL,  -- 1短信 2邮件
        status       INTEGER NOT NULL,  -- 1成功 0失败
        provider_msg TEXT,              -- 服务商返回的消息ID或错误
        error        TEXT,
        created_at   TEXT NOT NULL
    )",
    "CREATE INDEX IF NOT EXISTS idx_logs_letter ON send_logs(letter_id)",

    // 限流表
    "CREATE TABLE IF NOT EXISTS rate_limits (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        action       TEXT NOT NULL,   -- sms_code / letter_create
        identifier   TEXT NOT NULL,   -- 手机号/IP/用户ID
        created_at   TEXT NOT NULL
    )",
    "CREATE INDEX IF NOT EXISTS idx_rate_action_id_time ON rate_limits(action, identifier, created_at)",

    // 管理员表
    "CREATE TABLE IF NOT EXISTS admins (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        username     TEXT NOT NULL UNIQUE,
        password     TEXT NOT NULL,
        role         TEXT NOT NULL DEFAULT 'admin',
        last_login   TEXT,
        created_at   TEXT NOT NULL
    )",

    // 系统配置表
    "CREATE TABLE IF NOT EXISTS settings (
        skey   TEXT PRIMARY KEY,
        svalue TEXT
    )",

    // 管理员操作审计日志
    "CREATE TABLE IF NOT EXISTS admin_audit_logs (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        admin_id     INTEGER NOT NULL,
        action       TEXT NOT NULL,    -- letter_cancel/letter_delete/letter_resend/user_status/setting_save/...
        target_type  TEXT NOT NULL,    -- letter/user/setting
        target_id    TEXT,
        detail       TEXT,             -- 变更摘要
        ip           TEXT,
        created_at   TEXT NOT NULL
    )",
    "CREATE INDEX IF NOT EXISTS idx_audit_admin ON admin_audit_logs(admin_id)",
    "CREATE INDEX IF NOT EXISTS idx_audit_created ON admin_audit_logs(created_at)",

    // 信件模板（系统内置 + 用户自定义）
    "CREATE TABLE IF NOT EXISTS letter_templates (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id     INTEGER NOT NULL DEFAULT 0,  -- 0=系统内置模板
        scene       TEXT NOT NULL,               -- birthday/anniversary/graduation/newyear/apology/love/future_self/parent/child/farewell
        title       TEXT NOT NULL,
        content     TEXT NOT NULL,
        is_builtin  INTEGER NOT NULL DEFAULT 0,
        created_at  TEXT NOT NULL
    )",
    "CREATE INDEX IF NOT EXISTS idx_tpl_user ON letter_templates(user_id)",

    // 信件回复（收件人回复寄件人，形成双向通信）
    "CREATE TABLE IF NOT EXISTS letter_replies (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        letter_id   INTEGER NOT NULL,
        from_role   TEXT NOT NULL,        -- sender(寄件人) / recipient(收件人)
        author_name TEXT NOT NULL,        -- 回复者署名
        content     TEXT NOT NULL,
        created_at  TEXT NOT NULL
    )",
    "CREATE INDEX IF NOT EXISTS idx_reply_letter ON letter_replies(letter_id)",

    // 邮箱注册验证码表（30 分钟过期）
    "CREATE TABLE IF NOT EXISTS email_verifications (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        email       TEXT NOT NULL,
        token       TEXT NOT NULL,            -- 验证 token
        password    TEXT NOT NULL,            -- 加密后的密码（验证通过后写入用户表）
        nickname    TEXT,
        status      INTEGER NOT NULL DEFAULT 0,  -- 0 待验证 / 1 已验证 / 2 已过期
        expires_at  TEXT NOT NULL,            -- 过期时间
        used_at     TEXT,
        created_at  TEXT NOT NULL
    )",
    "CREATE INDEX IF NOT EXISTS idx_emailver_email ON email_verifications(email)",
    "CREATE INDEX IF NOT EXISTS idx_emailver_token ON email_verifications(token)",
];

foreach ($sqls as $sql) {
    $pdo->exec($sql);
}

// ============ 平滑迁移：给已存在的 users 表补充新字段 ============
$existingCols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
$colNames = array_column($existingCols, 'name');
if (!in_array('sec_question', $colNames)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN sec_question TEXT NOT NULL DEFAULT ''");
}
if (!in_array('sec_answer', $colNames)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN sec_answer TEXT NOT NULL DEFAULT ''");
}

// 初始化管理员（默认 admin / admin123，密码哈希）
$defaultPwd = password_hash('admin123', PASSWORD_DEFAULT);
$pdo->exec("INSERT OR IGNORE INTO admins(username, password, role, created_at) VALUES('admin', '{$defaultPwd}', 'super', '" . date('Y-m-d H:i:s') . "')");

// 初始化系统配置
$defaults = [
    'site_name'      => '时光邮局',
    'site_slogan'    => '写给未来的信 · 让时光替你说话',
    'site_desc'      => '在这里写下你此刻的心情，设定一个未来的时间，我们会在那时把信送到 TA 手里。',
    'sms_sign'       => '时光邮局',
    'max_retry'      => '5',
    'letter_per_day' => '50',
];
foreach ($defaults as $k => $v) {
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO settings(skey, svalue) VALUES(?, ?)");
    $stmt->execute([$k, $v]);
}

// === 内置信件模板种子（仅首次插入）===
$builtinTpls = [
    ['birthday',      '致最亲爱的你：生日快乐',      "生日快乐！\n\n又是一年，时光转了一圈，你我又长大了一岁。\n此刻写下这封信，是想让未来的你看到此刻我对你的祝福与心意。\n\n愿你新的一岁，眼里有光，心中有梦，被这世界温柔以待。\n愿我们一直都在彼此身边，看更多的风景，过更长的日子。\n\nhappy birthday，我亲爱的你。"],
    ['anniversary',   '给我们的纪念日',              "今天的纪念日，我提前把心意存进了时光胶囊。\n\n谢谢你陪我走过这段路，谢谢我们还有那么多明天可以期待。\n未来的那一天读到这封信时，希望我们仍然紧紧牵着彼此的手，\n仍然觉得，遇见你是我最大的幸运。\n\n纪念日快乐，我的爱人。"],
    ['graduation',    '毕业快乐，未来可期',          "毕业快乐！\n\n此刻的你，应该正站在人生的新路口。\n这封信穿越时光找到你，只想告诉你：\n无论未来你走了多远、变成了什么样的人，\n此刻为你骄傲的心情都是真的。\n\n愿你前程似锦，眼里有星辰，脚下有坦途。"],
    ['newyear',       '新年快乐，万事胜意',          "新年快乐！\n\n当这封信抵达时，应该是崭新的日子了。\n愿你过去一年的所有遗憾，都是今年惊喜的铺垫。\n愿你健康、平安、被人爱着，也仍有热爱的勇气。\n\n新的一年，我们继续好好生活。"],
    ['apology',       '对不起，把心里话留给未来',     "对不起。\n\n有些话此刻说不出口，怕争吵、怕误解、怕破坏了此刻的平静。\n所以我把它们写进这封信，等到时间冲淡一切的时候再送达。\n\n希望那时你能读懂我此刻的歉意与珍惜，\n希望我们还有机会，重新好好说话。"],
    ['love',          '一封迟到的心意',              "其实一直想告诉你，只是没能找到合适的时机。\n\n这封信穿过时间找到你，是想让未来的你知道：\n在某个普通的时刻，有个人认真地在想你，认真地喜欢过你。\n\n无论未来我们是什么关系，这份心意都是真实的。"],
    ['future_self',   '给未来的自己',                "嘿，未来的我：\n\n读到这封信时，你过得怎么样？\n此刻的我正在给你写信，心里装着一些小小的期待和一些未解的烦恼。\n\n希望你已经把我想做的事都做了，去过想去的远方，爱过想爱的人。\n如果还没有，也没关系，时间还长，我们慢慢来。\n\n记得对自己好一点。"],
    ['parent',        '写给爸妈的一封信',            "爸、妈：\n\n有些话当面说不出口，所以写进这封信，让时光替我转达。\n\n谢谢你们一直以来的付出和包容。\n我知道自己有时不够懂事，但你们给我的爱，我一直都记得。\n\n愿你们身体健康，慢点老去，等我变得更强，换我来照顾你们。"],
    ['child',         '写给孩子的一封时光信',        "亲爱的宝贝：\n\n当你能读懂这封信的时候，应该已经长大了不少。\n此刻写下这些字，是想让你知道：\n从你来到这个世界的第一天起，你就被深深爱着。\n\n愿你勇敢、善良、有自己的光。\n无论你未来成为什么样的人，家永远是你回来的地方。"],
    ['farewell',      '一封告别的信',                "当你读到这封信时，我们应该已经走散在时光里了。\n\n谢谢你曾出现在我的生命里，给过我那些闪亮的日子。\n这不是一封挽留的信，只是一份迟到的、郑重的告别。\n\n愿你以后的路都顺遂，愿我们各自，都过得很好。"],
];
$tplCheck = (int)$pdo->query("SELECT COUNT(*) c FROM letter_templates WHERE is_builtin=1")->fetch()['c'];
if ($tplCheck === 0) {
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO letter_templates(user_id, scene, title, content, is_builtin, created_at) VALUES(0,?,?,?,?,?)");
    foreach ($builtinTpls as $t) $stmt->execute([$t[0], $t[1], $t[2], 1, $now]);
    echo "已插入 " . count($builtinTpls) . " 个内置信件模板\n";
}

// === 增量迁移：为旧库补充新字段（已存在则跳过）===
$migrations = [
    "letter_attachments.local_url"  => "ALTER TABLE letter_attachments ADD COLUMN local_url TEXT DEFAULT ''",
    "letter_attachments.local_path" => "ALTER TABLE letter_attachments ADD COLUMN local_path TEXT DEFAULT ''",
    'letters.audit_status' => "ALTER TABLE letters ADD COLUMN audit_status INTEGER NOT NULL DEFAULT 0",
];
$cols = array_merge(
    $pdo->query("PRAGMA table_info(letters)")->fetchAll(PDO::FETCH_ASSOC),
    $pdo->query("PRAGMA table_info(letter_attachments)")->fetchAll(PDO::FETCH_ASSOC)
);
$existingCols = array_column($cols, 'name');
foreach ($migrations as $key => $sql) {
    $colName = explode('.', $key)[1];
    if (!in_array($colName, $existingCols)) {
        try { $pdo->exec($sql); echo "迁移: 新增 {$key}\n"; } catch (\Throwable $e) {}
    }
}
// 公开信件免审核：已发送的公开信件自动设为通过（audit_status=1）
$pdo->exec("UPDATE letters SET audit_status=1 WHERE is_public=1 AND status=1 AND audit_status=0");
$pdo->exec("UPDATE letters SET audit_status=3 WHERE is_public=0 AND audit_status=0");

// === 迁移：把旧的远程示范配图 URL 替换为本地路径（兼容旧库）===
$oldUrlCount = (int)$pdo->query("SELECT COUNT(*) c FROM letter_attachments WHERE share_url LIKE '%trae-api-cn.mchost.guru%'")->fetch()['c'];
if ($oldUrlCount > 0) {
    $migrateMap = [
        '%seaside%' => '/assets/img/demo_seaside.jpg',
        '%umbrella%' => '/assets/img/demo_umbrella.jpg',
        '%starry%' => '/assets/img/demo_starry.jpg',
    ];
    foreach ($migrateMap as $pattern => $localUrl) {
        $stmt = $pdo->prepare("UPDATE letter_attachments SET share_url=? WHERE share_url LIKE ?");
        $stmt->execute([$localUrl, '%trae-api-cn.mchost.guru%' . substr($pattern, 1, -1) . '%']);
    }
    // 兜底：所有剩余的远程 URL 统一替换为第一张
    $pdo->exec("UPDATE letter_attachments SET share_url='/assets/img/demo_seaside.jpg' WHERE share_url LIKE '%trae-api-cn.mchost.guru%'");
    echo "迁移: 已将 {$oldUrlCount} 条示范配图替换为本地路径\n";
}

echo "数据库初始化完成: {$dbPath}\n";
echo "默认管理员: admin / admin123（请尽快修改）\n";
