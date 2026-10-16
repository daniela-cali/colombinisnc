<?php
/**
 * @var string $title
 * @var array  $abbonamenti  Da AbbonamentiModel::elencoConDettagli() — include stato_calcolato, successore_id, successore_anno, anno_inizio, num_periodi, prima_frequenza, più il flag rinnovabile aggiunto dal controller
 * @var array  $tipiPresenti Nomi distinti dei tipi intervento presenti in $abbonamenti, ordinati alfabeticamente
 * @var array  $anniPresenti Anni distinti (da data_inizio) presenti in $abbonamenti, ordinati decrescenti
 * @var array  $statiLabel   AbbonamentiModel::STATI_LABEL
 * @var array  $statiBadge   AbbonamentiModel::STATI_BADGE
 * @var array  $frequenze    AbbonamentiModel::FREQUENZE_LABEL
 */
$this->extend('layouts/admin');

/* Rango per l'ordinamento della colonna Stato: la cella mostra un badge, quindi senza
   data-order DataTables ordinerebbe per l'etichetta, cioè alfabeticamente (Attivo,
   Disdetto, Proposta...). L'ordine segue la vita del contratto — nasce proposta,
   diventa attivo, può sospendersi, poi scade — con in fondo le due uscite anticipate:
   disdetto (chiuso prima del tempo) e rifiutata (proposta mai accettata). */
