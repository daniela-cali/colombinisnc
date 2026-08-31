<?php
/**
 * @var list<string>          $headers          Intestazioni lette dal CSV
 * @var array<string,string>  $campi_dest       [campo => etichetta] da ClientiAdhocModel::CAMPI_IMPORTABILI
 * @var array<string,string>  $mapping_salvato  [nome colonna => campo] dell'import precedente
 */
$this->extend('layouts/admin');
?>
<?= $this->section('title') ?>Import Clienti — Mappa colonne<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<ol class="breadcrumb float-sm-end">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('impostazioni') ?>">Impostazioni</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('impostazioni/import-clienti') ?>">Import Clienti</a></li>
    <li class="breadcrumb-item active">Mappa colonne</li>
</ol>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="bi bi-arrow-left-right me-2"></i>Mappa le colonne del CSV</h3>
            </div>
            <form method="post" action="<?= base_url('impostazioni/import-clienti/esegui') ?>">
                <?= csrf_field() ?>
                <div class="card-body p-0">

                    <div class="alert alert-info m-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Per ogni colonna del file scegliere il campo corrispondente; lasciare
                        <strong>— Non importare —</strong> per quelle da ignorare. Il campo
                        <strong>Codice</strong> è obbligatorio: identifica il record e permette di
                        aggiornarlo ai caricamenti successivi. La scelta viene ricordata per la
                        prossima volta.
                    </div>

                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Colonna nel file</th>
                                <th>Campo di destinazione</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($headers as $indice => $header): ?>
                            <?php
                            // Preferenza al mapping dell'import precedente; in mancanza,
                            // combacia il nome della colonna con quello del campo.
                            $preselezionato = $mapping_salvato[$header] ?? '';
                            if ($preselezionato === '' && array_key_exists(strtolower($header), $campi_dest)) {
                                $preselezionato = strtolower($header);
                            }
                            ?>
                            <tr>
                                <td class="ps-3"><code><?= esc($header) ?></code></td>
                                <td>
                                    <select name="mapping[<?= $indice ?>]" class="form-select form-select-sm select-mapping">
                                        <option value="">— Non importare —</option>
                                        <?php foreach ($campi_dest as $campo => $etichetta): ?>
                                            <option value="<?= esc($campo, 'attr') ?>"
                                                <?= $preselezionato === $campo ? 'selected' : '' ?>>
                                                <?= esc($etichetta) ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>

                </div>
                <div class="card-footer card-azioni">
                    <a href="<?= base_url('impostazioni/import-clienti') ?>"
                       class="btn btn-sm btn-outline-secondary azione-ritorno">
                        <i class="bi bi-arrow-left me-1"></i>Ricarica file
                    </a>
                    <button type="submit" class="btn btn-sm btn-success azione-primaria">
                        <i class="bi bi-play-fill me-1"></i>Avvia import
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
    const selects = Array.from(document.querySelectorAll('.select-mapping'));
    if (selects.length === 0) return;

    // Copia master delle opzioni: le select vengono ricostruite a ogni cambio,
    // quindi serve una sorgente stabile da cui ripescare quelle liberate.
    const tutteOpzioni = Array.from(selects[0].options).map(o => ({ value: o.value, text: o.text }));

    function aggiornaOpzioni() {
        const usati = new Set(selects.map(s => s.value).filter(v => v !== ''));

        selects.forEach(sel => {
            const corrente = sel.value;
            sel.innerHTML = '';

            tutteOpzioni
                .filter(o => o.value === '' || o.value === corrente || !usati.has(o.value))
                .forEach(o => {
                    const opt = document.createElement('option');
                    opt.value = o.value;
                    opt.text  = o.text;
                    opt.selected = (o.value === corrente);
                    sel.appendChild(opt);
                });
        });
    }

    selects.forEach(sel => sel.addEventListener('change', aggiornaOpzioni));
    aggiornaOpzioni();
})();
</script>
<?= $this->endSection() ?>
