<?php $isEdit = !empty($edit); $isLocationEdit = !empty($editLocation); ?>
<div class="page-hero"><div><span class="eyebrow">Mapa logístico</span><h1>Armazéns, setores e posições</h1><p>Organize cada armazém com setores e posições de picking/stock para facilitar a entrega e localização dos artigos.</p></div></div>
<div class="grid-two mb-4">
    <section class="panel-card">
        <div class="section-title"><h2><?= $isEdit ? 'Editar armazém' : 'Criar armazém' ?></h2><span class="soft-badge">Armazém</span></div>
        <form method="post" class="row g-3" data-smart-form>
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
            <div class="col-md-6"><label class="form-label">Nome</label><input class="form-control" name="name" placeholder="Armazém Central" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required></div>
            <div class="col-md-6"><label class="form-label">Secção principal</label><input class="form-control" name="section" placeholder="Matérias-primas" value="<?= htmlspecialchars($edit['section'] ?? '') ?>" required></div>
            <div class="col-12"><label class="form-label">Localização</label><input class="form-control" name="location" placeholder="Morada / zona" value="<?= htmlspecialchars($edit['location'] ?? '') ?>" required></div>
            <div class="col-12"><button class="btn btn-primary" title="Guardar armazém" aria-label="Guardar armazém"><i class="bi bi-building-add"></i></button></div>
        </form>
    </section>
    <section class="panel-card accent-panel">
        <div class="section-title"><h2><?= $isLocationEdit ? 'Editar setor/posição' : 'Criar setor/posição' ?></h2><span class="soft-badge">Localização interna</span></div>
        <form method="post" class="row g-3" data-smart-form>
            <input type="hidden" name="form_type" value="location">
            <?php if ($isLocationEdit): ?><input type="hidden" name="id" value="<?= (int)$editLocation['id'] ?>"><?php endif; ?>
            <div class="col-md-6"><label class="form-label">Armazém</label><select class="form-select" name="warehouse_id" required><?php foreach($rows as $w): ?><option value="<?= $w['id'] ?>" <?= (int)($editLocation['warehouse_id'] ?? 0)===(int)$w['id']?'selected':'' ?>><?= htmlspecialchars($w['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label">Tipo</label><select class="form-select" name="type"><option <?= ($editLocation['type'] ?? '')==='Setor'?'selected':'' ?>>Setor</option><option <?= ($editLocation['type'] ?? '')==='Posição'?'selected':'' ?>>Posição</option></select></div>
            <div class="col-md-5"><label class="form-label">Código</label><input class="form-control" name="code" placeholder="A-01" value="<?= htmlspecialchars($editLocation['code'] ?? '') ?>" required></div>
            <div class="col-md-7"><label class="form-label">Descrição</label><input class="form-control" name="description" placeholder="Corredor, rack, obra..." value="<?= htmlspecialchars($editLocation['description'] ?? '') ?>"></div>
            <div class="col-12"><button class="btn btn-dark" title="Guardar localização" aria-label="Guardar localização"><i class="bi bi-geo-alt"></i></button></div>
        </form>
    </section>
</div>
<div class="row g-4">
    <?php foreach($rows as $r): $children=array_values(array_filter($locations, fn($l)=>(int)$l['warehouse_id']===(int)$r['id'])); ?>
    <div class="col-lg-4"><article class="warehouse-card"><div class="warehouse-card__top"><div><span class="soft-badge">ID <?= (int)$r['id'] ?></span><h3><?= htmlspecialchars($r['name']) ?></h3></div><div class="dropdown"><button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown" title="Gerir" aria-label="Gerir"><i class="bi bi-three-dots"></i></button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="<?= \App\Core\Url::page('warehouses') ?>&edit=<?= $r['id'] ?>">Editar armazém</a></li><li><a class="dropdown-item text-danger" href="<?= \App\Core\Url::page('warehouses') ?>&delete=<?= $r['id'] ?>" onclick="return confirm('Apagar armazém?')">Eliminar armazém</a></li></ul></div></div><p class="warehouse-meta"><?= htmlspecialchars($r['section']) ?><br><?= htmlspecialchars($r['location']) ?></p><div class="location-list"><?php if(!$children): ?><span class="text-muted small">Sem setores/posições.</span><?php endif; ?><?php foreach($children as $child): ?><div class="location-chip"><div><strong><?= htmlspecialchars($child['code']) ?></strong><span><?= htmlspecialchars($child['type']) ?> · <?= htmlspecialchars($child['description'] ?? '') ?></span></div><div><a href="<?= \App\Core\Url::page('warehouses') ?>&edit_location=<?= $child['id'] ?>" class="mini-link" title="Editar" aria-label="Editar"><i class="bi bi-pencil"></i></a><a href="<?= \App\Core\Url::page('warehouses') ?>&delete_location=<?= $child['id'] ?>" class="mini-link danger" onclick="return confirm('Eliminar setor/posição?')" title="Eliminar" aria-label="Eliminar"><i class="bi bi-trash"></i></a></div></div><?php endforeach; ?></div></article></div>
    <?php endforeach; ?>
</div>
