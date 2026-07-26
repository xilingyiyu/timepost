<?php
// 信件模板 API（系统内置 + 用户自定义）

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\Database;

class Template extends BaseController
{
    /** GET /api/templates  获取可用模板（系统内置 + 当前用户自定义） */
    public function list()
    {
        $uid = $this->requireLogin();
        $list = Database::all(
            "SELECT id, scene, title, content, is_builtin, created_at
             FROM letter_templates
             WHERE is_builtin=1 OR user_id=?
             ORDER BY is_builtin DESC, id DESC",
            [$uid]
        );
        return $this->ok(['list' => $list]);
    }

    /** POST /api/template  保存自定义模板 {scene, title, content} */
    public function save()
    {
        $uid = $this->requireLogin();
        $scene = trim($this->input('scene', ''));
        $title = trim($this->input('title', ''));
        $content = trim($this->input('content', ''));
        if (!$title || !$content) return $this->fail('标题和内容不能为空');
        if (mb_strlen($title) > 50) return $this->fail('标题不能超过 50 字');
        if (mb_strlen($content) > 5000) return $this->fail('内容不能超过 5000 字');

        $id = (int)$this->input('id', 0);
        $now = date('Y-m-d H:i:s');
        if ($id > 0) {
            // 更新（仅允许修改自己的自定义模板）
            Database::update('letter_templates',
                ['scene' => $scene, 'title' => $title, 'content' => $content],
                'id=? AND user_id=? AND is_builtin=0', [$id, $uid]
            );
            return $this->ok(['id' => $id], '已更新');
        }
        $newId = Database::insert('letter_templates', [
            'user_id' => $uid, 'scene' => $scene, 'title' => $title,
            'content' => $content, 'is_builtin' => 0, 'created_at' => $now,
        ]);
        return $this->ok(['id' => $newId], '已保存');
    }

    /** DELETE /api/template/{id}  删除自定义模板 */
    public function delete(int $id)
    {
        $uid = $this->requireLogin();
        // 仅允许删除自己的自定义模板
        Database::q('DELETE FROM letter_templates WHERE id=? AND user_id=? AND is_builtin=0', [$id, $uid]);
        return $this->ok(null, '已删除');
    }
}
