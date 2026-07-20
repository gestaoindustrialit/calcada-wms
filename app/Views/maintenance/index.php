<?php
use App\Core\Url;

$isClosedView = ($viewMode ?? 'open') === 'closed';
$canDelegateMaintenance = !empty($canDelegateMaintenance);
$statusOptions = ['Aberto', 'Delegado', 'Em execução', 'A aguardar peças', 'Concluído', 'Cancelado'];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<div class="page-head maintenance-head">
    <div>
        <span class="eyebrow">Equipa de manutenção</span>
        <h1>Pedidos de manutenção</h1>
        <p>Registe intervenções e permita à equipa de manutenção delegar o responsável por cada tarefa.</p>
    </div>
    <div class="btn-cluster">
        <a class="btn <?= !$isClosedView ? 'btn-dark' : 'btn-light' ?>" href="<?= Url::page('maintenance') ?>&view=open"><i class="bi bi-wrench-adjustable"></i> Em aberto</a>
        <a class="btn <?= $isClosedView ? 'btn-dark' : 'btn-light' ?>" href="<?= Url::page('maintenance') ?>&view=closed"><i class="bi bi-check2-circle"></i> Fechados</a>
    </div>
</div>
<?php if ($flash): ?><div class="alert alert-info"><?= htmlspecialchars($flash) ?></div><?php endif; ?>

<?php if (!$isClosedView): ?>
<form method="post" action="<?= Url::page('maintenance_save') ?>" class="quick-form mb-4 maintenance-form">
    <div class="section-title"><h2><i class="bi bi-plus-circle me-2"></i>Novo pedido</h2><span class="soft-badge">Campos obrigatórios *</span></div>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Título *</label><input class="form-control" name="title" required placeholder="Ex.: Avaria na linha 2"></div>
        <div class="col-md-3"><label class="form-label">Equipamento / ativo *</label><input class="form-control" name="asset" required></div>
        <div class="col-md-3"><label class="form-label">Localização *</label><input class="form-control" name="location" required></div>
        <div class="col-md-2"><label class="form-label">Prioridade *</label><select class="form-select" name="priority" required><?php for($i=1;$i<=5;$i++): ?><option value="<?= $i ?>" <?= $i===3?'selected':'' ?>><?= $i ?></option><?php endfor; ?></select></div>
        <div class="col-md-3"><label class="form-label">Data limite</label><input class="form-control" name="due_date" type="date"></div>
        <?php if ($canDelegateMaintenance): ?>
        <div class="col-md-3"><label class="form-label">Delegar a</label><input class="form-control" name="assigned_to" list="maintenance-users" placeholder="Responsável"></div>
        <?php endif; ?>
        <div class="col-md-6"><label class="form-label">Descrição</label><input class="form-control" name="description" placeholder="Sintomas, impacto, instruções de segurança..."></div>
        <div class="col-md-12"><label class="form-label">Notas de delegação</label><input class="form-control" name="delegation_notes" placeholder="Orientações para quem vai executar a tarefa"></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-send"></i> Submeter pedido</button></div>
</form>
<?php endif; ?>

<datalist id="maintenance-users"><?php foreach($teamUsers as $member): ?><option value="<?= htmlspecialchars($member['name']) ?>"><?= htmlspecialchars($member['team'] . ' · ' . $member['role']) ?></option><?php endforeach; ?></datalist>
<div class="data-shell"><div class="table-responsive"><table class="table modern-table align-middle maintenance-table">
<thead><tr><th>Estado</th><th>Criado</th><th>Título</th><th>Ativo</th><th>Local</th><th>Solicitante</th><th>Responsável</th><th>Prior.</th><th>Limite</th><th>Descrição</th><?php if($canDelegateMaintenance): ?><th>Delegar</th><?php endif; ?></tr></thead>
<tbody>
<?php foreach($rows as $row): $modalId = 'maintenance-edit-' . (int)$row['id']; ?>
<tr>
<td><span class="badge status-badge maintenance-status"><?= htmlspecialchars($row['status']) ?></span></td>
<td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['created_at']))) ?></td>
<td><?= htmlspecialchars($row['title']) ?></td>
<td><?= htmlspecialchars($row['asset']) ?></td>
<td><?= htmlspecialchars($row['location']) ?></td>
<td><?= htmlspecialchars(trim(($row['requester_name'] ?? '') . ' · ' . ($row['requester_team'] ?? ''), ' ·')) ?></td>
<td><?= $row['assigned_to'] ? htmlspecialchars($row['assigned_to']) : '<span class="text-muted">Por delegar</span>' ?></td>
<td><span class="maintenance-priority" aria-label="Prioridade <?= (int)$row['priority'] ?>"><?= str_repeat('<i class="bi bi-exclamation-triangle-fill"></i>', (int)$row['priority']) ?></span></td>
<td><?= !empty($row['due_date']) ? htmlspecialchars(date('d/m/Y', strtotime($row['due_date']))) : '<span class="text-muted">Sem data</span>' ?></td>
<td><?= htmlspecialchars($row['description'] ?? '') ?></td>
<?php if($canDelegateMaintenance): ?><td><button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>"><i class="bi bi-person-check"></i> Delegar</button></td><?php endif; ?>
</tr>
<?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="<?= $canDelegateMaintenance ? 11 : 10 ?>" class="text-center text-muted py-5">Sem pedidos nesta vista.</td></tr><?php endif; ?>
</tbody></table></div></div>

<?php if($canDelegateMaintenance): foreach($rows as $row): $modalId = 'maintenance-edit-' . (int)$row['id']; ?>
<div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= Url::page('maintenance_status') ?>" class="modal-content">
            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
            <div class="modal-header"><div><h5 class="modal-title">Delegar manutenção</h5><small><?= htmlspecialchars($row['title']) ?> · <?= htmlspecialchars($row['asset']) ?></small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
            <div class="modal-body row g-3">
                <div class="col-md-6"><label class="form-label">Responsável</label><input class="form-control" name="assigned_to" list="maintenance-users" value="<?= htmlspecialchars($row['assigned_to'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Estado</label><select class="form-select" name="status"><?php foreach($statusOptions as $status): ?><option value="<?= htmlspecialchars($status) ?>" <?= $row['status']===$status?'selected':'' ?>><?= htmlspecialchars($status) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label">Prioridade</label><select class="form-select" name="priority"><?php for($i=1;$i<=5;$i++): ?><option value="<?= $i ?>" <?= (int)$row['priority']===$i?'selected':'' ?>><?= $i ?></option><?php endfor; ?></select></div>
                <div class="col-md-6"><label class="form-label">Data limite</label><input class="form-control" name="due_date" type="date" value="<?= htmlspecialchars($row['due_date'] ?? '') ?>"></div>
                <div class="col-12"><label class="form-label">Notas de delegação</label><textarea class="form-control" name="delegation_notes" rows="3"><?= htmlspecialchars($row['delegation_notes'] ?? '') ?></textarea></div>
            </div>
            <div class="modal-footer"><button class="btn btn-outline-danger me-auto" name="maintenance_action" value="delete" onclick="return confirm('Eliminar este pedido de manutenção?')"><i class="bi bi-trash"></i> Eliminar</button><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" name="maintenance_action" value="save"><i class="bi bi-check-lg"></i> Guardar</button></div>
        </form>
    </div>
</div>
<?php endforeach; endif; ?>
