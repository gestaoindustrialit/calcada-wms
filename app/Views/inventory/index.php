<?php
$isEdit = !empty($edit);
$filters = $filters ?? [];
$summary = $summary ?? ['lines' => count($rows), 'quantity' => array_sum(array_column($rows, 'quantity')), 'value' => array_sum(array_column($rows, 'stock_value'))];
$filterQuery = http_build_query(array_filter($filters, fn($v) => $v !== '' && $v !== null));
$exportSuffix = $filterQuery ? '&' . $filterQuery : '';
$locations = $locations ?? [];
$currentLocation = (string)($edit['location'] ?? '');
$inventoryRows = $inventoryRows ?? $rows;
$inventoryLocationPayload = array_map(fn($r) => ['item_id'=>(int)$r['item_id'], 'warehouse_id'=>(int)$r['warehouse_id'], 'location'=>(string)($r['location'] ?? ''), 'quantity'=>(float)$r['quantity'], 'min_quantity'=>(float)$r['min_quantity']], $inventoryRows);
$warehouseLocationPayload = array_map(fn($l) => ['warehouse_id'=>(int)$l['warehouse_id'], 'location'=>(string)($l['code'] ?? '')], $locations);
$warehouseFallbackPayload = array_map(fn($w) => ['warehouse_id'=>(int)$w['id'], 'location'=>(string)($w['location'] ?? '')], $warehouses);
?>
<div class="page-head"><div><span class="eyebrow">Stock em tempo real</span><h1>Inventário</h1><p>Filtre por artigo, armazém ou estado de stock e registe movimentos de entrada ou saída.</p></div></div>
<div class="btn-group-responsive mb-3"><a class="btn btn-success" href="<?= \App\Core\Url::page('export_excel') . $exportSuffix ?>" download="inventario.csv" title="Export CSV" aria-label="Export CSV"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</a><a class="btn btn-danger" href="<?= \App\Core\Url::page('export_pdf') . $exportSuffix ?>" download="inventario.pdf" title="Export PDF" aria-label="Export PDF"><i class="bi bi-file-earmark-pdf"></i> PDF</a></div>
<form method="post" action="<?= \App\Core\Url::page('inventory_save') ?>" class="row g-2 quick-form mb-4" data-inventory-movement-form data-inventory-locations='<?= htmlspecialchars(json_encode($inventoryLocationPayload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>' data-warehouse-locations='<?= htmlspecialchars(json_encode($warehouseLocationPayload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>' data-warehouse-fallbacks='<?= htmlspecialchars(json_encode($warehouseFallbackPayload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>'>
    <div class="section-title"><h2><?= $isEdit ? 'Atualizar stock' : 'Registar movimento' ?></h2><span class="soft-badge"><i class="bi bi-arrow-down-up"></i> Entrada/Saída</span></div>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
    <div class="col-md-3"><select class="form-select searchable-select" name="item_id" data-search-placeholder="Pesquisar artigo"><?php foreach($items as $i): ?><option value="<?= $i['id'] ?>" data-search="<?= htmlspecialchars(trim(($i['name'] ?? '') . ' ' . ($i['designation'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" <?= (int)($edit['item_id'] ?? 0) === (int)$i['id'] ? 'selected' : '' ?>><?= htmlspecialchars($i['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><select class="form-select searchable-select" name="warehouse_id" data-search-placeholder="Pesquisar armazém"><?php foreach($warehouses as $w): ?><option value="<?= $w['id'] ?>" <?= (int)($edit['warehouse_id'] ?? 0) === (int)$w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['name'].' · '.$w['section']) ?></option><?php endforeach; ?></select></div>
    <?php if (!$isEdit): ?><div class="col-md-2"><select class="form-select" name="movement_type"><option value="in">Entrada (+)</option><option value="out">Saída (-)</option><option value="split">Dividir stock</option></select></div><?php endif; ?>
    <?php if (!$isEdit): ?><input type="hidden" name="source_location" value=""><?php endif; ?>
    <div class="col-md-2"><input class="form-control" name="location" list="inventory-locations" placeholder="Localização" value="<?= htmlspecialchars($currentLocation) ?>" title="Localização do movimento; no modo dividir, indique aqui a localização de destino">
        <datalist id="inventory-locations"><?php foreach($locations as $l): ?><option value="<?= htmlspecialchars($l['code']) ?>"><?= htmlspecialchars($l['warehouse'] . ' · ' . $l['type']) ?></option><?php endforeach; ?></datalist></div>
    <div class="col-md-2"><input class="form-control" name="quantity" type="number" min="0" step="0.01" placeholder="<?= $isEdit ? 'Stock final' : 'Quantidade' ?>" value="<?= htmlspecialchars($edit['quantity'] ?? '') ?>" required></div>
    <div class="col-md-1"><input class="form-control" name="min_quantity" type="number" min="0" step="0.01" placeholder="Mín." value="<?= htmlspecialchars($edit['min_quantity'] ?? '') ?>" required></div>
    <div class="col-md-1"><button class="btn btn-primary w-100" title="Guardar stock" aria-label="Guardar stock"><i class="bi bi-save"></i></button></div>
</form>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="inventory-stat"><small>Linhas filtradas</small><strong><?= (int)$summary['lines'] ?></strong></div></div>
    <div class="col-md-4"><div class="inventory-stat"><small>Quantidade total</small><strong><?= number_format((float)$summary['quantity'], 2, ',', '.') ?></strong></div></div>
    <div class="col-md-4"><div class="inventory-stat"><small>Valor filtrado</small><strong>€ <?= number_format((float)$summary['value'], 2, ',', '.') ?></strong></div></div>
</div>
<form method="get" class="quick-form mb-4">
    <input type="hidden" name="page" value="inventory">
    <div class="section-title"><h2>Filtrar inventário</h2><span class="soft-badge"><i class="bi bi-funnel"></i> <?= (int)$summary['lines'] ?> linhas</span></div>
    <div class="row g-2 align-items-end">
        <div class="col-lg-3"><label class="form-label">Pesquisa</label><input class="form-control" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="Artigo, designação ou armazém"></div>
        <div class="col-lg-3"><label class="form-label">Artigo</label><select class="form-select searchable-select" name="item_id" data-search-placeholder="Pesquisar artigo"><option value="">Todos os artigos</option><?php foreach($items as $i): ?><option value="<?= $i['id'] ?>" data-search="<?= htmlspecialchars(trim(($i['name'] ?? '') . ' ' . ($i['designation'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" <?= (int)($filters['item_id'] ?? 0) === (int)$i['id'] ? 'selected' : '' ?>><?= htmlspecialchars($i['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-lg-2"><label class="form-label">Armazém</label><select class="form-select searchable-select" name="warehouse_id" data-search-placeholder="Pesquisar armazém"><option value="">Todos</option><?php foreach($warehouses as $w): ?><option value="<?= $w['id'] ?>" <?= (int)($filters['warehouse_id'] ?? 0) === (int)$w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-lg-2"><label class="form-label">Estado</label><select class="form-select" name="stock_status"><option value="">Todos</option><option value="available" <?= ($filters['stock_status'] ?? '') === 'available' ? 'selected' : '' ?>>Acima do mínimo</option><option value="low" <?= ($filters['stock_status'] ?? '') === 'low' ? 'selected' : '' ?>>Abaixo do mínimo</option></select></div>
        <div class="col-lg-2 d-flex gap-2"><button class="btn btn-primary flex-fill" title="Filtrar" aria-label="Filtrar"><i class="bi bi-search"></i></button><a class="btn btn-light" href="<?= \App\Core\Url::page('inventory') ?>" title="Limpar filtros" aria-label="Limpar filtros"><i class="bi bi-x-lg"></i></a></div>
    </div>
</form>


<div class="table-responsive data-shell">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Artigo</th>
                <th>Designação</th>
                <th>Armazém</th>
                <th>Setor</th>
                <th>Localização</th>
                <th>Qtd</th>
                <th>Mín.</th>
                <th>Valor</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($rows as $r): ?>
                <tr class="<?= $r['quantity'] <= $r['min_quantity'] ? 'table-warning' : '' ?>">
                    <td><?= htmlspecialchars($r['item']) ?></td>
                    <td><?= htmlspecialchars($r['designation'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['warehouse']) ?></td>
                    <td><?= htmlspecialchars($r['section'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['location'] ?? '') ?></td>
                    <td><strong><?= htmlspecialchars($r['quantity'].' '.$r['unit']) ?></strong></td>
                    <td><?= htmlspecialchars($r['min_quantity']) ?></td>
                    <td>€ <?= number_format((float)$r['stock_value'],2,',','.') ?></td>
                    <td>
                        <a class="btn btn-sm btn-outline-primary" href="<?= \App\Core\Url::page('inventory') ?>&edit=<?= $r['id'] ?>" title="Editar" aria-label="Editar"><i class="bi bi-pencil"></i></a>
                        <a class="btn btn-sm btn-outline-danger" href="<?= \App\Core\Url::page('inventory') ?>&delete=<?= $r['id'] ?>" onclick="return confirm('Apagar stock?')" title="Apagar" aria-label="Apagar"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
