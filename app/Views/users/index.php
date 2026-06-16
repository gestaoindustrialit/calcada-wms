<?php $isEdit = !empty($edit); ?>
<h1>Utilizadores</h1>
<form method="post" class="row g-2 quick-form mb-4">
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
    <div class="col-md-3"><input class="form-control" name="name" placeholder="Nome" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required></div>
    <div class="col-md-3"><input class="form-control" name="email" type="email" placeholder="Email" value="<?= htmlspecialchars($edit['email'] ?? '') ?>" required></div>
    <div class="col-md-2"><input class="form-control" name="role" placeholder="Role" value="<?= htmlspecialchars($edit['role'] ?? '') ?>" required></div>
    <div class="col-md-2"><input class="form-control" name="team" placeholder="Equipa" value="<?= htmlspecialchars($edit['team'] ?? '') ?>" required></div>
    <div class="col-md-2"><button class="btn btn-primary w-100"><?= $isEdit ? 'Guardar' : 'Adicionar' ?></button></div>
</form>
<div class="table-responsive"><table class="table align-middle"><tr><th>Nome</th><th>Email</th><th>Role</th><th>Equipa</th><th>Ações</th></tr><?php foreach($rows as $r): ?><tr><td><?= htmlspecialchars($r['name']) ?></td><td><?= htmlspecialchars($r['email']) ?></td><td><?= htmlspecialchars($r['role']) ?></td><td><?= htmlspecialchars($r['team']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= \App\Core\Url::page('users') ?>&edit=<?= $r['id'] ?>">Editar</a> <a class="btn btn-sm btn-outline-danger" href="<?= \App\Core\Url::page('users') ?>&delete=<?= $r['id'] ?>" onclick="return confirm('Apagar utilizador?')">Apagar</a></td></tr><?php endforeach; ?></table></div>
