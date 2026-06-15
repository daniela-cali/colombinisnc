<?php
/**
 * Picker Font Awesome riutilizzabile.
 *
 * @var string $suffix       Suffisso univoco per gli ID (es. 'new', 'edit')
 * @var string $valoreIcona  Nome icona FA attuale (es. 'fa-wrench'), o stringa vuota
 */
$iconaCorrente = $valoreIcona ?? '';
$iconaPreview  = $iconaCorrente ?: 'fa-question';
?>
<div>
    <label class="form-label form-label-sm">Icona <small class="text-muted">(Font Awesome)</small></label>
    <div class="input-group input-group-sm">
        <span class="input-group-text">
            <i class="fas <?= esc($iconaPreview) ?>" id="icona-preview-<?= $suffix ?>"></i>
        </span>
        <input type="text" name="icona" id="icona-<?= $suffix ?>" class="form-control form-control-sm icona-picker-field"
               value="<?= esc($iconaCorrente) ?>" placeholder="fa-wrench" maxlength="50" readonly
               onclick="toggleIconaPicker('<?= $suffix ?>')">
        <button type="button" class="btn btn-outline-secondary"
                onclick="toggleIconaPicker('<?= $suffix ?>')">
            <i class="bi bi-grid me-1"></i><span class="d-none d-sm-inline">Scegli</span>
        </button>
    </div>
    <div id="icona-picker-<?= $suffix ?>" class="border rounded p-2 mt-1"
         style="display:none;background:#fff;position:relative;z-index:200;">
        <input type="text" id="icona-search-<?= $suffix ?>" class="form-control form-control-sm mb-2"
               placeholder="Cerca icona… (es. acqua, filtro, camion)">
        <div id="icona-grid-<?= $suffix ?>"
             style="display:flex;flex-wrap:wrap;gap:6px;max-height:260px;overflow-y:auto;"></div>
        <p id="icona-no-results-<?= $suffix ?>" class="text-muted small mt-2 mb-0" style="display:none;">
            Nessuna icona trovata.
        </p>
    </div>
</div>

