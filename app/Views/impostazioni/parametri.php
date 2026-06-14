<?php
/**
 * @var array $tipi  [codice => nome] dei tipi di intervento
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Parametri Generali<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('impostazioni') ?>">Impostazioni</a></li>
    <li class="breadcrumb-item active">Parametri Generali</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form id="form-parametri" method="post" action="<?= base_url('impostazioni/parametri') ?>">
    <?= csrf_field() ?>
    <div class="row g-4">

        <!-- Sede aziendale -->
        <div class="col-lg-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0"><i class="bi bi-building me-2"></i>Sede aziendale</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Usata come punto di partenza per il calcolo dei tempi di viaggio.</p>

                    <div class="mb-3">
                        <label class="form-label">Nome / Ragione sociale</label>
                        <input type="text" name="sede_nome" class="form-control"
                               value="<?= esc(setting('Azienda.sede_nome') ?? '') ?>"
                               placeholder="es. Colombini Snc">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Indirizzo</label>
                        <input type="text" name="sede_indirizzo" class="form-control"
                               value="<?= esc(setting('Azienda.sede_indirizzo') ?? '') ?>"
                               placeholder="es. Via Aurelia, 296">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <label class="form-label">CAP</label>
                            <input type="text" name="sede_cap" class="form-control"
                                   value="<?= esc(setting('Azienda.sede_cap') ?? '') ?>"
                                   placeholder="00000" maxlength="5">
                        </div>
                        <div class="col-8">
                            <label class="form-label">Città</label>
                            <input type="text" name="sede_citta" class="form-control"
                                   value="<?= esc(setting('Azienda.sede_citta') ?? '') ?>"
                                   placeholder="es. Ceriale">
                        </div>
                    </div>
                    <div class="row g-3 mb-1 align-items-end">
                        <div class="col-5">
                            <label class="form-label">Latitudine</label>
                            <input type="number" name="sede_lat" id="sede_lat" class="form-control"
                                   step="0.000001" min="-90" max="90"
                                   value="<?= esc(setting('Azienda.sede_lat') ?? '') ?>"
                                   placeholder="es. 44.096563">
                        </div>
                        <div class="col-5">
                            <label class="form-label">Longitudine</label>
                            <input type="number" name="sede_lng" id="sede_lng" class="form-control"
                                   step="0.000001" min="-180" max="180"
                                   value="<?= esc(setting('Azienda.sede_lng') ?? '') ?>"
                                   placeholder="es. 8.231695">
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-outline-secondary w-100" id="btn-geo-sede"
                                    title="Rileva coordinate dall'indirizzo">
                                <i class="bi bi-geo-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div id="geo-sede-result" class="small mb-3"></div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Telefono</label>
                            <input type="text" name="sede_telefono" class="form-control"
                                   value="<?= esc(setting('Azienda.sede_telefono') ?? '') ?>"
                                   placeholder="es. +39 0182 931378">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Sito web</label>
                            <input type="text" name="sede_sito" class="form-control"
                                   value="<?= esc(setting('Azienda.sede_sito') ?? '') ?>"
                                   placeholder="es. https://www.colombini-snc.it/">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Logo aziendale <small class="text-muted">— PNG, JPG o SVG, max 1 MB</small></label>
                        <?php $logoPath = setting('Azienda.sede_logo_path'); ?>
                        <?php if ($logoPath): ?>
                            <div class="mb-2 d-flex align-items-center gap-2">
                                <img src="<?= base_url($logoPath) ?>?v=<?= time() ?>"
                                     alt="Logo attuale" class="img-thumbnail param-logo-preview">
                                <small class="text-muted">Logo attuale</small>
                            </div>
                        <?php endif ?>
                        <div class="input-group input-group-sm">
                            <input type="file" name="sede_logo" class="form-control"
                                   form="form-logo"
                                   accept="image/png,image/jpeg,image/svg+xml">
                            <button type="submit" class="btn btn-outline-secondary" form="form-logo">
                                <i class="bi bi-upload me-1"></i>Carica
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonna destra -->
        <div class="col-lg-6 d-flex flex-column gap-4">

            <!-- Orari aziendali -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0"><i class="bi bi-clock me-2"></i>Orari aziendali</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Orari di apertura della sede e pausa pranzo.</p>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Inizio giornata</label>
                            <input type="time" name="orario_inizio" class="form-control"
                                   value="<?= esc(setting('Azienda.orario_inizio') ?? '08:00') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fine giornata</label>
                            <input type="time" name="orario_fine" class="form-control"
                                   value="<?= esc(setting('Azienda.orario_fine') ?? '18:00') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Pausa pranzo — dalle</label>
                            <input type="time" name="pausa_inizio" class="form-control"
                                   value="<?= esc(setting('Azienda.pausa_inizio') ?? '12:30') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Pausa pranzo — alle</label>
                            <input type="time" name="pausa_fine" class="form-control"
                                   value="<?= esc(setting('Azienda.pausa_fine') ?? '14:30') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Durate standard interventi -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0"><i class="bi bi-stopwatch me-2"></i>Durata standard interventi</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Minuti stimati per tipo di intervento, usati nella pianificazione.</p>
                    <?php foreach ($tipi as $codice => $nome): ?>
                        <?php $minuti = (int)(setting('Interventi.durata_' . $codice) ?? 0); ?>
                        <div class="row align-items-center mb-2">
                            <label class="col-6 col-form-label"><?= esc($nome) ?></label>
                            <div class="col-4">
                                <div class="input-group input-group-sm">
                                    <input type="number" name="durata_<?= esc($codice) ?>" class="form-control durata-input"
                                           min="5" max="480" step="5"
                                           value="<?= $minuti ?: '' ?>">
                                    <span class="input-group-text">min</span>
                                </div>
                            </div>
                            <div class="col-2 text-muted small durata-display">
                                <?= $minuti ? floor($minuti / 60) . 'h ' . sprintf('%02d', $minuti % 60) . "'" : '' ?>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

        </div>
    </div>

    <div class="d-flex justify-content-between mt-4 mb-3">
        <a href="<?= base_url('impostazioni') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Indietro
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>Salva impostazioni
        </button>
    </div>
</form>

<form id="form-logo" action="<?= base_url('impostazioni/parametri/logo') ?>" method="post"
      enctype="multipart/form-data">
    <?= csrf_field() ?>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    // Geocodifica indirizzo sede → lat/lng via Nominatim
    document.getElementById('btn-geo-sede').addEventListener('click', function () {
        var indirizzo = document.querySelector('[name="sede_indirizzo"]').value.trim();
        var citta     = document.querySelector('[name="sede_citta"]').value.trim();
        var cap       = document.querySelector('[name="sede_cap"]').value.trim();
        var result    = document.getElementById('geo-sede-result');
        var btn       = this;

        if (!indirizzo && !citta) {
            result.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Inserisci indirizzo o città prima.</span>';
            return;
        }

        btn.disabled = true;
        result.innerHTML = '<span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Ricerca in corso…</span>';

        var q   = [indirizzo, cap, citta, 'Italia'].filter(Boolean).join(', ');
        var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q);

        fetch(url, { headers: { 'Accept-Language': 'it' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data[0]) {
                    var lat = parseFloat(data[0].lat).toFixed(6);
                    var lng = parseFloat(data[0].lon).toFixed(6);
                    document.getElementById('sede_lat').value = lat;
                    document.getElementById('sede_lng').value = lng;
                    result.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Coordinate aggiornate: ' + lat + ', ' + lng + '</span>';
                } else {
                    result.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Indirizzo non trovato — verifica i dati.</span>';
                }
            })
            .catch(function () {
                result.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Errore di rete.</span>';
            })
            .finally(function () { btn.disabled = false; });
    });

    // Aggiorna visualizzazione ore/min in tempo reale
    document.querySelectorAll('.durata-input').forEach(function (input) {
        input.addEventListener('input', function () {
            var m       = parseInt(this.value) || 0;
            var display = this.closest('.row').querySelector('.durata-display');
            display.textContent = m ? Math.floor(m / 60) + 'h ' + String(m % 60).padStart(2, '0') + "'" : '';
        });
    });
})();
</script>
<?= $this->endSection() ?>
