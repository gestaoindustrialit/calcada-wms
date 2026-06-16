<?php
$isEdit = !empty($edit);
$canManageRequests = !empty($canManageRequests);
$requesterValue = $canManageRequests ? ($edit['requester'] ?? $currentUser['name'] ?? '') : ($currentUser['name'] ?? '');
$teamValue = $canManageRequests ? ($edit['team'] ?? $currentUser['team'] ?? '') : ($currentUser['team'] ?? '');
$articleRows = $isEdit ? [[
    'item_id' => $edit['item_id'] ?? '',
    'warehouse_id' => $edit['warehouse_id'] ?? '',
    'quantity' => $edit['quantity'] ?? '',
    'notes' => $edit['notes'] ?? '',
]] : [[
    'item_id' => '',
    'warehouse_id' => '',
    'quantity' => '',
    'notes' => '',
]];
?>
<div class="page-head"><div><span class="eyebrow">Fluxo de armazém</span><h1>Requisição a armazém</h1><p>Pesquise artigos rapidamente, edite pedidos e faça entregas parciais até concluir.</p></div></div>
<form method="post" action="<?= \App\Core\Url::page('request_save') ?>" class="quick-form mb-4" data-smart-form data-request-form>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <label class="form-label">Requisitante</label>
            <input class="form-control" name="requester" placeholder="Requisitante" value="<?= htmlspecialchars($requesterValue) ?>" <?= $canManageRequests ? '' : 'readonly' ?> required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Equipa</label>
            <input class="form-control" name="team" placeholder="Equipa" value="<?= htmlspecialchars($teamValue) ?>" <?= $canManageRequests ? '' : 'readonly' ?> required>
        </div>
    </div>
    <div class="request-lines" data-request-lines>
        <?php foreach ($articleRows as $index => $line): ?>
            <div class="row g-3 request-line" data-request-line>
                <div class="col-md-4"><select class="form-select searchable-select" name="items[<?= $index ?>][item_id]" aria-label="Artigo"><?php foreach($items as $i): ?><option value="<?= $i['id'] ?>" <?= (int)($line['item_id'] ?? 0)===(int)$i['id']?'selected':'' ?>><?= htmlspecialchars($i['name'].' — '.$i['designation']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><select class="form-select" name="items[<?= $index ?>][warehouse_id]" aria-label="Armazém"><?php foreach($warehouses as $w): ?><option value="<?= $w['id'] ?>" <?= (int)($line['warehouse_id'] ?? 0)===(int)$w['id']?'selected':'' ?>><?= htmlspecialchars($w['name'].' · '.$w['section']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><input class="form-control" name="items[<?= $index ?>][quantity]" type="number" min="0.01" step="0.01" placeholder="Qtd" value="<?= htmlspecialchars($line['quantity'] ?? '') ?>" required></div>
                <div class="col-md-3"><input class="form-control" name="items[<?= $index ?>][notes]" placeholder="Notas / obra / centro custo" value="<?= htmlspecialchars($line['notes'] ?? '') ?>"></div>
                <div class="col-12 request-line__actions"><button type="button" class="btn btn-outline-danger btn-sm" data-remove-request-line title="Remover linha" aria-label="Remover linha" <?= $isEdit ? 'disabled' : '' ?>><i class="bi bi-dash-lg"></i></button></div>
            </div>
        <?php endforeach; ?>
    </div>
    <input type="hidden" name="status" value="<?= htmlspecialchars($edit['status'] ?? 'Pendente') ?>">
    <div class="request-form-actions">
        <?php if (!$isEdit): ?><button type="button" class="btn btn-light" data-add-request-line title="Adicionar artigo" aria-label="Adicionar artigo"><i class="bi bi-plus-lg"></i></button><?php endif; ?>
        <button class="btn btn-primary" title="Guardar pedido" aria-label="Guardar pedido"><i class="bi bi-send"></i></button>
    </div>
</form>
<?php require dirname(__DIR__).'/partials/requests_table.php'; ?>
