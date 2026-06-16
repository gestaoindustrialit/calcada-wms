<div class="row justify-content-center py-5">
    <div class="col-sm-10 col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-3 text-center">Login admin</h1>
                <p class="text-muted text-center">Entre para gerir o Calçada WMS.</p>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post" action="<?= \App\Core\Url::page('login') ?>">
                    <div class="mb-3">
                        <label class="form-label" for="username">Utilizador</label>
                        <input class="form-control" id="username" name="username" autocomplete="username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Palavra-passe</label>
                        <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit" title="Entrar" aria-label="Entrar"><i class="bi bi-box-arrow-in-right"></i></button>
                </form>
                <p class="small text-muted mt-3 mb-0">Acesso inicial: <strong>admin</strong> / <strong>admin123</strong>. Pode alterar com as variáveis <code>WMS_ADMIN_USER</code> e <code>WMS_ADMIN_PASSWORD</code>.</p>
            </div>
        </div>
    </div>
</div>
