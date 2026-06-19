<?php
$requestRows = $rows ?? [];
$canManageRequests = !empty($canManageRequests);
$groupedRequests = [];
foreach ($requestRows as $row) {
    $key = $row['request_group'] ?: 'single-' . $row['id'];
    if (!isset($groupedRequests[$key])) {
        $groupedRequests[$key] = $row;
        $groupedRequests[$key]['lines'] = [];
        $groupedRequests[$key]['quantity_total'] = 0;
        $groupedRequests[$key]['delivered_total'] = 0;
        $groupedRequests[$key]['request_value_total'] = 0;
    }
    $groupedRequests[$key]['lines'][] = $row;
    $groupedRequests[$key]['quantity_total'] += (float)$row['quantity'];
    $groupedRequests[$key]['delivered_total'] += (float)($row['delivered_quantity'] ?? 0);
    $groupedRequests[$key]['request_value_total'] += (float)$row['request_value'];
}
?>
<div class="table-responsive data-shell">
    <table class="table modern-table align-middle">
        <thead><tr><th>Data</th><th>Requisitante</th><th>Equipa</th><th>Artigos</th><th>Armazéns</th><th>Qtd</th><th>Entregue</th><th>Estado</th><th>Valor</th><th class="text-end">Ações</th></tr></thead>
        <tbody>
            <?php foreach($groupedRequests as $r):
                $remaining=max(0,(float)$r['quantity_total']-(float)$r['delivered_total']);
                $modalId='deliverRequest'.(int)$r['id'];
                $statuses = array_unique(array_column($r['lines'], 'status'));
                $status = count($statuses) === 1 ? $statuses[0] : 'Parcial';
                $warehouses = array_values(array_unique(array_map(fn($line) => $line['warehouse'] ?: '-', $r['lines'])));
            ?>
                <tr>
                    <td><?= htmlspecialchars(substr($r['created_at'],0,10)) ?></td>
                    <td><?= htmlspecialchars($r['requester']) ?></td>
                    <td><?= htmlspecialchars($r['team']) ?></td>
                    <td><div class="request-items-list"><?php foreach($r['lines'] as $line): ?><span><strong><?= htmlspecialchars($line['item']) ?></strong> × <?= htmlspecialchars($line['quantity']) ?></span><?php endforeach; ?></div></td>
                    <td><?= htmlspecialchars(implode(', ', $warehouses)) ?></td>
                    <td><?= htmlspecialchars($r['quantity_total']) ?></td>
                    <td><div class="progress-wrap"><span><strong><?= htmlspecialchars($r['delivered_total']) ?></strong> / <?= htmlspecialchars($r['quantity_total']) ?></span><div class="progress"><div class="progress-bar" style="width: <?= (float)$r['quantity_total'] > 0 ? min(100, ((float)$r['delivered_total']/(float)$r['quantity_total'])*100) : 0 ?>%"></div></div><small>faltam <?= $remaining ?></small></div></td>
                    <td><span class="badge status-badge status-<?= strtolower($status) ?>"><?= htmlspecialchars($status) ?></span></td>
                    <td>€ <?= number_format((float)$r['request_value_total'],2,',','.') ?></td>
                    <td class="text-end"><?php if ($canManageRequests): ?><div class="btn-cluster justify-content-end"><a class="btn btn-sm btn-outline-success" href="<?= \App\Core\Url::page('request_action') ?>&id=<?= $r['id'] ?>&do=approve" title="Aprovar" aria-label="Aprovar"><i class="bi bi-check-lg"></i></a><button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>" <?= $remaining<=0 || $status === 'Cancelado'?'disabled':'' ?> title="Entregar" aria-label="Entregar"><i class="bi bi-truck"></i></button><a class="btn btn-sm btn-outline-secondary" href="<?= \App\Core\Url::page('requests') ?>&edit=<?= $r['id'] ?>" title="Editar primeira linha" aria-label="Editar primeira linha"><i class="bi bi-pencil"></i></a><a class="btn btn-sm btn-outline-danger" href="<?= \App\Core\Url::page('request_action') ?>&id=<?= $r['id'] ?>&do=cancel" title="Cancelar" aria-label="Cancelar"><i class="bi bi-x-lg"></i></a><a class="btn btn-sm btn-danger" href="<?= \App\Core\Url::page('request_action') ?>&id=<?= $r['id'] ?>&do=delete" title="Eliminar dados" aria-label="Eliminar dados" onclick="return confirm('Eliminar esta requisição e todos os artigos associados?')"><i class="bi bi-trash"></i></a></div><?php else: ?><span class="text-muted small">—</span><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php foreach($groupedRequests as $r): $modalId='deliverRequest'.(int)$r['id']; ?>
<div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content deliver-modal"><div class="modal-header"><div><span class="eyebrow">Entrega parcial</span><h5 class="modal-title">Entregar requisição</h5></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div><div class="modal-body"><p class="text-muted mb-3">Confirme a entrega por artigo. Cada linha abate stock no respetivo armazém.</p><form method="post" action="<?= \App\Core\Url::page('request_action') ?>&id=<?= $r['id'] ?>&do=deliver_all" class="delivery-form"><?php $hasDeliverableLines = false; foreach($r['lines'] as $line): $lineRemaining=max(0,(float)$line['quantity']-(float)($line['delivered_quantity']??0)); $lineDeliverable = $lineRemaining > 0 && $line['status'] !== 'Cancelado'; $hasDeliverableLines = $hasDeliverableLines || $lineDeliverable; ?><div class="delivery-line"><div><strong><?= htmlspecialchars($line['item']) ?></strong><small><?= htmlspecialchars($line['warehouse'] ?? '-') ?> · faltam <?= $lineRemaining ?></small></div><input class="form-control" name="deliver_quantities[<?= (int)$line['id'] ?>]" type="number" min="0.01" max="<?= $lineRemaining ?>" step="0.01" value="<?= $lineRemaining ?>" required <?= $lineDeliverable ? '' : 'disabled' ?>><button type="submit" name="request_action" value="deliver" class="btn btn-primary" formaction="<?= \App\Core\Url::page('request_action') ?>&id=<?= $line['id'] ?>&do=deliver" formmethod="post" <?= $lineDeliverable?'':'disabled' ?> title="Validar esta quantidade" aria-label="Validar esta quantidade"><i class="bi bi-check2-circle"></i></button></div><?php endforeach; ?><div class="delivery-actions"><button type="submit" name="request_action" value="deliver_all" class="btn btn-primary w-100" formaction="<?= \App\Core\Url::page('request_action') ?>&id=<?= $r['id'] ?>&do=deliver_all" formmethod="post" <?= $hasDeliverableLines ? '' : 'disabled' ?>><i class="bi bi-check2-all me-2"></i>Validar todas as quantidades</button></div></form></div></div></div></div>
<?php endforeach; ?>
