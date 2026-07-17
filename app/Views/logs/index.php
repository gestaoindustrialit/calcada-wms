<?php
use App\Core\Url;

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$tableLabels = [
    'users'=>'Utilizadores', 'warehouses'=>'Armazéns', 'warehouse_locations'=>'Localizações', 'items'=>'Artigos',
    'inventory'=>'Stock', 'requests'=>'Pedidos de material', 'material_requests'=>'Pedidos internos', 'purchase_requests'=>'Pedidos de compras',
];
$actionLabels = ['create'=>'Criação', 'update'=>'Alteração', 'delete'=>'Eliminação'];
$fieldLabels = [
    'name'=>'Nome', 'designation'=>'Designação', 'unit'=>'Unidade', 'item_id'=>'Artigo', 'warehouse_id'=>'Armazém', 'location'=>'Localização',
    'quantity'=>'Quantidade', 'min_quantity'=>'Qtd. mínima', 'requester'=>'Requisitante', 'team'=>'Equipa', 'status'=>'Estado',
    'delivered_quantity'=>'Qtd. entregue', 'notes'=>'Notas', 'article_name'=>'Artigo', 'link'=>'Link', 'urgency'=>'Urgência',
    'requester_name'=>'Pedido por', 'requester_team'=>'Equipa', 'responsible'=>'Responsável', 'department'=>'Departamento',
    'product'=>'Produto', 'operation'=>'Operação', 'completed_quantity'=>'Qtd. concluída', 'due_date'=>'Prazo',
];
$hiddenFields = ['password_hash'];
$fmt = function ($value): string {
    if ($value === null || $value === '') return '—';
    if (is_array($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);
    return (string)$value;
};
$summary = function (array $before, array $after, string $table, string $action) use ($fmt): string {
    $data = $after ?: $before;
    if ($table === 'inventory') return trim(($action === 'create' ? 'Entrada/criação de stock' : 'Movimento/ajuste de stock') . ' · Artigo #' . $fmt($data['item_id'] ?? '') . ' · Armazém #' . $fmt($data['warehouse_id'] ?? '') . ' · Qtd ' . $fmt($data['quantity'] ?? ''));
    if ($table === 'items') return 'Artigo: ' . $fmt($data['name'] ?? '') . ' · ' . $fmt($data['designation'] ?? '');
    if ($table === 'requests') return 'Pedido de material de ' . $fmt($data['requester'] ?? '') . ' · Artigo #' . $fmt($data['item_id'] ?? '') . ' · Qtd ' . $fmt($data['quantity'] ?? '');
    if ($table === 'material_requests') return 'Pedido interno: ' . $fmt($data['product'] ?? '') . ' · ' . $fmt($data['operation'] ?? '') . ' · Qtd ' . $fmt($data['quantity'] ?? '');
    if ($table === 'purchase_requests') return 'Pedido de compra: ' . $fmt($data['article_name'] ?? '') . ' · Qtd ' . $fmt($data['quantity'] ?? '') . ' · Estado ' . $fmt($data['status'] ?? '');
    return $fmt($data['name'] ?? $data['code'] ?? ('Registo #' . ($data['id'] ?? '')));
};
?>
<div class="page-head"><div><span class="eyebrow">Auditoria completa</span><h1>Logs de ações</h1><p>Leitura simples de entradas e saídas de stock, artigos, pedidos de material, pedidos internos e compras, com detalhe antes/depois e utilizador responsável.</p></div></div>
<?php if ($flash): ?><div class="alert alert-info"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
<form method="get" class="quick-form mb-4">
    <input type="hidden" name="page" value="logs">
    <div class="section-title"><h2>Filtrar logs</h2><span class="soft-badge"><i class="bi bi-funnel"></i> <?= count($rows) ?> registos</span></div>
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label">Pesquisar</label><input class="form-control" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="artigo, utilizador, estado, nota..."></div>
        <div class="col-md-3"><label class="form-label">Área</label><select class="form-select" name="table_name"><option value="">Todas</option><?php foreach($tables as $table): ?><option value="<?= htmlspecialchars($table) ?>" <?= ($filters['table_name'] ?? '') === $table ? 'selected' : '' ?>><?= htmlspecialchars($tableLabels[$table] ?? $table) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label">Ação</label><select class="form-select" name="action"><option value="">Todas</option><?php foreach($actions as $action): ?><option value="<?= htmlspecialchars($action) ?>" <?= ($filters['action'] ?? '') === $action ? 'selected' : '' ?>><?= htmlspecialchars($actionLabels[$action] ?? $action) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="bi bi-search"></i> Filtrar</button><a class="btn btn-light" href="<?= Url::page('logs') ?>"><i class="bi bi-x-lg"></i></a></div>
    </div>
</form>
<div class="log-timeline">
<?php foreach($rows as $r):
    $before = $r['before_data'] ? (json_decode($r['before_data'], true) ?: []) : [];
    $after = $r['after_data'] ? (json_decode($r['after_data'], true) ?: []) : [];
    $keys = array_values(array_diff(array_unique(array_merge(array_keys($before), array_keys($after))), $hiddenFields));
    if ($r['action'] === 'update') $keys = array_values(array_filter($keys, fn($key) => ($before[$key] ?? null) !== ($after[$key] ?? null)));
?>
    <article class="log-card <?= (int)$r['reverted'] === 1 ? 'is-reverted' : '' ?>">
        <div class="log-card__main">
            <div class="log-icon log-icon--<?= htmlspecialchars($r['action']) ?>"><i class="bi bi-<?= $r['action']==='create'?'plus-lg':($r['action']==='delete'?'trash':'pencil') ?>"></i></div>
            <div class="log-card__content">
                <div class="log-card__top"><span class="pill"><?= htmlspecialchars($tableLabels[$r['table_name']] ?? $r['table_name']) ?></span><strong><?= htmlspecialchars($actionLabels[$r['action']] ?? $r['action']) ?></strong><span class="text-muted">#<?= (int)$r['row_id'] ?></span></div>
                <h3><?= htmlspecialchars($summary($before, $after, (string)$r['table_name'], (string)$r['action'])) ?></h3>
                <p><i class="bi bi-person-circle"></i> <?= htmlspecialchars(trim(($r['user_name'] ?: 'Sistema') . ' ' . ($r['user_role'] ? '(' . $r['user_role'] . ')' : ''))) ?> · <i class="bi bi-clock"></i> <?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($r['created_at']))) ?><?= (int)$r['reverted'] === 1 ? ' · Anulado em ' . htmlspecialchars($r['reverted_at'] ?? '') : '' ?></p>
                <details class="log-details"><summary>Ver todos os detalhes</summary><div class="log-diff-grid"><?php foreach($keys as $key): ?><div class="log-diff-row"><span><?= htmlspecialchars($fieldLabels[$key] ?? $key) ?></span><del><?= htmlspecialchars($fmt($before[$key] ?? null)) ?></del><i class="bi bi-arrow-right"></i><ins><?= htmlspecialchars($fmt($after[$key] ?? null)) ?></ins></div><?php endforeach; ?><?php if(!$keys): ?><p class="text-muted mb-0">Sem campos alterados para apresentar.</p><?php endif; ?></div></details>
            </div>
        </div>
        <div class="log-card__aside">
            <form method="post" action="<?= Url::page('log_action') ?>" class="note-form"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="log_action" value="save_note"><textarea class="form-control form-control-sm" name="note" rows="2" placeholder="Nota do auditor"><?= htmlspecialchars($r['note'] ?? '') ?></textarea><button class="btn btn-sm btn-outline-primary">Guardar nota</button></form>
            <?php if ((int)$r['reverted'] !== 1): ?><form method="post" action="<?= Url::page('log_action') ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="log_action" value="revert"><button class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Anular esta modificação?')"><i class="bi bi-arrow-counterclockwise"></i> Anular</button></form><?php endif; ?>
        </div>
    </article>
<?php endforeach; ?>
<?php if(!$rows): ?><div class="data-shell p-5 text-center text-muted">Sem logs para os filtros selecionados.</div><?php endif; ?>
</div>
