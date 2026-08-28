<?php
/**
 * @var array  $interventi    Righe da InterventiModel::elencoCompleto($sezione)
 * @var array  $prioritaLabel [codice => etichetta]
 * @var array  $statiLabel    [codice => etichetta]
 * @var string|null $sezione  Categoria corrente: generale | piscine | addolcitori, null = tutte
 * @var string $sezioneLabel  Etichetta della sezione (titolo pagina)
 * @var array  $categorieLabel TipiInterventoModel::CATEGORIE_LABEL [codice => etichetta]
 */
$this->extend('layouts/admin');

$statoBadge = [
    'da_pianificare' => 'secondary',
    'pianificato'    => 'primary',
    'in_corso'       => 'warning text-dark',
    'completato'     => 'success',
    'annullato'      => 'danger',
];
$prioritaBadge = [
    'abbonamento' => 'primary',
    'normale'     => 'secondary',
    'urgente'     => 'danger',
];

/* Rango per l'ordinamento della colonna Stato: senza, DataTables ordinerebbe per
   l'etichetta del badge, cioè in ordine alfabetico (Annullato, Completato, Da
   pianificare...). Così invece la lista si ordina come procede il lavoro. */
$statoOrdine = [
    'da_pianificare' => 1,
    'pianificato'    => 2,
    'in_corso'       => 3,
    'completato'     => 4,
    'annullato'      => 5,
    'sospeso'        => 6,
];

/* Secchielli di periodo per la tendina "Periodo": ogni riga elenca in una colonna nascosta
   i periodi a cui appartiene ("oggi settimana mese prossimi30"), così il filtro resta una
   ricerca di parola come tutti gli altri, senza funzioni di ricerca custom in JavaScript.
   La data di riferimento è la pianificata se c'è, altrimenti la scadenza — cioè "quando cade"
   l'intervento: quelli da pianificare hanno solo la seconda, e filtrando sulla pianificata
   sparirebbero tutti. I secchielli si sovrappongono di proposito (un intervento di oggi sta
   anche in settimana, mese e prossimi30). Le soglie sono calcolate al momento del rendering:
   una pagina lasciata aperta oltre la mezzanotte va ricaricata. */
$oggi            = new DateTimeImmutable('today');
$inizioSettimana = $oggi->modify('monday this week');
$fineSettimana   = $inizioSettimana->modify('+6 days');
$fra30Giorni     = $oggi->modify('+30 days');
$statiAperti     = ['da_pianificare', 'pianificato', 'in_corso', 'sospeso'];

