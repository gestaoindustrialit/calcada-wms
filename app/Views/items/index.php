<?php $isEdit = !empty($edit); $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); ?>
<div class="page-head"><div><span class="eyebrow">Catálogo SAGE</span><h1>Artigos</h1><p>Adicione manualmente, pesquise ou importe em massa por CSV, com ou sem dados de stock/localização.</p></div></div>
<?php if ($flash): ?><div class="alert alert-info"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
<div class="row g-3 mb-4 align-items-stretch">
    <div class="col-lg-6">
        <form method="post" class="row g-3 quick-form h-100" data-smart-form>
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
            <div class="col-md-6"><input class="form-control" name="name" placeholder="Nome / referência" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required></div>
            <div class="col-md-6"><input class="form-control" name="designation" placeholder="Designação" value="<?= htmlspecialchars($edit['designation'] ?? '') ?>" required></div>
            <div class="col-md-3"><input class="form-control" name="unit" placeholder="Unidade" value="<?= htmlspecialchars($edit['unit'] ?? '') ?>" required></div>
            <div class="col-md-3"><button class="btn btn-primary w-100" title="Guardar" aria-label="Guardar"><i class="bi bi-save"></i></button></div>
        </form>
    </div>
    <div class="col-lg-6">
        <div class="import-grid h-100">
            <form method="post" enctype="multipart/form-data" class="quick-form upload-zone import-card">
                <input type="hidden" name="import_mode" value="quick">
                <div class="import-card__content">
                    <h5>Importação ágil</h5>
                    <p>Primeira carga mais rápida: CSV com <code>nome;designação;unidade</code>, sem armazém, setor ou localização.</p>
                    <a class="btn btn-light btn-sm import-card__template" href="<?= \App\Core\Url::to('templates/artigos-importacao-agil.csv') ?>" download><i class="bi bi-filetype-csv"></i> Template ágil</a>
                </div>
                <input class="form-control" type="file" name="items_csv" accept=".csv,text/csv" required>
                <button class="btn btn-dark w-100" title="Importar artigos" aria-label="Importar artigos"><i class="bi bi-upload"></i></button>
            </form>
            <form method="post" enctype="multipart/form-data" class="quick-form upload-zone import-card">
                <input type="hidden" name="import_mode" value="located">
                <div class="import-card__content">
                    <h5>Importação completa</h5>
                    <p>CSV com <code>nome;designação;unidade;armazém;setor;localização;qtd</code> para criar stock e localizações.</p>
                    <a class="btn btn-light btn-sm import-card__template" href="<?= \App\Core\Url::to('templates/artigos-importacao-completa.csv') ?>" download><i class="bi bi-filetype-csv"></i> Template completo</a>
                </div>
                <input class="form-control" type="file" name="items_csv" accept=".csv,text/csv" required>
                <button class="btn btn-primary w-100" title="Importar com localização" aria-label="Importar com localização"><i class="bi bi-box-seam"></i></button>
            </form>
        </div>
    </div>
</div>
<?php $filters = $filters ?? []; ?>
<form method="get" class="quick-form mb-4">
    <input type="hidden" name="page" value="items">
    <div class="row g-2 align-items-end">
        <div class="col-md-10"><label class="form-label">Pesquisar artigos</label><input class="form-control" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="Nome, referência, designação ou unidade"></div>
        <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary flex-fill" title="Pesquisar" aria-label="Pesquisar"><i class="bi bi-search"></i></button><a class="btn btn-light" href="<?= \App\Core\Url::page('items') ?>" title="Limpar pesquisa" aria-label="Limpar pesquisa"><i class="bi bi-x-lg"></i></a></div>
    </div>
</form>
<div class="table-responsive data-shell"><table class="table align-middle"><thead><tr><th>Nome</th><th>Designação</th><th>Un.</th><th>Ações</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= htmlspecialchars($r['name']) ?></td><td><?= htmlspecialchars($r['designation']) ?></td><td><?= htmlspecialchars($r['unit']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= \App\Core\Url::page('items') ?>&edit=<?= $r['id'] ?>" title="Editar" aria-label="Editar"><i class="bi bi-pencil"></i></a> <a class="btn btn-sm btn-outline-danger" href="<?= \App\Core\Url::page('items') ?>&delete=<?= $r['id'] ?>" onclick="return confirm('Apagar artigo?')" title="Apagar" aria-label="Apagar"><i class="bi bi-trash"></i></a></td></tr><?php endforeach; ?></tbody></table></div>