$statoOrdine = [
    'proposta'  => 1,
    'attivo'    => 2,
    'sospeso'   => 3,
    'scaduto'   => 4,
    'disdetto'  => 5,
    'rifiutata' => 6,
];
?>
<?= $this->section('title') ?>Abbonamenti<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<?= $this->include('partials/datatables_styles') ?>
<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item active">Abbonamenti</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card card-outline card-primary">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-file-earmark-text me-2"></i>Abbonamenti
            <i class="bi bi-info-circle text-muted ms-2"
               style="font-size:.85rem; font-weight:normal"
               data-bs-toggle="tooltip"
               title="Clicca su un'intestazione per ordinare. Tieni premuto Shift e clicca su altre colonne per ordinare su più criteri."></i>
        </h3>
        <div class="card-tools ms-auto">
            <?php /* Il tooltip sta sullo span e non sul pulsante: un pulsante disattivato non riceve il
                     passaggio del mouse. Lo script ci scrive la descrizione o il motivo per cui è spento. */ ?>
            <span class="tooltip-pulsante" tabindex="0" data-bs-toggle="tooltip"
                  data-descrizione="Accetta le proposte selezionate e genera i loro interventi" data-bs-title="Seleziona almeno un abbonamento">
                <button type="submit" form="form-accetta-multiplo" id="btn-accetta-multiplo"
                        class="btn btn-sm btn-success" disabled
                        onclick="return confirm('Accettare le proposte selezionate? Verranno generati gli interventi.')">
                    <i class="bi bi-clipboard-check-fill"></i><span class="d-none d-sm-inline ms-1">Accetta selezionati</span>
                </button>
            </span>
            <span class="tooltip-pulsante" tabindex="0" data-bs-toggle="tooltip"
                  data-descrizione="Proposte in Word delle righe selezionate, in un unico zip" data-bs-title="Seleziona almeno un abbonamento">
                <button type="submit" form="form-accetta-multiplo" id="btn-proposte-word"
                        formaction="<?= base_url('abbonamenti/proposte-word') ?>"
                        class="btn btn-sm btn-outline-secondary" disabled>
                    <i class="bi bi-file-earmark-word"></i><span class="d-none d-sm-inline ms-1">Scarica Word</span>
                </button>
            </span>
            <span class="tooltip-pulsante" tabindex="0" data-bs-toggle="tooltip"
                  data-descrizione="Proposte in PDF delle righe selezionate, in un unico zip" data-bs-title="Seleziona almeno un abbonamento">
                <button type="button" id="btn-proposte-pdf"
                        class="btn btn-sm btn-outline-secondary" disabled>
                    <i class="bi bi-file-earmark-pdf"></i><span class="d-none d-sm-inline ms-1">Scarica PDF</span>
                </button>
            </span>
            <span class="tooltip-pulsante" tabindex="0" data-bs-toggle="tooltip"
                  data-descrizione="Invia per email le proposte selezionate, una alla volta" data-bs-title="Seleziona almeno un abbonamento">
                <button type="button" id="btn-invia-multiplo"
                        class="btn btn-sm btn-outline-secondary" disabled>
                    <i class="bi bi-envelope"></i><span class="d-none d-sm-inline ms-1">Invia per email</span>
                </button>
            </span>
            <span class="tooltip-pulsante" tabindex="0" data-bs-toggle="tooltip"
                  data-descrizione="Rinnova le righe selezionate, un form alla volta nell'ordine di selezione" data-bs-title="Seleziona almeno un abbonamento">
                <button type="button" id="btn-rinnova-multiplo"
                        class="btn btn-sm btn-outline-primary" disabled>
                    <i class="bi bi-arrow-repeat"></i><span class="d-none d-sm-inline ms-1">Rinnova selezionati</span>
                    <span class="badge text-bg-primary ms-1 d-none" id="conta-rinnovo"></span>
                </button>
            </span>
            <a href="<?= base_url('abbonamenti/nuovo') ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nuovo abbonamento
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($abbonamenti)): ?>
            <p class="text-muted text-center py-4 mb-0">Nessun abbonamento presente.</p>
        <?php else: ?>
            <?php
            // Tipo: sulla colonna 3 già visibile (Tipo intervento), una voce per tipo presente.
            $vociTipo = ['tutti' => ['label' => 'Tutti i tipi', 'default' => true]];
            foreach ($tipiPresenti as $tipo) {
                $vociTipo[$tipo] = ['label' => $tipo, 'col' => 3, 'q' => '^' . preg_quote($tipo, '/') . '$', 'regex' => true];
            }

            // Anno: sulla colonna nascosta 11 (anno_inizio), una voce per anno presente.
            $vociAnno = ['tutti' => ['label' => 'Tutti gli anni', 'default' => true]];
            foreach ($anniPresenti as $anno) {
                $vociAnno[$anno] = ['label' => $anno, 'col' => 11, 'q' => '^' . preg_quote($anno, '/') . '$', 'regex' => true];
            }
            ?>
            <div class="mb-3 d-flex flex-wrap gap-2">
                <?php /* Stato: tutto sulla colonna nascosta 10. "Scaduti" cerca per prefisso, senza
                         ancora di chiusura, così intercetta sia "scaduto con-rinnovo" sia
                         "scaduto senza-rinnovo", che restano disponibili come voci rientrate. */ ?>
                <?= view('partials/filtro_tendina', [
                    'tabella'   => 'tabella-abbonamenti',
                    'etichetta' => 'Stato',
                    'classe'    => 'btn-outline-primary',
                    'voci'      => [
                        'tutti'                 => ['label' => 'Tutti (' . count($abbonamenti) . ')'],
                        'attivo'                => ['label' => 'Attivi', 'icona' => 'bi-check-circle', 'default' => true,
                                                    'col' => 10, 'q' => '^attivo$', 'regex' => true],
                        'sospeso'               => ['label' => 'Sospesi', 'icona' => 'bi-pause-circle',  'col' => 10, 'q' => '^sospeso$', 'regex' => true],
                        'scaduto'               => ['label' => 'Scaduti', 'icona' => 'bi-clock-history', 'col' => 10, 'q' => '^scaduto',  'regex' => true],
                        'scaduto_con_rinnovo'   => ['label' => 'con rinnovo',   'sotto' => true, 'col' => 10, 'q' => '^scaduto con-rinnovo$',   'regex' => true],
                        'scaduto_senza_rinnovo' => ['label' => 'senza rinnovo', 'sotto' => true, 'col' => 10, 'q' => '^scaduto senza-rinnovo$', 'regex' => true],
                        'disdetto'              => ['label' => 'Disdetti',  'icona' => 'bi-x-circle',          'col' => 10, 'q' => '^disdetto$',  'regex' => true],
                        'proposta'              => ['label' => 'Proposte',  'icona' => 'bi-file-earmark-text', 'col' => 10, 'q' => '^proposta$',  'regex' => true],
                        'rifiutata'             => ['label' => 'Rifiutati', 'icona' => 'bi-x-circle',          'col' => 10, 'q' => '^rifiutata$', 'regex' => true],
                    ],
                ]) ?>

                <?= view('partials/filtro_tendina', [
                    'tabella'   => 'tabella-abbonamenti',
                    'etichetta' => 'Tipo',
                    'icona'     => 'bi-tag',
                    'attivo'    => 'Tutti',
                    'voci'      => $vociTipo,
                ]) ?>

                <?= view('partials/filtro_tendina', [
                    'tabella'   => 'tabella-abbonamenti',
                    'etichetta' => 'Anno',
                    'icona'     => 'bi-calendar3',
                    'attivo'    => 'Tutti',
                    'voci'      => $vociAnno,
                ]) ?>
            </div>
            <?php /* Quanti sono selezionati; perché un pulsante è spento lo dice il suo tooltip. */ ?>
            <p id="riepilogo-selezione" class="small text-muted mb-2 d-none"></p>
            <?php /* Il form della selezione multipla è vuoto e sta fuori dalla tabella: i bottoni gli
                     si collegano con l'attributo form, e gli id selezionati li aggiunge lo script al
                     momento dell'invio. Se racchiudesse la tabella, i form Accetta/Rifiuta di ogni
                     riga sarebbero annidati, cosa che l'HTML non ammette: il parser scarta il primo
                     form interno, e l'Accetta della prima proposta finiva per inviare la selezione
                     multipla. */ ?>
            <form id="form-accetta-multiplo" method="post" action="<?= base_url('abbonamenti/accetta-multiplo') ?>">
                <?= csrf_field() ?>
            </form>
                <div class="table-responsive">
                    <table id="tabella-abbonamenti" class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:30px"><input type="checkbox" id="check-tutti" title="Seleziona tutte le righe filtrate"></th>
                                <th style="width:70px">Rif.</th>
                                <th>Cliente</th>
                                <th>Tipo intervento</th>
                                <th>Frequenza</th>
                                <th class="text-center">Periodo</th>
                                <th class="text-center">Prezzo</th>
                                <th class="text-center">Stato</th>
                                <th>Rinnovo</th>
                                <th style="width:100px"></th>
                                <th></th><!-- 10 Filter stato_calcolato -->
                                <th></th><!-- 11 Filter anno_inizio -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($abbonamenti as $a): ?>
                                <tr>
                                    <!-- 0 Checkbox selezione, su ogni riga: sono i pulsanti a dire cosa si può fare con la selezione -->
                                    <td class="text-center">
                                        <input type="checkbox" value="<?= (int) $a['id'] ?>" class="check-riga"
                                               data-stato="<?= esc($a['stato_calcolato']) ?>"
                                               data-rinnovabile="<?= $a['rinnovabile'] ? 1 : 0 ?>"
                                               data-anno="<?= esc($a['anno_inizio']) ?>">
                                    </td>
                                    <!-- 1 Rif. -->
                                    <td data-order="<?= (int) $a['id'] ?>">
                                        <a href="<?= base_url('abbonamenti/' . $a['id']) ?>" class="text-decoration-none js-row-open">
                                            <code class="small">#<?= (int) $a['id'] ?></code>
                                        </a>
                                    </td>
                                    <!-- 2 Cliente -->
                                    <td>
                                        <a href="<?= base_url('abbonamenti/' . $a['id']) ?>" class="text-body text-decoration-none">
                                            <?= esc($a['cliente_denominazione']) ?>
                                        </a>
                                    </td>
                                    <!-- 3 Tipo intervento -->
                                    <td><?= esc($a['tipo_nome'] ?? '—') ?></td>
                                    <!-- 4 Frequenza -->
                                    <td>
                                        <?php if ($a['num_periodi'] > 1): ?>
                                            Multipla
                                            <small class="text-muted">(<?= (int) $a['num_periodi'] ?> periodi)</small>
                                        <?php else: ?>
                                            <?= esc($frequenze[$a['prima_frequenza']] ?? '—') ?>
                                        <?php endif ?>
                                    </td>
                                    <!-- 5 Periodo -->
                                    <td class="text-center text-nowrap" data-order="<?= esc($a['data_inizio']) ?>">
                                        <?= date('d/m/Y', strtotime($a['data_inizio'])) ?>
                                        –
                                        <?= date('d/m/Y', strtotime($a['data_fine'])) ?>
                                    </td>
                                    <!-- 6 Prezzo -->
                                    <td class="text-end text-nowrap" data-order="<?= $a['prezzo'] !== null ? (float) $a['prezzo'] : -1 ?>">
                                        <?= $a['prezzo'] !== null ? '€ ' . number_format((float) $a['prezzo'], 2, ',', '.') : '—' ?>
                                    </td>
                                    <!-- 7 Stato -->
                                    <td class="text-center" data-order="<?= $statoOrdine[$a['stato_calcolato']] ?? 99 ?>">
                                        <span class="badge <?= $statiBadge[$a['stato_calcolato']] ?? 'bg-secondary' ?>">
                                            <?= esc($statiLabel[$a['stato_calcolato']] ?? $a['stato_calcolato']) ?>
                                        </span>
                                    </td>
                                    <!-- 8 Rinnovo: dove porta il rinnovo già fatto, oppure il pulsante per farlo.
                                         L'ordinamento mette prima i rinnovati, poi i rinnovabili, poi gli altri. -->
                                    <td class="text-nowrap" data-order="<?= $a['successore_id'] ? 1 : ($a['rinnovabile'] ? 2 : 3) ?>">
                                        <?php if ($a['successore_id']): ?>
                                            <a href="<?= base_url('abbonamenti/' . $a['successore_id']) ?>"
                                               class="badge badge-rinnovato"
                                               title="Vai al rinnovo per il <?= esc($a['successore_anno']) ?>">Rinnovato →</a>
                                        <?php elseif ($a['rinnovabile']): ?>
                                            <a href="<?= base_url('abbonamenti/' . $a['id'] . '/rinnova') ?>"
                                               class="btn btn-sm btn-rinnova" title="Rinnova per l'anno successivo">
                                                <i class="bi bi-arrow-repeat me-1"></i>Rinnova
                                            </a>
                                        <?php endif ?>
                                    </td>
                                    <!-- 9 Azioni -->
                                    <td class="text-end text-nowrap">
                                        <a href="<?= base_url('abbonamenti/' . $a['id']) ?>"
                                           class="btn btn-sm btn-outline-secondary" title="Scheda">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (in_array($a['stato_calcolato'], ['proposta'], true)): ?>
                                            <?php if (\App\Libraries\PropostaAbbonamento::haModello($a['tipo_categoria'])): ?>
                                                <?php /* Scarica la proposta e dice se è già stata generata: pieno sì, contornato no */ ?>
                                                <a href="<?= base_url('abbonamenti/' . $a['id'] . '/proposta') ?>"
                                                   class="btn btn-sm <?= $a['proposta_generata_at'] ? 'btn-secondary' : 'btn-outline-secondary' ?>"
                                                   title="<?= $a['proposta_generata_at']
                                                       ? 'Proposta generata il ' . date('d/m/Y H:i', strtotime($a['proposta_generata_at'])) . ' — scarica di nuovo'
                                                       : 'Genera la proposta Word' ?>">
                                                    <i class="bi bi-file-earmark-word"></i>
                                                </a>
                                                <?php /* Invia per email e dice se è già partita: pieno sì, contornato no */ ?>
                                                <button type="button" data-invia="<?= (int) $a['id'] ?>"
                                                        class="btn btn-sm <?= $a['proposta_inviata_at'] ? 'btn-secondary' : 'btn-outline-secondary' ?>"
                                                        title="<?= $a['proposta_inviata_at']
                                                            ? 'Proposta inviata il ' . date('d/m/Y H:i', strtotime($a['proposta_inviata_at'])) . ' — invia di nuovo'
                                                            : 'Invia la proposta per email' ?>">
                                                    <i class="bi bi-envelope"></i>
                                                </button>
                                            <?php endif ?>
                                            <form action="<?= base_url('abbonamenti/' . $a['id'] . '/accetta') ?>" method="post" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Accetta"
                                                        onclick="return confirm('Accettare la proposta? Verranno generati gli interventi.')">
                                                    <i class="bi bi-clipboard-check-fill"></i>
                                                </button>
                                            </form>
                                            <form action="<?= base_url('abbonamenti/' . $a['id'] . '/rifiuta') ?>" method="post" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Rifiuta"
                                                        onclick="return confirm('Rifiutare questa proposta?')">
                                                    <i class="bi bi-clipboard-x-fill"></i>
                                                </button>
                                            </form>
                                        <?php endif ?>
                                    </td>
                                    <!-- 10 Filter stato_calcolato -->
                                    <td><?= esc($a['stato_calcolato']) ?><?= $a['stato_calcolato'] === 'scaduto' ? ($a['successore_id'] ? ' con-rinnovo' : ' senza-rinnovo') : '' ?></td>
                                    <!-- 11 Filter anno_inizio -->
                                    <td><?= esc($a['anno_inizio']) ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
        <?php endif ?>
    </div>
