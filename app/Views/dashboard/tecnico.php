<?php
/**
 * Agenda mobile-first del tecnico.
 *
 * @var array $giorni  Lista di 3 giorni: ognuno ['data','label','interventi'].
 *                     Ogni intervento ha id, data_pianificata, stato, cliente_denominazione,
 *                     indirizzo, citta, cap, lat, lng, tipo, materiali[].
 * @var array $urgenti Interventi urgenti non pianificati assegnati al tecnico.
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>La mia agenda<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/vendor/leaflet/leaflet.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/dashboard-tecnico.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="agenda-tecnico">

    <!-- Tab giorni: Oggi / Domani / Dopodomani -->
    <ul class="nav nav-pills nav-fill agenda-giorni mb-3" role="tablist">
        <?php foreach ($giorni as $n => $g): $num = count($g['interventi']); ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $n === 0 ? 'active' : '' ?>"
                    data-bs-toggle="pill"
                    data-bs-target="#giorno-<?= $n ?>"
                    type="button" role="tab">
                <span class="agenda-giorno-label"><?= esc($g['label']) ?></span>
                <span class="d-block small text-muted"><?= date('d/m', strtotime($g['data'])) ?></span>
                <?php if ($num > 0): ?>
                    <span class="badge rounded-pill bg-primary agenda-giorno-badge"><?= $num ?></span>
                <?php endif ?>
            </button>
        </li>
        <?php endforeach ?>
    </ul>

    <div class="tab-content">
        <?php foreach ($giorni as $n => $g): ?>
        <div class="tab-pane fade <?= $n === 0 ? 'show active' : '' ?>" id="giorno-<?= $n ?>" role="tabpanel">

            <?php if (empty($g['interventi'])): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-calendar-check fs-1 d-block mb-2"></i>
                Nessun intervento pianificato
            </div>
            <?php else: ?>
                <?php foreach ($g['interventi'] as $i):
                    $haCoord  = $i['lat'] !== null && $i['lng'] !== null;
                    $inCorso  = $i['stato'] === 'in_corso';
                ?>
                <div class="card agenda-card mb-2">
                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="agenda-ora">
                                <i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($i['data_pianificata'])) ?>
                            </span>
                            <span>
                                <?php if ($inCorso): ?>
                                    <span class="badge bg-warning text-dark me-1">In corso</span>
                                <?php endif ?>
                                <span class="badge bg-secondary"><?= esc($i['tipo'] ?? '—') ?></span>
                            </span>
                        </div>

                        <a href="<?= base_url('operativo/interventi/' . $i['id']) ?>" class="agenda-cliente">
                            <?= esc($i['cliente_denominazione']) ?>
                        </a>

                        <div class="agenda-indirizzo text-muted">
                            <i class="bi bi-geo-alt me-1"></i>
                            <?= esc(trim($i['indirizzo'] . ', ' . $i['cap'] . ' ' . $i['citta'], ', ')) ?>
                        </div>

                        <?php if (! empty($i['materiali'])): ?>
                        <div class="agenda-materiali mt-2">
                            <div class="agenda-materiali-titolo">
                                <i class="bi bi-box-seam me-1"></i>Materiali da portare
                            </div>
                            <ul class="agenda-materiali-lista">
                                <?php foreach ($i['materiali'] as $m): ?>
                                <li>
                                    <span class="agenda-mat-qta"><?= esc((float) $m['quantita']) ?>×</span>
                                    <?= esc($m['desc_materiale']) ?>
                                </li>
                                <?php endforeach ?>
                            </ul>
                        </div>
                        <?php endif ?>

                        <div class="agenda-azioni mt-2">
                            <?php if ($haCoord): ?>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#modalMappa"
                                    data-lat="<?= esc($i['lat']) ?>"
                                    data-lng="<?= esc($i['lng']) ?>"
                                    data-nome="<?= esc($i['cliente_denominazione']) ?>">
                                <i class="bi bi-geo-alt-fill me-1"></i>Mappa
                            </button>
                            <?php endif ?>
                            <a href="<?= base_url('operativo/interventi/' . $i['id']) ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Apri
                            </a>
                        </div>

                    </div>
                </div>
                <?php endforeach ?>
            <?php endif ?>

        </div>
        <?php endforeach ?>
    </div>

    <?php if (! empty($urgenti)): ?>
    <div class="card card-danger card-outline mt-3">
        <div class="card-header">
            <h3 class="card-title">
                <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>
                Urgenti da pianificare
            </h3>
            <div class="card-tools">
                <span class="badge bg-danger"><?= count($urgenti) ?></span>
            </div>
        </div>
        <div class="card-body p-0">
            <ul class="list-group list-group-flush">
                <?php foreach ($urgenti as $u): ?>
                <li class="list-group-item py-2">
                    <a href="<?= base_url('operativo/interventi/' . $u['id']) ?>" class="fw-semibold text-decoration-none d-block">
                        <?= esc($u['cliente_denominazione']) ?>
                    </a>
                    <small class="text-muted"><?= esc($u['tipo'] ?? '—') ?> · <?= esc($u['citta']) ?></small>
                </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
    <?php endif ?>

</div>

<!-- Modal mappa: riusato da tutte le card, popolato al click via relatedTarget -->
<div class="modal fade" id="modalMappa" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMappaTitolo"><i class="bi bi-geo-alt-fill me-2"></i>Posizione</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="mappaTecnico"></div>
            </div>
            <div class="modal-footer">
                <a href="#" id="btnGoogleMaps" class="btn btn-primary w-100" target="_blank" rel="noopener">
                    <i class="bi bi-sign-turn-right-fill me-1"></i>Apri in Google Maps
                </a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/leaflet/leaflet.js') ?>"></script>
<script>
(() => {
    'use strict';
    const modalEl = document.getElementById('modalMappa');
    if (!modalEl) return;

    let map = null;
    let marker = null;
    let target = { lat: 0, lng: 0 };

    // Corregge il path delle icone marker. _getIconUrl di default antepone sempre
    // un imagePath auto-rilevato dal CSS, anche davanti a un URL già assoluto
    // (causa doppio URL / 404) — va rimosso per usare le nostre URL così come sono.
    delete L.Icon.Default.prototype._getIconUrl;
    const iconBase = '<?= base_url('assets/vendor/leaflet/images/') ?>';
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: iconBase + 'marker-icon-2x.png',
        iconUrl:       iconBase + 'marker-icon.png',
        shadowUrl:     iconBase + 'marker-shadow.png',
    });

    // Legge i dati dal bottone che ha aperto il modal e prepara il link Google Maps.
    modalEl.addEventListener('show.bs.modal', (ev) => {
        const btn = ev.relatedTarget;
        if (!btn) return;
        target.lat = parseFloat(btn.dataset.lat);
        target.lng = parseFloat(btn.dataset.lng);
        document.getElementById('modalMappaTitolo').innerHTML =
            '<i class="bi bi-geo-alt-fill me-2"></i>' + (btn.dataset.nome || 'Posizione');
        document.getElementById('btnGoogleMaps').href =
            'https://www.google.com/maps/dir/?api=1&destination=' + target.lat + ',' + target.lng;
    });

    // La mappa va dimensionata DOPO che il modal è visibile: Leaflet non calcola
    // le dimensioni di un contenitore nascosto e le tile restano grigie.
    modalEl.addEventListener('shown.bs.modal', () => {
        if (!map) {
            map = L.map('mappaTecnico').setView([target.lat, target.lng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap',
            }).addTo(map);
        }
        map.invalidateSize();
        map.setView([target.lat, target.lng], 16);
        if (marker) {
            marker.setLatLng([target.lat, target.lng]);
        } else {
            marker = L.marker([target.lat, target.lng]).addTo(map);
        }
    });
})();
</script>
<?= $this->endSection() ?>
