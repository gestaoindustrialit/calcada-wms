<h1>Requisição a armazém</h1>
<p class="text-muted">Registe pedidos, aprove, entregue e abata automaticamente o stock do armazém selecionado.</p>
<form method="post" action="<?= \App\Core\Url::page('request_save') ?>" class="row g-2 quick-form mb-4">
    <div class="col-md-2"><input class="form-control" name="requester" placeholder="Requisitante" required></div>
    <div class="col-md-2"><input class="form-control" name="team" placeholder="Equipa" required></div>
    <div class="col-md-2"><select class="form-select" name="item_id"><?php foreach($items as $i): ?><option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><select class="form-select" name="warehouse_id"><?php foreach($warehouses as $w): ?><option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name'].' · '.$w['section']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-1"><input class="form-control" name="quantity" type="number" step="0.01" placeholder="Qtd" required></div>
    <div class="col-md-2"><input class="form-control" name="notes" placeholder="Notas"></div>
    <input type="hidden" name="status" value="Pendente">
    <div class="col-md-1"><button class="btn btn-primary w-100">Pedir</button></div>
</form>
<?php require dirname(__DIR__).'/partials/requests_table.php'; ?>
