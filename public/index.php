<?php
spl_autoload_register(function ($class) {
    $path = dirname(__DIR__) . '/' . str_replace('\\', '/', $class) . '.php';
    $path = str_replace('/App/', '/app/', $path);
    if (file_exists($path)) require $path;
});

use App\Core\Auth;
use App\Core\Url;
use App\Controllers\AppController;

$controller = new AppController();
$page = $_GET['page'] ?? 'dashboard';
$publicRoutes = ['login'];
if (!in_array($page, $publicRoutes, true) && !Auth::check()) {
    header('Location: ' . Url::page('login'));
    exit;
}
$routes = [
    'login'=>fn()=> $controller->login(),
    'logout'=>fn()=> $controller->logout(),
    'dashboard'=>fn()=> $controller->dashboard(),
    'clear_catalog'=>fn()=> $controller->clearCatalog(),
    'users'=>fn()=> $controller->users(),
    'warehouses'=>fn()=> $controller->warehouses(),
    'items'=>fn()=> $controller->items(),
    'inventory'=>fn()=> $controller->inventory(),
    'inventory_save'=>fn()=> $controller->saveInventory(),
    'requests'=>fn()=> $controller->requests(),
    'purchases'=>fn()=> $controller->purchases(),
    'purchase_save'=>fn()=> $controller->savePurchase(),
    'purchase_status'=>fn()=> $controller->purchaseStatus(),
    'purchase_delete'=>fn()=> $controller->deletePurchase(),
    'material'=>fn()=> $controller->material(),
    'material_save'=>fn()=> $controller->saveMaterial(),
    'material_status'=>fn()=> $controller->materialStatus(),
    'request_save'=>fn()=> $controller->saveRequest(),
    'request_action'=>fn()=> $controller->requestAction(),
    'reports'=>fn()=> $controller->reports(),
    'logs'=>fn()=> $controller->logs(),
    'log_action'=>fn()=> $controller->logAction(),
    'export_excel'=>fn()=> $controller->export('excel'),
    'export_pdf'=>fn()=> $controller->export('pdf'),
];
($routes[$page] ?? $routes['dashboard'])();