$periodiDi = static function (array $i) use ($oggi, $inizioSettimana, $fineSettimana, $fra30Giorni, $statiAperti): string {
    $riferimento = $i['data_pianificata'] ?: $i['data_scadenza'];
    if (! $riferimento) {
        return '';
    }

    $data       = new DateTimeImmutable(substr($riferimento, 0, 10));
    $secchielli = [];

    // Scaduto solo se c'è ancora qualcosa da fare: un completato in ritardo non è scaduto, è fatto.
    if ($data < $oggi && in_array($i['stato'], $statiAperti, true)) {
        $secchielli[] = 'scaduto';
    }
    if ($data->format('Y-m-d') === $oggi->format('Y-m-d')) {
        $secchielli[] = 'oggi';
    }
    if ($data >= $inizioSettimana && $data <= $fineSettimana) {
        $secchielli[] = 'settimana';
    }
    if ($data->format('Y-m') === $oggi->format('Y-m')) {
        $secchielli[] = 'mese';
    }
    if ($data >= $oggi && $data <= $fra30Giorni) {
        $secchielli[] = 'prossimi30';
    }

    return implode(' ', $secchielli);
};
?>
<?= $this->section('title') ?><?= esc($sezioneLabel) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<?= $this->include('partials/datatables_styles') ?>
<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item active"><?= esc($sezioneLabel) ?></li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">
                    <i class="bi bi-tools me-2"></i><?= esc($sezioneLabel) ?>
                    <i class="bi bi-info-circle text-muted ms-2 d-none d-md-inline"
                       style="font-size:.85rem; font-weight:normal"
                       data-bs-toggle="tooltip"
                       title="Clicca su un'intestazione per ordinare. Tieni premuto Shift e clicca su altre colonne per ordinare su più criteri."></i>
                </h3>
                <div class="card-tools ms-auto">
                    <a href="<?= base_url('operativo/interventi/nuovo') ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Nuovo intervento
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php
                /* Ogni tendina lavora su una colonna nascosta diversa e resta indipendente dalle
                   altre: search-bar.js azzera solo le colonne del proprio gruppo, quindi i filtri
                   si combinano (es. aperture da abbonamento ancora da pianificare), cosa che le
                   vecchie pillole mutuamente esclusive non permettevano. */
                $filtriStato = [
                    'tutti'          => [],
                    // "Aperti" raccoglie i tre stati di lavoro in corso; sotto restano le voci
                    // precise, come "Scaduti" negli abbonamenti ha le sue con/senza rinnovo.
                    // Sospeso resta fuori: è fermo, non è lavoro che si può fare oggi.
                    'aperti'         => ['col' => 9, 'q' => '^(da_pianificare|pianificato|in_corso)$', 'regex' => true],
                    'da_pianificare' => ['col' => 9, 'q' => '^da_pianificare$', 'regex' => true],
                    'pianificati'    => ['col' => 9, 'q' => '^pianificato$',    'regex' => true],
                    'in_corso'       => ['col' => 9, 'q' => '^in_corso$',       'regex' => true],
                    'completati'     => ['col' => 9, 'q' => '^completato$',     'regex' => true],
                    'annullati'      => ['col' => 9, 'q' => '^annullato$',      'regex' => true],
                    'sospesi'        => ['col' => 9, 'q' => '^sospeso$',        'regex' => true],
                ];
                /* Categoria: solo nella vista "Tutti", dove non è già decisa dalla voce di menu.
                   Gli interventi senza tipo contano come "generale", come fa elencoCompleto(). */
                $filtriCategoria = ['tutte' => []];
                foreach ($categorieLabel as $codice => $etichetta) {
                    $filtriCategoria[$codice] = ['col' => 14, 'q' => '^' . preg_quote($codice, '/') . '$', 'regex' => true];
                }
                $filtriOrigine = [
                    'tutte'       => [],
                    'abbonamento' => ['col' => 10, 'q' => '^abbonamento$', 'regex' => true],
                    'singoli'     => ['col' => 10, 'q' => '^singolo$',     'regex' => true],
                ];
                $filtriFase = [
                    'tutte'    => [],
                    'aperture' => ['col' => 11, 'q' => '^apertura$', 'regex' => true],
                    'chiusure' => ['col' => 11, 'q' => '^chiusura$', 'regex' => true],
                ];
                $filtriUrgenza = [
                    'tutti'   => [],
                    'urgenti' => ['col' => 12, 'q' => '^urgente$', 'regex' => true],
                ];
                /* La colonna 13 contiene più parole per riga ("oggi settimana mese"), quindi
                   qui si cerca la singola parola con \b e non l'intero contenuto con ^...$. */
                $filtriPeriodo = [
                    'tutti' => [],
                    // Voce larga con sotto le due precise, come "Aperti" negli stati: la settimana
                    // da sola nasconderebbe l'arretrato, che è proprio quello da non perdere di vista.
                    'settimana_arretrati' => ['col' => 13, 'q' => '\\b(scaduto|settimana)\\b', 'regex' => true],
                    'scaduti'             => ['col' => 13, 'q' => '\\bscaduto\\b',    'regex' => true],
                    'settimana'           => ['col' => 13, 'q' => '\\bsettimana\\b',  'regex' => true],
                    'oggi'                => ['col' => 13, 'q' => '\\boggi\\b',       'regex' => true],
                    'mese'                => ['col' => 13, 'q' => '\\bmese\\b',       'regex' => true],
                    'prossimi30'          => ['col' => 13, 'q' => '\\bprossimi30\\b', 'regex' => true],
                ];
                ?>
                <div class="mb-3 d-flex flex-wrap gap-2">
                    <?php if ($sezione === null): ?>
                        <div class="dropdown" data-pill-tabella="tabella-interventi"
                             data-pill-filtri='<?= esc(json_encode($filtriCategoria), 'attr') ?>'>
                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-tag me-1"></i>Categoria: <span class="filtro-label">Tutte</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><button type="button" class="dropdown-item" data-filtro="tutte" data-default>Tutte le categorie</button></li>
                                <?php foreach ($categorieLabel as $codice => $etichetta): ?>
                                    <li><button type="button" class="dropdown-item" data-filtro="<?= esc($codice, 'attr') ?>"><?= esc($etichetta) ?></button></li>
                                <?php endforeach ?>
                            </ul>
                        </div>
                    <?php endif ?>

                    <div class="dropdown" data-pill-tabella="tabella-interventi"
                         data-pill-filtri='<?= esc(json_encode($filtriStato), 'attr') ?>'>
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-funnel me-1"></i>Stato: <span class="filtro-label"><?= $sezione === null ? 'Aperti' : 'Da pianificare' ?></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><button type="button" class="dropdown-item" data-filtro="tutti">Tutti (<?= count($interventi) ?>)</button></li>
                            <li><button type="button" class="dropdown-item" data-filtro="aperti" <?= $sezione === null ? 'data-default' : '' ?>><i class="bi bi-play-circle me-1"></i>Aperti</button></li>
                            <li><button type="button" class="dropdown-item ps-4" data-filtro="da_pianificare" <?= $sezione !== null ? 'data-default' : '' ?>>↳ Da pianificare</button></li>
                            <li><button type="button" class="dropdown-item ps-4" data-filtro="pianificati">↳ Pianificati</button></li>
                            <li><button type="button" class="dropdown-item ps-4" data-filtro="in_corso">↳ In corso</button></li>
                            <li><button type="button" class="dropdown-item" data-filtro="completati"><i class="bi bi-check-circle me-1"></i>Completati</button></li>
                            <li><button type="button" class="dropdown-item" data-filtro="annullati"><i class="bi bi-x-circle me-1"></i>Annullati</button></li>
                            <li><button type="button" class="dropdown-item" data-filtro="sospesi"><i class="bi bi-pause-circle me-1"></i>Sospesi</button></li>
                        </ul>
                    </div>

                    <div class="dropdown" data-pill-tabella="tabella-interventi"
                         data-pill-filtri='<?= esc(json_encode($filtriPeriodo), 'attr') ?>'>
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-calendar3 me-1"></i>Periodo: <span class="filtro-label"><?= $sezione === null ? 'Settimana e arretrati' : 'Tutti' ?></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><button type="button" class="dropdown-item" data-filtro="tutti" <?= $sezione !== null ? 'data-default' : '' ?>>Tutti i periodi</button></li>
                            <li><button type="button" class="dropdown-item" data-filtro="settimana_arretrati" <?= $sezione === null ? 'data-default' : '' ?>><i class="bi bi-calendar-week me-1"></i>Settimana e arretrati</button></li>
                            <li><button type="button" class="dropdown-item ps-4" data-filtro="scaduti">↳ Solo scaduti</button></li>
                            <li><button type="button" class="dropdown-item ps-4" data-filtro="settimana">↳ Solo questa settimana</button></li>
                            <li><button type="button" class="dropdown-item" data-filtro="oggi">Oggi</button></li>
                            <li><button type="button" class="dropdown-item" data-filtro="mese">Questo mese</button></li>
                            <li><button type="button" class="dropdown-item" data-filtro="prossimi30">Prossimi 30 giorni</button></li>
                        </ul>
                    </div>

                    <?php // Nella vista "Tutti" il default è "Tutte": una pagina che serve a cercare
                          // non deve nascondere gli interventi da abbonamento appena la apri.
                          // In Generale la tendina non compare: gli abbonamenti sono solo piscine e addolcitori. ?>
                    <?php if (in_array($sezione, ['piscine', 'addolcitori'], true) || $sezione === null): ?>
                        <div class="dropdown" data-pill-tabella="tabella-interventi"
                             data-pill-filtri='<?= esc(json_encode($filtriOrigine), 'attr') ?>'>
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-file-earmark-text me-1"></i>Origine: <span class="filtro-label"><?= $sezione === null ? 'Tutte' : 'Singoli' ?></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><button type="button" class="dropdown-item" data-filtro="tutte" <?= $sezione === null ? 'data-default' : '' ?>>Tutte le origini</button></li>
                                <li><button type="button" class="dropdown-item" data-filtro="abbonamento">Da abbonamento</button></li>
                                <li><button type="button" class="dropdown-item" data-filtro="singoli" <?= $sezione !== null ? 'data-default' : '' ?>>Singoli</button></li>
                            </ul>
                        </div>
                    <?php endif ?>

                    <?php if ($sezione === 'piscine'): ?>
                        <div class="dropdown" data-pill-tabella="tabella-interventi"
                             data-pill-filtri='<?= esc(json_encode($filtriFase), 'attr') ?>'>
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-box-arrow-up me-1"></i>Fase: <span class="filtro-label">Tutte</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><button type="button" class="dropdown-item" data-filtro="tutte" data-default>Tutte</button></li>
                                <li><button type="button" class="dropdown-item" data-filtro="aperture"><i class="bi bi-box-arrow-up me-1"></i>Aperture</button></li>
                                <li><button type="button" class="dropdown-item" data-filtro="chiusure"><i class="bi bi-box-arrow-in-down me-1"></i>Chiusure</button></li>
                            </ul>
                        </div>
                    <?php endif ?>

                    <div class="dropdown" data-pill-tabella="tabella-interventi"
                         data-pill-filtri='<?= esc(json_encode($filtriUrgenza), 'attr') ?>'>
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-exclamation-triangle me-1"></i>Urgenza: <span class="filtro-label">Tutti</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><button type="button" class="dropdown-item" data-filtro="tutti" data-default>Tutti</button></li>
                            <li><button type="button" class="dropdown-item" data-filtro="urgenti"><i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>Solo urgenti</button></li>
                        </ul>
                    </div>
                </div>
                <?php if (empty($interventi)): ?>
                    <p class="text-muted text-center py-4 mb-0">Nessun intervento trovato.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="tabella-interventi" class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Codice</th> 
                                    <th>Cliente</th> 
                                    <th>Tipo</th> 
                                    <th>Stato</th> 
                                    <th class="text-center">Data pianificata</th> 
                                    <th class="text-center">Data scadenza</th> 
                                    <th>Tecnico</th> 
                                    <th class="text-center">Urg.</th> 
                                    <th></th>
                                    <th></th><!-- 9 Filter stato raw -->
                                    <th></th><!-- 10 Filter origine: abbonamento|singolo -->
                                    <th></th><!-- 11 Filter fase: apertura|chiusura|'' -->
                                    <th></th><!-- 12 Filter urgenza: urgente|'' -->
                                    <th></th><!-- 13 Filter periodo: scaduto oggi settimana mese prossimi30 -->
                                    <th></th><!-- 14 Filter categoria: generale|piscine|addolcitori -->
                                </tr>                      
                            </thead>
                            <tbody>
                                <?php foreach ($interventi as $i): ?>
                                    <tr class="<?= $i['urgenza'] ? 'table-danger' : '' ?>" title="ID intervento: <?= $i['id'] ?>"><?php // ID utile per debug DB ?>
                                        <!-- 1 Codice -->
                                        <td> 
                                            <a href="<?= base_url('operativo/interventi/' . $i['id']) ?>"
                                               class="text-decoration-none js-row-open">
                                                <code class="small"><?= esc($i['codice']) ?></code>
                                            </a>
                                        </td>
                                        <!-- 2 Cliente -->
                                        <td title="ID cliente: <?= $i['cliente_id'] ?>"> 
                                            <a href="<?= base_url('anagrafiche/clienti/' . $i['cliente_id']) ?>"
                                               class="text-body text-decoration-none">
                                                <?= esc($i['cliente_denominazione']) ?>
                                            </a>
                                        </td>
                                        <!-- 3 Tipo -->
                                        <td>
                                            <?php if ($i['tipo_intervento_nome']): ?>
                                                <?php if ($i['tipo_intervento_icona']): ?>
                                                    <i class="fas <?= esc($i['tipo_intervento_icona']) ?> text-muted me-1 small"></i>
                                                <?php endif ?>
                                                <span class="small"><?= esc($i['tipo_intervento_nome']) ?></span>
                                                <br>
                                            <?php endif ?>
                                            <span class="badge bg-<?= $prioritaBadge[$i['priorita']] ?? 'secondary' ?>">
                                                <?= esc($prioritaLabel[$i['priorita']] ?? $i['priorita']) ?>
                                            </span>
                                            <?php if ($i['extra']): ?>
                                                <span class="badge bg-warning text-dark ms-2">Extra</span>
                                            <?php endif ?>
                                            <?php if (! empty($i['apertura'])): ?>
                                                <span class="badge bg-info text-dark ms-2"><i class="bi bi-box-arrow-up me-1"></i>Apertura</span>
                                            <?php elseif (! empty($i['chiusura'])): ?>
                                                <span class="badge bg-info text-dark ms-2"><i class="bi bi-box-arrow-in-down me-1"></i>Chiusura</span>
                                            <?php endif ?>
                                        </td>
                                        <!-- 4 Stato-->
                                        <td data-order="<?= $statoOrdine[$i['stato']] ?? 99 ?>">
                                            <span class="badge bg-<?= $statoBadge[$i['stato']] ?? 'secondary' ?>">
                                                <?= esc($statiLabel[$i['stato']] ?? $i['stato']) ?>
                                            </span>
                                        </td>
                                        <!-- 5 Data pianificata -->
                                        <td data-order="<?= esc($i['data_pianificata'] ?? '') ?>">
                                            <?= $i['data_pianificata']
                                                ? esc(date('d/m/Y H:i', strtotime($i['data_pianificata'])))
                                                : '<span class="text-muted">—</span>' ?>
                                        </td>
                                        <!-- 6 Scadenza — chi non ha scadenza ordina in fondo (una stringa vuota, in
                                             crescente, finirebbe invece prima di qualsiasi data) e fra quelli conta
                                             la data di creazione: senza nessuna data il criterio giusto è l'ordine
                                             di arrivo. Il prefisso 9999- rende la colonna testo per DataTables,
                                             e sulle date ISO l'ordinamento alfabetico coincide con quello cronologico. -->
                                        <td data-order="<?= esc($i['data_scadenza'] ?: '9999-' . $i['created_at']) ?>">
                                            <?= $i['data_scadenza']
                                                ? esc(date('d/m/Y', strtotime($i['data_scadenza'])))
                                                : '<span class="text-muted">—</span>' ?>
                                        </td>
                                        <!-- 7 Tecnico -->
                                        <td class="text-muted small"><?= esc($i['tecnico_nome'] ?? '—') ?></td>
                                        <!-- 8 Urgenza — la cella contiene solo un'icona, cioè testo vuoto:
                                             senza data-order l'ordinamento su questa colonna non farebbe nulla -->
                                        <td class="text-center" data-order="<?= $i['urgenza'] ? 1 : 0 ?>">
                                            <?php if ($i['urgenza']): ?>
                                                <i class="bi bi-exclamation-triangle-fill text-danger"
                                                   data-bs-toggle="tooltip" data-bs-title="Urgente"></i>
                                            <?php endif ?>
                                        </td>
                                        <!-- 9 Edit-->
                                        <td class="text-end">
                                            <a href="<?= base_url('operativo/interventi/' . $i['id'] . '/edit') ?>"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </td>
                                        <!-- 10 Filter stato raw -->
                                        <td><?= esc($i['stato']) ?></td>
                                        <!-- 11 Filter origine: abbonamento|singolo -->
                                        <td><?= ($i['abbonamento_id'] && empty($i['extra'])) ? 'abbonamento' : 'singolo' ?></td>
                                        <!-- 12 Filter fase: apertura|chiusura|'' -->
                                        <td><?= ! empty($i['apertura']) ? 'apertura' : (! empty($i['chiusura']) ? 'chiusura' : '') ?></td>
                                        <!-- 13 Filter urgenza: urgente|'' -->
                                        <td><?= $i['urgenza'] ? 'urgente' : '' ?></td>
                                        <!-- 14 Filter periodo -->
                                        <td><?= $periodiDi($i) ?></td>
                                        <!-- 15 Filter categoria — senza tipo conta come generale, come in elencoCompleto() -->
                                        <td><?= esc($i['tipo_intervento_categoria'] ?: 'generale') ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->include('partials/datatables_scripts') ?>
