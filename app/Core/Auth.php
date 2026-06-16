<?php
namespace App\Core;

use App\Models\Repository;

class Auth
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    }

    public static function check(): bool
    {
        self::start();
        return !empty($_SESSION['admin_logged_in']);
    }

    public static function user(): array
    {
        self::start();
        return $_SESSION['user'] ?? ['name'=>$_SESSION['admin_username'] ?? 'admin','role'=>'Admin','team'=>'Admin'];
    }

    public static function attempt(string $username, string $password): bool
    {
        self::start();
        $repo = new Repository();
        $user = $repo->userByLogin($username);
        if ($user && !empty($user['password_hash']) && (password_verify($password, $user['password_hash']) || hash_equals($user['password_hash'], $password))) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $user['email'];
            $_SESSION['user'] = ['id'=>(int)$user['id'],'name'=>$user['name'],'email'=>$user['email'],'role'=>$user['role'],'team'=>$user['team']];
            return true;
        }
        $configuredUsername = getenv('WMS_ADMIN_USER') ?: 'admin';
        $configuredPassword = getenv('WMS_ADMIN_PASSWORD') ?: 'admin123';
        if ($username === $configuredUsername && hash_equals($configuredPassword, $password)) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['user'] = ['name'=>'Admin','email'=>$username,'role'=>'Admin','team'=>'Admin'];
            return true;
        }
        return false;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
