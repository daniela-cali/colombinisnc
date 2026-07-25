(function () {
    'use strict';

    /**
     * Mappa Leaflet "posizione" riusabile — scheda cliente, scheda cantiere, ecc.
     * Nessuna logica specifica dell'entità: tutto arriva via data-attribute sul
     * contenitore #mappa-posizione (lat, lng, città, nazione, sede) e id generici
     * per bottoni/form (vedi docs/spec/mappa_cliente_spec.md e
     * docs/spec/cantieri_luogo_referente_spec.md). Ogni pagina ne ha al più uno,
     * quindi non serve parametrizzare gli id.
     */
    var container = document.getElementById('mappa-posizione');
    if (! container) return;

    delete L.Icon.Default.prototype._getIconUrl;
    var iconBase = container.dataset.iconBase;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: iconBase + 'marker-icon-2x.png',
        iconUrl:       iconBase + 'marker-icon.png',
        shadowUrl:     iconBase + 'marker-shadow.png',
    });

    var latDb = parseFloat(container.dataset.lat);
    var lngDb = parseFloat(container.dataset.lng);
    var haPosizione = ! isNaN(latDb) && ! isNaN(lngDb);

    var sedeLat = parseFloat(container.dataset.sedeLat);
    var sedeLng = parseFloat(container.dataset.sedeLng);

    var btnGoogle     = document.getElementById('btn-google-maps');
    var btnCorreggi   = document.getElementById('btn-correggi-posizione');
    var btnAnnulla    = document.getElementById('btn-annulla-posizione');
    var formPosizione = document.getElementById('form-posizione');
    var latInput      = document.getElementById('posizione-lat-input');
    var lngInput      = document.getElementById('posizione-lng-input');

    var map = L.map('mappa-posizione', { scrollWheelZoom: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap',
    }).addTo(map);

    // Zoom da rotella attivo solo dopo un click sull'overlay, disattivato di
    // nuovo appena il mouse esce dalla mappa — evita che scrollare la pagina
    // con il cursore sopra la mappa la zoomi per errore invece di scorrere.
    var overlayZoom  = document.getElementById('mappa-overlay-zoom');
    var mappaWrapper = container.parentElement;

    overlayZoom.addEventListener('click', function () {
        map.scrollWheelZoom.enable();
        overlayZoom.classList.add('d-none');
    });

    mappaWrapper.addEventListener('mouseleave', function () {
        map.scrollWheelZoom.disable();
        overlayZoom.classList.remove('d-none');
    });

    // Pallino fisso della sede aziendale, sempre visibile e mai modificabile da qui
    // (si distingue volutamente dal pin dell'entità, non è un marker trascinabile).
    if (! isNaN(sedeLat) && ! isNaN(sedeLng)) {
        L.circleMarker([sedeLat, sedeLng], {
            radius: 8,
            color: '#fff',
            weight: 2,
            fillColor: '#dc3545',
            fillOpacity: 1,
        }).addTo(map).bindTooltip('Sede aziendale');
    }

    var marker = null;
    var modificaAttiva = false;

    function aggiornaHidden(lat, lng) {
        latInput.value = lat.toFixed(7);
        lngInput.value = lng.toFixed(7);
    }

    function aggiornaLinkGoogle(lat, lng) {
        btnGoogle.href = 'https://www.google.com/maps/dir/?api=1&destination=' + lat + ',' + lng;
        btnGoogle.classList.remove('disabled');
    }

    function piazzaMarker(lat, lng, draggable) {
        if (marker) map.removeLayer(marker);
        marker = L.marker([lat, lng], { draggable: draggable }).addTo(map);
        // Il listener va collegato sempre, non solo quando draggable è già true alla
        // creazione: il marker iniziale (view-mode) nasce non trascinabile e diventa
        // draggable solo dopo, tramite marker.dragging.enable() in "Correggi posizione".
        marker.on('dragend', function () {
            var p = marker.getLatLng();
            aggiornaHidden(p.lat, p.lng);
        });
        aggiornaHidden(lat, lng);
    }

    function centraSuSede() {
        if (! isNaN(sedeLat) && ! isNaN(sedeLng)) {
            map.setView([sedeLat, sedeLng], 10);
        } else {
            map.setView([44.3, 8.3], 9); // fallback estremo: area Liguria
        }
    }

    if (haPosizione) {
        map.setView([latDb, lngDb], 16);
        piazzaMarker(latDb, lngDb, false);
        aggiornaLinkGoogle(latDb, lngDb);
    } else {
        // Nessuna posizione precisa: prova a centrare sulla città (non salvato, solo per orientare la mappa)
        var citta   = container.dataset.citta;
        var nazione = container.dataset.nazione || 'Italia';

        if (citta) {
            var q = encodeURIComponent(citta + ', ' + nazione);
            fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + q, { headers: { 'Accept-Language': 'it' } })
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (data) {
                    if (data && data[0]) {
                        map.setView([parseFloat(data[0].lat), parseFloat(data[0].lon)], 13);
                    } else {
                        centraSuSede();
                    }
                })
                .catch(centraSuSede);
        } else {
            centraSuSede();
        }
    }

    map.on('click', function (e) {
        if (! modificaAttiva) return;
        piazzaMarker(e.latlng.lat, e.latlng.lng, true);
    });

    btnCorreggi.addEventListener('click', function () {
        modificaAttiva = true;
        formPosizione.classList.remove('d-none');
        btnCorreggi.classList.add('d-none');
        btnGoogle.classList.add('d-none');
        if (marker) marker.dragging.enable();
    });

    btnAnnulla.addEventListener('click', function () {
        modificaAttiva = false;
        formPosizione.classList.add('d-none');
        btnCorreggi.classList.remove('d-none');
        btnGoogle.classList.remove('d-none');
        if (haPosizione) {
            piazzaMarker(latDb, lngDb, false);
            map.setView([latDb, lngDb], 16);
        } else if (marker) {
            map.removeLayer(marker);
            marker = null;
        }
    });
})();
