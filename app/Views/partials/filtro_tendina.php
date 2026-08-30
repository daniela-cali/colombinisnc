<?php
/**
 * Tendina di filtro per una tabella DataTables: qui c'è solo il markup, il comportamento
 * sta in public/js/search-bar.js e la colonna nascosta su cui si cerca la dichiara la view.
 *
 * Ogni voce si dichiara una volta sola, con dentro sia cosa cercare sia come si chiama:
 * le chiavi col/q/regex (più col2/q2/regex2 per un secondo filtro) diventano il JSON di
 * data-pill-filtri, le altre diventano la riga del menu. Una voce senza 'col' azzera le
 * colonne del gruppo: è il classico "Tutti".
 *
 * Si rende con view() e non con $this->include(), perché ogni tendina ha i suoi dati
 * mentre include() condivide quelli della view chiamante:
 *
 *   <?= view('partials/filtro_tendina', [
 *       'tabella'   => 'tabella-interventi',
 *       'etichetta' => 'Stato',
 *       'classe'    => 'btn-outline-primary',
 *       'voci'      => [
 *           'tutti'    => ['label' => 'Tutti (' . count($interventi) . ')'],
 *           'aperti'   => ['label' => 'Aperti', 'icona' => 'bi-play-circle', 'default' => true,
 *                          'col' => 9, 'q' => '^(da_pianificare|pianificato|in_corso)$', 'regex' => true],
 *           'in_corso' => ['label' => 'In corso', 'sotto' => true,
 *                          'col' => 9, 'q' => '^in_corso$', 'regex' => true],
 *       ],
 *   ]) ?>
 *
 * @var string      $tabella   id della <table> già inizializzata come DataTable
 * @var array       $voci      [nome => voce]. Della voce: 'label' è obbligatoria; 'icona' aggiunge
 *                             un'icona; 'default' marca quella attiva all'apertura; 'sotto' rientra
 *                             la voce sotto quella larga che la precede (come "Scaduti" e le sue
 *                             con/senza rinnovo negli abbonamenti); col/q/regex dicono dove cercare
 * @var string      $etichetta testo fisso del bottone, prima del filtro attivo (es. "Stato")
 * @var string|null $gruppo    nome con cui search-bar.js ricorda questa tendina nella sessione.
 *                             Va passato quando nella pagina ci sono più tendine con la stessa
 *                             etichetta su tabelle diverse — la scheda cliente ne ha tre chiamate
 *                             "Stato" — altrimenti si sovrascriverebbero la memoria a vicenda.
 *                             In mancanza si ricava dall'etichetta.
 * @var string|null $icona     icona del bottone (default: imbuto)
 * @var string|null $classe    classe colore del bottone (default: btn-outline-secondary)
 * @var string|null $attivo    etichetta mostrata all'apertura; se non passata si usa quella
 *                             della voce marcata 'default'
 */
$classe = $classe ?? 'btn-outline-secondary';
$icona  = $icona ?? 'bi-funnel';
// Nome per la memoria di sessione: "Tipo intervento" → "tipo_intervento"
$gruppo = $gruppo ?? preg_replace('/[^a-z0-9]+/', '_', mb_strtolower($etichetta));

$chiaviRicerca = ['col', 'q', 'regex', 'col2', 'q2', 'regex2'];
$filtri        = [];

foreach ($voci as $nome => $voce) {
    $filtri[$nome] = array_intersect_key($voce, array_flip($chiaviRicerca));
}

if (! isset($attivo)) {
    $attivo = '';
    foreach ($voci as $voce) {
        if (! empty($voce['default'])) {
            $attivo = $voce['label'];
            break;
        }
    }
}
?>
<div class="dropdown" data-pill-tabella="<?= esc($tabella, 'attr') ?>"
     data-pill-gruppo="<?= esc($gruppo, 'attr') ?>"
     data-pill-filtri='<?= esc(json_encode($filtri), 'attr') ?>'>
    <button class="btn btn-sm <?= esc($classe, 'attr') ?> dropdown-toggle" type="button" data-bs-toggle="dropdown">
        <i class="bi <?= esc($icona, 'attr') ?> me-1"></i><?= esc($etichetta) ?>: <span class="filtro-label"><?= esc((string) $attivo) ?></span>
    </button>
    <ul class="dropdown-menu">
        <?php foreach ($voci as $nome => $voce): ?>
            <li>
                <button type="button"
                        class="dropdown-item<?= ! empty($voce['sotto']) ? ' ps-4' : '' ?>"
                        <?php /* Il cast a stringa serve alle tendine costruite dagli anni: lì le chiavi
                                 e le etichette sono interi, ed esc() vuole una stringa. */ ?>
                        data-filtro="<?= esc((string) $nome, 'attr') ?>"<?= ! empty($voce['default']) ? ' data-default' : '' ?>>
                    <?php if (! empty($voce['icona'])): ?><i class="bi <?= esc($voce['icona'], 'attr') ?> me-1"></i><?php endif ?>
                    <?= ! empty($voce['sotto']) ? '↳ ' : '' ?><?= esc((string) $voce['label']) ?>
                </button>
            </li>
        <?php endforeach ?>
    </ul>
</div>
