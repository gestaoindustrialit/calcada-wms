<?php
use App\Core\Url;

$isCompletedView = ($viewMode ?? 'pending') === 'completed';
$isBilledView = ($viewMode ?? 'pending') === 'billed';
$canManageMaterial = !empty($canManageMaterial);
$canEditMaterialDetails = !empty($canEditMaterialDetails);
$canInvoiceMaterial = !empty($canInvoiceMaterial);
$canActOnMaterial = $canManageMaterial || $canEditMaterialDetails || $canInvoiceMaterial;
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
        <a class="btn <?= (!$isCompletedView && !$isBilledView) ? 'btn-dark' : 'btn-light' ?>" href="<?= Url::page('material', ['view'=>'pending']) ?>"><i class="bi bi-list-task"></i> Pendentes</a>
        <a class="btn <?= $isCompletedView ? 'btn-dark' : 'btn-light' ?>" href="<?= Url::page('material', ['view'=>'completed']) ?>"><i class="bi bi-check2-circle"></i> Concluídos</a>
        <a class="btn <?= $isBilledView ? 'btn-dark' : 'btn-light' ?>" href="<?= Url::page('material', ['view'=>'billed']) ?>"><i class="bi bi-receipt"></i> Faturadas</a>
    </div>
</div>

<?php if (!$isCompletedView && !$isBilledView): ?>
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
<thead><tr><th>Estado</th><th>Criado</th><th>Responsável</th><th>Departamento</th><th>Produto</th><th>Operação</th><th>Qtd</th><th>Concl.</th><th>Urg.</th><th>Entrega</th><th>Obs.</th><th>Fich.</th><th>Faturado</th><?php if($canActOnMaterial): ?><th>Editar</th><?php endif; ?></tr></thead>
<tbody>
<?php foreach($rows as $row): ?>
<?php $modalId = 'material-edit-' . (int)$row['id']; ?>
<tr>
<td><span class="badge status-badge material-status"><?= htmlspecialchars($row['status']) ?></span></td><td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['created_at']))) ?></td><td><?= htmlspecialchars($row['responsible']) ?></td><td><span class="pill material-department-pill"><?= htmlspecialchars($row['department']) ?></span></td><td><?= htmlspecialchars($row['product']) ?></td><td><?= htmlspecialchars($row['operation']) ?></td><td><?= htmlspecialchars($row['quantity']) ?></td><td><?= htmlspecialchars($row['completed_quantity']) ?></td><td><span class="material-urgency" aria-label="Urgência <?= (int)$row['urgency'] ?>"><?= str_repeat('<i class="bi bi-flag-fill"></i>', (int)$row['urgency']) ?></span></td><td><?= htmlspecialchars(date('d/m/Y', strtotime($row['due_date']))) ?></td><td class="material-notes-cell"><?= htmlspecialchars($row['notes'] ?? '') ?></td><td class="text-center"><?php if(!empty($row['attachment_path'])): ?><a class="btn btn-outline-primary btn-sm material-download-btn" href="<?= Url::page('material_download') ?>&id=<?= (int)$row['id'] ?>" title="Descarregar <?= htmlspecialchars($row['attachment_name'] ?? 'ficheiro') ?>" aria-label="Descarregar ficheiro"><i class="bi bi-download"></i></a><?php endif; ?></td><td class="text-center"><?php if(!empty($row['billed'])): ?><i class="bi bi-check-circle-fill text-success" title="Faturado"></i><?php endif; ?></td>
<?php if($canActOnMaterial): ?><td><button class="btn btn-primary btn-sm material-edit-btn" type="button" data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>" title="Editar pedido"><i class="bi bi-pencil-square"></i><span>Editar</span></button></td><?php endif; ?>
</tr>
<?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="<?= $canActOnMaterial ? 14 : 13 ?>" class="text-center text-muted py-5">Sem pedidos nesta vista.</td></tr><?php endif; ?>
</tbody></table></div></div>

<?php if($canActOnMaterial): ?>
<?php foreach($rows as $row): ?>
<?php $modalId = 'material-edit-' . (int)$row['id']; ?>
<div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-labelledby="<?= $modalId ?>-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= Url::page('material_status') ?>" class="modal-content material-edit-modal">
            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="<?= $modalId ?>-title">Editar pedido de material</h5>
                    <small><?= htmlspecialchars($row['product']) ?> · <?= htmlspecialchars($row['responsible']) ?></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body material-edit-grid">
                <?php if($canManageMaterial): ?>
                <div><label class="form-label">Estado</label><select class="form-select" name="status"><?php foreach($statusOptions as $status): ?><option value="<?= htmlspecialchars($status) ?>" <?= $row['status']===$status?'selected':'' ?>><?= htmlspecialchars($status) ?></option><?php endforeach; ?></select><small class="text-muted">Ao concluir, a data de entrega passa a ser a data de hoje.</small></div>
                <div><label class="form-label">Qtd Entregue</label><input class="form-control" name="completed_quantity" type="number" min="0" step="0.01" value="<?= htmlspecialchars($row['completed_quantity']) ?>"></div>
                <?php endif; ?>
                <?php if($canEditMaterialDetails): ?>
                <div class="material-edit-grid__full"><label class="form-label">Obs. executantes</label><textarea class="form-control" name="executor_notes" rows="3"><?= htmlspecialchars($row['executor_notes'] ?? '') ?></textarea></div>
                <?php endif; ?>
                <?php if($canInvoiceMaterial): ?>
                <div class="form-check material-modal-check"><input class="form-check-input" type="checkbox" name="billed" value="1" id="<?= $modalId ?>-billed" <?= !empty($row['billed']) ? 'checked' : '' ?>><label class="form-check-label" for="<?= $modalId ?>-billed">Faturado</label></div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <?php if($canManageMaterial): ?><button class="btn btn-outline-danger me-auto" name="material_action" value="delete" onclick="return confirm('Eliminar este pedido de material?')"><i class="bi bi-trash"></i> Eliminar</button><?php endif; ?>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" name="material_action" value="save"><i class="bi bi-check-lg"></i> Guardar alterações</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
