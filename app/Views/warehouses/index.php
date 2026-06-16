<?php $isEdit = !empty($edit); ?>
<h1>Armazéns, secções e localizações</h1>
<form method="post" class="row g-2 quick-form mb-4">
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
    <div class="col-md-3"><input class="form-control" name="name" placeholder="Armazém" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required></div>
    <div class="col-md-3"><input class="form-control" name="section" placeholder="Secção" value="<?= htmlspecialchars($edit['section'] ?? '') ?>" required></div>
    <div class="col-md-4"><input class="form-control" name="location" placeholder="Localização" value="<?= htmlspecialchars($edit['location'] ?? '') ?>" required></div>
    <div class="col-md-2"><button class="btn btn-primary w-100"><?= $isEdit ? 'Guardar' : 'Adicionar' ?></button></div>
</form>
<div class="row g-3"><?php foreach($rows as $r): ?><div class="col-md-4"><div class="card h-100"><div class="card-body"><h5><?= htmlspecialchars($r['name']) ?></h5><p><?= htmlspecialchars($r['section']) ?><br><?= htmlspecialchars($r['location']) ?></p><a class="btn btn-sm btn-outline-primary" href="<?= \App\Core\Url::page('warehouses') ?>&edit=<?= $r['id'] ?>">Editar</a> <a class="btn btn-sm btn-outline-danger" href="<?= \App\Core\Url::page('warehouses') ?>&delete=<?= $r['id'] ?>" onclick="return confirm('Apagar armazém?')">Apagar</a></div></div></div><?php endforeach; ?></div>
