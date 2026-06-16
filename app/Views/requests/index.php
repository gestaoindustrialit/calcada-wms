<?php $isEdit = !empty($edit); ?>
<div class="page-head"><div><span class="eyebrow">Fluxo de armazém</span><h1>Requisição a armazém</h1><p>Pesquise artigos rapidamente, edite pedidos e faça entregas parciais até concluir.</p></div></div>
<form method="post" action="<?= \App\Core\Url::page('request_save') ?>" class="row g-3 quick-form mb-4" data-smart-form>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
    <div class="col-md-2"><input class="form-control" name="requester" placeholder="Requisitante" value="<?= htmlspecialchars($edit['requester'] ?? $currentUser['name'] ?? '') ?>" required></div>
    <div class="col-md-2"><input class="form-control" name="team" placeholder="Equipa" value="<?= htmlspecialchars($edit['team'] ?? $currentUser['team'] ?? '') ?>" required></div>
    <div class="col-md-3"><select class="form-select searchable-select" name="item_id"><?php foreach($items as $i): ?><option value="<?= $i['id'] ?>" <?= (int)($edit['item_id'] ?? 0)===(int)$i['id']?'selected':'' ?>><?= htmlspecialchars($i['name'].' — '.$i['designation']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><select class="form-select" name="warehouse_id"><?php foreach($warehouses as $w): ?><option value="<?= $w['id'] ?>" <?= (int)($edit['warehouse_id'] ?? 0)===(int)$w['id']?'selected':'' ?>><?= htmlspecialchars($w['name'].' · '.$w['section']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-1"><input class="form-control" name="quantity" type="number" step="0.01" placeholder="Qtd" value="<?= htmlspecialchars($edit['quantity'] ?? '') ?>" required></div>
    <div class="col-md-2"><input class="form-control" name="notes" placeholder="Notas / obra / centro custo" value="<?= htmlspecialchars($edit['notes'] ?? '') ?>"></div>
    <input type="hidden" name="status" value="<?= htmlspecialchars($edit['status'] ?? 'Pendente') ?>">
    <div class="col-12"><button class="btn btn-primary" title="Guardar pedido" aria-label="Guardar pedido"><i class="bi bi-send"></i></button></div>
</form>
<?php require dirname(__DIR__).'/partials/requests_table.php'; ?>