<script src="<?= base_url('js/search-bar.js') ?>"></script>
<script>
$(function () {

    var table = new DataTable('#tabella-interventi', {
        language: {
            search:       'Cerca:',
            lengthMenu:   'Mostra _MENU_ righe',
            info:         'Da _START_ a _END_ di _TOTAL_ record',
            infoEmpty:    'Nessun record',
            infoFiltered: '(filtrati da _MAX_ totali)',
            zeroRecords:  'Nessun risultato trovato',
            paginate: { first: '«', last: '»', next: '›', previous: '‹' },
            // In DataTables 2 l'etichetta di -1 arriva da qui, non dal lengthMenu:
            // il default inglese "All" vincerebbe su qualsiasi etichetta passata lì.
            lengthLabels: { '-1': 'Tutti' }
        },
        responsive: true,
        orderMulti: true, // già attivo di default in DataTables (Shift+clic ordina su più colonne)
        pageLength:  25,
        // -1 = nessuna paginazione; l'etichetta sta in language.lengthLabels.
        lengthMenu:  [25, 50, 100, -1],
        /* Ordine di default a due criteri, applicati in sequenza:
           colonna 3 (Stato) crescente sul rango del ciclo di vita — prima i da pianificare —
           e a parità di stato colonna 5 (Data scadenza) crescente, cioè cosa scade prima.
           La data pianificata non serve come criterio: sui da pianificare è vuota. 
           Urgenti, da pianificare, data scadenza
           */

        order:       [[7, 'desc'], [3, 'asc'], [5, 'asc']],
        columnDefs: [
        { name: 'codice', targets: 0 },
        { name: 'cliente', targets: 1, responsivePriority: 1 },
        { name: 'tipo', targets: 2 },
        // Ordina per il rango del ciclo di vita (data-order sulla cella), non per l'etichetta del badge.
        // Resta non cercabile: lo stato grezzo sta già nella colonna nascosta 9, cercarlo qui sarebbe un doppione.
        { name: 'stato', targets: 3, searchable: false, responsivePriority: 3 },
        { name: 'dt_pianificata', targets: 4, searchable: false, responsivePriority: 4 },
        { name: 'dt_scadenza', targets: 5, searchable: false },
        { name: 'tecnico', targets: 6 },
        { name: 'urgenza', targets: 7 },
        { name: 'edit', targets: 8, searchable: false, orderable: false, responsivePriority: 2 },
        { 
            name: 'filter_stato', 
            targets: 9, 
            searchable: true,
            orderable: false,
            visible: false
        },
        {
            name: 'filter_origine',
            targets: 10,
            searchable: true,
            orderable: false,
            visible: false
        },
        {
            name: 'filter_fase',
            targets: 11,
            searchable: true,
            orderable: false,
            visible: false
        },
        {
            name: 'filter_urgenza',
            targets: 12,
            searchable: true,
            orderable: false,
            visible: false
        },
        {
            name: 'filter_periodo',
            targets: 13,
            searchable: true,
            orderable: false,
            visible: false
        },
        {
            name: 'filter_categoria',
            targets: 14,
            searchable: true,
            orderable: false,
            visible: false
        }

        ]
    });

    // Attiva i filtri di default delle tendine (Stato: Da pianificare, Origine: Singoli) — gestite da search-bar.js
    document.querySelectorAll('[data-pill-tabella="tabella-interventi"] [data-default]').forEach(function (b) {
        b.click();
    });
});
</script>
<?= $this->endSection() ?>
