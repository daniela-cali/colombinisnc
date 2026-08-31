<?php
/**
 * @var array $cliente            Record clienti con tutti i campi
 * @var array $interventi         Righe da InterventiModel::perCliente() — include cantiere_titolo se collegato a un cantiere
 * @var array $sospesi            Righe da InterventiMaterialiModel::sospesiPerCliente()
 * @var array $articoliPerCat     Da ArticoliModel::perCategoria()
 * @var array $prioritaLabel      Map priorita → label leggibile
 * @var array $statiLabel         Map stato  → label leggibile (interventi)
 * @var array $abbonamenti          Righe da AbbonamentiModel::perCliente() — include stato_calcolato, num_periodi, prima_frequenza
 * @var array $abbonamentiLabel     AbbonamentiModel::STATI_LABEL
 * @var array $abbonamentiBadge     AbbonamentiModel::STATI_BADGE
 * @var array $abbonamentiFrequenze AbbonamentiModel::FREQUENZE_LABEL
 * @var array $cantieri             Righe da CantieriModel::perCliente() — include num_da_pianificare, ultima_nota_testo, ultima_nota_data
 * @var array $cantieriTipiLabel    CantieriModel::TIPI_LABEL
 * @var array $cantieriStatiLabel   CantieriModel::STATI_LABEL
 * @var array $cantieriStatiBadge   CantieriModel::STATI_BADGE
 */
$this->extend('layouts/admin');
// I secchielli di periodo della colonna nascosta 10 li calcola periodi_intervento()
helper('interventi');
$denom = \App\Models\ClientiModel::denominazione($cliente);

$zonaLabels = ['-1' => 'Ventimiglia', '0' => 'Ceriale', '1' => 'Savona'];
$zonaLabel  = ($cliente['zona'] !== null)
    ? ($zonaLabels[(string)(int)$cliente['zona']] ?? '—')
    : '—';

