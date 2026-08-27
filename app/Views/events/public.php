<?php use App\Core\Url; ?>
<div class="event-public-shell">
    <?php if (!$event): ?>
        <div class="event-public-card text-center"><h1>Evento não encontrado</h1><p class="text-muted">Confirme o endereço e tente novamente.</p></div>
    <?php else: ?>
        <header class="event-public-hero">
            <span class="eyebrow">Próximo evento</span>
            <h1><?= htmlspecialchars($event['title']) ?></h1>
            <div class="event-public-meta"><span><i class="bi bi-calendar3"></i> <?= date('d/m/Y · H:i', strtotime($event['starts_at'])) ?></span><span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($event['venue']) ?></span></div>
            <?php if ($event['description']): ?><p><?= nl2br(htmlspecialchars($event['description'])) ?></p><?php endif; ?>
        </header>
        <?php if (!empty($success)): ?><div class="alert alert-success mt-4"><i class="bi bi-check-circle-fill me-2"></i>A sua reserva foi registada. Enviaremos a confirmação para o email indicado.</div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="alert alert-danger mt-4"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ((int)$event['reservations_enabled']): ?>
        <section class="event-public-card mt-4" id="reserva">
            <div class="section-title"><div><span class="eyebrow text-primary">Reserva imediata</span><h2 class="mt-1">Garanta o seu lugar</h2></div><span class="soft-badge"><i class="bi bi-ticket-perforated"></i> Reserva online</span></div>
            <form method="post" action="<?= Url::page('event_reserve') ?>" class="row g-3">
                <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
                <div class="col-md-6"><label class="form-label" for="name">Nome completo</label><input class="form-control" id="name" name="name" required autocomplete="name"></div>
                <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control" id="email" name="email" type="email" required autocomplete="email"></div>
                <div class="col-md-6"><label class="form-label" for="phone">Telefone</label><input class="form-control" id="phone" name="phone" type="tel" autocomplete="tel"></div>
                <div class="col-md-6"><label class="form-label" for="tickets">Número de bilhetes</label><input class="form-control" id="tickets" name="tickets" type="number" min="1" max="20" value="1" required></div>
                <div class="col-12"><label class="form-label" for="notes">Notas <span class="text-muted fw-normal">(opcional)</span></label><textarea class="form-control" id="notes" name="notes" rows="2"></textarea></div>
                <div class="col-12"><div class="privacy-consent"><input class="form-check-input" type="checkbox" value="1" id="privacy_consent" name="privacy_consent" required><label for="privacy_consent">Consinto que os meus dados pessoais sejam tratados para gerir esta reserva e as comunicações diretamente relacionadas com o evento, nos termos do Regulamento Geral sobre a Proteção de Dados (Regulamento (UE) 2016/679). Posso retirar o consentimento a qualquer momento, sem comprometer a licitude do tratamento já efetuado.</label></div></div>
                <div class="col-12"><button class="btn btn-primary btn-lg w-100" type="submit"><i class="bi bi-check2-circle"></i> Confirmar reserva</button></div>
            </form>
        </section>
        <?php endif; ?>
    <?php endif; ?>
</div>
