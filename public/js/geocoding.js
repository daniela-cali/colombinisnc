(function () {
    'use strict';

    /**
     * Geocodifica generica via Nominatim.
     *
     * Il bottone trigger deve avere l'attributo [data-geocoder].
     * I campi vengono cercati per name all'interno del <form> più vicino.
     *
     * Data-attribute del bottone (tutti opzionali — default tra parentesi):
     *   data-indirizzo  nome del campo indirizzo  ("indirizzo")
     *   data-cap        nome del campo CAP         ("cap")
     *   data-citta      nome del campo città       ("citta")
     *   data-nazione    nome del campo nazione     ("nazione")
     *   data-lat        nome del campo latitudine  ("lat")
     *   data-lng        nome del campo longitudine ("lng")
     *   data-result     id del div di feedback     ("geo-result")
     *   data-fallita    nome del campo flag errore ("geocodifica_fallita")
     *
     * Esempio per un form con nomi standard (nessun data-attribute necessario):
     *   <button type="button" data-geocoder>...</button>
     *
     * Esempio per il form parametri con prefisso "sede_":
     *   <button type="button" data-geocoder
     *           data-indirizzo="sede_indirizzo"
     *           data-cap="sede_cap"
     *           data-citta="sede_citta"
     *           data-lat="sede_lat"
     *           data-lng="sede_lng"
     *           data-result="geo-sede-result">...</button>
     */
    function initGeocoder(btn) {
        btn.addEventListener('click', function () {
            var form = btn.closest('form');
            if (! form) return;

            // Legge il nome del campo dal data-attribute o usa il default
            function field(attr, defaultName) {
                var name = btn.dataset[attr] || defaultName;
                return form.querySelector('[name="' + name + '"]');
            }

            var indirizzoEl = field('indirizzo', 'indirizzo');
            var capEl       = field('cap',       'cap');
            var cittaEl     = field('citta',     'citta');
            var nazioneEl   = field('nazione',   'nazione');
            var latEl       = field('lat',        'lat');
            var lngEl       = field('lng',        'lng');
            var fallitaEl   = field('fallita',    'geocodifica_fallita');
            var geocodedEl  = field('geocodedAt', 'geocoded_at');
            var resultEl    = document.getElementById(btn.dataset.result || 'geo-result');

            var indirizzo = indirizzoEl ? indirizzoEl.value.trim() : '';
            var cap       = capEl       ? capEl.value.trim()       : '';
            var citta     = cittaEl     ? cittaEl.value.trim()     : '';
            var nazione   = nazioneEl   ? nazioneEl.value.trim()   : 'Italia';

            if (! indirizzo && ! citta) {
                if (resultEl) resultEl.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Inserisci indirizzo o città prima.</span>';
                return;
            }

            btn.disabled = true;
            if (resultEl) resultEl.innerHTML = '<span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Ricerca in corso…</span>';

            var q   = [indirizzo, cap, citta, nazione].filter(Boolean).join(', ');
            var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q);

            fetch(url, { headers: { 'Accept-Language': 'it' } })
                .then(function (r) {
                    if (! r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(function (data) {
                    if (data && data[0]) {
                        var lat = parseFloat(data[0].lat).toFixed(6);
                        var lng = parseFloat(data[0].lon).toFixed(6);

                        if (latEl)      latEl.value      = lat;
                        if (lngEl)      lngEl.value      = lng;
                        if (fallitaEl)  fallitaEl.value  = '0';
                        if (geocodedEl) geocodedEl.value = new Date().toISOString().replace('T', ' ').substring(0, 19);

                        if (resultEl) resultEl.innerHTML =
                            '<span class="text-success"><i class="bi bi-check-circle me-1"></i>'
                            + 'Coordinate aggiornate: ' + lat + ', ' + lng + '</span>';
                    } else {
                        if (fallitaEl) fallitaEl.value = '1';
                        if (resultEl) resultEl.innerHTML =
                            '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>'
                            + 'Indirizzo non trovato — verifica i dati.</span>';
                    }
                })
                .catch(function () {
                    if (fallitaEl) fallitaEl.value = '1';
                    if (resultEl) resultEl.innerHTML =
                        '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>'
                        + 'Errore di rete — riprova tra qualche secondo.</span>';
                })
                .finally(function () { btn.disabled = false; });
        });
    }

    // Inizializza tutti i bottoni [data-geocoder] presenti sulla pagina
    document.querySelectorAll('[data-geocoder]').forEach(initGeocoder);
})();
