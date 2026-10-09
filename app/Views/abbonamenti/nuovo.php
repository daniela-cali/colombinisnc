<?php
/**
 * @var string     $title
 * @var array|null $cliente     Preselezionato da ?cliente_id= o da rinnova(); null = mostra select
 * @var array      $clienti     Elenco completo per il select (vuoto in modalità rinnova)
 * @var array      $tipi        Righe da TipiInterventoModel::abbonabili()
 * @var array      $frequenze   AbbonamentiModel::FREQUENZE_LABEL
 * @var array|null $periodi     Periodi precaricati per rinnova(); null per nuovo
 * @var string|null $from       URL di ritorno dopo salvataggio
 * @var array|null $coda        Rinnovo in coda (da rinnova()): coda, fatti, saltati, posizione, totale,
 *                              urlSalta, urlInterrompi; null per nuovo e rinnovo singolo
 * @var array|null $abbonamento Pre-compilazione per rinnovo; null per nuovo. Nel rinnovo porta anche
 *                              prezzo_precedente e aumento_percento, per la riga sotto il prezzo
 * @var float      $listinoPulizia AbbonamentiModel::prezzoPuliziaFondo(): precompila la pulizia del fondo
 *                              quando l'abbonamento non ne porta una (nuovo, o rinnovo di uno senza)
 */
$this->extend('layouts/admin');

