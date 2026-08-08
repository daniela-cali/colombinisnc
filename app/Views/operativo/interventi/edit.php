<?php
/**
 * @var array      $intervento      Record interventi
 * @var array|null $cliente         Record clienti
 * @var array|null $cantiere        Record cantieri se l'intervento è collegato a un cantiere
 * @var array      $tecnici         Righe da PersonaleModel::elencoPerGruppi(['tecnico'])
 * @var array      $tipi            Righe da TipiInterventoModel::attivi()
 * @var array      $prioritaLabel   [codice => etichetta]
 * @var array      $statiLabel      [codice => etichetta]
 * @var array      $materiali       Righe da InterventiMaterialiModel::perIntervento()
 * @var array      $note            Righe da InterventiNoteModel::perIntervento() (diario)
 * @var array      $articoliPerCat  Da ArticoliModel::perCategoria() [cat_id => ['nome'=>..,'articoli'=>[..]]]
 * @var string     $from            URL di ritorno dopo il salvataggio (vuoto = lista interventi)
 * @var array      $assenzePerDipendente Da AssenzeModel::mappaPerDipendente() [personale_id => [['data_inizio','data_fine','tipo_label'], ...]]
 */
$this->extend('layouts/admin');

$durateDefault = array_column($tipi, 'durata_default', 'id');

// Fase stagionale corrente: derivata dai due flag mutuamente esclusivi.
$faseCorrente = ! empty($intervento['apertura']) ? 'apertura'
              : (! empty($intervento['chiusura']) ? 'chiusura' : 'ordinaria');
