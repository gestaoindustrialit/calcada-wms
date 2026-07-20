<?php
namespace App\Core;

class Url
{
    public static function base(): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $dir = rtrim(str_replace('/index.php', '', $script), '/');
        return $dir === '' ? '' : $dir;
    }

    public static function to(string $path = ''): string
    {
        return self::base() . '/' . ltrim($path, '/');
    }

    public static function page(string $page, array $params = []): string
    {
        return self::to('index.php') . '?' . http_build_query(array_merge(['page' => $page], $params));
    }
}