$operazioniStandardDefault = array_column($tipi, 'operazioni_standard', 'id');
?>
<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('abbonamenti') ?>">Abbonamenti</a></li>
    <li class="breadcrumb-item active"><?= esc($title) ?></li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-8">

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
                    <i class="bi bi-file-earmark-plus me-2"></i><?= esc($title) ?>
                </h3>
                <?php if ($coda): ?>
                    <div class="card-tools">
                        <span class="badge text-bg-primary">Rinnovo <?= (int) $coda['posizione'] ?> di <?= (int) $coda['totale'] ?></span>
                    </div>
                <?php endif ?>
            </div>
            <form action="<?= base_url('abbonamenti/store') ?>" method="post">
                <?= csrf_field() ?>
                <?php if ($from): ?>
                    <input type="hidden" name="from" value="<?= esc($from) ?>">
                <?php endif ?>
                <?php if (! empty($abbonamento['abbonamento_precedente_id'])): ?>
                    <input type="hidden" name="abbonamento_precedente_id" value="<?= (int) $abbonamento['abbonamento_precedente_id'] ?>">
                <?php endif ?>
                <?php if ($coda): ?>
                    <?php /* Il resto della coda: store() li legge per aprire il form successivo */ ?>
                    <input type="hidden" name="coda" value="<?= esc(implode(',', $coda['coda'])) ?>">
                    <input type="hidden" name="fatti" value="<?= (int) $coda['fatti'] ?>">
                    <input type="hidden" name="saltati" value="<?= esc(implode(',', $coda['saltati'])) ?>">
                <?php endif ?>

                <div class="card-body">

                    <!-- Cliente -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-person me-1"></i> Cliente</p>
                    <div class="row g-3 mb-4">
                        <?php //Caso cliente già definito da scheda cliente
                        if ($cliente): ?>
                            <div class="col-12">
                                <input type="hidden" name="cliente_id" value="<?= (int) $cliente['id'] ?>">
                                <input type="text" class="form-control" readonly
                                       value="<?= esc($cliente['tipo'] === 'persona_fisica'
                                           ? trim(($cliente['cognome'] ?? '') . ' ' . ($cliente['nome'] ?? ''))
                                           : $cliente['ragsoc']) ?>">
                            </div>
                        <?php else: //Caso cliente non definito (da pulsante nuovo)
                            ?>
                            <div class="col-12">
                                <label class="form-label">Cliente <span class="text-danger">*</span></label>
                                <select name="cliente_id" class="form-select">
                                    <option value="">— seleziona —</option>
                                    <?php foreach ($clienti as $c): ?>
                                        <option value="<?= $c['id'] ?>"
                                                <?= old('cliente_id', $abbonamento['cliente_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                            <?= esc($c['tipo'] === 'persona_fisica'
                                                ? trim(($c['cognome'] ?? '') . ' ' . ($c['nome'] ?? ''))
                                                : $c['ragsoc']) ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        <?php endif ?>
                    </div>

                    <!-- Contratto -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-file-text me-1"></i> Contratto</p>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label">Tipo abbonamento <span class="text-danger">*</span></label>
                            <select name="tipo_intervento_id" id="tipo-intervento-id" class="form-select">
                                <option value="">— seleziona —</option>
                                <?php foreach ($tipi as $t): ?>
                                    <option value="<?= $t['id'] ?>"
                                            data-ha-pulizia-fondo="<?= (int) $t['ha_pulizia_fondo'] ?>"
                                            data-categoria="<?= esc($t['categoria']) ?>"
                                            <?= old('tipo_intervento_id', $abbonamento['tipo_intervento_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
                                        <?= esc($t['nome']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>

                    <!-- Apparecchiature installate: solo addolcitori, mostrate dallo script in fondo -->
                    <div id="blocco-apparecchiature">
                        <p class="text-muted section-header mb-3"><i class="bi bi-cpu me-1"></i> Apparecchiature installate <span class="text-danger">*</span></p>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <textarea name="apparecchiature" id="apparecchiature" class="form-control" rows="3"><?= esc(old('apparecchiature', $abbonamento['apparecchiature'] ?? '')) ?></textarea>
                                <div class="form-text">Una riga per apparecchiatura, senza trattino iniziale: es. N. 1 ADDOLCITORE</div>
                            </div>
                        </div>
                    </div>

                    <!-- Operazioni incluse -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-list-check me-1"></i> Operazioni incluse</p>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <textarea name="operazioni_incluse" id="operazioni_incluse" class="form-control" rows="6"><?= esc(old('operazioni_incluse', $abbonamento['operazioni_incluse'] ?? '')) ?></textarea>
                            <div class="form-text">Una riga per operazione, senza trattino iniziale</div>
                        </div>
                    </div>

                    <!-- Periodo -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-calendar-range me-1"></i> Periodo di validità</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Data inizio <span class="text-danger">*</span></label>
                            <input type="date" name="data_inizio" class="form-control"
                                   value="<?= esc(old('data_inizio', $abbonamento['data_inizio'] ?? date('Y') . '-01-01')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data fine <span class="text-danger">*</span></label>
                            <input type="date" name="data_fine" class="form-control"
                                   value="<?= esc(old('data_fine', $abbonamento['data_fine'] ?? date('Y') . '-12-31')) ?>">
                        </div>
                    </div>

                    <!-- Periodi di frequenza -->
                    <?= view('abbonamenti/_form_periodi', ['frequenze' => $frequenze, 'periodi' => $periodi]) ?>

                    <!-- Prezzo e Note -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-cash me-1"></i> Prezzo e note</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Prezzo totale (€)</label>
                            <input type="text" data-currency-display="prezzo" class="form-control" inputmode="decimal" placeholder="0,00">
                            <input type="hidden" name="prezzo" id="prezzo"
                                   value="<?= esc(old('prezzo', $abbonamento['prezzo'] ?? '')) ?>">
                            <?php if (isset($abbonamento['prezzo_precedente'])): ?>
                                <div class="form-text">
                                    Anno precedente <?= number_format((float) $abbonamento['prezzo_precedente'], 2, ',', '.') ?> €
                                    — aumentato del <?= esc(rtrim(rtrim(number_format($abbonamento['aumento_percento'], 2, ',', ''), '0'), ',')) ?>%
                                    e arrotondato ai <?= \App\Models\AbbonamentiModel::ARROTONDAMENTO_RINNOVO ?> euro
                                </div>
                            <?php endif ?>
                        </div>
                        <div class="col-md-4" id="blocco-pulizia">
                            <label class="form-label">Pulizia del fondo su richiesta (€) <span class="text-danger">*</span></label>
                            <input type="text" data-currency-display="prezzo_pulizia_fondo" class="form-control" inputmode="decimal" placeholder="0,00">
                            <input type="hidden" name="prezzo_pulizia_fondo" id="prezzo_pulizia_fondo"
                                   value="<?= esc(old('prezzo_pulizia_fondo', $abbonamento['prezzo_pulizia_fondo'] ?? $listinoPulizia)) ?>">
                        </div>
                        <div class="col-md">
                            <label class="form-label">Modalità di pagamento</label>
                            <input type="text" name="modalita_pagamento" class="form-control"
                                   placeholder="es. a metà servizio, saldo ad Agosto"
                                   value="<?= esc(old('modalita_pagamento', $abbonamento['modalita_pagamento'] ?? '')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Note</label>
                            <textarea name="note" class="form-control" rows="2"><?= esc(old('note', $abbonamento['note'] ?? '')) ?></textarea>
                        </div>
                    </div>

                </div>
                <div class="card-footer card-azioni">
                    <?php if ($coda): ?>
                        <a href="<?= esc($coda['urlInterrompi']) ?>"
                           class="btn btn-sm btn-outline-secondary azione-ritorno">
                            <i class="bi bi-stop-circle me-1"></i>Interrompi
                        </a>
                        <a href="<?= esc($coda['urlSalta']) ?>"
                           class="btn btn-sm btn-outline-secondary azione-secondaria">
                            <i class="bi bi-skip-forward me-1"></i>Salta
                        </a>
                        <button type="submit" class="btn btn-sm btn-primary azione-primaria">
                            <i class="bi bi-check-lg me-1"></i><?= $coda['coda'] ? 'Salva e prossimo' : 'Salva e termina' ?>
                        </button>
                    <?php else: ?>
                        <a href="<?= esc($from ?: base_url('abbonamenti')) ?>"
                           class="btn btn-sm btn-outline-secondary azione-ritorno">
                            <i class="bi bi-arrow-left me-1"></i>Annulla
                        </a>
                        <button type="submit" class="btn btn-sm btn-primary azione-primaria">
                            <i class="bi bi-check-lg me-1"></i>Salva
                        </button>
                    <?php endif ?>
                </div>
            </form>
        </div>

    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const sel = document.getElementById('tipo-intervento-id');
    if (!sel) return;

    const operazioniStandardDefault = <?= json_encode($operazioniStandardDefault) ?>;

    // Il prezzo della pulizia, oltre a sparire, si disattiva: un campo disattivato non parte
    // col form, così un tipo senza pulizia non salva il listino precompilato.
    function aggiornaPulizia() {
        const opt = sel.options[sel.selectedIndex];
        const show = opt && opt.dataset.haPuliziaFondo === '1';
        if (typeof window.setPuliziaFondo === 'function') window.setPuliziaFondo(show);
        document.getElementById('blocco-pulizia').classList.toggle('d-none', ! show);
        document.getElementById('prezzo_pulizia_fondo').disabled = ! show;
    }

    function aggiornaOperazioniIncluse() {
        const testo = document.getElementById('operazioni_incluse');
        if (! testo) return;

        const nuovoDefault = operazioniStandardDefault[sel.value] || '';

        if (! testo.value) {
            testo.value = nuovoDefault;
            return;
        }

        if (confirm('Cambiare tipo riporta le operazioni incluse al valore standard del nuovo tipo, perdendo le eventuali modifiche fatte qui. Procedere?')) {
            testo.value = nuovoDefault;
        }
    }

    // Le apparecchiature servono solo agli addolcitori. Nascondere il campo non lo svuota:
    // tornando a un tipo addolcitori il testo è ancora lì.
    function aggiornaApparecchiature() {
        const opt = sel.options[sel.selectedIndex];
        const show = opt && opt.dataset.categoria === <?= json_encode(\App\Models\TipiInterventoModel::CATEGORIA_ADDOLCITORI) ?>;
        document.getElementById('blocco-apparecchiature').classList.toggle('d-none', ! show);
    }

    sel.addEventListener('change', function () {
        aggiornaPulizia();
        aggiornaOperazioniIncluse();
        aggiornaApparecchiature();
    });
    aggiornaPulizia();
    aggiornaApparecchiature();
})();
</script>
<script src="<?= asset_url('js/currency-input.js') ?>"></script>
<?= $this->endSection() ?>