</div>

<?= view('abbonamenti/_invio_email') ?>

<?php /* Avanzamento dello zip dei PDF: la conversione va a lotti e con molte proposte dura qualche
         minuto. Statica: si chiude da sola a zip scaricato, oppure con Chiudi dopo un errore. */ ?>
<div class="modal fade" id="modal-zip-pdf" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-pdf me-2"></i>Proposte in PDF</h5>
            </div>
            <div class="modal-body">
                <p class="mb-2" id="zip-pdf-stato">Conversione in corso…</p>
                <div class="progress" role="progressbar" aria-label="Avanzamento della conversione">
                    <div class="progress-bar" id="zip-pdf-barra"></div>
                </div>
                <div class="alert alert-danger mt-3 mb-0 d-none" id="zip-pdf-errore"></div>
            </div>
            <div class="modal-footer d-none" id="zip-pdf-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->include('partials/datatables_scripts') ?>
<script src="<?= asset_url('js/search-bar.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/fflate/index.js') ?>"></script>
<script src="<?= asset_url('js/zip-proposte-pdf.js') ?>"></script>
<script src="<?= asset_url('js/invio-proposte.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    var table = initTabella('#tabella-abbonamenti', {
        order: [[1, 'desc']],
        columnDefs: [
            { name: 'select',    targets: 0, searchable: false, orderable: false, responsivePriority: 3 },
            { name: 'rif',       targets: 1, searchable: false, responsivePriority: 2 },
            { name: 'cliente',   targets: 2, responsivePriority: 1 },
            { name: 'tipo',      targets: 3 },
            { name: 'frequenza', targets: 4 },
            { name: 'periodo',   targets: 5, searchable: false },
            { name: 'prezzo',    targets: 6, searchable: false },
            // Ordina per il rango del ciclo di vita (data-order sulla cella), non per l'etichetta del badge.
            // Resta non cercabile: lo stato grezzo è già nella colonna nascosta 10.
            { name: 'stato',     targets: 7, searchable: false, responsivePriority: 3 },
            { name: 'rinnovo',   targets: 8, searchable: false, responsivePriority: 3 },
            { name: 'azioni',    targets: 9, searchable: false, orderable: false, responsivePriority: 2 },
            { name: 'filter_stato', targets: 10, searchable: true, orderable: false, visible: false },
            { name: 'filter_anno',  targets: 11, searchable: true, orderable: false, visible: false }
        ]
    });

    // Filtri iniziali: quelli ricordati dalla sessione, altrimenti i default — vedi search-bar.js
    filtriIniziali('tabella-abbonamenti');

    /* Selezione multipla — vedi docs/spec/abbonamenti_invio_email_spec.md, «Selezione».
       Ogni riga ha la sua casella e le caselle non si bloccano a vicenda: ogni pulsante si
       accende solo se la selezione contiene ciò su cui sa lavorare, e il suo tooltip dice perché
       è spento. Lo stato vive qui e non nelle caselle presenti nella pagina, perché
       DataTables stacca dal documento le righe delle altre pagine e quelle escluse dai filtri:
       cercandole con querySelectorAll si perdevano. */
    var selezione = [];   // id nell'ordine di selezione: è l'ordine dei form del rinnovo
    var righe     = {};   // id → { stato, rinnovabile, anno } letti dalla casella

    var checkTutti = document.getElementById('check-tutti');
    var btnAccetta = document.getElementById('btn-accetta-multiplo');
    var btnWord    = document.getElementById('btn-proposte-word');
    var btnPdf     = document.getElementById('btn-proposte-pdf');
    var btnInvia   = document.getElementById('btn-invia-multiplo');
    var btnRinnova = document.getElementById('btn-rinnova-multiplo');
    var maxInvii   = <?= \App\Libraries\PropostaAbbonamento::inviiMassimi() ?>;
    var contaRinn  = document.getElementById('conta-rinnovo');
    var riepilogo  = document.getElementById('riepilogo-selezione');

    // Le caselle delle righe indicate, di tutte le pagine; senza opzioni, tutte le righe.
    function caselle(opzioni) {
        return table.rows(opzioni || {}).nodes().toArray()
            .map(function (tr) { return tr.querySelector('.check-riga'); })
            .filter(Boolean);
    }

    function aggiungi(cb) {
        cb.checked = true;
        if (selezione.indexOf(cb.value) === -1) selezione.push(cb.value);
        righe[cb.value] = {
            stato:       cb.dataset.stato,
            rinnovabile: cb.dataset.rinnovabile === '1',
            anno:        cb.dataset.anno
        };
    }

    function togli(cb) {
        cb.checked = false;
        var i = selezione.indexOf(cb.value);
        if (i !== -1) selezione.splice(i, 1);
        delete righe[cb.value];
    }

    // Vero se ogni riga selezionata rispetta la condizione (e ce n'è almeno una).
    function tutte(condizione) {
        return selezione.length > 0 && selezione.every(function (id) { return condizione(righe[id]); });
    }

    // Accende o spegne un pulsante; il tooltip dice cosa fa, oppure perché è spento.
    function stato(btn, motivo, descrizione) {
        var span = btn.parentElement;
        btn.disabled = motivo !== null;
        bootstrap.Tooltip.getOrCreateInstance(span).setContent({
            '.tooltip-inner': motivo || descrizione || span.dataset.descrizione
        });
    }

    // Gli id rinnovabili della selezione, nell'ordine di selezione.
    function idRinnovabili() {
        return selezione.filter(function (id) { return righe[id].rinnovabile; });
    }

    function aggiorna() {
        var n           = selezione.length;
        var proposte    = tutte(function (r) { return r.stato === 'proposta'; });
        var rinnovabili = idRinnovabili().length;
        var anni        = selezione.map(function (id) { return righe[id].anno; })
            .filter(function (a, i, tutti) { return tutti.indexOf(a) === i; });
        var nessuno     = n === 0 ? 'Seleziona almeno un abbonamento' : null;

        stato(btnWord,    nessuno);
        stato(btnPdf,     nessuno);
        stato(btnAccetta, nessuno || (proposte ? null : 'Solo proposte: nella selezione ci sono altri abbonamenti'));
        // Eccezione voluta: Rinnova lavora sui rinnovabili della selezione e ignora gli altri, perché
        // il rinnovo di fine anno si fa con «Seleziona tutto» sugli attivi, che comprendono quelli
        // già rinnovati nei giorni precedenti.
        stato(btnRinnova, nessuno || (rinnovabili ? null : 'Nessun abbonamento rinnovabile nella selezione'),
            rinnovabili < n ? 'Rinnova i ' + rinnovabili + ' rinnovabili su ' + n + ' selezionati; gli altri sono già rinnovati o non rinnovabili' : null);
        stato(btnInvia,   nessuno
            || (! proposte       ? 'Solo proposte: nella selezione ci sono altri abbonamenti'
            :  anni.length > 1   ? 'Le proposte devono essere tutte dello stesso anno: filtra per anno'
            :  n > maxInvii      ? 'Al massimo ' + maxInvii + ' proposte per volta (Impostazioni → Email)'
            :  null));

        contaRinn.textContent = rinnovabili;
        contaRinn.classList.toggle('d-none', ! rinnovabili);

        riepilogo.textContent = n + (n === 1 ? ' selezionato' : ' selezionati');
        riepilogo.classList.toggle('d-none', n === 0);

        checkTutti.checked = n > 0;
    }

    document.getElementById('tabella-abbonamenti').addEventListener('change', function (e) {
        var cb = e.target;
        if (! cb.classList.contains('check-riga')) return;

        cb.checked ? aggiungi(cb) : togli(cb);
        aggiorna();
    });

    // Spunta le righe filtrate nell'ordine attuale della tabella; tolta la spunta, azzera tutto.
    checkTutti.addEventListener('change', function () {
        if (checkTutti.checked) {
            caselle({ search: 'applied', order: 'applied' }).forEach(aggiungi);
        } else {
            caselle().forEach(togli);
        }
        aggiorna();
    });

    // Accetta e Scarica Word: gli id arrivano dalla selezione, non dalle caselle nella pagina.
    var formMultiplo = document.getElementById('form-accetta-multiplo');
    formMultiplo.addEventListener('submit', function () {
        formMultiplo.querySelectorAll('input[name="ids[]"]').forEach(function (el) { el.remove(); });
        selezione.forEach(function (id) {
            var campo = document.createElement('input');
            campo.type  = 'hidden';
            campo.name  = 'ids[]';
            campo.value = id;
            formMultiplo.appendChild(campo);
        });
    });

    // Scarica PDF: convertiti a lotti e messi in uno zip nel browser — vedi zip-proposte-pdf.js
    btnPdf.addEventListener('click', function () {
        scaricaZipProposte(selezione.slice(), {
            url:   '<?= base_url('abbonamenti/proposte-pdf/lotto') ?>',
            lotto: <?= \App\Controllers\AbbonamentiController::LOTTO_PDF ?>,
            csrf:  formMultiplo.querySelector('input[name="<?= csrf_token() ?>"]').value,
            nome:  'Proposte abbonamento PDF <?= date('Y-m-d') ?>.zip'
        });
    });

    // Invia per email: dall'elenco le selezionate, dalla riga una sola — vedi invio-proposte.js
    var opzioniInvio = {
        urlPrepara: '<?= base_url('abbonamenti/invio/prepara') ?>',
        urlInvia:   '<?= base_url('abbonamenti') ?>/{id}/invia',
        pausa:      2000
    };
    btnInvia.addEventListener('click', function () {
        apriInvioProposte(selezione.slice(), opzioniInvio);
    });
    document.getElementById('tabella-abbonamenti').addEventListener('click', function (e) {
        var btn = e.target.closest('[data-invia]');
        if (btn) apriInvioProposte([btn.dataset.invia], opzioniInvio);
    });

    // Rinnova: apre il form del primo, gli altri viaggiano nell'indirizzo (spec, punto 5).
    btnRinnova.addEventListener('click', function () {
        var ids = idRinnovabili();
        location.href = '<?= base_url('abbonamenti') ?>/' + ids[0]
            + '/rinnova?coda=' + ids.slice(1).join(',');
    });
});
</script>
<?= $this->endSection() ?>
