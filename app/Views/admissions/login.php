<?php use App\Core\Url; ?>
<div class="admissions-shell admissions-login">
    <div class="admissions-brand"><span><i class="bi bi-upc-scan"></i></span><div><strong>Admissões</strong><small>Controlo de entradas</small></div></div>
    <div class="event-public-card">
        <span class="eyebrow text-primary">Acesso reservado</span><h1 class="h2 mt-2">Bem-vindo à entrada</h1><p class="text-muted">Inicie sessão para aceder às reservas do evento de hoje.</p>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" action="<?= Url::page('admissions_login') ?>" class="d-grid gap-3">
            <div><label class="form-label" for="username">Utilizador</label><input class="form-control" id="username" name="username" required autofocus autocomplete="username"></div>
            <div><label class="form-label" for="password">Palavra-passe</label><input class="form-control" id="password" name="password" type="password" required autocomplete="current-password"></div>
            <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-arrow-right-circle"></i> Entrar</button>
        </form>
    </div>
</div>
