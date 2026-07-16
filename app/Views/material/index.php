<?php
use App\Core\Url;

$isCompletedView = ($viewMode ?? 'pending') === 'completed';
$canManageMaterial = !empty($canManageMaterial);
$statusOptions = ['A Aguardar', 'Em Produção', 'A Aguardar Material', 'Concluído', 'Cancelado'];
$departmentOptions = ['Desenho técnico 3D', 'Tornearia', 'Desenho técnico 3D e Tornearia'];
?>
<div class="page-head material-head">
    <div>
        <span class="eyebrow">Desenho técnico & Tornearia</span>
        <h1>Material</h1>
        <p>Registe pedidos de utensílios e acompanhe os pendentes da equipa de Tornearia / Desenho Técnico num fluxo semelhante ao SharePoint.</p>
    </div>
    <div class="btn-cluster">
        <a class="btn <?= $isCompletedView ? 'btn-light' : 'btn-dark' ?>" href="<?= Url::page('material') ?>&view=pending"><i class="bi bi-list-task"></i> Pendentes</a>
        <a class="btn <?= $isCompletedView ? 'btn-dark' : 'btn-light' ?>" href="<?= Url::page('material') ?>&view=completed"><i class="bi bi-check2-circle"></i> Concluídos</a>
    </div>
</div>

<?php if (!$isCompletedView): ?>
<form method="post" enctype="multipart/form-data" action="<?= Url::page('material_save') ?>" class="quick-form mb-4 material-form">
    <div class="section-title"><h2><i class="bi bi-plus-circle me-2"></i>Novo pedido</h2><span class="soft-badge">Campos obrigatórios *</span></div>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Responsável *</label><input class="form-control" name="responsible" value="<?= htmlspecialchars($currentUser['name'] ?? '') ?>" required></div>
        <div class="col-md-4"><label class="form-label">Departamento solicitado *</label><select class="form-select" name="department" required><?php foreach($departmentOptions as $department): ?><option value="<?= htmlspecialchars($department) ?>"><?= htmlspecialchars($department) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Data limite de entrega *</label><input class="form-control" name="due_date" type="date" required></div>
        <div class="col-md-4"><label class="form-label">Produto *</label><input class="form-control" name="product" required></div>
        <div class="col-md-4"><label class="form-label">Operação *</label><input class="form-control" name="operation" required></div>
        <div class="col-md-2"><label class="form-label">Quantidade *</label><input class="form-control" name="quantity" type="number" min="0.01" step="0.01" required></div>
        <div class="col-md-2"><label class="form-label">Urgência *</label><select class="form-select" name="urgency" required><?php for($i=1;$i<=5;$i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?></select></div>
        <div class="col-md-8"><label class="form-label">Observações</label><input class="form-control" name="notes" placeholder="Notas, medidas, contexto do pedido..."></div>
        <div class="col-md-4"><label class="form-label">Plano / Dxf / Step</label><input class="form-control" name="attachment" type="file" accept=".doc,.docx,.xls,.xlsx,.ppt,.pptx,.pdf,.jpg,.jpeg,.png,.gif,.mp4,.mp3,.dxf,.step,.stp"></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-send"></i> Submeter pedido</button></div>
</form>
<?php endif; ?>

<div class="data-shell"><div class="table-responsive"><table class="table modern-table align-middle material-table">
<thead><tr><th>Estado</th><th>Criado</th><th>Responsável</th><th>Departamento solicitado</th><th>Produto</th><th>Operação</th><th>Qtd</th><th>Qtd concluída</th><th>Urgência</th><th>Data entrega</th><th>Observações</th><th>Ficheiro</th><?php if($canManageMaterial): ?><th>Ações</th><?php endif; ?></tr></thead>
<tbody>
<?php foreach($rows as $row): ?>
<tr>
<td><span class="badge status-badge material-status"><?= htmlspecialchars($row['status']) ?></span></td><td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['created_at']))) ?></td><td><?= htmlspecialchars($row['responsible']) ?></td><td><span class="pill"><?= htmlspecialchars($row['department']) ?></span></td><td><?= htmlspecialchars($row['product']) ?></td><td><?= htmlspecialchars($row['operation']) ?></td><td><?= htmlspecialchars($row['quantity']) ?></td><td><?= htmlspecialchars($row['completed_quantity']) ?></td><td><?= str_repeat('<i class="bi bi-flag"></i>', (int)$row['urgency']) ?></td><td><?= htmlspecialchars(date('d/m/Y', strtotime($row['due_date']))) ?></td><td><?= htmlspecialchars($row['notes'] ?? '') ?></td><td><?= htmlspecialchars($row['attachment_name'] ?? '') ?></td>
<?php if($canManageMaterial): ?><td><form method="post" action="<?= Url::page('material_status') ?>" class="material-status-form"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><select class="form-select form-select-sm" name="status"><?php foreach($statusOptions as $status): ?><option value="<?= htmlspecialchars($status) ?>" <?= $row['status']===$status?'selected':'' ?>><?= htmlspecialchars($status) ?></option><?php endforeach; ?></select><input class="form-control form-control-sm" name="completed_quantity" type="number" min="0" step="0.01" value="<?= htmlspecialchars($row['completed_quantity']) ?>" title="Quantidade concluída"><button class="btn btn-primary btn-sm" title="Guardar"><i class="bi bi-check-lg"></i></button></form></td><?php endif; ?>
</tr>
<?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="<?= $canManageMaterial ? 13 : 12 ?>" class="text-center text-muted py-5">Sem pedidos nesta vista.</td></tr><?php endif; ?>
</tbody></table></div></div>
