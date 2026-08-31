<?php
/**
 * Partial incluso da abbonamenti/nuovo.php e abbonamenti/edit.php.
 * Gestisce il widget dinamico dei periodi di frequenza.
 *
 * Variabili disponibili dallo scope del parent:
 * @var array      $frequenze  AbbonamentiModel::FREQUENZE_LABEL
 * @var array|null $periodi    Periodi precaricati (edit/rinnova); null = nuovo
 */

$initialPeriodi = old('periodi') ?: ($periodi ?? []);
?>

<p class="text-muted section-header mb-2"><i class="bi bi-calendar3 me-1"></i> Periodi di frequenza</p>
<p class="text-muted small mb-3">Definisci uno o più periodi con frequenze diverse (es. estivo settimanale, invernale mensile).</p>

<div id="periodi-container"></div>

<button type="button" class="btn btn-outline-secondary btn-sm mb-4" id="btn-add-periodo">
    <i class="bi bi-plus me-1"></i>Aggiungi periodo
</button>

<script>
(function () {
    const container  = document.getElementById('periodi-container');
    const freqOpts   = <?= json_encode($frequenze, JSON_UNESCAPED_UNICODE) ?>;
    const abbInizio  = document.querySelector('input[name="data_inizio"]');
    const abbFine    = document.querySelector('input[name="data_fine"]');
    let counter = 0;

    function buildFreqOptions(selected) {
        let html = '<option value="">— frequenza —</option>';
        for (const [val, label] of Object.entries(freqOpts)) {
            html += `<option value="${val}"${val === selected ? ' selected' : ''}>${label}</option>`;
        }
        return html;
    }

    function addPeriodo(dataInizio, dataFine, frequenza, conPuliziaFondo) {
        dataInizio      = dataInizio      || '';
        dataFine        = dataFine        || '';
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

    // La prima riga eredita sempre la Data inizio dell'abbonamento: con un solo periodo
    // non è una scelta, deve necessariamente coincidere (altrimenti resterebbe calendario scoperto).
    function sincronizzaPrimoPeriodo() {
        const prima = container.querySelector('.periodo-row');
        const inputInizio = prima ? prima.querySelector('input[name$="[data_inizio]"]') : null;
        if (inputInizio && abbInizio) inputInizio.value = abbInizio.value;
    }
    if (abbInizio) abbInizio.addEventListener('input', sincronizzaPrimoPeriodo);

    const initial = <?= json_encode(array_values($initialPeriodi), JSON_UNESCAPED_UNICODE) ?>;
    if (initial.length > 0) {
        initial.forEach(p => addPeriodo(p.data_inizio, p.data_fine, p.frequenza, p.con_pulizia_fondo));
    } else {
        addPeriodo(abbInizio ? abbInizio.value : '', '', '', '0');
    }

    // Data fine non si propone mai in automatico (nemmeno per la prima riga): l'utente la sceglie
    // sempre di persona, il "required" sul campo forza la scelta prima del salvataggio.
    document.getElementById('btn-add-periodo').addEventListener('click', () => {
        const righe      = container.querySelectorAll('.periodo-row');
        const ultima     = righe[righe.length - 1];
        const fineUltima = ultima ? ultima.querySelector('input[name$="[data_fine]"]') : null;
        let nuovaInizio  = '';
        if (fineUltima && fineUltima.value) {
            const giorno = new Date(fineUltima.value + 'T00:00:00');
            giorno.setDate(giorno.getDate() + 1);
            const pad = (n) => String(n).padStart(2, '0');
            nuovaInizio = giorno.getFullYear() + '-' + pad(giorno.getMonth() + 1) + '-' + pad(giorno.getDate());
        }
        addPeriodo(nuovaInizio, '', '', '0');
    });

    // Blocca il salvataggio se i periodi non coprono l'intero arco dell'abbonamento:
    // altrimenti resterebbero date senza nessun periodo a definirne la frequenza.
    const form = container.closest('form');
    if (form) {
        form.addEventListener('submit', (e) => {
            const righe = container.querySelectorAll('.periodo-row');
            if (righe.length === 0) return;

            const inizioPrima = righe[0].querySelector('input[name$="[data_inizio]"]');
            const fineUltima  = righe[righe.length - 1].querySelector('input[name$="[data_fine]"]');
            const problemi = [];
            if (abbInizio && inizioPrima && inizioPrima.value && inizioPrima.value !== abbInizio.value) {
                problemi.push('il primo periodo non inizia il ' + abbInizio.value + ' (Data inizio abbonamento)');
            }
            if (abbFine && fineUltima && fineUltima.value && fineUltima.value !== abbFine.value) {
                problemi.push('l\'ultimo periodo non finisce il ' + abbFine.value + ' (Data fine abbonamento)');
            }

            if (problemi.length > 0) {
                e.preventDefault();
                alert('I periodi non coprono l\'intero arco dell\'abbonamento:\n- ' + problemi.join('\n- ')
                    + '\n\nCorreggi le date dei periodi oppure il periodo di validità dell\'abbonamento.');
                return;
            }

            // Un periodo più corto di un mese è quasi sempre un errore di battitura sulla data.
            // Non si blocca — il caso raro deve restare possibile — ma si chiede conferma.
            // Il giorno zero invece è vietato davvero, dalla regola successiva_a lato server.
            const corti = [];
            righe.forEach((riga, i) => {
                const inizio = riga.querySelector('input[name$="[data_inizio]"]');
                const fine   = riga.querySelector('input[name$="[data_fine]"]');
                if (! inizio || ! fine || ! inizio.value || ! fine.value) return;

                const da = new Date(inizio.value + 'T00:00:00');
                const a  = new Date(fine.value + 'T00:00:00');
                const unMeseDopo = new Date(da);
                unMeseDopo.setMonth(unMeseDopo.getMonth() + 1);

                if (a < unMeseDopo) corti.push(i + 1);
            });

            if (corti.length > 0) {
                const quali = corti.length === 1
                    ? 'Il periodo ' + corti[0] + ' dura'
                    : 'I periodi ' + corti.join(', ') + ' durano';

                if (! confirm(quali + ' meno di un mese. Vuoi salvare lo stesso?')) {
                    e.preventDefault();
                }
            }
        });
    }
})();
</script>
