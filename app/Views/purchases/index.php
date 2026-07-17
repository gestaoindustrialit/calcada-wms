<?php
use App\Core\Url;

$isCompletedView = ($viewMode ?? 'pending') === 'completed';
$canManagePurchases = !empty($canManagePurchases);
$statusOptions = ['Pendente', 'Aprovado', 'Cancelado', 'Encomendado', 'Entregue'];
?>
<div class="page-head purchases-head">
    <div>
        <span class="eyebrow">Fluxo de compras</span>
        <h1>Compras</h1>
        <p>Registe pedidos de compra com artigo, quantidade, link e urgência, acompanhando o estado até à entrega.</p>
    </div>
    <div class="btn-cluster">
        <a class="btn <?= $isCompletedView ? 'btn-light' : 'btn-dark' ?>" href="<?= Url::page('purchases') ?>&view=pending"><i class="bi bi-list-task"></i> Ativas</a>
        <a class="btn <?= $isCompletedView ? 'btn-dark' : 'btn-light' ?>" href="<?= Url::page('purchases') ?>&view=completed"><i class="bi bi-check2-circle"></i> Fechadas</a>
    </div>
</div>

<?php if (!$isCompletedView): ?>
<form method="post" action="<?= Url::page('purchase_save') ?>" class="quick-form mb-4 purchases-form" data-smart-form>
    <div class="section-title"><h2><i class="bi bi-plus-circle me-2"></i>Novo pedido de compra</h2><span class="soft-badge">Campos obrigatórios *</span></div>
    <div class="row g-3">
        <div class="col-md-5"><label class="form-label">Nome do artigo *</label><input class="form-control" name="article_name" placeholder="Ex.: Sensor, ferramenta, consumível..." required></div>
        <div class="col-md-2"><label class="form-label">Quantidade *</label><input class="form-control" name="quantity" type="number" min="0.01" step="0.01" required></div>
        <div class="col-md-2"><label class="form-label">Urgência *</label><select class="form-select" name="urgency" required><?php for($i=1;$i<=5;$i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?></select></div>
        <div class="col-md-3"><label class="form-label">Link</label><input class="form-control" name="link" type="url" placeholder="https://..."></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-send"></i> Submeter compra</button></div>
</form>
<?php endif; ?>

<div class="data-shell"><div class="table-responsive"><table class="table modern-table align-middle purchases-table">
<thead><tr><th>Estado</th><th>Criado</th><th>Pedido por</th><th>Equipa</th><th>Artigo</th><th>Qtd</th><th>Urgência</th><th>Link</th><?php if($canManagePurchases): ?><th>Ações</th><?php endif; ?></tr></thead>
<tbody>
<?php foreach($rows as $row): ?>
<tr>
<td><span class="badge status-badge status-<?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
<td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['created_at']))) ?></td>
<td><?= htmlspecialchars($row['requester_name'] ?? '') ?></td>
<td><?= htmlspecialchars($row['requester_team'] ?? '') ?></td>
<td><strong><?= htmlspecialchars($row['article_name']) ?></strong></td>
<td><?= htmlspecialchars($row['quantity']) ?></td>
<td><?= str_repeat('<i class="bi bi-flag-fill"></i>', (int)$row['urgency']) ?></td>
<td><?php if(!empty($row['link'])): ?><a href="<?= htmlspecialchars($row['link']) ?>" target="_blank" rel="noopener" class="mini-link">Abrir <i class="bi bi-box-arrow-up-right"></i></a><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
<?php if($canManagePurchases): ?><td><form method="post" action="<?= Url::page('purchase_status') ?>" class="purchase-status-form"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><select class="form-select form-select-sm" name="status"><?php foreach($statusOptions as $status): ?><option value="<?= htmlspecialchars($status) ?>" <?= $row['status']===$status?'selected':'' ?>><?= htmlspecialchars($status) ?></option><?php endforeach; ?></select><button class="btn btn-primary btn-sm" title="Guardar"><i class="bi bi-check-lg"></i></button></form></td><?php endif; ?>
</tr>
<?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="<?= $canManagePurchases ? 9 : 8 ?>" class="text-center text-muted py-5">Sem pedidos nesta vista.</td></tr><?php endif; ?>
</tbody></table></div></div>
