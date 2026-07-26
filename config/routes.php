<?php
// 路由表

return [
    // ===== 页面路由 =====
    ['GET',  '/',                         'Home@index'],
    ['GET',  '/write',                    'Home@write'],
    ['GET',  '/login',                    'Home@login'],
    ['GET',  '/letters',                  'Home@letters'],
    ['GET',  '/my-letters',               'Home@myLetters'],
    ['GET',  '/settings',                 'Home@settings'],
    ['GET',  '/v/{token}',                'Home@viewLetter'],     // H5 查看信件
    ['GET',  '/v/{token}/replies',        'Api\\Letter@publicReplies'],  // 收件人获取对话
    ['POST', '/v/{token}/reply',          'Api\\Letter@recipientReply'], // 收件人回复（免登录）
    ['GET',  '/admin',                    'Admin@index'],
    ['GET',  '/admin/login',              'Admin@loginPage'],
    ['POST', '/admin/login',              'Admin@login'],
    ['GET',  '/admin/logout',             'Admin@logout'],
    ['GET',  '/admin/users',              'Admin@users'],
    ['GET',  '/admin/letters',            'Admin@letters'],
    ['GET',  '/admin/settings',           'Admin@settings'],
    ['GET', '/admin/logs',               'Admin@logs'],
    ['GET', '/admin/audit',              'Admin@audit'],

    // ===== 后台 API =====
    ['GET',    '/admin/api/dashboard',           'AdminApi@dashboard'],
    ['GET',    '/admin/api/letters',             'AdminApi@letters'],
    ['GET',    '/admin/api/letter/{id}',         'AdminApi@letterDetail'],
    ['POST',   '/admin/api/letter/{id}/cancel',  'AdminApi@forceCancel'],
    ['POST',   '/admin/api/letter/{id}/resend',  'AdminApi@resendLetter'],
    ['POST',   '/admin/api/letter/{id}/audit',   'AdminApi@auditLetter'],
    ['DELETE', '/admin/api/letter/{id}',         'AdminApi@deleteLetter'],
    ['GET',    '/admin/api/users',               'AdminApi@users'],
    ['POST',   '/admin/api/user/{id}/status',    'AdminApi@userStatus'],
    ['POST',   '/admin/api/user/{id}/password',  'AdminApi@resetUserPassword'],
    ['GET',    '/admin/api/settings',            'AdminApi@getSettings'],
    ['POST',   '/admin/api/settings',            'AdminApi@saveSettings'],
    ['GET',    '/admin/api/logs',                'AdminApi@logs'],
    ['GET',    '/admin/api/audit',               'AdminApi@auditList'],
    ['GET',    '/admin/api/export/letters',      'AdminApi@exportLetters'],
    ['GET',    '/admin/api/export/users',        'AdminApi@exportUsers'],
    ['POST',   '/admin/api/password',            'AdminApi@changePassword'],
    ['POST',   '/admin/api/smtp/test',           'AdminApi@testSmtp'],

    // ===== C 端 API =====
    ['POST', '/api/auth/register',             'Api\\Auth@register'],
    ['POST', '/api/auth/register/email',       'Api\\Auth@registerByEmail'],
    ['GET',  '/api/auth/email/verify',         'Api\\Auth@verifyEmail'],
    ['POST', '/api/auth/login',                'Api\\Auth@login'],
    ['POST', '/api/auth/logout',               'Api\\Auth@logout'],
    ['GET',  '/api/user/profile',              'Api\\Auth@profile'],

    // 密保问题
    ['GET',  '/api/user/security',        'Api\\Auth@getSecurity'],
    ['POST', '/api/user/security',        'Api\\Auth@setSecurity'],
    ['POST', '/api/user/security/verify', 'Api\\Auth@verifySecurity'],

    // 个人信息 / 修改密码
    ['POST', '/api/user/profile',         'Api\\Auth@updateProfile'],
    ['POST', '/api/user/password',        'Api\\Auth@changePassword'],

    // 第三方聚合登录
    ['GET',  '/api/auth/oauth/url',       'Api\\OauthLogin@getUrl'],
    ['GET',  '/api/auth/oauth/callback',  'Api\\OauthLogin@callback'],
    ['POST', '/api/auth/oauth/login',     'Api\\OauthLogin@login'],

    // 附件
    ['POST',   '/api/attachment/upload',  'Api\\Attachment@upload'],
    ['DELETE', '/api/attachment/{id}',    'Api\\Attachment@delete'],

    // 信件
    ['POST',   '/api/letter',             'Api\\Letter@create'],
    ['GET',    '/api/letter',             'Api\\Letter@list'],
    ['GET',    '/api/letter/public',      'Api\\Letter@publicList'],
    ['GET',    '/api/letter/{id}',        'Api\\Letter@show'],
    ['POST',   '/api/letter/{id}/cancel', 'Api\\Letter@cancel'],
    ['POST',   '/api/letter/{id}/reply',  'Api\\Letter@reply'],
    ['GET',    '/api/letter/{id}/security-question', 'Api\\Letter@securityQuestion'],

    // 信件模板
    ['GET',    '/api/templates',          'Api\\Template@list'],
    ['POST',   '/api/template',           'Api\\Template@save'],
    ['DELETE', '/api/template/{id}',      'Api\\Template@delete'],
];
