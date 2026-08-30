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

// I secchielli di periodo della colonna nascosta 13 li calcola periodi_intervento()
helper('interventi');
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
                   vecchie pillole mutuamente esclusive non permettevano.
                   Nella vista "Tutti" i default sono più larghi che nelle sezioni: quella pagina
                   serve a cercare senza sapere la categoria, quindi non deve nascondere nulla
                   appena la si apre. */
                $vistaTutti = $sezione === null;

                // Categoria: solo dove non è già decisa dalla voce di menu. Gli interventi senza
                // tipo contano come "generale", come fa elencoCompleto().
                $vociCategoria = ['tutte' => ['label' => 'Tutte le categorie', 'default' => true]];
                foreach ($categorieLabel as $codice => $etichetta) {
                    $vociCategoria[$codice] = [
                        'label' => $etichetta,
                        'col'   => 14,
                        'q'     => '^' . preg_quote($codice, '/') . '$',
                        'regex' => true,
                    ];
                }
                ?>
                <div class="mb-3 d-flex flex-wrap gap-2">
                    <?php if ($vistaTutti): ?>
                        <?= view('partials/filtro_tendina', [
                            'tabella'   => 'tabella-interventi',
                            'etichetta' => 'Categoria',
                            'icona'     => 'bi-tag',
                            'classe'    => 'btn-outline-primary',
                            'attivo'    => 'Tutte',
                            'voci'      => $vociCategoria,
                        ]) ?>
                    <?php endif ?>

                    <?php /* "Aperti" raccoglie i tre stati di lavoro in corso; sotto restano le voci
                             precise, come "Scaduti" negli abbonamenti ha le sue con/senza rinnovo.
                             Sospeso resta fuori: è fermo, non è lavoro che si può fare oggi. */ ?>
                    <?= view('partials/filtro_tendina', [
                        'tabella'   => 'tabella-interventi',
                        'etichetta' => 'Stato',
                        'classe'    => 'btn-outline-primary',
                        'attivo'    => $vistaTutti ? 'Aperti' : 'Da pianificare',
                        'voci'      => [
                            'tutti'          => ['label' => 'Tutti (' . count($interventi) . ')'],
                            'aperti'         => ['label' => 'Aperti', 'icona' => 'bi-play-circle', 'default' => $vistaTutti,
                                                 'col' => 9, 'q' => '^(da_pianificare|pianificato|in_corso)$', 'regex' => true],
                            'da_pianificare' => ['label' => 'Da pianificare', 'sotto' => true, 'default' => ! $vistaTutti,
                                                 'col' => 9, 'q' => '^da_pianificare$', 'regex' => true],
                            'pianificati'    => ['label' => 'Pianificati', 'sotto' => true, 'col' => 9, 'q' => '^pianificato$', 'regex' => true],
                            'in_corso'       => ['label' => 'In corso',    'sotto' => true, 'col' => 9, 'q' => '^in_corso$',    'regex' => true],
                            'completati'     => ['label' => 'Completati', 'icona' => 'bi-check-circle', 'col' => 9, 'q' => '^completato$', 'regex' => true],
                            'annullati'      => ['label' => 'Annullati',  'icona' => 'bi-x-circle',     'col' => 9, 'q' => '^annullato$',  'regex' => true],
                            'sospesi'        => ['label' => 'Sospesi',    'icona' => 'bi-pause-circle', 'col' => 9, 'q' => '^sospeso$',    'regex' => true],
                        ],
                    ]) ?>

                    <?php /* La colonna 13 contiene più parole per riga ("oggi settimana mese"), quindi
                             si cerca la singola parola con \b e non l'intero contenuto con ^...$.
                             La voce larga tiene dentro l'arretrato: la settimana da sola nasconderebbe
                             proprio quello che non va perso di vista. */ ?>
                    <?= view('partials/filtro_tendina', [
                        'tabella'   => 'tabella-interventi',
                        'etichetta' => 'Periodo',
                        'icona'     => 'bi-calendar3',
                        'attivo'    => $vistaTutti ? 'Settimana e arretrati' : 'Tutti',
                        'voci'      => [
                            'tutti'               => ['label' => 'Tutti i periodi', 'default' => ! $vistaTutti],
                            'settimana_arretrati' => ['label' => 'Settimana e arretrati', 'icona' => 'bi-calendar-week', 'default' => $vistaTutti,
                                                      'col' => 13, 'q' => '\\b(scaduto|settimana)\\b', 'regex' => true],
                            'scaduti'             => ['label' => 'Solo scaduti',          'sotto' => true, 'col' => 13, 'q' => '\\bscaduto\\b',   'regex' => true],
                            'settimana'           => ['label' => 'Solo questa settimana', 'sotto' => true, 'col' => 13, 'q' => '\\bsettimana\\b', 'regex' => true],
                            'oggi'                => ['label' => 'Oggi',               'col' => 13, 'q' => '\\boggi\\b',       'regex' => true],
                            'mese'                => ['label' => 'Questo mese',        'col' => 13, 'q' => '\\bmese\\b',       'regex' => true],
                            'prossimi30'          => ['label' => 'Prossimi 30 giorni', 'col' => 13, 'q' => '\\bprossimi30\\b', 'regex' => true],
                        ],
                    ]) ?>

                    <?php // In Generale la tendina Origine non compare: gli abbonamenti sono solo piscine e addolcitori ?>
                    <?php if ($vistaTutti || in_array($sezione, ['piscine', 'addolcitori'], true)): ?>
                        <?= view('partials/filtro_tendina', [
                            'tabella'   => 'tabella-interventi',
                            'etichetta' => 'Origine',
                            'icona'     => 'bi-file-earmark-text',
                            'attivo'    => $vistaTutti ? 'Tutte' : 'Singoli',
                            'voci'      => [
                                'tutte'       => ['label' => 'Tutte le origini', 'default' => $vistaTutti],
                                'abbonamento' => ['label' => 'Da abbonamento', 'col' => 10, 'q' => '^abbonamento$', 'regex' => true],
                                'singoli'     => ['label' => 'Singoli', 'default' => ! $vistaTutti, 'col' => 10, 'q' => '^singolo$', 'regex' => true],
                            ],
                        ]) ?>
                    <?php endif ?>

                    <?php if ($sezione === 'piscine'): ?>
                        <?= view('partials/filtro_tendina', [
                            'tabella'   => 'tabella-interventi',
                            'etichetta' => 'Fase',
                            'icona'     => 'bi-box-arrow-up',
                            'voci'      => [
                                'tutte'    => ['label' => 'Tutte', 'default' => true],
                                'aperture' => ['label' => 'Aperture', 'icona' => 'bi-box-arrow-up',      'col' => 11, 'q' => '^apertura$', 'regex' => true],
                                'chiusure' => ['label' => 'Chiusure', 'icona' => 'bi-box-arrow-in-down', 'col' => 11, 'q' => '^chiusura$', 'regex' => true],
                            ],
                        ]) ?>
                    <?php endif ?>

                    <?= view('partials/filtro_tendina', [
                        'tabella'   => 'tabella-interventi',
                        'etichetta' => 'Urgenza',
                        'icona'     => 'bi-exclamation-triangle',
                        'voci'      => [
                            'tutti'   => ['label' => 'Tutti', 'default' => true],
                            'urgenti' => ['label' => 'Solo urgenti', 'icona' => 'bi-exclamation-triangle-fill',
                                          'col' => 12, 'q' => '^urgente$', 'regex' => true],
                        ],
                    ]) ?>
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
                                        <td><?= periodi_intervento($i) ?></td>
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

    // Filtri iniziali: quelli ricordati dalla sessione, altrimenti i default — vedi search-bar.js
    filtriIniziali('tabella-interventi');
});
</script>
<?= $this->endSection() ?>