?>
<?= $this->section('title') ?><?= esc($intervento['codice']) ?><?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('operativo/interventi') ?>">Interventi</a></li>
    <li class="breadcrumb-item active"><?= esc($intervento['codice']) ?></li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-9">

        <?php if ($errors = session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= esc($e) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-tools me-2"></i><?= esc($intervento['codice']) ?>
                    <?php if ($intervento['urgenza']): ?>
                        <i class="bi bi-exclamation-triangle-fill text-danger ms-1"
                           data-bs-toggle="tooltip" data-bs-title="Urgente"></i>
                    <?php endif ?>
                    <?php if ($cliente): ?>
                        <span class="text-muted small ms-2">
                            — <a href="<?= base_url('anagrafiche/clienti/' . $cliente['id']) ?>"
                                 class="text-muted text-decoration-none">
                                <?= esc(\App\Models\ClientiModel::denominazione($cliente)) ?>
                            </a>
                        </span>
                    <?php endif ?>
                </h3>
            </div>

            <form id="form-update"
                  action="<?= base_url('operativo/interventi/' . $intervento['id'] . '/update') ?>"
                  method="post">
                <?= csrf_field() ?>
                <?php if ($from): ?>
                    <input type="hidden" name="from" value="<?= esc($from) ?>">
                <?php endif ?>
                <?php if ($cantiere): ?>
                    <input type="hidden" name="cantiere_id" value="<?= (int) $cantiere['id'] ?>">
                <?php endif ?>

                <div class="card-body">

                    <?php if ($cantiere): ?>
                        <div class="alert alert-warning d-flex align-items-center mb-4">
                            <i class="bi bi-bricks me-2 fs-5"></i>
                            <div>
                                Intervento collegato al cantiere
                                <a href="<?= base_url('cantieri/' . $cantiere['id']) ?>" class="fw-semibold alert-link">
                                    <?= esc($cantiere['titolo']) ?>
                                </a>.
                            </div>
                        </div>
                    <?php endif ?>

                    <!-- Assegnazione -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-people me-1"></i> Assegnazione</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-7">
                            <label class="form-label">Cliente</label>
                            <input type="hidden" name="cliente_id" value="<?= esc($intervento['cliente_id']) ?>">
                            <p class="form-control-plaintext">
                                <?php if ($cliente): ?>
                                    <a href="<?= base_url('anagrafiche/clienti/' . $cliente['id']) ?>">
                                        <?= esc(\App\Models\ClientiModel::denominazione($cliente)) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif ?>
                            </p>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Tecnico assegnato</label>
                            <select name="tecnico_id" class="form-select">
                                <option value="">— nessuno —</option>
                                <?php foreach ($tecnici as $t): ?>
                                    <option value="<?= $t['id'] ?>"
                                            <?= old('tecnico_id', $intervento['tecnico_id']) == $t['id'] ? 'selected' : '' ?>>
                                        <?= esc($t['cognome'] . ' ' . $t['nome']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                    <div id="avviso-assenza-tecnico" class="alert alert-danger py-2 mb-4 d-none" role="alert"></div>

                    <!-- Descrizione -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-card-text me-1"></i> Descrizione</p>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label">Descrizione <span class="text-danger">*</span></label>
                            <input type="text" name="descrizione" id="descrizione" class="form-control"
                                   maxlength="255" placeholder="Oggetto / motivo dell'intervento…"
                                   value="<?= esc(old('descrizione', $intervento['descrizione'] ?? '')) ?>">
                        </div>
                    </div>

                    <!-- Classificazione -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-tag me-1"></i> Classificazione</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Priorità <span class="text-danger">*</span></label>
                            <select name="priorita" id="priorita" class="form-select">
                                <?php foreach ($prioritaLabel as $codice => $etichetta): ?>
                                    <option value="<?= $codice ?>"
                                            <?= old('priorita', $intervento['priorita']) === $codice ? 'selected' : '' ?>>
                                        <?= esc($etichetta) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo intervento <span class="text-danger">*</span></label>
                            <select name="tipo_intervento_id" id="tipo_intervento_id" class="form-select">
                                <option value="">— seleziona —</option>
                                <?php foreach ($tipi as $t): ?>
                                    <option value="<?= $t['id'] ?>"
                                            data-categoria="<?= esc($t['categoria'] ?? 'generale') ?>"
                                            <?= old('tipo_intervento_id', $intervento['tipo_intervento_id']) == $t['id'] ? 'selected' : '' ?>>
                                        <?= esc($t['nome']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Stato <span class="text-danger">*</span></label>
                            <select name="stato" id="stato" class="form-select">
                                <?php foreach ($statiLabel as $codice => $etichetta):
                                    if ($codice === \App\Models\InterventiModel::STATO_ANNULLATO) continue; ?>
                                    <option value="<?= $codice ?>"
                                            <?= old('stato', $intervento['stato']) === $codice ? 'selected' : '' ?>>
                                        <?= esc($etichetta) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="urgenza"
                                       id="urgenza" value="1"
                                       <?= old('urgenza', $intervento['urgenza']) ? 'checked' : '' ?>>
                                <label class="form-check-label text-danger fw-semibold" for="urgenza">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Urgente
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Fase stagionale (solo tipi piscine) -->
                    <div id="blocco-fase" class="d-none">
                        <p class="text-muted section-header mb-3"><i class="bi bi-water me-1"></i> Fase stagionale piscina</p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Fase</label>
                                <select name="fase" id="fase" class="form-select">
                                    <option value="ordinaria" <?= old('fase', $faseCorrente) === 'ordinaria' ? 'selected' : '' ?>>Ordinaria</option>
                                    <option value="apertura"  <?= old('fase', $faseCorrente) === 'apertura' ? 'selected' : '' ?>>Apertura piscina</option>
                                    <option value="chiusura"  <?= old('fase', $faseCorrente) === 'chiusura' ? 'selected' : '' ?>>Chiusura piscina</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Pianificazione -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-calendar me-1"></i> Pianificazione</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Data pianificata</label>
                            <input type="datetime-local" name="data_pianificata" id="data_pianificata" class="form-control"
                                   value="<?= esc(old('data_pianificata',
                                       $intervento['data_pianificata']
                                           ? date('Y-m-d\TH:i', strtotime($intervento['data_pianificata']))
                                           : ''
                                   )) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data scadenza</label>
                            <input type="date" name="data_scadenza" class="form-control"
                                   value="<?= esc(old('data_scadenza', $intervento['data_scadenza'] ?? '')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Durata stimata</label>
                            <div class="input-group">
                                <input type="number" name="durata_stimata" id="durata_stimata"
                                       class="form-control" min="5" max="480" step="5"
                                       value="<?= esc(old('durata_stimata', $intervento['durata_stimata'] ?? '')) ?>"
                                       placeholder="min">
                                <span class="input-group-text">min</span>
                            </div>
                        </div>
                    </div>

                    <!-- Note -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-sticky me-1"></i> Note</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <textarea name="note" class="form-control" rows="4"><?= esc(old('note', $intervento['note'] ?? '')) ?></textarea>
                        </div>
                    </div>

                </div><!-- /card-body form -->
            </form>

            <!-- Sezione materiali — form separato, fuori dal form-update -->
            <div class="card-body border-top pt-4">
                <p class="text-muted section-header mb-3"><i class="bi bi-box-seam me-1"></i> Materiali da portare</p>

                <?php if (! empty($materiali)): ?>
                    <table class="table table-sm align-middle mb-4">
                        <thead>
                            <tr>
                                <th>Descrizione</th>
                                <th class="text-center" style="width:70px">Qtà</th>
                                <th>Note</th>
                                <th class="text-center" style="width:110px">Stato</th>
                                <th style="width:48px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materiali as $m): ?>
                                <tr>
                                    <td><?= esc($m['desc_materiale']) ?></td>
                                    <td class="text-center"><?= esc($m['quantita']) ?></td>
                                    <td class="text-muted small"><?= esc($m['note'] ?? '') ?></td>
                                    <td class="text-center">
                                        <?php if (($m['stato'] ?? '') === \App\Models\InterventiMaterialiModel::STATO_CONSEGNATO): ?>
                                            <span class="badge bg-success">Consegnato</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Da portare</span>
                                        <?php endif ?>
                                    </td>
                                    <td class="text-end">
                                        <form action="<?= base_url('operativo/materiali/' . $m['id'] . '/delete') ?>"
                                              method="post" class="d-inline"
                                              onsubmit="return confirm('Eliminare questo materiale?')">
                                            <?= csrf_field() ?>
                                            <?php if ($from): ?>
                                                <input type="hidden" name="from" value="<?= esc($from) ?>">
                                            <?php endif ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                <?php endif ?>

                <!-- Mini-form aggiunta materiale -->
                <?php $this->setData(['interventoId' => $intervento['id'], 'clienteId' => $intervento['cliente_id']]); ?>
                <?= $this->include('operativo/interventi/_form_materiale') ?>
            </div>

            <!-- Sezione diario — form separati, fuori dal form-update -->
            <div class="card-body border-top pt-4" id="sec-diario">
                <p class="text-muted section-header mb-3"><i class="bi bi-journal-text me-1"></i> Diario</p>

                <?php if (! empty($note)): ?>
                    <ul class="list-group list-group-flush mb-4">
                        <?php foreach ($note as $n): ?>
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-start"
                                title="ID nota: <?= $n['id'] ?>">
                                <div class="me-2">
                                    <span class="badge bg-light text-dark border me-2"><?= esc(date('d/m/Y', strtotime($n['data_nota']))) ?></span>
                                    <span class="text-preline"><?= esc($n['testo']) ?></span>
                                    <?php if (! empty($n['autore'])): ?>
                                        <span class="text-muted small ms-1">— <?= esc($n['autore']) ?></span>
                                    <?php endif ?>
                                </div>
                                <form method="post"
                                      action="<?= base_url('operativo/interventi/note/' . $n['id'] . '/elimina') ?>"
                                      class="d-inline" onsubmit="return confirm('Eliminare questa nota?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="from"
                                           value="<?= esc(base_url('operativo/interventi/' . $intervento['id'] . '/edit') . '#sec-diario') ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Elimina nota">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </li>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>

                <!-- Mini-form aggiunta nota -->
                <form method="post" action="<?= base_url('operativo/interventi/note/aggiungi') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="intervento_id" value="<?= $intervento['id'] ?>">
                    <input type="hidden" name="from"
                           value="<?= esc(base_url('operativo/interventi/' . $intervento['id'] . '/edit') . '#sec-diario') ?>">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small">Data</label>
                            <input type="date" name="data_nota" class="form-control form-control-sm"
                                   value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small">Nota</label>
                            <input type="text" name="testo" class="form-control form-control-sm"
                                   placeholder="Aggiungi una nota al diario…" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-plus-lg me-1"></i>Aggiungi
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <?php $codiceIntervento = $intervento['codice']; ?>
            <div class="card-footer d-flex justify-content-between align-items-center gap-2">
                <form action="<?= base_url('operativo/interventi/' . $intervento['id'] . '/delete') ?>"
                      method="post" class="d-inline"
                      onsubmit="return confirm('Eliminare definitivamente l\'intervento <?= esc($codiceIntervento) ?>?')">
                    <?= csrf_field() ?>
                    <?php if ($from): ?>
                        <input type="hidden" name="from" value="<?= esc($from) ?>">
                    <?php endif ?>
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash me-1"></i>Elimina
                    </button>
                </form>
                <div class="ms-auto d-flex gap-2">
                    <a href="<?= esc($from ?: base_url('operativo/interventi')) ?>"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-lg me-1"></i>Annulla
                    </a>
                    <button type="submit" form="form-update" id="btn-salva-modifiche" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg me-1"></i>Salva modifiche
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/vendor/tom-select/tom-select.bootstrap5.min.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->include('operativo/interventi/_form_materiale_scripts') ?>
<script>
(function () {
    var durateDefault = <?= json_encode($durateDefault) ?>;

    document.getElementById('tipo_intervento_id').addEventListener('change', function () {
        var durata = document.getElementById('durata_stimata');
        if (! durata.value && durateDefault[this.value]) {
            durata.value = durateDefault[this.value];
        }

        var descrizione = document.getElementById('descrizione');
        var opt = this.options[this.selectedIndex];
        if (! descrizione.value && opt && opt.value) {
            descrizione.value = 'Intervento: ' + opt.text;
        }
    });

    // ── Fase stagionale: visibile solo per i tipi di categoria "piscine" ──────
    var selTipo    = document.getElementById('tipo_intervento_id');
    var bloccoFase = document.getElementById('blocco-fase');
    var selFase    = document.getElementById('fase');

    function aggiornaBloccoFase() {
        var opt = selTipo.options[selTipo.selectedIndex];
        var cat = opt ? opt.dataset.categoria : '';
        if (cat === 'piscine') {
            bloccoFase.classList.remove('d-none');
        } else {
            bloccoFase.classList.add('d-none');
            selFase.value = 'ordinaria'; // un tipo non-piscina non invia mai apertura/chiusura
        }
    }

    selTipo.addEventListener('change', aggiornaBloccoFase);
    aggiornaBloccoFase(); // init: mostra il blocco se l'intervento è già di tipo piscina

    // ── Impostando la data pianificata, lo stato passa da "da pianificare" a "pianificato" ──
    document.getElementById('data_pianificata').addEventListener('change', function () {
        var stato = document.getElementById('stato');
        if (this.value && stato.value === 'da_pianificare') {
            stato.value = 'pianificato';
        }
    });

    // ── Blocco: il tecnico assegnato ha un'assenza che copre la data pianificata? ──
    // Il salvataggio viene disabilitato solo se tecnico o data vengono CAMBIATI verso una
    // combinazione in conflitto: un conflitto già presente all'apertura (es. assenza aggiunta
    // dopo la pianificazione) resta visibile ma non impedisce di salvare altre modifiche,
    // coerente col controllo lato server in InterventiController::update().
    var assenzePerDipendente = <?= json_encode($assenzePerDipendente) ?>;
    var tecnicoIniziale  = '<?= esc($intervento['tecnico_id'] ?? '') ?>';
    var dataPianIniziale = '<?= $intervento['data_pianificata'] ? date('Y-m-d\TH:i', strtotime($intervento['data_pianificata'])) : '' ?>';
    var selTecnico  = document.querySelector('select[name="tecnico_id"]');
    var dataPianEl  = document.getElementById('data_pianificata');
    var avvisoAssEl = document.getElementById('avviso-assenza-tecnico');
    var btnSalva    = document.getElementById('btn-salva-modifiche');

    function aggiornaAvvisoAssenzaTecnico() {
        var tecnicoId = selTecnico.value;
        var giorno    = dataPianEl.value ? dataPianEl.value.substring(0, 10) : null;
        var assenze   = tecnicoId ? (assenzePerDipendente[tecnicoId] || []) : [];
        var trovata   = null;

        if (giorno) {
            assenze.forEach(function (a) {
                if (giorno >= a.data_inizio && giorno <= a.data_fine) trovata = a;
            });
        }

        var cambiato = (selTecnico.value !== tecnicoIniziale) || (dataPianEl.value !== dataPianIniziale);

        if (trovata) {
            var nome = selTecnico.options[selTecnico.selectedIndex].text;
            avvisoAssEl.classList.remove('d-none');
            avvisoAssEl.innerHTML = '<i class="bi bi-calendar-x me-1"></i>'
                + '<strong>' + nome + '</strong> risulta assente (' + trovata.tipo_label + ') in questa data.'
                + (cambiato ? ' Scegli un altro tecnico o un\'altra data.' : '');
            btnSalva.disabled = cambiato;
        } else {
            avvisoAssEl.classList.add('d-none');
            avvisoAssEl.innerHTML = '';
            btnSalva.disabled = false;
        }
    }

    selTecnico.addEventListener('change', aggiornaAvvisoAssenzaTecnico);
    dataPianEl.addEventListener('change', aggiornaAvvisoAssenzaTecnico);
    aggiornaAvvisoAssenzaTecnico(); // init: mostra un eventuale conflitto già presente, senza bloccare
})();
</script>
<?= $this->endSection() ?>
