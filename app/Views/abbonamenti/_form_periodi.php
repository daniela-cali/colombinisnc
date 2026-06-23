<?php
/**
 * Partial incluso da abbonamenti/nuovo.php e abbonamenti/edit.php.
 * Gestisce il widget dinamico dei periodi di frequenza.
 *
 * Variabili disponibili dallo scope del parent:
 * @var array      $frequenze  AbbonamentiModel::FREQUENZE_LABEL
 * @var array|null $periodi    Periodi precaricati (edit/rinnova); null = nuovo
 */

$y = date('Y');
$initialPeriodi = old('periodi') ?: ($periodi ?? []);
if (empty($initialPeriodi)) {
    $initialPeriodi = [['data_inizio' => "$y-01-01", 'data_fine' => "$y-12-31", 'frequenza' => '', 'con_pulizia_fondo' => '0']];
}
?>

<p class="text-muted section-header mb-2"><i class="bi bi-calendar3 me-1"></i> Periodi di frequenza</p>
<p class="text-muted small mb-3">Definisci uno o più periodi con frequenze diverse (es. estivo settimanale, invernale mensile).</p>

<div id="periodi-container"></div>

<button type="button" class="btn btn-outline-secondary btn-sm mb-4" id="btn-add-periodo">
    <i class="bi bi-plus me-1"></i>Aggiungi periodo
</button>

<script>
(function () {
    const container = document.getElementById('periodi-container');
    const freqOpts  = <?= json_encode($frequenze, JSON_UNESCAPED_UNICODE) ?>;
    let counter = 0;

    function buildFreqOptions(selected) {
        let html = '<option value="">— frequenza —</option>';
        for (const [val, label] of Object.entries(freqOpts)) {
            html += `<option value="${val}"${val === selected ? ' selected' : ''}>${label}</option>`;
        }
        return html;
    }

    function addPeriodo(dataInizio, dataFine, frequenza, conPuliziaFondo) {
        const y = new Date().getFullYear();
        dataInizio      = dataInizio      || (y + '-01-01');
        dataFine        = dataFine        || (y + '-12-31');
        frequenza       = frequenza       || '';
        conPuliziaFondo = conPuliziaFondo != null ? String(conPuliziaFondo) : '0';

        const showPulizia = container.dataset.showPulizia === 'true';
        const n = counter++;
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-end mb-2 periodo-row';
        row.innerHTML = `
            <div class="col-md-3">
                <label class="form-label small mb-1">Data inizio</label>
                <input type="date" name="periodi[${n}][data_inizio]" class="form-control form-control-sm"
                       value="${dataInizio}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Data fine</label>
                <input type="date" name="periodi[${n}][data_fine]" class="form-control form-control-sm"
                       value="${dataFine}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Frequenza</label>
                <select name="periodi[${n}][frequenza]" class="form-select form-select-sm" required>
                    ${buildFreqOptions(frequenza)}
                </select>
            </div>
            <div class="col-md-2 cell-pulizia${showPulizia ? '' : ' d-none'}">
                <label class="form-label small mb-1">Pulizia fondo</label>
                <select name="periodi[${n}][con_pulizia_fondo]" class="form-select form-select-sm">
                    <option value="0"${conPuliziaFondo === '0' ? ' selected' : ''}>No</option>
                    <option value="1"${conPuliziaFondo === '1' ? ' selected' : ''}>Sì</option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small mb-1 d-block">&nbsp;</label>
                <button type="button" class="btn btn-outline-danger btn-sm w-100"
                        onclick="removePeriodo(this)" title="Rimuovi periodo">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(row);
    }

    window.removePeriodo = function (btn) {
        if (container.querySelectorAll('.periodo-row').length > 1) {
            btn.closest('.periodo-row').remove();
        }
    };

    // Chiamata dalla view parent per mostrare/nascondere la colonna pulizia fondo.
    window.setPuliziaFondo = function (show) {
        container.dataset.showPulizia = show ? 'true' : 'false';
        container.querySelectorAll('.cell-pulizia').forEach(el => {
            el.classList.toggle('d-none', !show);
        });
    };

    container.dataset.showPulizia = 'false';

    const initial = <?= json_encode(array_values($initialPeriodi), JSON_UNESCAPED_UNICODE) ?>;
    initial.forEach(p => addPeriodo(p.data_inizio, p.data_fine, p.frequenza, p.con_pulizia_fondo));

    document.getElementById('btn-add-periodo').addEventListener('click', () => addPeriodo());
})();
</script>
