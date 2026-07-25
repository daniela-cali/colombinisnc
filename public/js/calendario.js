(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var cfg = window.CalendarioConfig;

        var csrfHash      = cfg.csrfHash;
        var oraInizio      = cfg.oraInizio;
        var filtroTecnico  = '';
        var tecnici        = cfg.tecnici;
        var assenzePerDipendente = cfg.assenzePerDipendente;
        var from           = encodeURIComponent(cfg.urls.calendario);
        var urlInterventi  = cfg.urls.interventi;

        // Id dell'evento da evidenziare non appena viene montato (barra "In ritardo":
        // dopo un gotoDate() verso una data fuori dalla vista corrente, il fetch degli
        // eventi è asincrono, quindi il flash non può scattare subito dopo la chiamata).
        var pendingFlashId = null;

        function escHtml(s) {
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        // Riempie (o nasconde) il blocco "Materiali da portare" nel modal dettaglio intervento.
        function popolaMaterialiModal(lista) {
            var wrap = document.getElementById('modal-materiali-wrap');
            var list = document.getElementById('modal-materiali-list');
            if (!lista || !lista.length) {
                wrap.style.display = 'none';
                list.innerHTML = '';
                return;
            }
            list.innerHTML = lista.map(function (m) {
                var nota = m.note ? ' <em>(' + escHtml(m.note) + ')</em>' : '';
                return '<li>' + (m.qta != null ? escHtml(m.qta) + '&times; ' : '') + escHtml(m.desc || '') + nota + '</li>';
            }).join('');
            wrap.style.display = '';
        }

        // ---- Giorno selezionato (Genera viaggio) ----
        function selezionaData(dateStr) {
            document.querySelectorAll('.fc-day-selected').forEach(function (el) {
                el.classList.remove('fc-day-selected');
            });
            document.querySelectorAll('[data-date="' + dateStr + '"]').forEach(function (el) {
                el.classList.add('fc-day-selected');
            });
            document.getElementById('form-data-giornata').value = dateStr;
            var p = dateStr.split('-');
            document.getElementById('label-data-giornata').textContent = p[2] + '/' + p[1] + '/' + p[0];
            document.getElementById('btn-genera-viaggio').disabled = false;
        }

        document.getElementById('calendario').addEventListener('click', function (e) {
            var cell = e.target.closest('.fc-col-header-cell[data-date]');
            if (cell) selezionaData(cell.dataset.date);
        });

        // ---- Filtro tecnici ----
        document.querySelectorAll('.btn-filtro').forEach(function (btn) {
            btn.addEventListener('click', function () {
                filtroTecnico = this.dataset.id;
                document.getElementById('form-tecnico-giornata').value = filtroTecnico;
                document.querySelectorAll('.btn-filtro').forEach(function (b) {
                    if (b.dataset.id === '') {
                        b.classList.toggle('btn-secondary',         b.dataset.id === filtroTecnico);
                        b.classList.toggle('btn-outline-secondary', b.dataset.id !== filtroTecnico);
                    } else {
                        b.style.opacity = (b.dataset.id === filtroTecnico || filtroTecnico === '') ? '1' : '.4';
                    }
                    b.classList.toggle('active', b.dataset.id === filtroTecnico);
                });
                calendar.refetchEvents();
            });
        });

        // ---- Tecnico select nel modal pianifica ----
        function buildTecnicoSelect(selectEl) {
            var html = '<option value="">— Non assegnato —</option>';
            tecnici.forEach(function (t) {
                html += '<option value="' + t.id + '">' + t.cognome + ' ' + t.nome + '</option>';
            });
            selectEl.innerHTML = html;
        }

        // ---- Pool Draggable ----
        new FullCalendar.Draggable(document.getElementById('pool-container'), {
            itemSelector: '.pool-card',
            eventData: function (cardEl) {
                return {
                    id:       'pool-' + cardEl.dataset.id,
                    title:    cardEl.dataset.cliente || ('#' + cardEl.dataset.id),
                    duration: { minutes: parseInt(cardEl.dataset.durata) || 60 },
                };
            },
        });

        // ---- Click su card del pool: stesso modal dettaglio degli eventi pianificati ----
        document.getElementById('pool-container').addEventListener('click', function (e) {
            var card = e.target.closest('.pool-card');
            if (!card) return;

            var id = card.dataset.id;
            var url = urlInterventi + '/' + id;

            var scadenzaStr = null;
            if (card.dataset.scadenza) {
                var ep = card.dataset.scadenza.split('-');
                scadenzaStr = ep[2] + '/' + ep[1] + '/' + ep[0];
            }

            var materiali = [];
            try { materiali = JSON.parse(card.dataset.materiali || '[]'); } catch (err) {}

            document.getElementById('modal-icona').className = 'fas ' + (card.dataset.icona || 'fa-wrench') + ' me-2';
            document.getElementById('modal-cliente').textContent = card.dataset.cliente || ('#' + id);
            document.getElementById('modal-content').style.borderLeft = '4px solid #6c757d';
            document.getElementById('modal-tipo').textContent    = card.dataset.tipoNome || '—';
            document.getElementById('modal-tecnico').textContent = card.dataset.tecnicoNome || 'Non assegnato';
            document.getElementById('modal-data').textContent    = 'Da pianificare';
            document.getElementById('modal-stato').innerHTML     = '<span class="badge bg-secondary">Da pianificare</span>';
            document.getElementById('modal-descrizione').textContent = card.dataset.descr || '';
            document.getElementById('modal-descrizione-wrap').style.display = card.dataset.descr ? '' : 'none';
            if (card.dataset.creato) {
                var cp = card.dataset.creato.substring(0, 10).split('-');
                document.getElementById('modal-creato').textContent = cp[2] + '/' + cp[1] + '/' + cp[0];
            }
            if (scadenzaStr) {
                document.getElementById('modal-scadenza').textContent = scadenzaStr;
                document.getElementById('modal-scadenza').style.display       = '';
                document.getElementById('modal-scadenza-label').style.display = '';
            } else {
                document.getElementById('modal-scadenza').style.display       = 'none';
                document.getElementById('modal-scadenza-label').style.display = 'none';
            }
            popolaMaterialiModal(materiali);
            document.getElementById('modal-btn-apri').href     = url + '?from=' + from;
            document.getElementById('modal-btn-modifica').href = url + '/edit?from=' + from;
            modalIntervento.show();
        });

        // ---- Modal pianifica ----
        var modalPianificaEl = document.getElementById('modal-pianifica');
        var modalPianifica   = new bootstrap.Modal(modalPianificaEl, { backdrop: 'static' });
        var pianTecnicoEl    = document.getElementById('pian-tecnico');
        var pianOraEl        = document.getElementById('pian-ora');

        var pendingCard    = null;
        var pendingDateStr = null;

        // Blocco: il tecnico scelto nel select ha un'assenza che copre pendingDateStr?
        function aggiornaAvvisoAssenza() {
            var avvisoEl   = document.getElementById('pian-avviso-assenza');
            var btnConf    = document.getElementById('btn-pian-conferma');
            var tecnicoId  = pianTecnicoEl.value;
            var assenze    = tecnicoId ? (assenzePerDipendente[tecnicoId] || []) : [];
            var trovata    = null;

            if (pendingDateStr) {
                assenze.forEach(function (a) {
                    if (pendingDateStr >= a.data_inizio && pendingDateStr <= a.data_fine) trovata = a;
                });
            }

            if (trovata) {
                var nome = pianTecnicoEl.options[pianTecnicoEl.selectedIndex].text;
                avvisoEl.classList.remove('d-none');
                avvisoEl.innerHTML = '<i class="bi bi-calendar-x me-1"></i>'
                    + '<strong>' + nome + '</strong> risulta assente (' + trovata.tipo_label + ') in questa data. Scegli un altro tecnico o un\'altra data.';
                btnConf.disabled = true;
            } else {
                avvisoEl.classList.add('d-none');
                avvisoEl.innerHTML = '';
                btnConf.disabled = false;
            }
        }
        pianTecnicoEl.addEventListener('change', aggiornaAvvisoAssenza);

        // Suggerimento: propone l'orario subito dopo l'ultimo impegno del tecnico in quella data.
        // Solo un default comodo per il form — applicato solo se più tardi dell'orario già impostato.
        function aggiornaOrarioSuggerito() {
            var suggEl = document.getElementById('pian-orario-sugg');
            suggEl.classList.add('d-none');
            var tecnicoId = pianTecnicoEl.value;
            if (!tecnicoId || !pendingDateStr) return;

            fetch(cfg.urls.orarioSuggerito
                + '?tecnico_id=' + encodeURIComponent(tecnicoId)
                + '&data='       + encodeURIComponent(pendingDateStr))
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (!json.ora) return;
                    var toMin     = function (t) { var p = t.split(':'); return parseInt(p[0]) * 60 + parseInt(p[1]); };
                    var suggerito = toMin(json.ora);
                    var attuale   = toMin(pianOraEl.value || oraInizio);
                    if (suggerito > attuale) {
                        pianOraEl.value = json.ora;
                        if (json.n_prev > 0) {
                            suggEl.textContent = 'Dopo ' + json.n_prev + ' interv. precedenti in questa data';
                            suggEl.classList.remove('d-none');
                        }
                    }
                })
                .catch(function () {});
        }
        pianTecnicoEl.addEventListener('change', aggiornaOrarioSuggerito);

        function showModalPianifica(cardEl, dateStr, timeStr) {
            pendingCard    = cardEl;
            pendingDateStr = dateStr;

            var d      = new Date(dateStr + 'T12:00:00');
            var giorni = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
            document.getElementById('pian-giorno').textContent  = giorni[d.getDay()] + ' ' +
                String(d.getDate()).padStart(2, '0') + '/' + String(d.getMonth() + 1).padStart(2, '0');
            document.getElementById('pian-cliente').textContent = cardEl.dataset.cliente || '—';
            document.getElementById('pian-tipo').textContent    = cardEl.dataset.tipoNome || '';
            pianOraEl.value = timeStr || oraInizio;
            document.getElementById('pian-orario-sugg').classList.add('d-none');
            buildTecnicoSelect(pianTecnicoEl);
            // Se l'intervento ha già un tecnico assegnato (es. impostato alla creazione), lo
            // preseleziona — altrimenti trascinare la card sul calendario lo perderebbe.
            pianTecnicoEl.value = cardEl.dataset.tecnicoId || '';
            aggiornaAvvisoAssenza();

            // Avviso scadenza: se la data del drop è successiva alla data_scadenza dell'intervento.
            var avvisoEl  = document.getElementById('pian-avviso-scadenza');
            var scadenza  = cardEl.dataset.scadenza;
            var urgente   = cardEl.dataset.urgenza === '1';
            if (scadenza && dateStr > scadenza) {
                var sc = scadenza.split('-');
                var scLabel = sc[2] + '/' + sc[1] + '/' + sc[0];
                if (urgente) {
                    avvisoEl.className = 'alert alert-danger py-2 mb-3';
                    avvisoEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>'
                        + '<strong>Urgente — scadenza superata.</strong> La scadenza era il ' + scLabel
                        + '. Puoi comunque procedere.';
                } else {
                    avvisoEl.className = 'alert alert-warning py-2 mb-3';
                    avvisoEl.innerHTML = '<i class="bi bi-clock me-1"></i>'
                        + 'Attenzione: la scadenza era il ' + scLabel + '. Puoi comunque procedere.';
                }
            } else {
                avvisoEl.className = 'alert py-2 mb-3 d-none';
                avvisoEl.innerHTML = '';
            }

            modalPianifica.show();
        }

        modalPianificaEl.addEventListener('hide.bs.modal', function () {
            pendingCard = pendingDateStr = null;
        });

        document.getElementById('btn-pian-annulla').addEventListener('click', function () {
            modalPianifica.hide();
        });

        document.getElementById('btn-pian-conferma').addEventListener('click', function () {
            if (!pendingCard || !pendingDateStr) return;
            var card     = pendingCard;
            var ora      = pianOraEl.value || oraInizio;
            var dataPian = pendingDateStr + 'T' + ora;
            var btnConf  = this;

            btnConf.disabled  = true;
            btnConf.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Salvo…';

            var fd = new FormData();
            fd.append('data_pianificata', dataPian);
            var tecnicoAsseg = pianTecnicoEl.value;
            if (tecnicoAsseg) fd.append('tecnico_id', tecnicoAsseg);

            fetch(urlInterventi + '/' + card.dataset.id + '/pianifica', {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': csrfHash },
                body:    fd,
            })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                btnConf.disabled  = false;
                btnConf.innerHTML = '<i class="bi bi-check-lg me-1"></i>Pianifica';
                if (!json.ok) { alert(json.msg || 'Errore durante il salvataggio.'); return; }
                if (json.csrf) csrfHash = json.csrf;
                modalPianifica.hide();
                // Rimuove la card dal pool e aggiorna i contatori
                var group = card.closest('.mb-1');
                if (group) {
                    var groupBadge = group.querySelector('a .badge');
                    if (groupBadge) {
                        var gc = parseInt(groupBadge.textContent) - 1;
                        if (gc <= 0) { group.remove(); }
                        else { groupBadge.textContent = gc; }
                    }
                }
                card.remove();
                var countEl = document.getElementById('pool-count');
                var n = parseInt(countEl.textContent || '1') - 1;
                countEl.textContent = n;
                if (n <= 0) {
                    document.getElementById('pool-container').innerHTML =
                        '<div class="pool-empty">'
                        + '<i class="bi bi-check-circle text-success d-block mb-2" style="font-size:2rem;opacity:.5;"></i>'
                        + 'Tutti pianificati</div>';
                }
                calendar.refetchEvents();
            })
            .catch(function () {
                btnConf.disabled  = false;
                btnConf.innerHTML = '<i class="bi bi-check-lg me-1"></i>Pianifica';
                alert('Errore di rete.');
            });
        });

        // ---- Modal dettaglio intervento ----
        var modalIntervento = new bootstrap.Modal(document.getElementById('modalIntervento'));

        // ---- Promemoria (definite qui perché referenziate da customButtons del calendario) ----
        var openPromemoriaModalNew, openPromemoriaModalEdit;

        if (cfg.puoPromemoria) {
            var modalPromemoria = new bootstrap.Modal(document.getElementById('modalPromemoria'));
            var formProm        = document.getElementById('formPromemoria');
            var formPromDelete  = document.getElementById('formPromemoriaDelete');
            var promBtnElimina  = document.getElementById('prom-btn-elimina');

            openPromemoriaModalNew = function () {
                formProm.action = cfg.urls.promemoriaStore;
                document.getElementById('prom-modal-titolo').textContent = 'Nuovo promemoria';
                document.getElementById('prom-titolo').value = '';
                // Precompila l'inizio con la giornata selezionata sul calendario (se presente),
                // con un'orario di default alle 09:00; altrimenti lascia il campo vuoto.
                var giornata = document.getElementById('form-data-giornata').value;
                document.getElementById('prom-inizio').value = giornata ? giornata + 'T09:00' : '';
                document.getElementById('prom-fine').value   = '';
                document.getElementById('prom-note').value   = '';
                promBtnElimina.classList.add('d-none');
                modalPromemoria.show();
            };

            openPromemoriaModalEdit = function (event) {
                var p  = event.extendedProps;
                var id = p.prom_id;
                formProm.action       = cfg.urls.promemoria + '/' + id + '/update';
                formPromDelete.action = cfg.urls.promemoria + '/' + id + '/delete';
                document.getElementById('prom-modal-titolo').textContent = 'Modifica promemoria';
                document.getElementById('prom-titolo').value = p.titolo || '';
                document.getElementById('prom-inizio').value = (p.inizio || '').replace(' ', 'T').substring(0, 16);
                document.getElementById('prom-fine').value   = p.fine ? p.fine.replace(' ', 'T').substring(0, 16) : '';
                document.getElementById('prom-note').value   = p.note || '';
                promBtnElimina.classList.remove('d-none');
                modalPromemoria.show();
            };

            promBtnElimina.addEventListener('click', function () {
                if (confirm('Eliminare questo promemoria?')) formPromDelete.submit();
            });
        }

        // ---- FullCalendar ----
        var isMobile = window.innerWidth < 768;

        var calendarOptions = {
            locale: 'it',
            initialView: isMobile ? 'timeGridDay' : 'timeGridWeek',
            headerToolbar: isMobile ? {
                left:   'prev,next',
                center: 'title',
                right:  '',
            } : {
                left:   'prev,next today',
                center: 'title',
                right:  (cfg.puoPromemoria ? 'nuovoPromemoria ' : '') + 'timeGridDay,timeGridWeek,dayGridMonth',
            },
            buttonText: { today: 'Oggi', day: 'Giorno', week: 'Settimana', month: 'Mese' },
            slotMinTime:    '07:00:00',
            slotMaxTime:    '20:00:00',
            slotDuration:   '00:30:00',
            allDaySlot:     true,
            nowIndicator:   true,
            height:         'auto',
            editable:       true,
            droppable:      true,
            longPressDelay: 300,
            eventDragStart: function (info) {
                document.body.classList.add('fc-dragging');
                // Smonta subito il tooltip: FullCalendar manipola il nodo DOM durante il drag
                // e i listener mouseenter/mouseleave del tooltip, se ancora attivi, vanno in
                // conflitto con quella manipolazione (bug noto di Bootstrap 5 Tooltip).
                var tooltip = bootstrap.Tooltip.getInstance(info.el);
                if (tooltip) tooltip.dispose();
            },
            eventDragStop:  function () { document.body.classList.remove('fc-dragging'); },
            eventDrop: function (info) {
                var dt  = info.event.start;
                var pad = function (n) { return String(n).padStart(2, '0'); };
                var startLocal = dt.getFullYear() + '-' + pad(dt.getMonth() + 1) + '-' + pad(dt.getDate())
                               + ' ' + pad(dt.getHours()) + ':' + pad(dt.getMinutes()) + ':00';
                var fd = new FormData();
                fd.append('id', info.event.id);
                fd.append('start', startLocal);
                fetch(cfg.urls.sposta, {
                    method:  'POST',
                    headers: { 'X-CSRF-TOKEN': csrfHash },
                    body:    fd,
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.csrf) csrfHash = res.csrf;
                    if (!res || !res.ok) { info.revert(); alert((res && res.msg) || 'Errore nel salvataggio.'); }
                })
                .catch(function () { info.revert(); alert('Errore di rete.'); });
            },
            eventReceive: function (info) {
                var cardEl  = info.draggedEl;
                var start   = info.event.start;
                var pad     = function (n) { return String(n).padStart(2, '0'); };
                var dateStr = start.getFullYear() + '-' + pad(start.getMonth() + 1) + '-' + pad(start.getDate());
                var timeStr = pad(start.getHours()) + ':' + pad(start.getMinutes());
                info.event.remove();
                showModalPianifica(cardEl, dateStr, timeStr);
            },
            events: function (fetchInfo, successCallback, failureCallback) {
                var url = cfg.urls.eventi
                        + '?start=' + fetchInfo.startStr.substring(0, 10)
                        + '&end='   + fetchInfo.endStr.substring(0, 10);
                if (filtroTecnico) url += '&tecnico_id=' + filtroTecnico;
                fetch(url)
                    .then(function (r) { return r.json(); })
                    .then(successCallback)
                    .catch(failureCallback);
            },
            eventDidMount: function (info) {
                // Il "mirror" è il doppio dell'evento che segue il cursore durante il drag:
                // passa anche lui da qui, ma essendo creato/distrutto in continuazione un
                // tooltip su di lui scatenerebbe la stessa race condition del drag stesso.
                if (info.isMirror) return;
                info.el.dataset.eventoId = info.event.id;
                var p   = info.event.extendedProps;
                var tip = [info.event.title, p.citta, p.tecnico, p.tipo].filter(Boolean).join(' · ');
                new bootstrap.Tooltip(info.el, { title: tip, placement: 'top', trigger: 'hover', container: 'body' });
                info.el.addEventListener('click', function () {
                    var tooltip = bootstrap.Tooltip.getInstance(info.el);
                    if (tooltip) tooltip.hide();
                });
                // Evento appena montato dopo un gotoDate innescato dalla barra "In ritardo".
                if (pendingFlashId && String(info.event.id) === String(pendingFlashId)) {
                    pendingFlashId = null;
                    flashElement(info.el);
                }
            },
            eventWillUnmount: function (info) {
                if (info.isMirror) return;
                var tooltip = bootstrap.Tooltip.getInstance(info.el);
                if (tooltip) tooltip.dispose();
            },
            eventClick: function (info) {
                info.jsEvent.preventDefault();
                var p   = info.event.extendedProps;

                // Promemoria: apre il modal di modifica (solo per chi può gestirli).
                if (p.tipo_evento === 'promemoria') {
                    if (cfg.puoPromemoria) openPromemoriaModalEdit(info.event);
                    return;
                }

                // Assenze: non editabili dal calendario, si gestiscono dalla scheda Personale.
                if (p.tipo_evento === 'assenza') {
                    return;
                }

                var url = info.event.url;

                var badgeClass = { da_pianificare: 'bg-secondary', pianificato: 'bg-primary', in_corso: 'bg-warning text-dark', completato: 'bg-success', annullato: 'bg-danger' };
                var statoLabel = { da_pianificare: 'Da pianificare', pianificato: 'Pianificato', in_corso: 'In corso', completato: 'Completato', annullato: 'Annullato' };

                var start   = info.event.start;
                var dataFmt = start
                    ? start.toLocaleDateString('it-IT', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })
                    + ' alle ' + start.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' })
                    : '—';

                var iconaClass = p.icona || 'fa-wrench';
                document.getElementById('modal-icona').className = 'fas ' + iconaClass + ' me-2';
                document.getElementById('modal-cliente').textContent = info.event.title;
                document.getElementById('modal-content').style.borderLeft = '4px solid ' + (info.event.backgroundColor || '#6c757d');
                document.getElementById('modal-tipo').textContent    = p.tipo || '—';
                document.getElementById('modal-tecnico').textContent = p.tecnico || 'Non assegnato';
                document.getElementById('modal-data').textContent    = dataFmt;
                document.getElementById('modal-stato').innerHTML     = '<span class="badge ' + (badgeClass[p.stato] || 'bg-secondary') + '">' + (statoLabel[p.stato] || p.stato) + '</span>';
                document.getElementById('modal-descrizione').textContent = p.descrizione || '';
                document.getElementById('modal-descrizione-wrap').style.display = p.descrizione ? '' : 'none';
                if (p.creato) {
                    var cp = p.creato.substring(0, 10).split('-');
                    document.getElementById('modal-creato').textContent = cp[2] + '/' + cp[1] + '/' + cp[0];
                }
                if (p.data_scadenza) {
                    var ep = p.data_scadenza.split('-');
                    document.getElementById('modal-scadenza').textContent = ep[2] + '/' + ep[1] + '/' + ep[0];
                    document.getElementById('modal-scadenza').style.display       = '';
                    document.getElementById('modal-scadenza-label').style.display = '';
                } else {
                    document.getElementById('modal-scadenza').style.display       = 'none';
                    document.getElementById('modal-scadenza-label').style.display = 'none';
                }
                popolaMaterialiModal(p.materiali);
                document.getElementById('modal-btn-apri').href     = url + '?from=' + from;
                document.getElementById('modal-btn-modifica').href = url + '/edit?from=' + from;
                modalIntervento.show();
            },
            dateClick: function (info) {
                selezionaData(info.dateStr.substring(0, 10));
            },
            eventContent: function (info) {
                var p        = info.event.extendedProps;

                // Promemoria: campanella + titolo, senza il bottone × di rimozione pianificazione.
                if (p.tipo_evento === 'promemoria') {
                    return { html: '<div style="padding:2px 4px;line-height:1.25;overflow:hidden;">'
                        + '<div style="font-size:.78rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'
                        + '<i class="bi bi-bell-fill" style="margin-right:3px;opacity:.85;"></i>'
                        + info.timeText + ' &nbsp;' + info.event.title
                        + '</div></div>' };
                }

                // Assenze: icona dedicata, nessun bottone di rimozione (non editabili da qui).
                if (p.tipo_evento === 'assenza') {
                    return { html: '<div style="padding:2px 4px;line-height:1.25;overflow:hidden;">'
                        + '<div style="font-size:.78rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'
                        + '<i class="bi bi-calendar-x" style="margin-right:3px;opacity:.85;"></i>'
                        + info.event.title
                        + '</div></div>' };
                }

                var time     = info.timeText;
                var iconaCls = (p.icona || 'fa-wrench');
                var completato = p.stato === 'completato';
                var spuntaHtml = completato
                    ? '<i class="bi bi-check-circle-fill evt-badge-completato" title="Completato"></i>'
                    : '';
                function fmtDd(s) { var pp = s.split('-'); return pp[2] + '/' + pp[1]; }

                var sottotitolo = (p.tecnico || '')
                    + (p.citta ? ' · ' + p.citta : '')
                    + (p.data_scadenza ? ' · <i class="bi bi-clock"></i> ' + fmtDd(p.data_scadenza) : '');

                var html = `
                    <div class="evt-body${completato ? ' completato' : ''}">
                        <div class="evt-title"><i class="fas ${iconaCls}"></i>${time} &nbsp;${info.event.title}</div>
                        <div class="evt-sub">${sottotitolo}</div>
                        <button class="btn-rimuovi-pian evt-btn-rimuovi" data-id="${info.event.id}" title="Rimuovi pianificazione">&times;</button>
                        ${spuntaHtml}
                    </div>
                `;
                return { html: html };
            },
            datesSet: function (info) {
                montaInputVaiAData();
                aggiornaPoolPeriodo(info.endStr);
            },
        };

        if (cfg.dataIniziale) calendarOptions.initialDate = cfg.dataIniziale;
        if (cfg.puoPromemoria) {
            calendarOptions.customButtons = {
                nuovoPromemoria: {
                    text: '+ Promemoria',
                    click: function () { openPromemoriaModalNew(); },
                },
            };
        }

        var calendar = new FullCalendar.Calendar(document.getElementById('calendario'), calendarOptions);
        calendar.render();

        // ---- Vai a data: input reale incollato al titolo (invisibile) ----
        // Il tap/click dell'utente cade sempre sul vero <input type="date">, non
        // su un elemento intermedio: il focus arriva davvero all'input (verificato
        // in console), ma cliccare sul campo data di Chrome/Edge apre il picker
        // solo se si colpisce l'iconcina interna del calendario, non il testo —
        // serve comunque showPicker() per aprirlo cliccando ovunque nel titolo.
        // Chiamarla da un listener attaccato DIRETTAMENTE sull'input (non delegato
        // da un elemento ancestor) è ciò che la rende un gesto utente valido anche
        // su Chrome/Firefox iOS, che prima rifiutavano con NotAllowedError.
        // Posizionamento con CSS puro (position:absolute dentro il contenitore del
        // titolo, vedi calendario.css), non con getBoundingClientRect(): il motore
        // di layout del browser tiene l'input allineato ad ogni riflusso (resize,
        // scrollbar che compare, font che finisce di caricare...) senza bisogno di
        // ricalcolare le coordinate a mano su ogni possibile evento.
        // Rigenera il pool "da pianificare" per il periodo visibile: le occorrenze da abbonamento
        // compaiono solo entro la fine di quanto FullCalendar sta mostrando, non più fino a fine
        // mese fisso. `info.endStr` è esclusivo (il giorno DOPO l'ultimo visibile), quindi si
        // toglie un giorno per ottenere l'ultimo giorno realmente in vista.
        function aggiornaPoolPeriodo(fineEsclusivaStr) {
            var fine = new Date(fineEsclusivaStr.substring(0, 10) + 'T00:00:00');
            fine.setDate(fine.getDate() - 1);
            var pad = function (n) { return String(n).padStart(2, '0'); };
            var fineStr = fine.getFullYear() + '-' + pad(fine.getMonth() + 1) + '-' + pad(fine.getDate());

            fetch(cfg.urls.poolPeriodo + '?fine=' + fineStr)
                .then(function (r) { return r.text(); })
                .then(function (html) {
                    var container = document.getElementById('pool-container');
                    container.innerHTML = html;
                    document.getElementById('pool-count').textContent = container.querySelectorAll('.pool-card').length;
                });
        }

        function montaInputVaiAData() {
            var titolo = document.querySelector('#calendario .fc-toolbar-title');
            var input  = document.getElementById('calendario-vai-a-data');
            if (!titolo || !input) return;
            var contenitore = titolo.closest('.fc-toolbar-chunk') || titolo.parentElement;
            if (input.parentElement !== contenitore) {
                contenitore.appendChild(input);
            }
            var d = calendar.getDate();
            var pad = function (n) { return String(n).padStart(2, '0'); };
            input.value = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        }
        montaInputVaiAData();
        document.getElementById('calendario-vai-a-data').addEventListener('click', function () {
            if (this.showPicker) this.showPicker();
        });
        document.getElementById('calendario-vai-a-data').addEventListener('change', function () {
            if (this.value) calendar.gotoDate(this.value);
        });

        // ---- Rimozione pianificazione (bottone × sugli eventi) ----
        document.getElementById('calendario').addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-rimuovi-pian');
            if (!btn) return;
            e.stopPropagation();
            e.preventDefault();
            if (!confirm('Rimuovere questo intervento dalla pianificazione?\nTornerà nella coda "Da pianificare".')) return;
            var interventoId = btn.dataset.id;
            fetch(urlInterventi + '/' + interventoId + '/annulla-pianificazione', {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': csrfHash },
            })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (json.csrf) csrfHash = json.csrf;
                if (json.ok) { window.location.reload(); }
                else { alert('Errore: ' + (json.msg || 'riprovare.')); }
            })
            .catch(function () { alert('Errore di rete.'); });
        }, true);

        // ---- Sidebar ridimensionabile ----
        var poolPanel    = document.getElementById('pool-panel');
        var resizeHandle = document.getElementById('resize-handle');
        var savedW       = localStorage.getItem('pool-sidebar-width');
        var startMini    = localStorage.getItem('pool-mini') === '1';
        // In mini la larghezza è data dal CSS (auto): non applicare l'inline width,
        // altrimenti vincerebbe sul foglio di stile e il pannello resterebbe largo.
        if (savedW && !startMini) poolPanel.style.width = savedW + 'px';
        var resizing = false, startX, startW;
        resizeHandle.addEventListener('mousedown', function (e) {
            resizing = true; startX = e.clientX; startW = poolPanel.offsetWidth;
            document.body.style.cursor     = 'col-resize';
            document.body.style.userSelect = 'none';
        });
        document.addEventListener('mousemove', function (e) {
            if (!resizing) return;
            var w = Math.max(180, Math.min(450, startW + e.clientX - startX));
            poolPanel.style.width = w + 'px';
        });
        document.addEventListener('mouseup', function () {
            if (!resizing) return;
            resizing = false;
            document.body.style.cursor     = '';
            document.body.style.userSelect = '';
            localStorage.setItem('pool-sidebar-width', poolPanel.offsetWidth);
        });

        // ---- Pool comprimibile a sola icona (toggle sull'header) ----
        var poolHeader = document.getElementById('pool-header');
        if (poolHeader) {
            if (startMini) {
                poolPanel.classList.add('pool-mini');
                calendar.updateSize();
            }
            poolHeader.addEventListener('click', function () {
                var mini = poolPanel.classList.toggle('pool-mini');
                localStorage.setItem('pool-mini', mini ? '1' : '0');
                // Comprimendo lascio decidere il CSS (width auto); riespandendo
                // ripristino la larghezza salvata dal resize, se presente.
                if (mini) {
                    poolPanel.style.width = '';
                } else {
                    var w = localStorage.getItem('pool-sidebar-width');
                    poolPanel.style.width = w ? w + 'px' : '';
                }
                calendar.updateSize();
            });
        }

        // ---- Barra "In ritardo": click = evidenzia in pagina, doppio click = apri scheda ----
        // Su mobile la sidebar pool non esiste: il tap naviga direttamente alla scheda
        // intervento (comportamento nativo del link, nessun listener da aggiungere qui).
        function flashElement(el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.add('cal-flash');
            setTimeout(function () { el.classList.remove('cal-flash'); }, 1800);
        }

        // Apre (se chiuse) le collapse Bootstrap di zona/sottogruppo che contengono la
        // card e, se il pool è compresso a icona, lo riespande prima di fare scroll.
        function evidenziaScadenzaPool(id) {
            var card = document.querySelector('.pool-card[data-id="' + id + '"]');
            if (!card) return;
            if (poolPanel.classList.contains('pool-mini')) {
                poolPanel.classList.remove('pool-mini');
                localStorage.setItem('pool-mini', '0');
                var w = localStorage.getItem('pool-sidebar-width');
                poolPanel.style.width = w ? w + 'px' : '';
                calendar.updateSize();
            }
            var el = card;
            while ((el = el.parentElement) && el.id !== 'pool-container') {
                if (el.classList.contains('collapse') && !el.classList.contains('show')) {
                    bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).show();
                }
            }
            flashElement(card);
        }

        // Porta in vista la data pianificata dell'evento e lo evidenzia. Se la data è
        // già nella vista corrente l'evento è già montato: flash immediato. Altrimenti
        // il gotoDate() forza un nuovo fetch asincrono e il flash scatta da eventDidMount
        // (vedi pendingFlashId più sopra) appena l'evento compare nel DOM.
        function evidenziaScadenzaEvento(badge) {
            var id          = badge.dataset.id;
            var pianificata = badge.dataset.pianificata;
            if (pianificata) calendar.gotoDate(pianificata.substring(0, 10));
            var el = document.querySelector('.fc-event[data-evento-id="' + id + '"]');
            if (el) flashElement(el);
            else    pendingFlashId = id;
        }

        // Le pill usano data-bs-toggle="collapse" (per l'espansione), quindi il loop
        // globale di inizializzazione tooltip in admin.php (che cerca data-bs-toggle
        // "tooltip") non le intercetta: le inizializziamo qui esplicitamente.
        document.querySelectorAll('.scadenza-pill').forEach(function (el) {
            new bootstrap.Tooltip(el, { placement: 'top', trigger: 'hover' });
        });

        var barraScadenze = document.getElementById('barra-scadenze');
        if (barraScadenze) {
            var timerClickScadenza = null;
            barraScadenze.addEventListener('click', function (e) {
                var badge = e.target.closest('.scadenza-badge');
                if (!badge || isMobile) return; // mobile: lascia navigare il link nativo
                e.preventDefault();
                if (timerClickScadenza) {
                    // Secondo click di un doppio click: annulla l'evidenziazione singola,
                    // la navigazione la fa il listener dblclick qui sotto.
                    clearTimeout(timerClickScadenza);
                    timerClickScadenza = null;
                    return;
                }
                timerClickScadenza = setTimeout(function () {
                    timerClickScadenza = null;
                    if (badge.dataset.stato === 'da_pianificare') {
                        evidenziaScadenzaPool(badge.dataset.id);
                    } else {
                        evidenziaScadenzaEvento(badge);
                    }
                }, 250);
            });
            barraScadenze.addEventListener('dblclick', function (e) {
                var badge = e.target.closest('.scadenza-badge');
                if (!badge || isMobile) return;
                e.preventDefault();
                window.location.href = badge.href;
            });
        }
    });
})();
