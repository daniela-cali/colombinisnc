<?php

/**
 * Tabella degli account, condivisa dalle schede Personale e Clienti di Impostazioni → Utenti.
 * Le due schede mostrano le stesse colonne: cambia chi c'è dentro e a quale scheda rimanda il
 * nome, che il partial ricava dalla riga senza bisogno di saperlo dal chiamante.
 *
 * @var array  $righe         account da mostrare
 * @var array  $gruppi        mappa chiave => etichetta dei gruppi dell'applicazione
 * @var string $etichettaNome intestazione della colonna del nome
 * @var string $vuoto         testo da mostrare quando non c'è nessun account
 * @var string $id            id della tabella, per agganciarci DataTables dove serve
 */
?>
<?php if (empty($righe)): ?>
    <p class="text-muted text-center py-4 mb-0"><?= esc($vuoto) ?></p>
<?php else: ?>
    <div class="table-responsive">
        <table id="<?= esc($id) ?>" class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Username</th>
                    <th><?= esc($etichettaNome) ?></th>
                    <th>Email</th>
                    <th>Gruppi</th>
                    <th class="text-center">Stato</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($righe as $u): ?>
                    <?php
                    $sospeso  = $u['status'] === 'banned';
                    $ioStesso = (int) $u['id'] === user_id();

                    // Il nome viene dalla scheda che l'account ha davvero: quella del cliente se
                    // è un account cliente, quella del dipendente altrimenti. Chi non ha né l'una
                    // né l'altra resta comunque in elenco, senza link: questa pagina è l'unico
                    // posto da cui vederlo.
                    if ($u['cliente_id']) {
                        $nome   = $u['cliente_denominazione'];
                        $scheda = base_url('anagrafiche/clienti/' . $u['cliente_id']);
                    } else {
                        $nome   = trim(($u['cognome'] ?? '') . ' ' . ($u['nome'] ?? ''));
                        $scheda = $u['personale_id'] ? base_url('anagrafiche/personale/' . $u['personale_id']) : null;
                    }

                    // Estratti qui perché finiscono dentro l'attributo onsubmit più sotto: un
                    // accesso ad array nel mezzo di due livelli di apici confonde il linter.
                    $username = $u['username'];
                    $azione   = base_url('impostazioni/utenti-app/' . $u['id'] . '/stato');
                    ?>
                    <tr title="ID utente: <?= $u['id'] ?>"><?php // ID utile per debug DB ?>
                        <td><?= esc($username) ?></td>
                        <td>
                            <?php if ($nome === '' || $nome === null): ?>
                                <span class="text-muted">—</span>
                            <?php elseif ($scheda): ?>
                                <a href="<?= $scheda ?>" class="text-body text-decoration-none"><?= esc($nome) ?></a>
                            <?php else: ?>
                                <?= esc($nome) ?>
                            <?php endif ?>
                        </td>
                        <td class="text-muted small"><?= esc($u['email'] ?? '—') ?></td>
                        <td>
                            <?php if ($u['gruppi']): ?>
                                <?php foreach (explode(', ', $u['gruppi']) as $g): ?>
                                    <span class="badge bg-secondary me-1"><?= esc($gruppi[$g] ?? ucfirst($g)) ?></span>
                                <?php endforeach ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif ?>
                        </td>
                        <td class="text-center">
                            <?php if ($sospeso): ?>
                                <span class="badge bg-danger">Sospeso</span>
                            <?php else: ?>
                                <span class="badge bg-success">Attivo</span>
                            <?php endif ?>
                        </td>
                        <td class="text-end">
                            <?php if ($ioStesso): ?>
                                <span class="text-muted" title="Non puoi sospendere l'account con cui stai lavorando">—</span>
                            <?php elseif ($sospeso): ?>
                                <form action="<?= $azione ?>" method="post" class="d-inline"
                                      onsubmit="return confirm('Riattivare l&#39;accesso di <?= esc($username) ?>?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-unlock me-1"></i>Riattiva
                                    </button>
                                </form>
                            <?php else: ?>
                                <form action="<?= $azione ?>" method="post" class="d-inline"
                                      onsubmit="return confirm('Sospendere l&#39;accesso di <?= esc($username) ?>? Non potrà più entrare nel gestionale, ma la sua scheda e il suo storico restano.')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-lock me-1"></i>Sospendi
                                    </button>
                                </form>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>
