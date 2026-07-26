<?php /** 后台侧边栏 */
$current = $_SERVER['REQUEST_URI'] ?? '';
$menu = [
    ['url' => '/admin',         'icon' => \App\Icons::get('dashboard'), 'name' => '仪表盘'],
    ['url' => '/admin/letters', 'icon' => \App\Icons::get('mail'),      'name' => '信件管理'],
    ['url' => '/admin/users',   'icon' => \App\Icons::get('users'),     'name' => '用户管理'],
    ['url' => '/admin/logs',    'icon' => \App\Icons::get('list'),      'name' => '发送日志'],
    ['url' => '/admin/audit',   'icon' => \App\Icons::get('shield'),    'name' => '审计日志'],
    ['url' => '/admin/settings','icon' => \App\Icons::get('settings'),  'name' => '系统配置'],
];
?>
<aside class="admin-sidebar">
    <div class="sidebar-logo">
        <span class="ic"><?= \App\Icons::get('heart_mail') ?></span>
        <span>时光邮局后台</span>
    </div>
    <ul class="sidebar-menu">
        <?php foreach ($menu as $item):
            $active = $current === $item['url'] || ($item['url'] !== '/admin' && str_starts_with($current, $item['url']));
        ?>
            <li><a href="<?= $item['url'] ?>" class="<?= $active ? 'active' : '' ?>"><span class="ic"><?= $item['icon'] ?></span><span><?= $item['name'] ?></span></a></li>
        <?php endforeach; ?>
    </ul>
</aside>
