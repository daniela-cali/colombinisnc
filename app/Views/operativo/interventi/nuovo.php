<?php
/**
 * @var array    $clienti        Righe da ClientiModel::elencoCompleto()
 * @var array    $tecnici        Righe da PersonaleModel::elencoPerGruppi(['tecnico'])
 * @var array    $tipi           Righe da TipiInterventoModel::attivi()
 * @var array    $prioritaLabel  [codice => etichetta]
 * @var array    $statiLabel     [codice => etichetta]
 * @var array    $articoliPerCat Da ArticoliModel::perCategoria()
 * @var int      $cliente_id     Pre-selezione da ?cliente_id= (0 se non presente)
 * @var string   $from           URL di ritorno dopo il salvataggio (vuoto = lista interventi)
 */
$this->extend('layouts/admin');

$durateDefault = array_column($tipi, 'durata_default', 'id');
?>
<?= $this->section('title') ?>Nuovo intervento<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('operativo/interventi') ?>">Interventi</a></li>
    <li class="breadcrumb-item active">Nuovo</li>
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
                <h3 class="card-title mb-0"><i class="bi bi-plus-circle me-2"></i>Nuovo intervento</h3>
            </div>
            <form action="<?= base_url('operativo/interventi/store') ?>" method="post" id="form-nuovo">
                <?= csrf_field() ?>
                <?php if ($from): ?>
                    <input type="hidden" name="from" value="<?= esc($from) ?>">
                <?php endif ?>
                <div class="card-body">

                    <!-- Assegnazione -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-people me-1"></i> Assegnazione</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-7">
                            <label class="form-label">Cliente <span class="text-danger">*</span></label>
                            <select name="cliente_id" class="form-select">
                                <option value="">— seleziona —</option>
                                <?php foreach ($clienti as $c): ?>
                                    <option value="<?= $c['id'] ?>"
                                            <?= old('cliente_id', $cliente_id) == $c['id'] ? 'selected' : '' ?>>
                                        <?= esc($c['denominazione']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Tecnico assegnato</label>
                            <select name="tecnico_id" class="form-select">
                                <option value="">— nessuno —</option>
                                <?php foreach ($tecnici as $t): ?>
                                    <option value="<?= $t['id'] ?>"
                                            <?= old('tecnico_id') == $t['id'] ? 'selected' : '' ?>>
                                        <?= esc($t['cognome'] . ' ' . $t['nome']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
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
                                            <?= old('priorita', 'normale') === $codice ? 'selected' : '' ?>>
                                        <?= esc($etichetta) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo intervento</label>
                            <select name="tipo_intervento_id" id="tipo_intervento_id" class="form-select">
                                <option value="">— nessuno —</option>
                                <?php foreach ($tipi as $t): ?>
                                    <option value="<?= $t['id'] ?>"
                                            <?= old('tipo_intervento_id') == $t['id'] ? 'selected' : '' ?>>
                                        <?= esc($t['nome']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Stato <span class="text-danger">*</span></label>
                            <select name="stato" class="form-select">
                                <?php foreach ($statiLabel as $codice => $etichetta): ?>
                                    <option value="<?= $codice ?>"
                                            <?= old('stato', 'da_pianificare') === $codice ? 'selected' : '' ?>>
                                        <?= esc($etichetta) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="urgenza"
                                       id="urgenza" value="1"
                                       <?= old('urgenza') ? 'checked' : '' ?>>
                                <label class="form-check-label text-danger fw-semibold" for="urgenza">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Urgente
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Date e durata -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-calendar me-1"></i> Pianificazione</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Data pianificata</label>
                            <input type="date" name="data_pianificata" class="form-control"
                                   value="<?= esc(old('data_pianificata')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data scadenza</label>
                            <input type="date" name="data_scadenza" class="form-control"
                                   value="<?= esc(old('data_scadenza')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Durata stimata</label>
                            <div class="input-group">
                                <input type="number" name="durata_stimata" id="durata_stimata"
                                       class="form-control" min="5" max="480" step="5"
                                       value="<?= esc(old('durata_stimata')) ?>"
                                       placeholder="min">
                                <span class="input-group-text">min</span>
                            </div>
                        </div>
                    </div>

                    <!-- Note -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-sticky me-1"></i> Note</p>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <textarea name="note" class="form-control" rows="4"><?= esc(old('note')) ?></textarea>
                        </div>
                    </div>

                    <!-- Materiali -->
                    <p class="text-muted section-header mb-3"><i class="bi bi-box-seam me-1"></i> Materiali da portare</p>

                    <div id="lista-materiali" class="mb-3"></div>
                    <div id="hidden-materiali"></div>

                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small">Articolo / Descrizione</label>
                            <select id="sel-materiale" placeholder="Cerca articolo o digita descrizione libera…">
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
                            <input type="number" id="inp-qta" class="form-control form-control-sm" min="1" value="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Note</label>
                            <input type="text" id="inp-note" class="form-control form-control-sm" maxlength="255">
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="btn-add-mat" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-plus-lg me-1"></i>Aggiungi
                            </button>
                        </div>
                    </div>

                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="<?= esc($from ?: base_url('operativo/interventi')) ?>" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Annulla
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg me-1"></i>Salva
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/vendor/tom-select/tom-select.bootstrap5.min.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/tom-select/tom-select.complete.min.js') ?>"></script>
<script>
(function () {
    var durateDefault = <?= json_encode($durateDefault) ?>;
    var materiali = [];

    var ts = new TomSelect('#sel-materiale', {
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

    document.getElementById('tipo_intervento_id').addEventListener('change', function () {
        var durata = document.getElementById('durata_stimata');
        if (! durata.value && durateDefault[this.value]) {
            durata.value = durateDefault[this.value];
        }
    });

    document.getElementById('btn-add-mat').addEventListener('click', function () {
        var val  = ts.getValue();
        if (! val) { alert('Seleziona un articolo o digita una descrizione.'); return; }
        var qta  = parseInt(document.getElementById('inp-qta').value) || 1;
        var note = document.getElementById('inp-note').value;
        var isNum = /^\d+$/.test(val);
        var label = isNum ? ts.getOption(val).textContent.trim() : val;

        materiali.push({
            label:      label,
            articolo_id: isNum ? val : '',
            descrizione: isNum ? '' : val,
            quantita:   qta,
            note:       note
        });

        ts.clear();
        document.getElementById('inp-qta').value  = 1;
        document.getElementById('inp-note').value = '';
        renderMateriali();
    });

    function renderMateriali() {
        var lista  = document.getElementById('lista-materiali');
        var hidden = document.getElementById('hidden-materiali');
        lista.innerHTML  = '';
        hidden.innerHTML = '';

        if (materiali.length === 0) return;

        var html = '<table class="table table-sm align-middle mb-2"><thead><tr>'
            + '<th>Descrizione</th><th class="text-center" style="width:60px">Qtà</th>'
            + '<th>Note</th><th style="width:40px"></th></tr></thead><tbody>';

        materiali.forEach(function (m, i) {
            html += '<tr>'
                + '<td>' + escHtml(m.label) + '</td>'
                + '<td class="text-center">' + m.quantita + '</td>'
                + '<td class="text-muted small">' + escHtml(m.note) + '</td>'
                + '<td><button type="button" class="btn btn-sm btn-outline-danger" onclick="window._rimuoviMat(' + i + ')">'
                + '<i class="bi bi-trash"></i></button></td>'
                + '</tr>';

            hidden.innerHTML +=
                '<input type="hidden" name="materiali[' + i + '][articolo_id]" value="' + escAttr(m.articolo_id) + '">'
                + '<input type="hidden" name="materiali[' + i + '][descrizione]" value="' + escAttr(m.descrizione) + '">'
                + '<input type="hidden" name="materiali[' + i + '][quantita]" value="' + escAttr(m.quantita) + '">'
                + '<input type="hidden" name="materiali[' + i + '][note]" value="' + escAttr(m.note) + '">';
        });

        html += '</tbody></table>';
        lista.innerHTML = html;
    }

    window._rimuoviMat = function (i) {
        materiali.splice(i, 1);
        renderMateriali();
    };

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function escAttr(s) {
        return String(s).replace(/"/g,'&quot;');
    }
})();
</script>
<?= $this->endSection() ?>