<script>
(function () {
    if (!window.ICONE_FA) {
        window.ICONE_FA = [
            /* acqua / piscine */
            ['fa-person-swimming','nuoto'],['fa-water','acqua'],['fa-water-ladder','scala piscina'],
            ['fa-droplet','goccia'],['fa-droplet-slash','senza acqua'],['fa-faucet','rubinetto'],
            ['fa-faucet-drip','perdita'],['fa-shower','doccia'],['fa-bath','vasca'],
            ['fa-hot-tub','idromassaggio'],['fa-fill-drip','riempimento'],['fa-bucket','secchio'],
            ['fa-pump-medical','pompa'],
            ['fa-fish','acquario'],['fa-fish-fins','acquatico'],
            /* impianti / trattamento */
            ['fa-filter','filtro'],['fa-oil-can','lubrificazione'],['fa-gas-pump','carburante'],
            ['fa-flask','chimica'],['fa-vial','analisi'],['fa-flask-vial','laboratorio'],
            ['fa-vial-circle-check','test ok'],['fa-tablets','prodotti'],
            ['fa-prescription-bottle-medical','contenitore'],['fa-radiation','trattamento'],
            /* clima / temperatura */
            ['fa-fire','fuoco'],['fa-fire-flame-curved','fiamma'],['fa-fire-extinguisher','estintore'],
            ['fa-snowflake','freddo'],['fa-thermometer','termometro'],['fa-thermometer-half','temperatura'],
            ['fa-temperature-high','caldo'],['fa-temperature-arrow-up','riscaldamento'],
            ['fa-temperature-arrow-down','raffreddamento'],['fa-cloud-rain','pioggia'],
            ['fa-wind','vento'],['fa-solar-panel','solare'],
            ['fa-bolt','corrente'],['fa-plug','spina'],['fa-battery-full','batteria'],
            /* utensili / manutenzione */
            ['fa-screwdriver-wrench','manutenzione'],['fa-wrench','chiave'],
            ['fa-screwdriver','cacciavite'],['fa-hammer','martello'],
            ['fa-gear','ingranaggio'],['fa-gears','ingranaggi'],
            ['fa-toolbox','cassetta attrezzi'],['fa-helmet-safety','sicurezza'],
            ['fa-ruler','misura'],['fa-ruler-combined','progetto'],
            ['fa-person-digging','scavo'],['fa-box','materiale'],
            ['fa-box-open','consegna'],['fa-boxes-stacked','magazzino'],
            /* edifici / strutture */
            ['fa-house','residenziale'],['fa-building','condominio'],['fa-industry','industria'],
            ['fa-warehouse','capannone'],['fa-shop','negozio'],['fa-hospital','ospedale'],
            ['fa-building-columns','ente pubblico'],['fa-campground','campeggio'],
            ['fa-tent','tendostruttura'],['fa-person-shelter','rifugio'],
            /* veicoli */
            ['fa-truck','camion'],['fa-truck-fast','urgente'],['fa-van-shuttle','furgone'],
            ['fa-car','auto'],['fa-car-side','veicolo'],['fa-motorcycle','moto'],
            /* commerciale / ufficio */
            ['fa-handshake','commerciale'],['fa-briefcase','lavoro'],
            ['fa-file-invoice','fattura'],['fa-clipboard-list','lista lavori'],
            ['fa-chart-bar','statistiche'],['fa-calendar','agenda'],
            ['fa-calendar-days','programmazione'],['fa-calendar-check','scadenza'],
            ['fa-clock','orario'],['fa-bell','notifica'],['fa-phone','telefono'],
            ['fa-phone-volume','assistenza'],['fa-envelope','email'],
            ['fa-location-dot','posizione'],['fa-tag','etichetta'],['fa-star','preferito'],
            ['fa-check-circle','completato'],['fa-flag','segnalazione'],
            ['fa-info-circle','informazione'],['fa-exclamation-triangle','attenzione'],
            /* persone */
            ['fa-user','tecnico'],['fa-users','squadra'],['fa-user-cog','responsabile'],
            /* ecologico */
            ['fa-leaf','eco'],['fa-seedling','sostenibile'],['fa-recycle','riciclo'],
            ['fa-earth-europe','europa'],['fa-globe','globale'],
        ];
    }

    if (!window.toggleIconaPicker) {
        window.toggleIconaPicker = function (sfx) {
            var picker = document.getElementById('icona-picker-' + sfx);
            var aperto = picker.style.display !== 'none';
            picker.style.display = aperto ? 'none' : 'block';
            if (!aperto) {
                var search = document.getElementById('icona-search-' + sfx);
                search.value = '';
                renderIconaFa(sfx, window.ICONE_FA);
                search.focus();
            }
        };
    }

    if (!window.renderIconaFa) {
        window.renderIconaFa = function (sfx, list) {
            var grid  = document.getElementById('icona-grid-' + sfx);
            var input = document.getElementById('icona-' + sfx);
            var noRes = document.getElementById('icona-no-results-' + sfx);

            grid.innerHTML = '';
            list.forEach(function (item) {
                var cls = item[0];
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.title = cls + ' — ' + item[1];
                btn.className = 'btn btn-sm btn-outline-secondary' + (input.value === cls ? ' active' : '');
                btn.style.cssText = 'width:42px;height:42px;padding:0;font-size:18px;color:#212529;';
                btn.innerHTML = '<i class="fas ' + cls + '"></i>';
                btn.addEventListener('click', function () {
                    input.value = cls;
                    document.getElementById('icona-preview-' + sfx).className = 'fas ' + cls;
                    grid.querySelectorAll('.btn').forEach(function (b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    document.getElementById('icona-picker-' + sfx).style.display = 'none';
                });
                grid.appendChild(btn);
            });
            noRes.style.display = list.length ? 'none' : 'block';
        };
    }

    document.getElementById('icona-search-<?= $suffix ?>').addEventListener('input', function () {
        var q = this.value.trim().toLowerCase();
        var filtered = q
            ? window.ICONE_FA.filter(function (i) { return i[0].includes(q) || i[1].includes(q); })
            : window.ICONE_FA;
        window.renderIconaFa('<?= $suffix ?>', filtered);
    });

    document.addEventListener('click', function (e) {
        var picker = document.getElementById('icona-picker-<?= $suffix ?>');
        if (!picker || picker.style.display === 'none') return;
        var input  = document.getElementById('icona-<?= $suffix ?>');
        var toggle = input ? input.nextElementSibling : null;
        if (!picker.contains(e.target) && e.target !== input && e.target !== toggle
            && !(toggle && toggle.contains(e.target))) {
            picker.style.display = 'none';
        }
    });
})();
</script>
