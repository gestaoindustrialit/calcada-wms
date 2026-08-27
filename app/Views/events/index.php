<section class="event-admissions" data-event-admissions
    data-list-url="<?= htmlspecialchars(\App\Core\Url::page('event_reservations')) ?>"
    data-validate-url="<?= htmlspecialchars(\App\Core\Url::page('event_validate')) ?>"
    data-reset-url="<?= htmlspecialchars(\App\Core\Url::page('event_reset')) ?>"
    data-email-url="<?= htmlspecialchars(\App\Core\Url::page('event_email')) ?>">
  <header class="event-admissions__head">
    <div><span class="eyebrow"><i class="bi bi-shield-check"></i> Controlo de entrada</span><h1>Admissões do evento</h1><p>Digitaliza o QR-Code ou introduz o token da reserva.</p></div>
    <span class="operation-pill"><i class="bi bi-circle-fill"></i> Operação ativa</span>
  </header>

  <?php if (!$events): ?>
    <div class="event-empty"><i class="bi bi-calendar2-x"></i><h2>Sem eventos disponíveis</h2><p>Só são apresentados eventos abertos que tenham reservas.</p></div>
  <?php else: ?>
  <div class="event-admissions__grid">
    <div class="admission-scanner panel-card">
      <label class="form-label" for="admission-event">Evento</label>
      <select id="admission-event" class="form-select" data-event-select>
        <?php foreach ($events as $event): ?><option value="<?= (int)$event['id'] ?>" <?= (int)$event['id'] === $selectedId ? 'selected' : '' ?>><?= htmlspecialchars($event['name']) ?> · <?= date('d/m H:i', strtotime($event['starts_at'])) ?></option><?php endforeach; ?>
      </select>
      <div class="scanner-frame" data-qr-reader><div class="scanner-placeholder"><i class="bi bi-qr-code-scan"></i><strong>Câmara desligada</strong><small>Toca no botão para começar</small></div></div>
      <div class="scanner-actions"><button class="btn btn-primary" type="button" data-camera-start><i class="bi bi-camera"></i> Iniciar câmara</button><button class="btn btn-outline-secondary" type="button" data-camera-stop disabled><i class="bi bi-stop-circle"></i> Parar</button></div>
      <form class="token-form" data-token-form><label class="visually-hidden" for="reservation-token">Token</label><input id="reservation-token" name="token" class="form-control" placeholder="Token lido / QR payload" autocomplete="off" required><button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Validar</button></form>
      <div class="admission-feedback" data-feedback hidden role="status" aria-live="polite"></div>
    </div>
    <aside class="reservation-panel panel-card"><div class="reservation-panel__head"><div><span class="eyebrow">Reservas</span><h2>Estado das entradas</h2></div><strong data-counter>—</strong></div><div data-reservation-list class="reservation-list"><div class="list-loading"><span class="spinner-border spinner-border-sm"></span> A carregar…</div></div></aside>
  </div>
  <?php endif; ?>
</section>
<?php if ($events): ?><script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" defer></script><?php endif; ?>
