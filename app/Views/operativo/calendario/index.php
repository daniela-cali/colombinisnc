<?php
/**
 * @var array[] $tecnici
 * @var array[] $tipiPerId
 * @var array[] $pool
 * @var array[] $poolPerZona
 * @var array   $zoneLabel
 * @var array[] $scadenze
 * @var string      $oraInizio
 * @var bool        $puoPromemoria
 * @var string|null $dataIniziale
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Calendario<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item active">Calendario</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/calendario.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$prioritaInfo = [
    'urgente'     => ['badge' => 'bg-danger',           'label' => 'Urgente'],
    'normale'     => ['badge' => 'bg-secondary',        'label' => 'Normale'],
    'programmato' => ['badge' => 'bg-primary',          'label' => 'Programmato'],
];
?>

<div id="cal-layout">

    <!-- Pool sidebar: comprimibile a sola icona su desktop, nascosta su mobile -->
    <div id="pool-panel">
        <div class="card card-primary">
            <div class="card-header py-2 d-flex align-items-center justify-content-between" id="pool-header" title="Comprimi / espandi">
                <h3 class="card-title mb-0">
                    <i class="bi bi-inbox me-1"></i>
                    <span class="pool-label">Da pianificare</span>
                    <span class="badge bg-primary ms-1" id="pool-count"><?= count($pool) ?></span>
                </h3>
                <button type="button" class="btn btn-sm p-0 border-0 bg-transparent text-reset pool-label" id="btn-pool-toggle" aria-label="Comprimi pannello">
                    <i class="bi bi-chevron-double-left"></i>
                </button>
            </div>
            <div class="card-body p-2">
                <p class="small text-muted mb-2 px-1">
                    <i class="bi bi-arrows-move me-1"></i>Trascina una card sul calendario
                </p>
                <div id="pool-container">
                    <?php if (empty($pool)): ?>
                    <div class="pool-empty">
                        <i class="bi bi-check-circle text-success d-block mb-2" style="font-size:2rem;opacity:.5;"></i>
                        Tutti pianificati
                    </div>
                    <?php else: ?>
                    <?php
                    $zonaStyleMap = [
                        -1       => ['bg' => 'warning',   'text' => 'dark'],
                         0       => ['bg' => 'success',   'text' => 'white'],
                         1       => ['bg' => 'primary',   'text' => 'white'],
                        'nessuna'=> ['bg' => 'secondary', 'text' => 'white'],
                    ];
                    foreach ($poolPerZona as $zonaKey => $invPerZona):
                        $zonaNome   = $zoneLabel[$zonaKey] ?? 'Senza zona';
                        $zonaStyle  = $zonaStyleMap[$zonaKey] ?? ['bg' => 'secondary', 'text' => 'white'];
                        $collapseId = 'pool-zona-' . (is_int($zonaKey) ? ($zonaKey + 2) : 'nessuna');
                    ?>
                    <div class="mb-1">
                        <a class="d-flex align-items-center justify-content-between px-2 py-1 bg-<?= $zonaStyle['bg'] ?> text-<?= $zonaStyle['text'] ?>"
                           data-bs-toggle="collapse" href="#<?= $collapseId ?>"
                           style="border-radius:4px;text-decoration:none;">
                            <span class="small fw-bold">
                                <i class="bi bi-geo-alt me-1"></i>Zona <?= esc($zonaNome) ?>
                            </span>
                            <span class="badge bg-black bg-opacity-25"><?= count($invPerZona) ?></span>
                        </a>
                        <div class="collapse pool-zone show" id="<?= $collapseId ?>">
                        <?php foreach ($invPerZona as $i):
                            $tipoInfo = $tipiPerId[(int) ($i['tipo_intervento_id'] ?? 0)]
                                     ?? ['nome' => 'Senza tipo', 'icona' => 'bi-tools', 'durata_default' => 60];
                            if ($i['urgenza']) {
                                $badge = 'bg-danger'; $badgeLabel = 'Urgente';
                            } else {
                                $pi = $prioritaInfo[$i['priorita']] ?? ['badge' => 'bg-secondary', 'label' => $i['priorita']];
                                $badge = $pi['badge']; $badgeLabel = $pi['label'];
                            }
                        ?>
                        <div class="pool-card <?= esc($i['urgenza'] ? 'urgente' : ($i['priorita'] ?? 'normale')) ?>"
                             data-id="<?= $i['id'] ?>"
                             data-tipo-id="<?= (int) ($i['tipo_intervento_id'] ?? 0) ?>"
                             data-cliente="<?= htmlspecialchars($i['cliente_denominazione'] ?? '', ENT_QUOTES) ?>"
                             data-durata="<?= (int) ($i['durata_stimata'] ?: $tipoInfo['durata_default']) ?>"
                             data-tipo-nome="<?= htmlspecialchars($tipoInfo['nome'], ENT_QUOTES) ?>"
                             data-descr="<?= htmlspecialchars($i['descrizione'] ?? '', ENT_QUOTES) ?>"
                             data-scadenza="<?= esc($i['data_scadenza'] ?? '') ?>"
                             data-urgenza="<?= (int) ($i['urgenza'] ?? 0) ?>">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div class="d-flex gap-1">
                                    <span class="badge <?= esc($badge) ?>" style="font-size:.65rem;"><?= esc($badgeLabel) ?></span>
                                    <?php if (! empty($i['extra'])): ?>
                                    <span class="badge bg-warning text-dark" style="font-size:.65rem;">Extra</span>
                                    <?php endif ?>
                                </div>
                                <small class="text-muted">
                                    <?php if (!empty($i['data_scadenza'])): ?>
                                        <i class="bi bi-clock" style="font-size:.65rem;"></i> <?= date('d/m', strtotime($i['data_scadenza'])) ?> ·
                                    <?php endif; ?>
                                    #<?= $i['id'] ?>
                                    <?php if (!empty($i['distanza_sede'])): ?>
                                        · <?= number_format((float)$i['distanza_sede'], 1) ?> km
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="fw-bold small mb-1 text-truncate">
                                <?= !empty($i['cliente_denominazione']) ? esc($i['cliente_denominazione']) : '<em class="text-muted">Senza cliente</em>' ?>
                            </div>
                            <?php if (!empty($i['cliente_citta'])): ?>
                            <div class="small text-muted mb-1 text-truncate">
                                <i class="bi bi-geo-alt me-1"></i><?= esc($i['cliente_citta']) ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($i['descrizione'])): ?>
                            <div class="small text-muted text-truncate" style="font-size:.74rem;">
                                <?= esc(mb_strimwidth($i['descrizione'], 0, 55, '…')) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; // interventi per zona ?>
                        </div>
                    </div>
                    <?php endforeach; // zone ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="resize-handle" title="Trascina per ridimensionare"></div>

    <!-- Colonna calendario -->
    <div id="cal-column">

        <!-- Filtro tecnici + genera viaggio -->
        <div class="mb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex flex-wrap gap-1">
                <button type="button" class="btn btn-sm btn-secondary btn-filtro active" data-id="">
                    <i class="bi bi-people me-1"></i>Tutti
                </button>
                <?php foreach ($tecnici as $t): ?>
                <button type="button" class="btn btn-sm btn-filtro"
                        data-id="<?= $t['id'] ?>"
                        data-colore="<?= esc($t['colore'] ?: '#6c757d') ?>"
                        style="background:<?= esc($t['colore'] ?: '#6c757d') ?>;border-color:<?= esc($t['colore'] ?: '#6c757d') ?>;color:#fff;">
                    <?= esc(trim($t['cognome'] . ' ' . $t['nome'])) ?>
                </button>
                <?php endforeach; ?>
            </div>

            <form method="post" action="<?= base_url('operativo/calendario/genera-viaggio') ?>"
                  class="d-flex align-items-center gap-2"
                  target="<?= strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'Mobile') === false ? '_blank' : '_self' ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="tecnico_id" id="form-tecnico-giornata" value="">
                <input type="hidden" name="data"       id="form-data-giornata"    value="">
                <small id="label-data-giornata" class="text-muted">Clicca un giorno sul calendario</small>
                <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap" id="btn-genera-viaggio" disabled>
                    <i class="bi bi-map me-1"></i>Genera viaggio
                </button>
            </form>
        </div>

        <?php if (!empty($scadenze)): ?>
        <div class="alert alert-warning py-2 mb-2 d-flex align-items-center flex-wrap gap-2">
            <small class="fw-bold text-nowrap" style="cursor:help;"
                   data-bs-toggle="tooltip" data-bs-placement="top"
                   data-bs-title="Interventi con una data di scadenza ancora da completare: tutti quelli singoli e, dagli abbonamenti, solo quelli in scadenza entro questo mese. Clicca un badge per aprire l'intervento.">
                <i class="bi bi-clock me-1"></i>Scadenze aperte:
                <i class="bi bi-info-circle ms-1"></i>
            </small>
            <?php foreach ($scadenze as $s): ?>
            <a href="<?= base_url('operativo/interventi/' . $s['id']) ?>"
               class="badge bg-light border fw-normal text-dark text-decoration-none">
                <?= esc($s['cliente_denominazione'] ?: '—') ?>
                <span class="text-muted">· <?= date('d/m', strtotime($s['data_scadenza'])) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body p-2 p-md-3">
                <div id="calendario"></div>
            </div>
        </div>

    </div>
</div>

<!-- Modal dettaglio intervento (click su evento FC) -->
<div class="modal fade" id="modalIntervento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-tools me-2" id="modal-icona"></i>
                    <span id="modal-cliente"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted fw-normal small">Tipo</dt>
                    <dd class="col-sm-8" id="modal-tipo"></dd>
                    <dt class="col-sm-4 text-muted fw-normal small">Tecnico</dt>
                    <dd class="col-sm-8" id="modal-tecnico"></dd>
                    <dt class="col-sm-4 text-muted fw-normal small">Data</dt>
                    <dd class="col-sm-8" id="modal-data"></dd>
                    <dt class="col-sm-4 text-muted fw-normal small">Stato</dt>
                    <dd class="col-sm-8" id="modal-stato"></dd>
                    <dt class="col-sm-4 text-muted fw-normal small" id="modal-scadenza-label" style="display:none;">Scadenza</dt>
                    <dd class="col-sm-8" id="modal-scadenza" style="display:none;"></dd>
                </dl>
                <div id="modal-descrizione-wrap" class="mt-3 pt-3 border-top" style="display:none;">
                    <p class="small text-muted mb-1">Descrizione</p>
                    <p id="modal-descrizione" class="mb-0" style="white-space:pre-wrap;font-size:.9rem;"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Chiudi</button>
                <a href="#" id="modal-btn-modifica" class="btn btn-warning btn-sm">
                    <i class="bi bi-pencil me-1"></i>Modifica
                </a>
                <a href="#" id="modal-btn-apri" class="btn btn-primary btn-sm">
                    <i class="bi bi-eye me-1"></i>Apri scheda
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal pianifica (drop dal pool) -->
<div class="modal fade" id="modal-pianifica" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success py-2">
                <h5 class="modal-title text-white">
                    <i class="bi bi-calendar-day me-1"></i>
                    Pianifica — <span id="pian-giorno"></span>
                </h5>
            </div>
            <div class="modal-body">
                <p class="mb-0 fw-bold" id="pian-cliente"></p>
                <p class="small text-muted mb-2" id="pian-tipo"></p>
                <div id="pian-avviso-scadenza" class="alert py-2 mb-3 d-none" role="alert"></div>
                <div class="row g-2">
                    <div class="col-sm-5 mb-2">
                        <label class="small">Orario <span class="text-danger">*</span></label>
                        <input type="time" id="pian-ora" class="form-control form-control-sm" step="900">
                    </div>
                </div>
                <div class="mb-0">
                    <label class="small">Tecnico <span class="text-muted fw-normal">(opzionale)</span></label>
                    <select id="pian-tecnico" class="form-select form-select-sm"></select>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" id="btn-pian-annulla">Annulla</button>
                <button type="button" class="btn btn-success btn-sm" id="btn-pian-conferma">
                    <i class="bi bi-check-lg me-1"></i>Pianifica
                </button>
            </div>
        </div>
    </div>
</div>

<?php if ($puoPromemoria): ?>
<div class="modal fade" id="modalPromemoria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formPromemoria" method="post" action="">
                <?= csrf_field() ?>
                <div class="modal-header bg-info py-2">
                    <h5 class="modal-title text-white">
                        <i class="bi bi-bell me-1"></i><span id="prom-modal-titolo">Nuovo promemoria</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Titolo <span class="text-danger">*</span></label>
                            <input type="text" name="titolo" id="prom-titolo" class="form-control"
                                   maxlength="150" placeholder="Es. Cliente arriva/chiede di aprire" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Inizio <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="data_ora_inizio" id="prom-inizio" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Fine</label>
                            <input type="datetime-local" name="data_ora_fine" id="prom-fine" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Note</label>
                            <textarea name="note" id="prom-note" class="form-control" rows="3"
                                      placeholder="Dettagli facoltativi"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-danger btn-sm me-auto d-none" id="prom-btn-elimina">
                        <i class="bi bi-trash me-1"></i>Elimina
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Salva</button>
                </div>
            </form>
            <form id="formPromemoriaDelete" method="post" action="" class="d-none"><?= csrf_field() ?></form>
        </div>
    </div>
</div>
<?php endif ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/fullcalendar/index.global.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/fullcalendar/locales/it.global.min.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var _csrfToken = '<?= csrf_token() ?>';
    var _csrfHash  = '<?= csrf_hash() ?>';
    var oraInizio  = '<?= esc($oraInizio) ?>';
    var filtroTecnico = '';

    var tecnici = <?= json_encode(array_map(fn($t) => [
        'id'      => $t['id'],
        'nome'    => $t['nome'],
        'cognome' => $t['cognome'],
    ], $tecnici)) ?>;

    var from = encodeURIComponent('<?= base_url('operativo/calendario') ?>');

    // ---- Giorno selezionato (Genera viaggio) ----
    function selezionaData(dateStr) {
        document.querySelectorAll('.fc-day-selected').forEach(function (el) {
            el.classList.remove('fc-day-selected');
        });
        document.querySelectorAll('[data-date="' + dateStr + '"]').forEach(function (el) {
            el.classList.add('fc-day-selected');
        });
        document.getElementById('form-data-giornata').value = dateStr;
        var p = dateStr.split('-');
        document.getElementById('label-data-giornata').textContent = p[2] + '/' + p[1] + '/' + p[0];
        document.getElementById('btn-genera-viaggio').disabled = false;
    }

    document.getElementById('calendario').addEventListener('click', function (e) {
        var cell = e.target.closest('.fc-col-header-cell[data-date]');
        if (cell) selezionaData(cell.dataset.date);
    });

    // ---- Filtro tecnici ----
    document.querySelectorAll('.btn-filtro').forEach(function (btn) {
        btn.addEventListener('click', function () {
            filtroTecnico = this.dataset.id;
            document.getElementById('form-tecnico-giornata').value = filtroTecnico;
            document.querySelectorAll('.btn-filtro').forEach(function (b) {
                if (b.dataset.id === '') {
                    b.classList.toggle('btn-secondary',         b.dataset.id === filtroTecnico);
                    b.classList.toggle('btn-outline-secondary', b.dataset.id !== filtroTecnico);
                } else {
                    b.style.opacity = (b.dataset.id === filtroTecnico || filtroTecnico === '') ? '1' : '.4';
                }
                b.classList.toggle('active', b.dataset.id === filtroTecnico);
            });
            calendar.refetchEvents();
        });
    });

    // ---- Tecnico select nel modal pianifica ----
    function buildTecnicoSelect(selectEl) {
        var html = '<option value="">— Non assegnato —</option>';
        tecnici.forEach(function (t) {
            html += '<option value="' + t.id + '">' + t.cognome + ' ' + t.nome + '</option>';
        });
        selectEl.innerHTML = html;
    }

    // ---- Pool Draggable ----
    new FullCalendar.Draggable(document.getElementById('pool-container'), {
        itemSelector: '.pool-card',
        eventData: function (cardEl) {
            return {
                id:       'pool-' + cardEl.dataset.id,
                title:    cardEl.dataset.cliente || ('#' + cardEl.dataset.id),
                duration: { minutes: parseInt(cardEl.dataset.durata) || 60 },
            };
        },
    });

    // ---- Modal pianifica ----
    var modalPianificaEl = document.getElementById('modal-pianifica');
    var modalPianifica   = new bootstrap.Modal(modalPianificaEl, { backdrop: 'static' });
    var pianTecnicoEl    = document.getElementById('pian-tecnico');
    var pianOraEl        = document.getElementById('pian-ora');

    var pendingCard    = null;
    var pendingDateStr = null;

    function showModalPianifica(cardEl, dateStr, timeStr) {
        pendingCard    = cardEl;
        pendingDateStr = dateStr;

        var d      = new Date(dateStr + 'T12:00:00');
        var giorni = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
        document.getElementById('pian-giorno').textContent  = giorni[d.getDay()] + ' ' +
            String(d.getDate()).padStart(2, '0') + '/' + String(d.getMonth() + 1).padStart(2, '0');
        document.getElementById('pian-cliente').textContent = cardEl.dataset.cliente || '—';
        document.getElementById('pian-tipo').textContent    = cardEl.dataset.tipoNome || '';
        pianOraEl.value = timeStr || oraInizio;
        buildTecnicoSelect(pianTecnicoEl);

        // Avviso scadenza: se la data del drop è successiva alla data_scadenza dell'intervento.
        var avvisoEl  = document.getElementById('pian-avviso-scadenza');
        var scadenza  = cardEl.dataset.scadenza;
        var urgente   = cardEl.dataset.urgenza === '1';
        if (scadenza && dateStr > scadenza) {
            var sc = scadenza.split('-');
            var scLabel = sc[2] + '/' + sc[1] + '/' + sc[0];
            if (urgente) {
                avvisoEl.className = 'alert alert-danger py-2 mb-3';
                avvisoEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>'
                    + '<strong>Urgente — scadenza superata.</strong> La scadenza era il ' + scLabel
                    + '. Puoi comunque procedere.';
            } else {
                avvisoEl.className = 'alert alert-warning py-2 mb-3';
                avvisoEl.innerHTML = '<i class="bi bi-clock me-1"></i>'
                    + 'Attenzione: la scadenza era il ' + scLabel + '. Puoi comunque procedere.';
            }
        } else {
            avvisoEl.className = 'alert py-2 mb-3 d-none';
            avvisoEl.innerHTML = '';
        }

        modalPianifica.show();
    }

    modalPianificaEl.addEventListener('hide.bs.modal', function () {
        pendingCard = pendingDateStr = null;
    });

    document.getElementById('btn-pian-annulla').addEventListener('click', function () {
        modalPianifica.hide();
    });

    document.getElementById('btn-pian-conferma').addEventListener('click', function () {
        if (!pendingCard || !pendingDateStr) return;
        var card     = pendingCard;
        var ora      = pianOraEl.value || oraInizio;
        var dataPian = pendingDateStr + 'T' + ora;
        var btnConf  = this;

        btnConf.disabled  = true;
        btnConf.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Salvo…';

        var fd = new FormData();
        fd.append('data_pianificata', dataPian);
        var tecnicoAsseg = pianTecnicoEl.value;
        if (tecnicoAsseg) fd.append('tecnico_id', tecnicoAsseg);

        fetch('<?= base_url('operativo/interventi/') ?>' + card.dataset.id + '/pianifica', {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': _csrfHash },
            body:    fd,
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            btnConf.disabled  = false;
            btnConf.innerHTML = '<i class="bi bi-check-lg me-1"></i>Pianifica';
            if (!json.ok) { alert(json.msg || 'Errore durante il salvataggio.'); return; }
            if (json.csrf) _csrfHash = json.csrf;
            modalPianifica.hide();
            // Rimuove la card dal pool e aggiorna i contatori
            var group = card.closest('.mb-1');
            if (group) {
                var groupBadge = group.querySelector('a .badge');
                if (groupBadge) {
                    var gc = parseInt(groupBadge.textContent) - 1;
                    if (gc <= 0) { group.remove(); }
                    else { groupBadge.textContent = gc; }
                }
            }
            card.remove();
            var countEl = document.getElementById('pool-count');
            var n = parseInt(countEl.textContent || '1') - 1;
            countEl.textContent = n;
            if (n <= 0) {
                document.getElementById('pool-container').innerHTML =
                    '<div class="pool-empty">'
                    + '<i class="bi bi-check-circle text-success d-block mb-2" style="font-size:2rem;opacity:.5;"></i>'
                    + 'Tutti pianificati</div>';
            }
            calendar.refetchEvents();
        })
        .catch(function () {
            btnConf.disabled  = false;
            btnConf.innerHTML = '<i class="bi bi-check-lg me-1"></i>Pianifica';
            alert('Errore di rete.');
        });
    });

    // ---- Modal dettaglio intervento ----
    var modalIntervento = new bootstrap.Modal(document.getElementById('modalIntervento'));

    // ---- FullCalendar ----
    var isMobile = window.innerWidth < 768;
    var puoPromemoria = <?= $puoPromemoria ? 'true' : 'false' ?>;
    var calendar = new FullCalendar.Calendar(document.getElementById('calendario'), {
        locale: 'it',
        initialView: isMobile ? 'timeGridDay' : 'timeGridWeek',
<?php if ($dataIniziale): ?>
        initialDate: '<?= $dataIniziale ?>',
<?php endif ?>
<?php if ($puoPromemoria): ?>
        customButtons: {
            nuovoPromemoria: {
                text: '+ Promemoria',
                click: function () { openPromemoriaModalNew(); },
            },
        },
<?php endif ?>
        headerToolbar: isMobile ? {
            left:   'prev,next',
            center: 'title',
            right:  '',
        } : {
            left:   'prev,next today',
            center: 'title',
            right:  '<?= $puoPromemoria ? 'nuovoPromemoria ' : '' ?>timeGridDay,timeGridWeek,dayGridMonth',
        },
        buttonText: { today: 'Oggi', day: 'Giorno', week: 'Settimana', month: 'Mese' },
        slotMinTime:    '07:00:00',
        slotMaxTime:    '20:00:00',
        slotDuration:   '00:30:00',
        allDaySlot:     false,
        nowIndicator:   true,
        height:         'auto',
        editable:       true,
        droppable:      true,
        longPressDelay: 300,
        eventDragStart: function () { document.body.classList.add('fc-dragging'); },
        eventDragStop:  function () { document.body.classList.remove('fc-dragging'); },
        eventDrop: function (info) {
            var dt  = info.event.start;
            var pad = function (n) { return String(n).padStart(2, '0'); };
            var startLocal = dt.getFullYear() + '-' + pad(dt.getMonth() + 1) + '-' + pad(dt.getDate())
                           + ' ' + pad(dt.getHours()) + ':' + pad(dt.getMinutes()) + ':00';
            var fd = new FormData();
            fd.append('id', info.event.id);
            fd.append('start', startLocal);
            fetch('<?= base_url('operativo/calendario/sposta') ?>', {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': _csrfHash },
                body:    fd,
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.csrf) _csrfHash = res.csrf;
                if (!res || !res.ok) { info.revert(); alert('Errore nel salvataggio.'); }
            })
            .catch(function () { info.revert(); alert('Errore di rete.'); });
        },
        eventReceive: function (info) {
            var cardEl  = info.draggedEl;
            var start   = info.event.start;
            var pad     = function (n) { return String(n).padStart(2, '0'); };
            var dateStr = start.getFullYear() + '-' + pad(start.getMonth() + 1) + '-' + pad(start.getDate());
            var timeStr = pad(start.getHours()) + ':' + pad(start.getMinutes());
            info.event.remove();
            showModalPianifica(cardEl, dateStr, timeStr);
        },
        events: function (fetchInfo, successCallback, failureCallback) {
            var url = '<?= base_url('operativo/calendario/eventi') ?>'
                    + '?start=' + fetchInfo.startStr.substring(0, 10)
                    + '&end='   + fetchInfo.endStr.substring(0, 10);
            if (filtroTecnico) url += '&tecnico_id=' + filtroTecnico;
            fetch(url)
                .then(function (r) { return r.json(); })
                .then(successCallback)
                .catch(failureCallback);
        },
        eventDidMount: function (info) {
            var p   = info.event.extendedProps;
            var tip = [info.event.title, p.citta, p.tecnico, p.tipo].filter(Boolean).join(' · ');
            new bootstrap.Tooltip(info.el, { title: tip, placement: 'top', trigger: 'hover', container: 'body' });
            info.el.addEventListener('click', function () {
                var tooltip = bootstrap.Tooltip.getInstance(info.el);
                if (tooltip) tooltip.hide();
            });
        },
        eventWillUnmount: function (info) {
            var tooltip = bootstrap.Tooltip.getInstance(info.el);
            if (tooltip) tooltip.dispose();
        },
        eventClick: function (info) {
            info.jsEvent.preventDefault();
            var p   = info.event.extendedProps;

            // Promemoria: apre il modal di modifica (solo per chi può gestirli).
            if (p.tipo_evento === 'promemoria') {
                if (puoPromemoria) openPromemoriaModalEdit(info.event);
                return;
            }

            var url = info.event.url;

            var badgeClass = { da_pianificare: 'bg-secondary', pianificato: 'bg-primary', in_corso: 'bg-warning text-dark', completato: 'bg-success', annullato: 'bg-danger' };
            var statoLabel = { da_pianificare: 'Da pianificare', pianificato: 'Pianificato', in_corso: 'In corso', completato: 'Completato', annullato: 'Annullato' };

            var start   = info.event.start;
            var dataFmt = start
                ? start.toLocaleDateString('it-IT', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })
                + ' alle ' + start.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' })
                : '—';

            var iconaClass = p.icona || 'bi-tools';
            document.getElementById('modal-icona').className = 'bi ' + iconaClass + ' me-2';
            document.getElementById('modal-cliente').textContent = info.event.title;
            document.getElementById('modal-header').style.borderLeft = '4px solid ' + (info.event.backgroundColor || '#6c757d');
            document.getElementById('modal-tipo').textContent    = p.tipo || '—';
            document.getElementById('modal-tecnico').textContent = p.tecnico || 'Non assegnato';
            document.getElementById('modal-data').textContent    = dataFmt;
            document.getElementById('modal-stato').innerHTML     = '<span class="badge ' + (badgeClass[p.stato] || 'bg-secondary') + '">' + (statoLabel[p.stato] || p.stato) + '</span>';
            document.getElementById('modal-descrizione').textContent = p.descrizione || '';
            document.getElementById('modal-descrizione-wrap').style.display = p.descrizione ? '' : 'none';
            if (p.data_scadenza) {
                var ep = p.data_scadenza.split('-');
                document.getElementById('modal-scadenza').textContent = ep[2] + '/' + ep[1] + '/' + ep[0];
                document.getElementById('modal-scadenza').style.display       = '';
                document.getElementById('modal-scadenza-label').style.display = '';
            } else {
                document.getElementById('modal-scadenza').style.display       = 'none';
                document.getElementById('modal-scadenza-label').style.display = 'none';
            }
            document.getElementById('modal-btn-apri').href     = url + '?from=' + from;
            document.getElementById('modal-btn-modifica').href = url + '/edit?from=' + from;
            modalIntervento.show();
        },
        dateClick: function (info) {
            selezionaData(info.dateStr.substring(0, 10));
        },
        eventContent: function (info) {
            var p        = info.event.extendedProps;

            // Promemoria: campanella + titolo, senza il bottone × di rimozione pianificazione.
            if (p.tipo_evento === 'promemoria') {
                return { html: '<div style="padding:2px 4px;line-height:1.25;overflow:hidden;">'
                    + '<div style="font-size:.78rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'
                    + '<i class="bi bi-bell-fill" style="margin-right:3px;opacity:.85;"></i>'
                    + info.timeText + ' &nbsp;' + info.event.title
                    + '</div></div>' };
            }

            var time     = info.timeText;
            var iconaCls = (p.icona || 'bi-tools');
            function fmtDd(s) { var pp = s.split('-'); return pp[2] + '/' + pp[1]; }
            var html = '<div style="position:relative;padding:2px 4px;overflow:hidden;line-height:1.25;">'
                     + '<div style="font-size:.78rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;padding-right:14px;">'
                     +   '<i class="bi ' + iconaCls + '" style="margin-right:3px;opacity:.8;"></i>'
                     +   time + ' &nbsp;' + info.event.title
                     + '</div>'
                     + '<div style="font-size:.72rem;opacity:.8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'
                     +   (p.tecnico || '')
                     +   (p.citta ? ' · ' + p.citta : '')
                     +   (p.data_scadenza ? ' · <i class="bi bi-clock" style="font-size:.65rem;"></i> ' + fmtDd(p.data_scadenza) : '')
                     + '</div>'
                     + '<button class="btn-rimuovi-pian" data-id="' + info.event.id + '"'
                     + ' style="position:absolute;top:1px;right:2px;background:none;border:none;'
                     + 'color:rgba(255,255,255,.8);font-size:.95rem;padding:0 2px;line-height:1;cursor:pointer;"'
                     + ' title="Rimuovi pianificazione">&times;</button>'
                     + '</div>';
            return { html: html };
        },
    });
    calendar.render();

<?php if ($puoPromemoria): ?>
    // ---- Promemoria (modal create/edit/delete dal calendario) ----
    var modalPromemoria = new bootstrap.Modal(document.getElementById('modalPromemoria'));
    var formProm        = document.getElementById('formPromemoria');
    var formPromDelete  = document.getElementById('formPromemoriaDelete');
    var promBtnElimina  = document.getElementById('prom-btn-elimina');

    function openPromemoriaModalNew() {
        formProm.action = '<?= base_url('promemoria/store') ?>';
        document.getElementById('prom-modal-titolo').textContent = 'Nuovo promemoria';
        document.getElementById('prom-titolo').value = '';
        // Precompila l'inizio con la giornata selezionata sul calendario (se presente),
        // con un'orario di default alle 09:00; altrimenti lascia il campo vuoto.
        var giornata = document.getElementById('form-data-giornata').value;
        document.getElementById('prom-inizio').value = giornata ? giornata + 'T09:00' : '';
        document.getElementById('prom-fine').value   = '';
        document.getElementById('prom-note').value   = '';
        promBtnElimina.classList.add('d-none');
        modalPromemoria.show();
    }

    function openPromemoriaModalEdit(event) {
        var p  = event.extendedProps;
        var id = p.prom_id;
        formProm.action       = '<?= base_url('promemoria') ?>/' + id + '/update';
        formPromDelete.action = '<?= base_url('promemoria') ?>/' + id + '/delete';
        document.getElementById('prom-modal-titolo').textContent = 'Modifica promemoria';
        document.getElementById('prom-titolo').value = p.titolo || '';
        document.getElementById('prom-inizio').value = (p.inizio || '').replace(' ', 'T').substring(0, 16);
        document.getElementById('prom-fine').value   = p.fine ? p.fine.replace(' ', 'T').substring(0, 16) : '';
        document.getElementById('prom-note').value   = p.note || '';
        promBtnElimina.classList.remove('d-none');
        modalPromemoria.show();
    }

    promBtnElimina.addEventListener('click', function () {
        if (confirm('Eliminare questo promemoria?')) formPromDelete.submit();
    });
<?php endif ?>

    // ---- Rimozione pianificazione (bottone × sugli eventi) ----
    document.getElementById('calendario').addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-rimuovi-pian');
        if (!btn) return;
        e.stopPropagation();
        e.preventDefault();
        if (!confirm('Rimuovere questo intervento dalla pianificazione?\nTornerà nella coda "Da pianificare".')) return;
        var interventoId = btn.dataset.id;
        fetch('<?= base_url('operativo/interventi/') ?>' + interventoId + '/annulla-pianificazione', {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': _csrfHash },
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            if (json.csrf) _csrfHash = json.csrf;
            if (json.ok) { window.location.reload(); }
            else { alert('Errore: ' + (json.msg || 'riprovare.')); }
        })
        .catch(function () { alert('Errore di rete.'); });
    }, true);

    // ---- Sidebar ridimensionabile ----
    var poolPanel    = document.getElementById('pool-panel');
    var resizeHandle = document.getElementById('resize-handle');
    var savedW       = localStorage.getItem('pool-sidebar-width');
    var startMini    = localStorage.getItem('pool-mini') === '1';
    // In mini la larghezza è data dal CSS (auto): non applicare l'inline width,
    // altrimenti vincerebbe sul foglio di stile e il pannello resterebbe largo.
    if (savedW && !startMini) poolPanel.style.width = savedW + 'px';
    var resizing = false, startX, startW;
    resizeHandle.addEventListener('mousedown', function (e) {
        resizing = true; startX = e.clientX; startW = poolPanel.offsetWidth;
        document.body.style.cursor     = 'col-resize';
        document.body.style.userSelect = 'none';
    });
    document.addEventListener('mousemove', function (e) {
        if (!resizing) return;
        var w = Math.max(180, Math.min(450, startW + e.clientX - startX));
        poolPanel.style.width = w + 'px';
    });
    document.addEventListener('mouseup', function () {
        if (!resizing) return;
        resizing = false;
        document.body.style.cursor     = '';
        document.body.style.userSelect = '';
        localStorage.setItem('pool-sidebar-width', poolPanel.offsetWidth);
    });

    // ---- Pool comprimibile a sola icona (toggle sull'header) ----
    var poolHeader = document.getElementById('pool-header');
    if (poolHeader) {
        if (startMini) {
            poolPanel.classList.add('pool-mini');
            calendar.updateSize();
        }
        poolHeader.addEventListener('click', function () {
            var mini = poolPanel.classList.toggle('pool-mini');
            localStorage.setItem('pool-mini', mini ? '1' : '0');
            // Comprimendo lascio decidere il CSS (width auto); riespandendo
            // ripristino la larghezza salvata dal resize, se presente.
            if (mini) {
                poolPanel.style.width = '';
            } else {
                var w = localStorage.getItem('pool-sidebar-width');
                poolPanel.style.width = w ? w + 'px' : '';
            }
            calendar.updateSize();
        });
    }
});
</script>
<?= $this->endSection() ?>