$statoBadge = [
    'da_pianificare' => 'bg-secondary',
    'pianificato'    => 'bg-primary',
    'in_corso'       => 'bg-warning text-dark',
    'completato'     => 'bg-success',
    'annullato'      => 'bg-danger',
];
?>
<?= $this->section('title') ?>Scheda — <?= esc($denom) ?><?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('anagrafiche/clienti') ?>">Clienti</a></li>
    <li class="breadcrumb-item active"><?= esc($denom) ?></li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<?= $this->include('partials/datatables_styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/vendor/tom-select/tom-select.bootstrap5.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/leaflet/leaflet.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-0 flex-nowrap">

    <!-- ── Colonna contenuto ──────────────────────────────────── -->
    <div class="col" style="min-width:0">

        <!-- Header -->
        <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
            <a href="<?= base_url('anagrafiche/clienti') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Clienti
            </a>
            <div class="ms-1">
                <h5 class="mb-0 fw-bold">
                    <?= esc($denom) ?>
                    <span class="badge ms-1 <?= $cliente['attivo'] ? 'bg-success' : 'bg-danger' ?>">
                        <?= $cliente['attivo'] ? 'Attivo' : 'Inattivo' ?>
                    </span>
                </h5>
                <small class="text-muted">
                    <?php if ($cliente['codice']): ?><?= esc($cliente['codice']) ?> &nbsp;·&nbsp; <?php endif ?>
                    <?= $cliente['tipo'] === 'societa' ? 'Società' : 'Persona fisica' ?>
                    <?php if ($zonaLabel !== '—'): ?>&nbsp;·&nbsp; <?= esc($zonaLabel) ?><?php endif ?>
                </small>
            </div>
            <div class="ms-auto d-flex gap-2 flex-wrap">
                <a href="<?= base_url('anagrafiche/clienti/' . $cliente['id'] . '/pdf') ?>"
                   target="_blank" class="btn btn-sm btn-outline-secondary" title="Stampa PDF">
                    <i class="bi bi-file-earmark-pdf"></i><span class="d-none d-sm-inline ms-1">Stampa PDF</span>
                </a>
                <a href="<?= base_url('anagrafiche/clienti/' . $cliente['id'] . '/edit') ?>"
                   class="btn btn-sm btn-outline-primary" title="Modifica">
                    <i class="bi bi-pencil"></i><span class="d-none d-sm-inline ms-1">Modifica</span>
                </a>
            </div>
        </div>

        <!-- ══ ANAGRAFICA ══════════════════════════════════════ -->
        <div class="section-anchor" id="sec-anagrafica">
            <div class="section-title"><i class="bi bi-person-lines-fill"></i> Anagrafica</div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="info-grid">

                    <?php if ($cliente['tipo'] === 'societa'): ?>
                        <div class="info-item">
                            <label>Ragione sociale</label>
                            <span class="fw-semibold"><?= esc($cliente['ragsoc'] ?? '—') ?></span>
                        </div>
                    <?php else: ?>
                        <div class="info-item">
                            <label>Nome e cognome</label>
                            <span class="fw-semibold"><?= esc(trim(($cliente['cognome'] ?? '') . ' ' . ($cliente['nome'] ?? ''))) ?: '—' ?></span>
                        </div>
                    <?php endif ?>

                    <?php if ($cliente['telefono']): ?>
                        <div class="info-item">
                            <label>Telefono</label>
                            <span><a href="tel:<?= esc($cliente['telefono']) ?>" class="text-reset"><?= esc($cliente['telefono']) ?></a></span>
                        </div>
                    <?php endif ?>

                    <?php if ($cliente['email']): ?>
                        <div class="info-item">
                            <label>Email</label>
                            <span><a href="mailto:<?= esc($cliente['email']) ?>" class="text-reset"><?= esc($cliente['email']) ?></a></span>
                        </div>
                    <?php endif ?>

                    <?php if ($cliente['indirizzo'] || $cliente['citta']): ?>
                        <div class="info-item">
                            <label>Indirizzo</label>
                            <span>
                                <?= esc($cliente['indirizzo'] ?? '') ?>
                                <?php if ($cliente['cap'] || $cliente['citta']): ?>
                                    <br><small><?= esc($cliente['cap'] ?? '') ?> <?= esc($cliente['citta'] ?? '') ?><?= $cliente['provincia'] ? ' (' . esc($cliente['provincia']) . ')' : '' ?></small>
                                <?php endif ?>
                            </span>
                        </div>
                    <?php endif ?>

                    <div class="info-item">
                        <label>Zona</label>
                        <span><?= esc($zonaLabel) ?></span>
                    </div>

                    <div class="info-item">
                        <label>Tecnico preferito</label>
                        <span><?= esc($cliente['tecnico_preferito_nome'] ?? '—') ?></span>
                    </div>

                    <?php if ($cliente['distanza_sede'] !== null): ?>
                        <div class="info-item">
                            <label>Distanza sede</label>
                            <span><?= esc($cliente['distanza_sede']) ?> km</span>
                        </div>
                    <?php endif ?>

                    <?php if ($cliente['piva']): ?>
                        <div class="info-item">
                            <label>P.IVA</label>
                            <span><?= esc($cliente['piva']) ?></span>
                        </div>
                    <?php endif ?>

                    <?php if ($cliente['cfisc']): ?>
                        <div class="info-item">
                            <label>Cod. fiscale</label>
                            <span><?= esc($cliente['cfisc']) ?></span>
                        </div>
                    <?php endif ?>

                    <?php if ($cliente['codice_esterno']): ?>
                        <div class="info-item">
                            <label>Cod. esterno</label>
                            <span><code><?= esc($cliente['codice_esterno']) ?></code></span>
                        </div>
                    <?php endif ?>

                    <?php if ($cliente['contatti']): ?>
                        <div class="info-item info-grid-full">
                            <label>Altri contatti</label>
                            <span class="text-preline"><?= esc($cliente['contatti']) ?></span>
                        </div>
                    <?php endif ?>

                    <?php if ($cliente['note']): ?>
                        <div class="info-item info-grid-full">
                            <label>Note</label>
                            <span class="text-preline"><?= esc($cliente['note']) ?></span>
                        </div>
                    <?php endif ?>

                </div>
            </div>
        </div>

        <!-- ══ POSIZIONE ═══════════════════════════════════════ -->
        <div class="section-anchor" id="sec-posizione">
            <div class="section-title"><i class="bi bi-geo-alt"></i> Posizione</div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">

                <div id="posizione-stato" class="small mb-2">
                    <?php if ($cliente['geocoded_at']): ?>
                        <span class="text-success">
                            <i class="bi bi-check-circle me-1"></i>
                            Geocodificato il <?= esc(date('d/m/Y H:i', strtotime($cliente['geocoded_at']))) ?>
                        </span>
                    <?php elseif ($cliente['geocodifica_fallita']): ?>
                        <span class="text-danger">
                            <i class="bi bi-x-circle me-1"></i>Geocodifica automatica fallita — posiziona il pin manualmente.
                        </span>
                    <?php else: ?>
                        <span class="text-muted">
                            <i class="bi bi-question-circle me-1"></i>Posizione non ancora impostata.
                        </span>
                    <?php endif ?>
                </div>

                <div class="mappa-wrapper">
                    <div id="mappa-posizione"
                         data-lat="<?= esc($cliente['lat'] ?? '') ?>"
                         data-lng="<?= esc($cliente['lng'] ?? '') ?>"
                         data-citta="<?= esc($cliente['citta'] ?? '') ?>"
                         data-nazione="<?= esc($cliente['nazione'] ?? 'Italia') ?>"
                         data-sede-lat="<?= esc(setting('Azienda.sede_lat') ?? '') ?>"
                         data-sede-lng="<?= esc(setting('Azienda.sede_lng') ?? '') ?>"
                         data-icon-base="<?= base_url('assets/vendor/leaflet/images/') ?>"
                    ></div>
                    <div id="mappa-overlay-zoom" class="mappa-overlay-zoom">
                        <i class="bi bi-mouse"></i> Clicca per attivare lo zoom
                    </div>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button type="button" id="btn-correggi-posizione" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil me-1"></i>Correggi posizione
                    </button>
                    <a href="#" id="btn-google-maps" target="_blank" rel="noopener"
                       class="btn btn-sm btn-outline-primary <?= $cliente['lat'] === null ? 'disabled' : '' ?>">
                        <i class="bi bi-sign-turn-right-fill me-1"></i>Apri in Google Maps
                    </a>
                </div>

                <form id="form-posizione" class="d-none mt-2"
                      action="<?= base_url('anagrafiche/clienti/' . $cliente['id'] . '/posizione') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="lat" id="posizione-lat-input">
                    <input type="hidden" name="lng" id="posizione-lng-input">
                    <p class="small text-muted mb-2">Clicca sulla mappa o trascina il pin per posizionarlo, poi salva.</p>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="bi bi-check-lg me-1"></i>Salva posizione
                        </button>
                        <button type="button" id="btn-annulla-posizione" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i>Annulla
                        </button>
                    </div>
                </form>

            </div>
        </div>

        <!-- ══ MATERIALI DA PORTARE ════════════════════════════ -->
        <div class="section-anchor" id="sec-materiali">
            <div class="section-title">
                <i class="bi bi-box-seam"></i> Materiali da portare
                <?php if (! empty($sospesi)): ?>
                    <span class="badge bg-warning text-dark" style="font-size:.6rem"><?= count($sospesi) ?></span>
                <?php endif ?>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">

                <?php if (! empty($sospesi)): ?>
                    <div class="mb-3">
                        <?php foreach ($sospesi as $s): ?>
                            <div class="sospeso-row" title="ID materiale: <?= $s['id'] ?>"><?php // ID utile per debug DB ?>
                                <span class="sospeso-qty"><?= (int) $s['quantita'] ?> ×</span>
                                <span class="sospeso-desc"><?= esc($s['desc_materiale']) ?></span>
                                <?php if ($s['note']): ?>
                                    <span class="sospeso-note" title="<?= esc($s['note']) ?>"><?= esc($s['note']) ?></span>
                                <?php endif ?>
                                <form action="<?= base_url('operativo/materiali/' . $s['id'] . '/delete') ?>"
                                      method="post" class="ms-auto d-inline"
                                      onsubmit="return confirm('Eliminare questo materiale?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-3">Nessun materiale in attesa.</p>
                <?php endif ?>

                <!-- Mini-form aggiunta sospeso -->
                <form action="<?= base_url('operativo/materiali/store') ?>" method="post" id="form-sospeso">
                    <?= csrf_field() ?>
                    <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
                    <input type="hidden" name="articolo_id" id="hs-articolo-id">
                    <input type="hidden" name="descrizione"  id="hs-descrizione">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small">Articolo / Descrizione <span class="text-danger">*</span></label>
                            <select id="sel-sospeso" placeholder="Cerca articolo o digita descrizione libera…">
                                <option value=""></option>
                                <?php foreach ($articoliPerCat as $cat): ?>
                                    <optgroup label="<?= esc($cat['nome']) ?>">
                                        <?php foreach ($cat['articoli'] as $a): ?>
                                            <option value="<?= $a['id'] ?>"><?= esc($a['descrizione']) ?></option>
                                        <?php endforeach ?>
                                    </optgroup>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Qtà</label>
                            <input type="number" name="quantita" class="form-control form-control-sm" min="1" value="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Note</label>
                            <input type="text" name="note" class="form-control form-control-sm" maxlength="255">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-plus-lg me-1"></i>Aggiungi
                            </button>
                        </div>
                    </div>
                </form>

            </div>
            <div class="card-footer border-0 bg-transparent text-end pt-0 pb-3">
                <a href="<?= base_url('anagrafiche/clienti/' . $cliente['id'] . '/materiali') ?>"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-archive me-1"></i>Storico materiali
                </a>
            </div>
        </div>

        <!-- ══ INTERVENTI ══════════════════════════════════════ -->
        <div class="section-anchor" id="sec-interventi">
            <div class="section-title">
                <i class="bi bi-tools"></i> Interventi
                <?php if ($interventi): ?>
                    <span class="badge bg-secondary" style="font-size:.6rem"><?= count($interventi) ?></span>
                <?php endif ?>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">

                <div class="mb-3 filtri-bar">
                    <?php
                    /* Stesse tendine dell'elenco interventi, sulle colonne nascoste di questa tabella:
                       8 = stato grezzo, 9 = origine ('abbonamento', 'extra' o vuoto per i singoli),
                       10 = periodo, 11 = urgenza, 12 = fase.
                       "Aperti" raccoglie i tre stati di lavoro in corso e sostituisce le due pillole
                       separate Da pianificare e Pianificati; non esclude più gli interventi da
                       abbonamento, perché nella scheda di un cliente il lavoro in ballo va visto tutto. */
                    ?>
                    <div class="filtri-scroll">
                        <?= view('partials/filtro_tendina', [
                            'tabella'   => 'tbl-interventi',
                            'etichetta' => 'Stato',
                            'gruppo'    => 'interventi_stato',
                            'classe'    => 'btn-outline-primary',
                            'voci'      => [
                                'tutti'          => ['label' => 'Tutti (' . count($interventi) . ')'],
                                'aperti'         => ['label' => 'Aperti', 'icona' => 'bi-play-circle', 'default' => true,
                                                     'col' => 8, 'q' => '^(da_pianificare|pianificato|in_corso)$', 'regex' => true],
                                'da_pianificare' => ['label' => 'Da pianificare', 'sotto' => true, 'col' => 8, 'q' => '^da_pianificare$', 'regex' => true],
                                'pianificati'    => ['label' => 'Pianificati',    'sotto' => true, 'col' => 8, 'q' => '^pianificato$',    'regex' => true],
                                'in_corso'       => ['label' => 'In corso',       'sotto' => true, 'col' => 8, 'q' => '^in_corso$',       'regex' => true],
                                'completati'     => ['label' => 'Completati', 'icona' => 'bi-check-circle', 'col' => 8, 'q' => '^completato$', 'regex' => true],
                                'annullati'      => ['label' => 'Annullati',  'icona' => 'bi-x-circle',     'col' => 8, 'q' => '^annullato$',  'regex' => true],
                                'sospesi'        => ['label' => 'Sospesi',    'icona' => 'bi-pause-circle', 'col' => 8, 'q' => '^sospeso$',    'regex' => true],
                            ],
                        ]) ?>

                        <?php // La colonna 10 contiene più parole per riga: si cerca la singola con \b ?>
                        <?= view('partials/filtro_tendina', [
                            'tabella'   => 'tbl-interventi',
                            'etichetta' => 'Periodo',
                            'gruppo'    => 'interventi_periodo',
                            'icona'     => 'bi-calendar3',
                            'voci'      => [
                                'tutti'               => ['label' => 'Tutti i periodi', 'default' => true],
                                'settimana_arretrati' => ['label' => 'Settimana e arretrati', 'icona' => 'bi-calendar-week',
                                                          'col' => 10, 'q' => '\\b(scaduto|settimana)\\b', 'regex' => true],
                                'scaduti'             => ['label' => 'Solo scaduti',          'sotto' => true, 'col' => 10, 'q' => '\\bscaduto\\b',    'regex' => true],
                                'settimana'           => ['label' => 'Solo questa settimana', 'sotto' => true, 'col' => 10, 'q' => '\\bsettimana\\b',  'regex' => true],
                                'oggi'                => ['label' => 'Oggi',                'col' => 10, 'q' => '\\boggi\\b',       'regex' => true],
                                'mese'                => ['label' => 'Questo mese',         'col' => 10, 'q' => '\\bmese\\b',       'regex' => true],
                                'prossimi30'          => ['label' => 'Prossimi 30 giorni',  'col' => 10, 'q' => '\\bprossimi30\\b', 'regex' => true],
                            ],
                        ]) ?>

                        <?php // I singoli hanno la colonna 9 vuota, quindi si cercano con ^$ ?>
                        <?= view('partials/filtro_tendina', [
                            'tabella'   => 'tbl-interventi',
                            'etichetta' => 'Origine',
                            'gruppo'    => 'interventi_origine',
                            'icona'     => 'bi-file-earmark-text',
                            'attivo'    => 'Tutte',
                            'voci'      => [
                                'tutte'       => ['label' => 'Tutte le origini', 'default' => true],
                                'abbonamento' => ['label' => 'Da abbonamento', 'col' => 9, 'q' => '^abbonamento$', 'regex' => true],
                                'extra'       => ['label' => 'Visite extra',   'col' => 9, 'q' => '^extra$',       'regex' => true],
                                'singoli'     => ['label' => 'Singoli',        'col' => 9, 'q' => '^$',            'regex' => true],
                            ],
                        ]) ?>

                        <?= view('partials/filtro_tendina', [
                            'tabella'   => 'tbl-interventi',
                            'etichetta' => 'Fase',
                            'gruppo'    => 'interventi_fase',
                            'icona'     => 'bi-box-arrow-up',
                            'voci'      => [
                                'tutte'    => ['label' => 'Tutte', 'default' => true],
                                'aperture' => ['label' => 'Aperture', 'icona' => 'bi-box-arrow-up',      'col' => 12, 'q' => '^apertura$', 'regex' => true],
                                'chiusure' => ['label' => 'Chiusure', 'icona' => 'bi-box-arrow-in-down', 'col' => 12, 'q' => '^chiusura$', 'regex' => true],
                            ],
                        ]) ?>

                        <?= view('partials/filtro_tendina', [
                            'tabella'   => 'tbl-interventi',
                            'etichetta' => 'Urgenza',
                            'gruppo'    => 'interventi_urgenza',
                            'icona'     => 'bi-exclamation-triangle',
                            'voci'      => [
                                'tutti'   => ['label' => 'Tutti', 'default' => true],
                                'urgenti' => ['label' => 'Solo urgenti', 'icona' => 'bi-exclamation-triangle-fill',
                                              'col' => 11, 'q' => '^urgente$', 'regex' => true],
                            ],
                        ]) ?>
                    </div>
                    <a href="<?= base_url('operativo/interventi/nuovo?cliente_id=' . $cliente['id']
                        . '&from=' . urlencode(base_url('anagrafiche/clienti/' . $cliente['id']) . '#sec-interventi')) ?>"
                       class="btn btn-sm btn-outline-success filtri-nuovo">
                        <i class="bi bi-plus-lg me-1"></i>Nuovo intervento
                    </a>
                </div>

                <div class="table-responsive">
                    <table id="tbl-interventi" class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Codice</th>
                                <th>Descrizione</th>
                                <th>Tipo</th>
                                <th>Genere</th>
                                <th>Tecnico</th>
                                <th>Data pianificata</th>
                                <th class="ps-4">Scadenza</th>
                                <th>Stato</th>
                                <th></th><!-- 8 stato raw — nascosto, usato dai filtri -->
                                <?php /* Il th della colonna 9 mancava: le righe avevano una cella in più
                                        delle intestazioni, quindi DataTables si fermava a dieci colonne
                                        e quella delle azioni restava fuori dalla sua gestione. */ ?>
                                <th></th><!-- 9 origine: abbonamento|extra|'' -->
                                <th></th><!-- 10 periodo: scaduto oggi settimana mese prossimi30 -->
                                <th></th><!-- 11 urgenza: urgente|'' -->
                                <th></th><!-- 12 fase: apertura|chiusura|'' -->
                                <th></th><!-- 13 azioni -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($interventi as $iv): ?>
                                <tr title="ID intervento: <?= $iv['id'] ?>"><?php // ID utile per debug DB ?>
                                    <td>
                                        <a href="<?= base_url('operativo/interventi/' . $iv['id']) ?>"
                                           class="text-decoration-none js-row-open">
                                            <code class="small"><?= esc($iv['codice']) ?></code>
                                        </a>
                                    </td>
                                    <td class="text-muted small"><?= esc($iv['descrizione'] ?? '') ?></td>
                                    <td>
                                        <?php if ($iv['tipo_intervento_icona']): ?>
                                            <i class="fas <?= esc($iv['tipo_intervento_icona']) ?> me-1 text-muted"></i>
                                        <?php endif ?>
                                        <?= esc($iv['tipo_intervento_nome'] ?? '—') ?>
                                        <?php if (! empty($iv['extra'])): ?>
                                            <span class="badge bg-warning text-dark ms-1">Extra</span>
                                        <?php endif ?>
                                        <?php if (! empty($iv['apertura'])): ?>
                                            <span class="badge bg-info text-dark ms-1"><i class="bi bi-box-arrow-up me-1"></i>Apertura</span>
                                        <?php elseif (! empty($iv['chiusura'])): ?>
                                            <span class="badge bg-info text-dark ms-1"><i class="bi bi-box-arrow-in-down me-1"></i>Chiusura</span>
                                        <?php endif ?>
                                        <?php if (! empty($iv['cantiere_id'])): ?>
                                            <a href="<?= base_url('cantieri/' . $iv['cantiere_id']) ?>"
                                               class="badge bg-warning text-dark ms-1 text-decoration-none" title="Vai al cantiere">
                                                <i class="bi bi-bricks me-1"></i><?= esc($iv['cantiere_titolo'] ?? 'Cantiere') ?>
                                            </a>
                                        <?php endif ?>
                                    </td>
                                    <td class="text-muted small"><?= esc($prioritaLabel[$iv['priorita']] ?? $iv['priorita']) ?></td>
                                    <td class="text-muted small"><?= esc($iv['tecnico_nome'] ?? '—') ?></td>
                                    <td data-order="<?= esc($iv['data_pianificata'] ?? '') ?>">
                                        <?= $iv['data_pianificata'] ? esc(date('d/m/Y H:i', strtotime($iv['data_pianificata']))) : '<span class="text-muted">--/--/---- --:--</span>' ?>
                                    </td>
                                    <td class="ps-4" data-order="<?= esc($iv['data_scadenza'] ?? '') ?>">
                                        <?= $iv['data_scadenza'] ? esc(date('d/m/Y', strtotime($iv['data_scadenza']))) : '<span class="text-muted">--/--/----</span>' ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $statoBadge[$iv['stato']] ?? 'bg-secondary' ?>">
                                            <?= esc($statiLabel[$iv['stato']] ?? $iv['stato']) ?>
                                        </span>
                                        <?php if ($iv['urgenza']): ?>
                                            <i class="bi bi-exclamation-triangle-fill text-warning ms-1" title="Urgente"></i>
                                        <?php endif ?>
                                    </td>
                                    <td><?= esc($iv['stato']) ?></td>
                                    <td><?= $iv['abbonamento_id'] ? ($iv['extra'] ? 'extra' : 'abbonamento') : '' ?></td>
                                    <td><?= periodi_intervento($iv) ?></td>
                                    <td><?= $iv['urgenza'] ? 'urgente' : '' ?></td>
                                    <td><?= ! empty($iv['apertura']) ? 'apertura' : (! empty($iv['chiusura']) ? 'chiusura' : '') ?></td>
                                    <td class="text-end">
                                        <a href="<?= base_url('operativo/interventi/' . $iv['id'] . '/edit')
                                            . '?from=' . urlencode(base_url('anagrafiche/clienti/' . $cliente['id']) . '#sec-interventi') ?>"
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <!-- ══ ABBONAMENTI ════════════════════════════════════ -->
        <div class="section-anchor" id="sec-abbonamenti">
            <div class="section-title">
                <i class="bi bi-file-earmark-text"></i> Abbonamenti
                <?php if (! empty($abbonamenti)): ?>
                    <span class="badge bg-secondary" style="font-size:.6rem"><?= count($abbonamenti) ?></span>
                <?php endif ?>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="mb-3 filtri-bar">
                    <?php /* Colonna nascosta 6 = stato_calcolato. Rispetto alle vecchie pillole ci sono
                             anche Proposte e Rifiutati, che esistono come stato ma non avevano filtro. */ ?>
                    <div class="filtri-scroll">
                        <?= view('partials/filtro_tendina', [
                            'tabella'   => 'tbl-abbonamenti',
                            'etichetta' => 'Stato',
                            'gruppo'    => 'abbonamenti_stato',
                            'classe'    => 'btn-outline-primary',
                            'voci'      => [
                                'tutti'     => ['label' => 'Tutti (' . count($abbonamenti) . ')'],
                                'attivo'    => ['label' => 'Attivi',    'icona' => 'bi-check-circle',       'default' => true, 'col' => 6, 'q' => '^attivo$',    'regex' => true],
                                'sospeso'   => ['label' => 'Sospesi',   'icona' => 'bi-pause-circle',       'col' => 6, 'q' => '^sospeso$',   'regex' => true],
                                'scaduto'   => ['label' => 'Scaduti',   'icona' => 'bi-clock-history',      'col' => 6, 'q' => '^scaduto$',   'regex' => true],
                                'disdetto'  => ['label' => 'Disdetti',  'icona' => 'bi-x-circle',           'col' => 6, 'q' => '^disdetto$',  'regex' => true],
                                'proposta'  => ['label' => 'Proposte',  'icona' => 'bi-file-earmark-text',  'col' => 6, 'q' => '^proposta$',  'regex' => true],
                                'rifiutata' => ['label' => 'Rifiutati', 'icona' => 'bi-x-circle',           'col' => 6, 'q' => '^rifiutata$', 'regex' => true],
                            ],
                        ]) ?>
                    </div>
                    <a href="<?= base_url('abbonamenti/nuovo?cliente_id=' . $cliente['id']
                        . '&from=' . urlencode(base_url('anagrafiche/clienti/' . $cliente['id']) . '#sec-abbonamenti')) ?>"
                       class="btn btn-sm btn-outline-success filtri-nuovo">
                        <i class="bi bi-plus-lg me-1"></i>Nuovo abbonamento
                    </a>
                </div>
                <?php if (empty($abbonamenti)): ?>
                    <p class="text-muted small mb-0">Nessun abbonamento.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="tbl-abbonamenti" class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Rif.</th>
                                    <th>Tipo</th>
                                    <th>Frequenza</th>
                                    <th>Periodo</th>
                                    <th class="text-end">Prezzo</th>
                                    <th class="text-center">Stato</th>
                                    <th></th><!-- stato raw — nascosto, usato dal filtro -->
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($abbonamenti as $ab): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= base_url('abbonamenti/' . $ab['id']) ?>" class="text-decoration-none js-row-open">
                                                <code class="small">#<?= (int) $ab['id'] ?></code>
                                            </a>
                                        </td>
                                        <td><?= esc($ab['tipo_nome'] ?? '—') ?></td>
                                        <td>
                                            <?php if ($ab['num_periodi'] > 1): ?>
                                                <span class="text-muted">Multipla</span>
                                            <?php else: ?>
                                                <?= esc($abbonamentiFrequenze[$ab['prima_frequenza']] ?? '—') ?>
                                            <?php endif ?>
                                        </td>
                                        <td class="text-nowrap">
                                            <?= date('d/m/Y', strtotime($ab['data_inizio'])) ?>
                                            –
                                            <?= date('d/m/Y', strtotime($ab['data_fine'])) ?>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <?= $ab['prezzo'] !== null ? '€ ' . number_format((float) $ab['prezzo'], 2, ',', '.') : '—' ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $abbonamentiBadge[$ab['stato_calcolato']] ?? 'bg-secondary' ?>">
                                                <?= esc($abbonamentiLabel[$ab['stato_calcolato']] ?? $ab['stato_calcolato']) ?>
                                            </span>
                                        </td>
                                        <td><?= esc($ab['stato_calcolato']) ?></td>
                                        <td>
                                            <a href="<?= base_url('abbonamenti/' . $ab['id']) ?>"
                                               class="btn btn-sm btn-outline-secondary" title="Scheda">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </div>
        </div>

        <!-- ══ CANTIERI ════════════════════════════════════ -->
        <div class="section-anchor" id="sec-cantieri">
            <div class="section-title">
                <i class="bi bi-bricks"></i> Cantieri
                <?php if (! empty($cantieri)): ?>
                    <span class="badge bg-secondary" style="font-size:.6rem"><?= count($cantieri) ?></span>
                <?php endif ?>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="mb-3 filtri-bar">
                    <?php // Colonna nascosta 7 = stato del cantiere ?>
                    <div class="filtri-scroll">
                        <?= view('partials/filtro_tendina', [
                            'tabella'   => 'tbl-cantieri',
                            'etichetta' => 'Stato',
                            'gruppo'    => 'cantieri_stato',
                            'classe'    => 'btn-outline-primary',
                            'voci'      => [
                                'tutti'   => ['label' => 'Tutti (' . count($cantieri) . ')'],
                                'aperto'  => ['label' => 'Aperti',  'icona' => 'bi-unlock',       'default' => true, 'col' => 7, 'q' => '^aperto$',  'regex' => true],
                                'sospeso' => ['label' => 'Sospesi', 'icona' => 'bi-pause-circle', 'col' => 7, 'q' => '^sospeso$', 'regex' => true],
                                'chiuso'  => ['label' => 'Chiusi',  'icona' => 'bi-lock',         'col' => 7, 'q' => '^chiuso$',  'regex' => true],
                            ],
                        ]) ?>
                    </div>
                    <a href="<?= base_url('cantieri/nuovo?cliente_id=' . $cliente['id']
                        . '&from=' . urlencode(base_url('anagrafiche/clienti/' . $cliente['id']) . '#sec-cantieri')) ?>"
                       class="btn btn-sm btn-outline-success filtri-nuovo">
                        <i class="bi bi-plus-lg me-1"></i>Nuovo cantiere
                    </a>
                </div>
                <?php if (empty($cantieri)): ?>
                    <p class="text-muted small mb-0">Nessun cantiere.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="tbl-cantieri" class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Rif.</th>
                                    <th>Titolo</th>
                                    <th>Tipo</th>
                                    <th>Periodo</th>
                                    <th class="text-center">Da pianificare</th>
                                    <th>Ultima nota</th>
                                    <th class="text-center">Stato</th>
                                    <th></th><!-- 7: stato raw — nascosto, usato dal filtro -->
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cantieri as $ct): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= base_url('cantieri/' . $ct['id']) ?>" class="text-decoration-none js-row-open">
                                                <code class="small">#<?= (int) $ct['id'] ?></code>
                                            </a>
                                        </td>
                                        <td class="fw-semibold"><?= esc($ct['titolo']) ?></td>
                                        <td class="text-muted small"><?= esc($cantieriTipiLabel[$ct['tipo']] ?? $ct['tipo']) ?></td>
                                        <td class="text-nowrap small">
                                            <?= $ct['data_inizio'] ? date('d/m/Y', strtotime($ct['data_inizio'])) : '—' ?>
                                            <?php if ($ct['data_fine_prevista']): ?>
                                                – <?= date('d/m/Y', strtotime($ct['data_fine_prevista'])) ?>
                                            <?php endif ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($ct['num_da_pianificare'] > 0): ?>
                                                <span class="badge bg-warning text-dark" title="Interventi da pianificare">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i><?= (int) $ct['num_da_pianificare'] ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?php if (! empty($ct['ultima_nota_testo'])): ?>
                                                <span class="badge bg-light text-dark border me-1"><?= date('d/m', strtotime($ct['ultima_nota_data'])) ?></span>
                                                <?= esc(mb_strimwidth($ct['ultima_nota_testo'], 0, 50, '…')) ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $cantieriStatiBadge[$ct['stato']] ?? 'bg-secondary' ?>">
                                                <?= esc($cantieriStatiLabel[$ct['stato']] ?? $ct['stato']) ?>
                                            </span>
                                        </td>
                                        <td><?= esc($ct['stato']) ?></td>
                                        <td>
                                            <a href="<?= base_url('cantieri/' . $ct['id']) ?>"
                                               class="btn btn-sm btn-outline-secondary" title="Apri cantiere">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </div>
        </div>


    </div><!-- /col contenuto -->

    <!-- ── Anchor nav laterale (xl+) ────────────────────────── -->
    <div class="col-auto d-none d-xl-block ps-3" style="width:130px">
        <nav class="page-nav">
            <a href="#sec-anagrafica">Anagrafica</a>
            <a href="#sec-posizione">Posizione</a>
            <a href="#sec-materiali">
                Da portare
                <?php if (! empty($sospesi)): ?>
                    <span class="badge bg-warning text-dark ms-auto" style="font-size:.6rem"><?= count($sospesi) ?></span>
                <?php endif ?>
            </a>
            <a href="#sec-interventi">
                Interventi
                <?php if ($interventi): ?>
                    <span class="badge bg-secondary ms-auto" style="font-size:.6rem"><?= count($interventi) ?></span>
                <?php endif ?>
            </a>
            <a href="#sec-abbonamenti">
                Abbonamenti
                <?php if (! empty($abbonamenti)): ?>
                    <span class="badge bg-secondary ms-auto" style="font-size:.6rem"><?= count($abbonamenti) ?></span>
                <?php endif ?>
            </a>
            <a href="#sec-cantieri">
                Cantieri
                <?php if (! empty($cantieri)): ?>
                    <span class="badge bg-secondary ms-auto" style="font-size:.6rem"><?= count($cantieri) ?></span>
                <?php endif ?>
            </a>
        </nav>
    </div>

