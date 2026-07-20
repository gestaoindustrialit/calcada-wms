<?php $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); $canViewAllData = in_array(strtolower((string)($currentUser['role'] ?? '')), ['admin', 'rh'], true); ?>
<div class="page-head"><div><span class="eyebrow">Dashboard</span><h1>Painel de controlo</h1><p><?= $canViewAllData ? 'Visão global operacional do WMS.' : 'Visão filtrada aos seus pedidos e requisições.' ?></p></div></div>
<?php if ($flash): ?><div class="alert alert-info"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
<?php if ($canViewAllData): ?><div class="row g-3 mb-4"><?php foreach([['Utilizadores',$stats['users']],['Armazéns',$stats['warehouses']],['Artigos',$stats['items']],['Valor stock','€ '.number_format($stats['stock_value'],2,',','.')]] as $m): ?><div class="col-6 col-lg-3"><div class="card metric"><div class="card-body"><small><?= $m[0] ?></small><h2><?= $m[1] ?></h2></div></div></div><?php endforeach; ?></div><?php endif; ?>

<?php if (strtolower((string)($currentUser['role'] ?? '')) === 'admin'): ?>
<div class="quick-form danger-zone mb-4">
    <div class="section-title"><h2>Administração de dados</h2><span class="soft-badge danger"><i class="bi bi-exclamation-triangle"></i> Zona crítica</span></div>
    <p>Elimine todos os artigos, armazéns, localizações, inventário e requisições para reiniciar o catálogo operacional.</p>
    <form method="post" action="<?= \App\Core\Url::page('clear_catalog') ?>" class="row g-2 align-items-end">
        <div class="col-md-9"><label class="form-label">Escreva ELIMINAR para confirmar</label><input class="form-control" name="confirm_clear" placeholder="ELIMINAR" required></div>
        <div class="col-md-3"><button class="btn btn-outline-danger w-100" onclick="return confirm('Esta ação elimina artigos, armazéns, inventário e requisições. Continuar?')"><i class="bi bi-trash3"></i> Eliminar dados</button></div>
    </form>
</div>
<?php endif; ?>
<div class="row g-3 mb-4"><?php foreach([['Gastos semanais',$stats['spending']['week']],['Despesa mensal',$stats['spending']['month']],['Gastos anuais',$stats['spending']['year']]] as $m): ?><div class="col-md-4"><div class="card spend-card"><div class="card-body"><small><?= $m[0] ?></small><h3>€ <?= number_format((float)$m[1],2,',','.') ?></h3></div></div></div><?php endforeach; ?></div>
<div class="chart-panel mb-4"><h2>Gastos por artigo</h2><canvas id="articleSpendChart"></canvas></div><script>window.articleSpend=<?= json_encode(['labels'=>array_column($stats['article_spend'],'item'),'data'=>array_map('floatval',array_column($stats['article_spend'],'total'))], JSON_UNESCAPED_UNICODE) ?>;</script>
<h2>Últimos pedidos</h2><?php $rows=array_slice($requests,0,5); require dirname(__DIR__).'/partials/requests_table.php'; ?>
