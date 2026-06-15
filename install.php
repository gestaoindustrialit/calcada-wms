<?php
/**
 * Instalador do Calçada WMS.
 *
 * Pode ser executado via CLI:
 *   php install.php
 *
 * Ou aberto no navegador antes de iniciar a aplicação:
 *   http://localhost:8000/install.php
 */

declare(strict_types=1);

$root = __DIR__;
$messages = [];
$errors = [];

function addMessage(array &$messages, string $message): void
{
    $messages[] = $message;
}

function addError(array &$errors, string $message): void
{
    $errors[] = $message;
}

function respond(array $messages, array $errors): void
{
    $isCli = PHP_SAPI === 'cli';
    $status = empty($errors) ? 'Instalação concluída' : 'Instalação com erros';

    if ($isCli) {
        echo $status . PHP_EOL . PHP_EOL;
        foreach ($messages as $message) {
            echo '[OK] ' . $message . PHP_EOL;
        }
        foreach ($errors as $error) {
            echo '[ERRO] ' . $error . PHP_EOL;
        }
        exit(empty($errors) ? 0 : 1);
    }

    http_response_code(empty($errors) ? 200 : 500);
    $badge = empty($errors) ? 'success' : 'danger';
    echo '<!doctype html><html lang="pt"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . htmlspecialchars($status) . ' · Calçada WMS</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light">';
    echo '<main class="container py-5"><div class="card shadow-sm"><div class="card-body">';
    echo '<h1><span class="badge text-bg-' . $badge . '">' . htmlspecialchars($status) . '</span></h1>';
    echo '<ul class="list-group my-4">';
    foreach ($messages as $message) {
        echo '<li class="list-group-item list-group-item-success">' . htmlspecialchars($message) . '</li>';
    }
    foreach ($errors as $error) {
        echo '<li class="list-group-item list-group-item-danger">' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul><a class="btn btn-primary" href="/">Abrir aplicação</a></div></div></main></body></html>';
    exit;
}

if (PHP_VERSION_ID < 80200) {
    addError($errors, 'É necessário PHP 8.2 ou superior. Versão atual: ' . PHP_VERSION);
} else {
    addMessage($messages, 'Versão PHP compatível: ' . PHP_VERSION);
}

if (!extension_loaded('pdo_sqlite')) {
    addError($errors, 'A extensão pdo_sqlite não está ativa. Ative-a no PHP antes de instalar.');
} else {
    addMessage($messages, 'Extensão pdo_sqlite ativa.');
}

$dataDir = $root . '/data';
if (!is_dir($dataDir) && !mkdir($dataDir, 0775, true)) {
    addError($errors, 'Não foi possível criar a pasta data/.');
} else {
    addMessage($messages, 'Pasta data/ pronta.');
}

if (!is_writable($dataDir)) {
    addError($errors, 'A pasta data/ não tem permissões de escrita.');
} else {
    addMessage($messages, 'Permissões de escrita confirmadas em data/.');
}

if (!is_file($root . '/database.sql')) {
    addError($errors, 'Ficheiro database.sql não encontrado.');
}

if (empty($errors)) {
    require_once $root . '/app/Core/Database.php';

    try {
        \App\Core\Database::connection();
        addMessage($messages, 'Base de dados SQLite criada/atualizada em data/wms.sqlite.');
        addMessage($messages, 'Dados iniciais verificados. Pode iniciar com: php -S localhost:8000 -t public');
    } catch (Throwable $exception) {
        addError($errors, 'Falha ao criar a base de dados: ' . $exception->getMessage());
    }
}

respond($messages, $errors);
