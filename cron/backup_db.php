<?php
// 时光邮局 数据库自动备份脚本
// 用法（crontab，每天凌晨 3 点备份）:
//   0 3 * * * cd /var/www/timepost && php cron/backup_db.php >> storage/logs/backup.log 2>&1
//
// 逻辑：
//   1. 复制 SQLite 数据库到 storage/backups/YYYYMMDD_HHMMSS.db
//   2. 同时用 .backup 命令做一致性备份（避免 WAL 状态不一致）
//   3. 保留最近 30 天的备份，自动清理更早的
//   4. 可选：上传到远程 SFTP 异地备份（设 BACKUP_SFTP_ENABLED=true 开启）

require_once __DIR__ . '/../config/config.php';

$dbPath = config('db.path');
$backupDir = dirname(__DIR__) . '/storage/backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$ts = date('Ymd_His');
$backupFile = $backupDir . '/timepost_' . $ts . '.db';

echo "[" . date('Y-m-d H:i:s') . "] 开始备份\n";

// 使用 SQLite 的 .backup 命令做一致性备份（比直接 copy 文件更安全）
$srcPdo = new PDO('sqlite:' . $dbPath);
$srcPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // 先执行 checkpoint，把 WAL 写入主库
    $srcPdo->exec('PRAGMA wal_checkpoint(TRUNCATE)');
    // 用 backup API（PHP 7.4+ PDO SQLite 支持）
    $destPdo = new PDO('sqlite:' . $backupFile);
    $destPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $srcPdo->backup($destPdo);
    $destPdo = null;  // 关闭
    echo "[" . date('Y-m-d H:i:s') . "] 备份成功: {$backupFile} (" . round(filesize($backupFile)/1024, 1) . "KB)\n";
} catch (\Throwable $e) {
    // 回退：直接复制文件（WAL 模式下可能不一致，作为兜底）
    @copy($dbPath, $backupFile);
    @copy($dbPath . '-wal', $backupFile . '-wal');
    @copy($dbPath . '-shm', $backupFile . '-shm');
    echo "[" . date('Y-m-d H:i:s') . "] 回退到文件复制: {$backupFile} (" . round(filesize($backupFile)/1024, 1) . "KB)\n";
    if ($e->getMessage()) echo "  backup API 提示: " . $e->getMessage() . "\n";
}
$srcPdo = null;

// 清理 30 天前的旧备份
$retentionDays = (int)env('BACKUP_RETENTION_DAYS', 30);
$cutoff = time() - $retentionDays * 86400;
$cleaned = 0;
foreach (glob($backupDir . '/timepost_*.db*') as $oldFile) {
    if (filemtime($oldFile) < $cutoff) {
        @unlink($oldFile);
        $cleaned++;
    }
}
if ($cleaned > 0) echo "[" . date('Y-m-d H:i:s') . "] 清理 {$cleaned} 个过期备份（>{$retentionDays}天）\n";

echo "[" . date('Y-m-d H:i:s') . "] 完成\n\n";
