<?php
namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = dirname(__DIR__) . '/Views/' . $view . '.php';
        require dirname(__DIR__) . '/Views/layout/app.php';
    }
    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}
