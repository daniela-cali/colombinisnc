<?php
/**
 * @var string     $title
 * @var array      $abbonamento Dati abbonamento esistente
 * @var array      $cliente     Dati cliente (non modificabile)
 * @var array      $tipi        Righe da TipiInterventoModel::abbonabili()
 * @var array      $frequenze   AbbonamentiModel::FREQUENZE_LABEL
 * @var array      $periodi     Periodi esistenti da AbbonamentiPeriodiModel::perAbbonamento()
 * @var string|null $from       URL di ritorno dopo salvataggio
 */
$this->extend('layouts/admin');

$operazioniStandardDefault = array_column($tipi, 'operazioni_standard', 'id');
?>
<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('abbonamenti') ?>">Abbonamenti</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('abbonamenti/' . $abbonamento['id']) ?>">Abbonamento</a></li>
    <li class="breadcrumb-item active">Modifica</li>
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

        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-pencil me-2"></i><?= esc($title) ?>
                </h3>
            </div>
            <form action="<?= base_url('abbonamenti/' . $abbonamento['id'] . '/update') ?>" method="post">
                <?= csrf_field() ?>
                <?php if ($from): ?>
                    <input type="hidden" name="from" value="<?= esc($from) ?>">
                <?php endif ?>
                <input type="hidden" name="cliente_id" value="<?= (int) $abbonamento['cliente_id'] ?>">

                <div class="card-body">

                    <!-- Cliente (sola lettura) -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-person me-1"></i> Cliente</p>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <input type="text" class="form-control" readonly
                                   value="<?= esc($cliente['tipo'] === 'persona_fisica'
                                       ? trim(($cliente['cognome'] ?? '') . ' ' . ($cliente['nome'] ?? ''))
                                       : $cliente['ragsoc']) ?>">
                        </div>
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
                                            <?= old('tipo_intervento_id', $abbonamento['tipo_intervento_id']) == $t['id'] ? 'selected' : '' ?>>
                                        <?= esc($t['nome']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>

                    <!-- Operazioni incluse -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-list-check me-1"></i> Operazioni incluse</p>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <textarea name="operazioni_incluse" id="operazioni_incluse" class="form-control" rows="6"><?= esc(old('operazioni_incluse', $abbonamento['operazioni_incluse'] ?? '')) ?></textarea>
                        </div>
                    </div>

                    <!-- Periodo -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-calendar-range me-1"></i> Periodo di validità</p>
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Modificare le date o i periodi non rigenera gli interventi già creati.
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Data inizio <span class="text-danger">*</span></label>
                            <input type="date" name="data_inizio" class="form-control"
                                   value="<?= esc(old('data_inizio', $abbonamento['data_inizio'])) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data fine <span class="text-danger">*</span></label>
                            <input type="date" name="data_fine" class="form-control"
                                   value="<?= esc(old('data_fine', $abbonamento['data_fine'])) ?>">
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
                        </div>
                        <div class="col-md-8">
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
                <div class="card-footer d-flex justify-content-between">
                    <a href="<?= esc($from ?: base_url('abbonamenti/' . $abbonamento['id'])) ?>" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Annulla
                    </a>
                    <button type="submit" class="btn btn-warning btn-sm ms-auto">
                        <i class="bi bi-check-lg me-1"></i>Salva modifiche
                    </button>
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

    function aggiornaPulizia() {
        const opt = sel.options[sel.selectedIndex];
        const show = opt && opt.dataset.haPuliziaFondo === '1';
        if (typeof window.setPuliziaFondo === 'function') window.setPuliziaFondo(show);
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

    sel.addEventListener('change', function () {
        aggiornaPulizia();
        aggiornaOperazioniIncluse();
    });
    aggiornaPulizia();
})();
</script>
<script src="<?= base_url('js/currency-input.js') ?>"></script>
<?= $this->endSection() ?>
