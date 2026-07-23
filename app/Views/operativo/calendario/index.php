<?php
/**
 * @var array[] $tecnici
 * @var array[] $tipiPerId
 * @var array[] $materialiPerIntervento
 * @var int $totaleDaPianificare
 * @var array $totaliPerZona Map zona => totale interventi
 * @var array $poolPerZona Map zona => blocchi[] (ciascuno: key, label, icona, interventi[])
 * @var array   $zoneLabel
 * @var array $scadenzePerMotivo Map motivo (mancato/ritardo/fermo) => righe[] da
 *      InterventiModel::scadenzeInRitardo() (id, data_scadenza, stato, data_pianificata,
 *      created_at, cliente_denominazione, motivo, giorni)
 * @var string      $oraInizio
 * @var bool        $puoPromemoria
 * @var string|null $dataIniziale
 * @var array       $assenzePerDipendente Map personale_id => [['data_inizio','data_fine','tipo_label'], ...]
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

<div id="cal-layout">

    <!-- Pool sidebar: comprimibile a sola icona su desktop, nascosta su mobile -->
    <div id="pool-panel">
        <div class="card card-primary">
            <div class="card-header py-2 d-flex align-items-center justify-content-between" id="pool-header" title="Comprimi / espandi">
                <h3 class="card-title mb-0">
                    <i class="bi bi-inbox me-1"></i>
                    <span class="pool-label">Da pianificare</span>
                    <span class="badge bg-primary ms-1" id="pool-count"><?= $totaleDaPianificare ?></span>
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
                    <?= view('operativo/calendario/_pool', [
                        'totaleDaPianificare'    => $totaleDaPianificare,
                        'poolPerZona'            => $poolPerZona,
                        'totaliPerZona'          => $totaliPerZona,
                        'zoneLabel'              => $zoneLabel,
                        'tipiPerId'              => $tipiPerId,
                        'materialiPerIntervento' => $materialiPerIntervento,
                    ]) ?>
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
                        style="background:<?= esc($t['colore'] ?: '#6c757d') ?>;border-color:<?= esc($t['colore'] ?: '#6c757d') ?>;color:<?= colore_testo($t['colore'] ?: '#6c757d') ?>;">
                    <?= esc(trim($t['cognome'] . ' ' . $t['nome'])) ?>
                </button>
                <?php endforeach; ?>
            </div>

            <form method="post" action="<?= base_url('operativo/calendario/genera-viaggio') ?>"
                  class="d-flex align-items-center gap-2"
                  target="<?= //Se da mobile, carica su self, altrimenti nuova pagina blank
                  strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'Mobile') === false ? '_blank' : '_self' ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="tecnico_id" id="form-tecnico-giornata" value="">
                <input type="hidden" name="data"       id="form-data-giornata"    value="">
                <small id="label-data-giornata" class="text-muted">Clicca un giorno sul calendario</small>
                <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap" id="btn-genera-viaggio" disabled>
                    <i class="bi bi-map me-1"></i>Genera viaggio
                </button>
            </form>
        </div>

        <?php if (array_sum(array_map('count', $scadenzePerMotivo))):
            $motivoInfo = [
                'mancato' => ['label' => 'Non completato', 'badge' => 'badge-scadenza-mancato', 'icona' => 'bi-calendar-x',
                              'tooltip' => 'Interventi pianificati con data ormai passata, mai completati né annullati.'],
                'ritardo' => ['label' => 'In ritardo',     'badge' => 'badge-scadenza-ritardo', 'icona' => 'bi-clock',
                              'tooltip' => 'Interventi con la data di scadenza superata, qualunque sia lo stato.'],
                'fermo'   => ['label' => 'Fermo',          'badge' => 'badge-scadenza-fermo',   'icona' => 'bi-hourglass-split',
                              'tooltip' => 'Interventi da pianificare, inseriti da più di 7 giorni e senza una scadenza già superata.'],
            ];
        ?>
        <div class="alert alert-warning py-2 mb-2" id="barra-scadenze">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <small class="fw-bold text-nowrap">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Attenzione
                </small>
                <?php foreach ($motivoInfo as $motivo => $info):
                    if (empty($scadenzePerMotivo[$motivo])) continue;
                ?>
                <a class="badge scadenza-pill <?= $info['badge'] ?> fw-normal text-decoration-none"
                   data-bs-toggle="collapse" href="#scadenze-<?= $motivo ?>"
                   data-bs-placement="top" data-bs-title="<?= esc($info['tooltip']) ?>">
                    <i class="bi <?= $info['icona'] ?> me-1"></i><?= $info['label'] ?>
                    <span class="badge bg-black bg-opacity-25 ms-1"><?= count($scadenzePerMotivo[$motivo]) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php foreach ($motivoInfo as $motivo => $info):
                if (empty($scadenzePerMotivo[$motivo])) continue;
            ?>
            <div class="collapse mt-2" id="scadenze-<?= $motivo ?>">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($scadenzePerMotivo[$motivo] as $s):
                        $giorniLabel = $s['giorni'] . ' giorn' . ($s['giorni'] == 1 ? 'o' : 'i');
                        if ($motivo === 'mancato') {
                            $tooltip      = 'Appuntamento del ' . date('d/m', strtotime($s['data_pianificata'])) . ' risulta non completato';
                            $dataMostrata = date('d/m', strtotime($s['data_pianificata']));
                        } elseif ($motivo === 'ritardo') {
                            $tooltip      = 'In ritardo di ' . $giorniLabel;
                            $dataMostrata = date('d/m', strtotime($s['data_scadenza']));
                        } else {
                            $tooltip      = 'Fermo da ' . $giorniLabel;
                            $dataMostrata = null;
                        }
                    ?>
                    <a href="<?= base_url('operativo/interventi/' . $s['id']) ?>"
                       class="badge scadenza-badge <?= $info['badge'] ?> fw-normal text-decoration-none"
                       data-id="<?= $s['id'] ?>"
                       data-stato="<?= esc($s['stato']) ?>"
                       data-pianificata="<?= esc($s['data_pianificata'] ?? '') ?>"
                       data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?= esc($tooltip) ?>">
                        <?= esc($s['cliente_denominazione'] ?: '—') ?>
                        <?php if ($dataMostrata): ?><span class="opacity-75">· <?= $dataMostrata ?></span><?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body p-2 p-md-3">
                <!-- Input nativo invisibile, tenuto sempre sovrapposto al titolo del calendario
                     (vedi posizionaInputVaiAData() più sotto): il tap dell'utente cade sul vero
                     <input>, che apre da solo il datepicker nativo del browser per saltare a una
                     data. Va prima di #calendario nel DOM così il popup si ancora vicino al
                     titolo (in alto), non in fondo dopo tutta la griglia del calendario. -->
                <input type="date" id="calendario-vai-a-data" class="cal-date-jump" tabindex="-1">
                <div id="calendario"></div>
            </div>
        </div>

    </div>
</div>

<!-- Modal dettaglio intervento (click su evento FC) -->
<div class="modal fade" id="modalIntervento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" id="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-wrench me-2" id="modal-icona"></i>
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
                    <dt class="col-sm-4 text-muted fw-normal small">Creato il</dt>
                    <dd class="col-sm-8" id="modal-creato"></dd>
                </dl>
                <div id="modal-descrizione-wrap" class="mt-3 pt-3 border-top" style="display:none;">
                    <p class="small text-muted mb-1">Descrizione</p>
                    <p id="modal-descrizione" class="mb-0" style="white-space:pre-wrap;font-size:.9rem;"></p>
                </div>
                <div id="modal-materiali-wrap" class="mt-3 pt-3 border-top" style="display:none;">
                    <p class="small text-warning-emphasis fw-semibold mb-1">
                        <i class="bi bi-box-seam me-1"></i>Materiali da portare
                    </p>
                    <ul id="modal-materiali-list" class="mb-0 ps-3 small text-muted"></ul>
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
                <div id="pian-avviso-assenza" class="alert alert-danger py-2 mb-3 d-none" role="alert"></div>
                <div class="row g-2">
                    <div class="col-sm-5 mb-2">
                        <label class="small">Orario <span class="text-danger">*</span></label>
                        <input type="time" id="pian-ora" class="form-control form-control-sm" step="900">
                        <small class="text-muted d-none" id="pian-orario-sugg"></small>
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
<script>
window.CalendarioConfig = {
    csrfHash:  '<?= csrf_hash() ?>',
    oraInizio: '<?= esc($oraInizio) ?>',
    dataIniziale: <?= $dataIniziale ? json_encode($dataIniziale) : 'null' ?>,
    puoPromemoria: <?= $puoPromemoria ? 'true' : 'false' ?>,

    tecnici: <?= json_encode(array_map(fn($t) => [
        'id'      => $t['id'],
        'nome'    => $t['nome'],
        'cognome' => $t['cognome'],
    ], $tecnici)) ?>,

    assenzePerDipendente: <?= json_encode($assenzePerDipendente) ?>,

    urls: {
        calendario:      '<?= base_url('operativo/calendario') ?>',
        interventi:      '<?= base_url('operativo/interventi') ?>',
        orarioSuggerito: '<?= base_url('operativo/calendario/orario-suggerito') ?>',
        sposta:          '<?= base_url('operativo/calendario/sposta') ?>',
        eventi:          '<?= base_url('operativo/calendario/eventi') ?>',
        poolPeriodo:     '<?= base_url('operativo/calendario/pool-periodo') ?>',
        promemoriaStore: '<?= base_url('promemoria/store') ?>',
        promemoria:      '<?= base_url('promemoria') ?>',
    },
};
</script>
<script src="<?= base_url('assets/vendor/fullcalendar/index.global.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/fullcalendar/locales/it.global.min.js') ?>"></script>
<script src="<?= base_url('js/calendario.js') ?>"></script>
<?= $this->endSection() ?>
