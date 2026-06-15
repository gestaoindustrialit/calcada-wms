<?php $isEdit = !empty($edit); ?>
<h1>Artigos</h1>
<form method="post" class="row g-2 quick-form mb-4">
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
    <div class="col-md-3"><input class="form-control" name="name" placeholder="Nome" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required></div>
    <div class="col-md-3"><input class="form-control" name="designation" placeholder="Designação" value="<?= htmlspecialchars($edit['designation'] ?? '') ?>" required></div>
    <div class="col-md-2"><input class="form-control" name="unit" placeholder="Unidade" value="<?= htmlspecialchars($edit['unit'] ?? '') ?>" required></div>
    <div class="col-md-2"><input class="form-control" name="weighted_price" type="number" step="0.01" placeholder="P. ponderado" value="<?= htmlspecialchars($edit['weighted_price'] ?? '') ?>" required></div>
    <div class="col-md-2"><button class="btn btn-primary w-100"><?= $isEdit ? 'Guardar' : 'Adicionar' ?></button></div>
</form>
<div class="table-responsive"><table class="table align-middle"><tr><th>Nome</th><th>Designação</th><th>Un.</th><th>P. Ponderado</th><th>Ações</th></tr><?php foreach($rows as $r): ?><tr><td><?= htmlspecialchars($r['name']) ?></td><td><?= htmlspecialchars($r['designation']) ?></td><td><?= htmlspecialchars($r['unit']) ?></td><td>€ <?= number_format((float)$r['weighted_price'],2,',','.') ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= \App\Core\Url::page('items') ?>&edit=<?= $r['id'] ?>">Editar</a> <a class="btn btn-sm btn-outline-danger" href="<?= \App\Core\Url::page('items') ?>&delete=<?= $r['id'] ?>" onclick="return confirm('Apagar artigo?')">Apagar</a></td></tr><?php endforeach; ?></table></div>
