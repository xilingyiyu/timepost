<?php
// 后台管理（占位实现，后续完善）

namespace App\Controllers;

use App\Libraries\Database;

class Admin extends BaseController
{
    public function index()
    {
        $this->requireAdmin();
        $this->view('admin/index');
    }

    public function users()
    {
        $this->requireAdmin();
        $this->view('admin/users');
    }

    public function letters()
    {
        $this->requireAdmin();
        $this->view('admin/letters');
    }

    public function settings()
    {
        $this->requireAdmin();
        $this->view('admin/settings');
    }

    public function logs()
    {
        $this->requireAdmin();
        $this->view('admin/logs');
    }

    public function audit()
    {
        $this->requireAdmin();
        $this->view('admin/audit');
    }

    public function loginPage()
    {
        $this->view('admin/login');
    }

    public function login()
    {
        $username = $this->input('username', '');
        $password = $this->input('password', '');
        $admin = Database::one('SELECT * FROM admins WHERE username=?', [$username]);
        if (!$admin || !password_verify($password, $admin['password'])) {
            return $this->fail('账号或密码错误');
        }
        session_start();
        $_SESSION['admin_id'] = $admin['id'];
        Database::update('admins', ['last_login' => date('Y-m-d H:i:s')], 'id=?', [$admin['id']]);
        return $this->ok(['redirect' => '/admin']);
    }

    public function logout()
    {
        session_start();
        session_destroy();
        $this->redirect('/admin/login');
    }

    private function requireAdmin(): void
    {
        session_start();
        if (empty($_SESSION['admin_id'])) $this->redirect('/admin/login');
    }
}
