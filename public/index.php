<?php
spl_autoload_register(function ($class) {
    $path = dirname(__DIR__) . '/' . str_replace('\\', '/', $class) . '.php';
    $path = str_replace('/App/', '/app/', $path);
    if (file_exists($path)) require $path;
});

use App\Controllers\AppController;

$controller = new AppController();
$page = $_GET['page'] ?? 'dashboard';
$routes = [
    'dashboard'=>fn()=> $controller->dashboard(),
    'users'=>fn()=> $controller->users(),
    'warehouses'=>fn()=> $controller->warehouses(),
    'items'=>fn()=> $controller->items(),
    'inventory'=>fn()=> $controller->inventory(),
    'inventory_save'=>fn()=> $controller->saveInventory(),
    'requests'=>fn()=> $controller->requests(),
    'request_save'=>fn()=> $controller->saveRequest(),
    'reports'=>fn()=> $controller->reports(),
    'export_excel'=>fn()=> $controller->export('excel'),
    'export_pdf'=>fn()=> $controller->export('pdf'),
];
($routes[$page] ?? $routes['dashboard'])();
