<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\Database;
use App\Libraries\RateLimiter;

class Attachment extends BaseController
{
    private const MIME_MAP = [
        'jpg'   => ['image/jpeg'],
        'jpeg'  => ['image/jpeg'],
        'png'   => ['image/png'],
        'gif'   => ['image/gif'],
        'webp'  => ['image/webp'],
        'mp4'   => ['video/mp4', 'application/mp4'],
        'mov'   => ['video/quicktime'],
        'avi'   => ['video/x-msvideo'],
        'webm'  => ['video/webm'],
    ];

    /** POST /api/attachment/upload */
    public function upload()
    {
        $uid = $this->requireLogin();

        if (!RateLimiter::hit('attach_upload', (string)$uid, 10, 60)) {
            return $this->fail('上传过于频繁，请稍后再试', 429, 429);
        }

        if (empty($_FILES['file'])) return $this->fail('未上传文件');
        $f = $_FILES['file'];
        if ($f['error'] !== UPLOAD_ERR_OK) return $this->fail('上传失败 code=' . $f['error']);

        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $allowExt = config('attachment.allow_ext');
        if (!in_array($ext, $allowExt)) return $this->fail('不支持的文件类型: ' . $ext);

        $realMime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realMime = finfo_file($finfo, $f['tmp_name']) ?: '';
            finfo_close($finfo);
        }
        $allowedMimes = self::MIME_MAP[$ext] ?? [];
        if ($realMime && $allowedMimes && !in_array($realMime, $allowedMimes, true)) {
            return $this->fail('文件内容与扩展名不匹配（检测到 ' . $realMime . '）');
        }

        $fileType = preg_match('/^(jpg|jpeg|png|gif|webp)$/i', $ext) ? 'image' : 'video';
        $mime = $realMime ?: ($f['type'] ?? '');

        $maxSize = $fileType === 'image' ? 20971520 : 1073741824;
        $sizeLimitText = $fileType === 'image' ? '20MB' : '1GB';
        if ($f['size'] > $maxSize) return $this->fail('文件超过限制 ' . $sizeLimitText);

        $storagePath = config('storage.local_path', '/mnt/timepost-storage');
        $userDir = $storagePath . '/' . $uid;
        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }

        $safeName = 'u' . $uid . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;

        if (!move_uploaded_file($f['tmp_name'], $userDir . '/' . $safeName)) {
            return $this->fail('文件保存失败', 500, 500);
        }

        $localUrl = '/uploads/' . $uid . '/' . $safeName;
        $localPath = $uid . '/' . $safeName;

        $aid = Database::insert('letter_attachments', [
            'letter_id'         => 0,
            'user_id'           => $uid,
            'cloudreve_path'    => $localPath,
            'file_name'         => $f['name'],
            'file_size'         => $f['size'],
            'file_type'         => $fileType,
            'mime_type'         => $mime,
            'share_url'         => $localUrl,
            'local_url'         => $localUrl,
            'local_path'        => $localPath,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);

        return $this->ok([
            'id'        => $aid,
            'file_name' => $f['name'],
            'file_size' => $f['size'],
            'file_type' => $fileType,
            'share_url' => $localUrl,
            'local_url' => $localUrl,
        ]);
    }

    /** DELETE /api/attachment/{id} */
    public function delete(int $id)
    {
        $uid = $this->requireLogin();
        $att = Database::one('SELECT * FROM letter_attachments WHERE id=? AND user_id=?', [$id, $uid]);
        if (!$att) return $this->fail('附件不存在');

        if ($att['letter_id'] > 0) {
            $letter = Database::one('SELECT status FROM letters WHERE id=?', [$att['letter_id']]);
            if ($letter && in_array((int)$letter['status'], [1, 3])) {
                return $this->fail('信件已发送/撤回，附件不可删除');
            }
        }

        $storagePath = config('storage.local_path', '/mnt/timepost-storage');
        $filePath = $storagePath . '/' . ($att['local_path'] ?: $att['cloudreve_path']);
        if ($filePath && file_exists($filePath)) {
            @unlink($filePath);
        }

        Database::q('DELETE FROM letter_attachments WHERE id=?', [$id]);
        return $this->ok(null, '已删除');
    }
}
