<?php
/**
 * Finestra dell'invio per email delle proposte, uguale per una e per venti: la usano la scheda
 * e l'elenco. La compila e la guida public/js/invio-proposte.js; qui c'è solo la struttura.
 * Non riceve variabili dal controller.
 */
?>
<div class="modal fade" id="modal-invio-email" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-envelope me-2"></i>Invia per email</h5>
            </div>
            <div class="modal-body">
                <?php // Il token CSRF per le richieste dello script ?>
                <form id="form-invio-email"><?= csrf_field() ?></form>

                <p class="text-muted mb-0" id="invio-caricamento">Preparazione…</p>
                <div class="alert alert-danger mb-0 d-none" id="invio-errore"></div>

                <div class="d-none" id="invio-contenuto">
                    <div class="mb-3">
                        <label class="form-label" for="invio-oggetto">Oggetto</label>
                        <input type="text" class="form-control" id="invio-oggetto" maxlength="200">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="invio-testo">Testo</label>
                        <textarea class="form-control" id="invio-testo" rows="8"></textarea>
                        <div class="form-text">Le modifiche valgono solo per questo invio. Il PDF della proposta è allegato.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Firma</label>
                        <iframe class="anteprima-firma" id="invio-firma" sandbox title="Anteprima della firma"></iframe>
                    </div>

                    <table class="table table-sm align-middle mb-3">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Email</th>
                                <th>Stato</th>
                            </tr>
                        </thead>
                        <tbody id="invio-righe"></tbody>
                    </table>

                    <div class="progress d-none" id="invio-avanzamento" role="progressbar" aria-label="Avanzamento dell'invio">
                        <div class="progress-bar" id="invio-barra"></div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0 d-none" id="invio-riepilogo"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="invio-annulla" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-sm btn-primary" id="invio-invia" disabled>
                    <i class="bi bi-send me-1"></i>Invia
                </button>
                <button type="button" class="btn btn-sm btn-primary d-none" id="invio-chiudi">Chiudi</button>
            </div>
        </div>
    </div>
</div>