</div><!-- /row -->
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/tom-select/tom-select.complete.min.js') ?>"></script>
<?= $this->include('partials/datatables_scripts') ?>
<script src="<?= base_url('js/search-bar.js') ?>"></script>

<script>
// Tom Select — form materiali sospesi
(function () {
    var ts = new TomSelect('#sel-sospeso', {
        wrapperClass: 'ts-wrapper ts-upper',
        create: function (input) {
            var v = input.trim().toUpperCase();
            return { value: v, text: v };
        },
        createOnBlur: true,
        placeholder: 'Cerca articolo o digita descrizione libera…',
        allowEmptyOption: true,
        createFilter: function (input) { return input.trim().length > 0; }
    });

    document.getElementById('form-sospeso').addEventListener('submit', function (e) {
        var val = ts.getValue();
        if (! val) { e.preventDefault(); alert('Seleziona un articolo o digita una descrizione.'); return; }
        if (/^\d+$/.test(val)) {
            document.getElementById('hs-articolo-id').value = val;
            document.getElementById('hs-descrizione').value  = '';
        } else {
            document.getElementById('hs-articolo-id').value = '';
            document.getElementById('hs-descrizione').value  = val;
        }
    });
})();

$(function () {
    // DataTable interventi
    var table = initTabella('#tbl-interventi', {
        // Meno righe che negli elenchi: qui la tabella è dentro un tab della scheda cliente.
        pageLength: 10,
        order: [[5, 'desc']],
        columnDefs: [
            { targets: [5, 6], className: 'text-start', type: 'string' },
            // 8 stato raw, 9 origine, 10 periodo, 11 urgenza, 12 fase: nascoste, servono ai filtri
            { targets: [8, 9, 10, 11, 12], visible: false },
            { targets: 13, orderable: false, searchable: false, responsivePriority: 2 },
            { targets: 0, responsivePriority: 1 },
            { targets: 7, responsivePriority: 3 }
        ],
        language: {
            emptyTable:  'Nessun intervento registrato.',
            zeroRecords: 'Nessun intervento trovato.'
        }
    });

    // Filtri iniziali: quelli ricordati dalla sessione, altrimenti i default — vedi search-bar.js
    filtriIniziali('tbl-interventi');

    // DataTable abbonamenti
    if (document.getElementById('tbl-abbonamenti')) {
        initTabella('#tbl-abbonamenti', {
            pageLength: 10,
            order: [],
            columnDefs: [
                { targets: 3, orderable: false },
                { targets: 6, visible: false },
                { targets: 7, orderable: false, searchable: false }
            ],
            language: {
                emptyTable:  'Nessun abbonamento registrato.',
                zeroRecords: 'Nessun abbonamento trovato.'
            }
        });
        filtriIniziali('tbl-abbonamenti');
    }

    // DataTable cantieri
    if (document.getElementById('tbl-cantieri')) {
        initTabella('#tbl-cantieri', {
            pageLength: 10,
            order: [],
            columnDefs: [
                { targets: 7, visible: false },
                { targets: 8, orderable: false, searchable: false }
            ],
            language: {
                emptyTable:  'Nessun cantiere registrato.',
                zeroRecords: 'Nessun cantiere trovato.'
            }
        });
        filtriIniziali('tbl-cantieri');
    }

});

// Anchor nav — IntersectionObserver (root: null = viewport)
(function () {
    var sections = document.querySelectorAll('.section-anchor');
    var navLinks = document.querySelectorAll('.page-nav a');
    if (! navLinks.length || window.innerWidth < 1200) return;

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                navLinks.forEach(function (a) { a.classList.remove('active'); });
                var link = document.querySelector('.page-nav a[href="#' + entry.target.id + '"]');
                if (link) link.classList.add('active');
            }
        });
    }, { root: null, rootMargin: '-10% 0px -80% 0px' });

    sections.forEach(function (s) { observer.observe(s); });
})();
</script>

<script src="<?= base_url('assets/vendor/leaflet/leaflet.js') ?>"></script>
<script src="<?= base_url('js/mappa-posizione.js') ?>"></script>
<?= $this->endSection() ?>
