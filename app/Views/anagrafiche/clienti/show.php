<?php
/**
 * @var array $cliente            Record clienti con tutti i campi
 * @var array $interventi         Righe da InterventiModel::perCliente()
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
                   target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Stampa PDF
                </a>
                <a href="<?= base_url('anagrafiche/clienti/' . $cliente['id'] . '/edit') ?>"
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Modifica
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

                <div id="mappaCliente"
                     data-lat="<?= esc($cliente['lat'] ?? '') ?>"
                     data-lng="<?= esc($cliente['lng'] ?? '') ?>"
                     data-citta="<?= esc($cliente['citta'] ?? '') ?>"
                     data-nazione="<?= esc($cliente['nazione'] ?? 'Italia') ?>"
                     data-sede-lat="<?= esc(setting('Azienda.sede_lat') ?? '') ?>"
                     data-sede-lng="<?= esc(setting('Azienda.sede_lng') ?? '') ?>"
                ></div>

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
                    <div class="filtri-scroll">
                        <button class="btn btn-sm btn-outline-primary" data-filtro="aperti">
                            <i class="bi bi-folder2-open me-1"></i>Da pianificare
                        </button>
                        <button class="btn btn-sm btn-outline-info" data-filtro="pianificati">
                            <i class="bi bi-calendar-check me-1"></i>Pianificati
                        </button>
                        <button class="btn btn-sm btn-outline-success" data-filtro="completati">
                            <i class="bi bi-check-circle me-1"></i>Completati
                        </button>
                        <button class="btn btn-sm btn-outline-danger" data-filtro="annullati">
                            <i class="bi bi-x-circle me-1"></i>Annullati
                        </button>
                        <button class="btn btn-sm btn-outline-info" data-filtro="abbonamento">
                            <i class="bi bi-file-earmark-text me-1"></i>Abbonamenti
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" data-filtro="tutti">
                            Tutti (<?= count($interventi) ?>)
                        </button>
                    </div>
                    <a href="<?= base_url('operativo/interventi/nuovo?cliente_id=' . $cliente['id']
                        . '&from=' . urlencode(base_url('anagrafiche/clienti/' . $cliente['id']) . '#sec-interventi')) ?>"
                       class="btn btn-sm btn-outline-success filtri-nuovo">
                        <i class="bi bi-plus-lg me-1"></i>Nuovo intervento
                    </a>
                </div>

                <div class="table-responsive">
                    <table id="tbl-interventi" class="table table-hover table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Codice</th>
                                <th>Descrizione</th>
                                <th>Tipo</th>
                                <th>Genere</th>
                                <th>Tecnico</th>
                                <th>Data pianificata</th>
                                <th class="ps-4">Scadenza</th>
                                <th>Stato</th>
                                <th></th><!-- stato raw — nascosto, usato dal filtro -->
                                <th></th><!-- azioni -->
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
                <div class="mb-3 d-flex">
                    <a href="<?= base_url('abbonamenti/nuovo?cliente_id=' . $cliente['id']
                        . '&from=' . urlencode(base_url('anagrafiche/clienti/' . $cliente['id']) . '#sec-abbonamenti')) ?>"
                       class="btn btn-sm btn-outline-success ms-auto">
                        <i class="bi bi-plus-lg me-1"></i>Nuovo abbonamento
                    </a>
                </div>
                <?php if (empty($abbonamenti)): ?>
                    <p class="text-muted small mb-0">Nessun abbonamento.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Rif.</th>
                                    <th>Tipo</th>
                                    <th>Frequenza</th>
                                    <th>Periodo</th>
                                    <th class="text-end">Prezzo</th>
                                    <th class="text-center">Stato</th>
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
                <div class="mb-3 d-flex">
                    <a href="<?= base_url('cantieri/nuovo?cliente_id=' . $cliente['id']
                        . '&from=' . urlencode(base_url('anagrafiche/clienti/' . $cliente['id']) . '#sec-cantieri')) ?>"
                       class="btn btn-sm btn-outline-warning ms-auto">
                        <i class="bi bi-plus-lg me-1"></i>Nuovo cantiere
                    </a>
                </div>
                <?php if (empty($cantieri)): ?>
                    <p class="text-muted small mb-0">Nessun cantiere.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Rif.</th>
                                    <th>Titolo</th>
                                    <th>Tipo</th>
                                    <th>Periodo</th>
                                    <th class="text-center">Da pianificare</th>
                                    <th>Ultima nota</th>
                                    <th class="text-center">Stato</th>
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

    <!-- ── Anchor nav laterale (xxl+) ────────────────────────── -->
    <div class="col-auto d-none d-xxl-block ps-3" style="width:130px">
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
    var table = $('#tbl-interventi').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[5, 'desc']],
        columnDefs: [
            { targets: [5, 6], className: 'text-start', type: 'string' },
            { targets: [8, 9], visible: false },
            { targets: 10, orderable: false, searchable: false, responsivePriority: 2 },
            { targets: 0, responsivePriority: 1 },
            { targets: 7, responsivePriority: 3 }
        ],
        language: {
            emptyTable:   'Nessun intervento registrato.',
            info:         'Da _START_ a _END_ di _TOTAL_',
            infoEmpty:    'Nessun risultato',
            infoFiltered: '(filtrati da _MAX_ totali)',
            lengthMenu:   'Mostra _MENU_ righe',
            search:       'Cerca:',
            paginate:     { first: '«', last: '»', next: '›', previous: '‹' },
            zeroRecords:  'Nessun intervento trovato.'
        }
    });

    var filtri = {
        aperti:     { col: 8, q: '^da_pianificare$',               regex: true  },
        pianificati:{ col: 8, q: '^(pianificato|in_corso)$',        regex: true  },
        completati: { col: 8, q: '^completato$',                    regex: true  },
        annullati:  { col: 8, q: '^annullato$',                     regex: true  },
        abbonamento:{ col: 9, q: 'abbonamento',                     regex: false },
        tutti:      { col: 8, q: '',                                regex: false }
    };

    function setFiltro(nome) {
        var f = filtri[nome];
        table.column(8).search('', false, false);
        table.column(9).search('', false, false);
        table.column(f.col).search(f.q, f.regex, false);
        // "aperti" esclude gli interventi batch da abbonamento (le visite extra restano visibili)
        if (nome === 'aperti') table.column(9).search('^(?!abbonamento)', true, false);
        table.draw();
        document.querySelectorAll('[data-filtro]').forEach(function (b) {
            b.classList.toggle('active', b.dataset.filtro === nome);
        });
    }

    document.querySelectorAll('[data-filtro]').forEach(function (b) {
        b.addEventListener('click', function () { setFiltro(this.dataset.filtro); });
    });

    setFiltro('aperti');


});

// Anchor nav — IntersectionObserver (root: null = viewport)
(function () {
    var sections = document.querySelectorAll('.section-anchor');
    var navLinks = document.querySelectorAll('.page-nav a');
    if (! navLinks.length) return;

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
<script>
// Mappa Leaflet in scheda cliente — vedi docs/spec/mappa_cliente_spec.md
(function () {
    'use strict';
    var container = document.getElementById('mappaCliente');
    if (! container) return;

    // Corregge il path delle icone marker. _getIconUrl di default antepone sempre
    // un imagePath auto-rilevato dal CSS, anche davanti a un URL già assoluto
    // (causa doppio URL / 404) — va rimosso per usare le nostre URL così come sono.
    delete L.Icon.Default.prototype._getIconUrl;
    var iconBase = '<?= base_url('assets/vendor/leaflet/images/') ?>';
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: iconBase + 'marker-icon-2x.png',
        iconUrl:       iconBase + 'marker-icon.png',
        shadowUrl:     iconBase + 'marker-shadow.png',
    });

    var latDb = parseFloat(container.dataset.lat);
    var lngDb = parseFloat(container.dataset.lng);
    var haPosizione = ! isNaN(latDb) && ! isNaN(lngDb);

    var sedeLat = parseFloat(container.dataset.sedeLat);
    var sedeLng = parseFloat(container.dataset.sedeLng);

    var btnGoogle     = document.getElementById('btn-google-maps');
    var btnCorreggi   = document.getElementById('btn-correggi-posizione');
    var btnAnnulla    = document.getElementById('btn-annulla-posizione');
    var formPosizione = document.getElementById('form-posizione');
    var latInput      = document.getElementById('posizione-lat-input');
    var lngInput      = document.getElementById('posizione-lng-input');

    var map = L.map('mappaCliente');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap',
    }).addTo(map);

    // Pallino fisso della sede aziendale, sempre visibile e mai modificabile da qui
    // (si distingue volutamente dal pin del cliente, non è un marker trascinabile).
    if (! isNaN(sedeLat) && ! isNaN(sedeLng)) {
        L.circleMarker([sedeLat, sedeLng], {
            radius: 8,
            color: '#fff',
            weight: 2,
            fillColor: '#dc3545',
            fillOpacity: 1,
        }).addTo(map).bindTooltip('Sede aziendale');
    }

    var marker = null;
    var modificaAttiva = false;

    function aggiornaHidden(lat, lng) {
        latInput.value = lat.toFixed(7);
        lngInput.value = lng.toFixed(7);
    }

    function aggiornaLinkGoogle(lat, lng) {
        btnGoogle.href = 'https://www.google.com/maps/dir/?api=1&destination=' + lat + ',' + lng;
        btnGoogle.classList.remove('disabled');
    }

    function piazzaMarker(lat, lng, draggable) {
        if (marker) map.removeLayer(marker);
        marker = L.marker([lat, lng], { draggable: draggable }).addTo(map);
        // Il listener va collegato sempre, non solo quando draggable è già true alla
        // creazione: il marker iniziale (view-mode) nasce non trascinabile e diventa
        // draggable solo dopo, tramite marker.dragging.enable() in "Correggi posizione".
        marker.on('dragend', function () {
            var p = marker.getLatLng();
            aggiornaHidden(p.lat, p.lng);
        });
        aggiornaHidden(lat, lng);
    }

    function centraSuSede() {
        if (! isNaN(sedeLat) && ! isNaN(sedeLng)) {
            map.setView([sedeLat, sedeLng], 10);
        } else {
            map.setView([44.3, 8.3], 9); // fallback estremo: area Liguria
        }
    }

    if (haPosizione) {
        map.setView([latDb, lngDb], 16);
        piazzaMarker(latDb, lngDb, false);
        aggiornaLinkGoogle(latDb, lngDb);
    } else {
        // Nessuna posizione precisa: prova a centrare sulla città (non salvato, solo per orientare la mappa)
        var citta   = container.dataset.citta;
        var nazione = container.dataset.nazione || 'Italia';

        if (citta) {
            var q = encodeURIComponent(citta + ', ' + nazione);
            fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + q, { headers: { 'Accept-Language': 'it' } })
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (data) {
                    if (data && data[0]) {
                        map.setView([parseFloat(data[0].lat), parseFloat(data[0].lon)], 13);
                    } else {
                        centraSuSede();
                    }
                })
                .catch(centraSuSede);
        } else {
            centraSuSede();
        }
    }

    map.on('click', function (e) {
        if (! modificaAttiva) return;
        piazzaMarker(e.latlng.lat, e.latlng.lng, true);
    });

    btnCorreggi.addEventListener('click', function () {
        modificaAttiva = true;
        formPosizione.classList.remove('d-none');
        btnCorreggi.classList.add('d-none');
        btnGoogle.classList.add('d-none');
        if (marker) marker.dragging.enable();
    });

    btnAnnulla.addEventListener('click', function () {
        modificaAttiva = false;
        formPosizione.classList.add('d-none');
        btnCorreggi.classList.remove('d-none');
        btnGoogle.classList.remove('d-none');
        if (haPosizione) {
            piazzaMarker(latDb, lngDb, false);
            map.setView([latDb, lngDb], 16);
        } else if (marker) {
            map.removeLayer(marker);
            marker = null;
        }
    });
})();
</script>
<?= $this->endSection() ?>
