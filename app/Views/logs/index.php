<?php $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); ?>
<div class="page-head"><div><span class="eyebrow">Auditoria</span><h1>Logs de ações</h1><p>Consulte todas as criações, edições e eliminações. Pode adicionar notas ao log ou anular uma modificação.</p></div></div>
<?php if ($flash): ?><div class="alert alert-info"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
<form method="get" class="quick-form mb-4">
    <input type="hidden" name="page" value="logs">
    <div class="section-title"><h2>Filtrar logs</h2><span class="soft-badge"><i class="bi bi-funnel"></i> <?= count($rows) ?> registos</span></div>
    <div class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label">Tabela</label><select class="form-select" name="table_name"><option value="">Todas</option><?php foreach($tables as $table): ?><option value="<?= htmlspecialchars($table) ?>" <?= ($filters['table_name'] ?? '') === $table ? 'selected' : '' ?>><?= htmlspecialchars($table) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Ação</label><select class="form-select" name="action"><option value="">Todas</option><?php foreach($actions as $action): ?><option value="<?= htmlspecialchars($action) ?>" <?= ($filters['action'] ?? '') === $action ? 'selected' : '' ?>><?= htmlspecialchars($action) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="bi bi-search"></i> Filtrar</button><a class="btn btn-light" href="<?= \App\Core\Url::page('logs') ?>"><i class="bi bi-x-lg"></i></a></div>
    </div>
</form>
<div class="table-responsive data-shell"><table class="table align-middle"><thead><tr><th>Data</th><th>Utilizador</th><th>Tabela</th><th>Ação</th><th>Registo</th><th>Alterações</th><th>Nota</th><th>Ações</th></tr></thead><tbody>
<?php foreach($rows as $r): $before = $r['before_data'] ? json_decode($r['before_data'], true) : []; $after = $r['after_data'] ? json_decode($r['after_data'], true) : []; $keys = array_values(array_unique(array_merge(array_keys($before ?: []), array_keys($after ?: [])))); ?>
<tr class="<?= (int)$r['reverted'] === 1 ? 'table-secondary' : '' ?>">
    <td><small><?= htmlspecialchars($r['created_at']) ?></small></td>
    <td><?= htmlspecialchars(trim(($r['user_name'] ?? 'Sistema') . ' ' . ($r['user_role'] ? '(' . $r['user_role'] . ')' : ''))) ?></td>
    <td><span class="pill"><?= htmlspecialchars($r['table_name']) ?></span></td>
    <td><?= htmlspecialchars($r['action']) ?><?= (int)$r['reverted'] === 1 ? '<br><small class="text-muted">Anulado</small>' : '' ?></td>
    <td>#<?= (int)$r['row_id'] ?></td>
    <td><details><summary>Ver detalhe</summary><div class="small log-diff"><?php foreach($keys as $key): if (in_array($key, ['password_hash'], true)) continue; $old = $before[$key] ?? ''; $new = $after[$key] ?? ''; if ($old === $new && $r['action'] === 'update') continue; ?><div><strong><?= htmlspecialchars($key) ?>:</strong> <span class="text-danger"><?= htmlspecialchars((string)$old) ?></span> → <span class="text-success"><?= htmlspecialchars((string)$new) ?></span></div><?php endforeach; ?></div></details></td>
    <td><form method="post" action="<?= \App\Core\Url::page('log_action') ?>" class="d-flex gap-2"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="log_action" value="save_note"><input class="form-control form-control-sm" name="note" value="<?= htmlspecialchars($r['note'] ?? '') ?>" placeholder="Nota"><button class="btn btn-sm btn-outline-primary">Guardar</button></form></td>
    <td><?php if ((int)$r['reverted'] !== 1): ?><form method="post" action="<?= \App\Core\Url::page('log_action') ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="log_action" value="revert"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Anular esta modificação?')"><i class="bi bi-arrow-counterclockwise"></i> Anular</button></form><?php else: ?><span class="text-muted small"><?= htmlspecialchars($r['reverted_at'] ?? '') ?></span><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
