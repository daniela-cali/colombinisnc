<?php

/**
 * Helper interventi — formattazione di elenchi di interventi dentro i messaggi flash.
 */

if (! function_exists('elenco_interventi_link')) {
    /**
     * Rende un elenco di interventi come link alla loro modifica, per poterci andare
     * direttamente dall'avviso.
     *
     * Si ferma ai primi $max: oltre, un messaggio flash lungo una pagina nasconderebbe
     * l'avviso invece di darlo.
     *
     * L'etichetta di ogni voce la decide il chiamante, perché cambia con il contesto: il
     * nome del cliente quando gli interventi sono di tecnici diversi, il codice quando
     * appartengono tutti allo stesso abbonamento. Viene sempre escapata qui, perché il
     * layout stampa i flash senza filtrarli — deve poterci mettere questi link — e quindi
     * tutto ciò che arriva dal database va reso sicuro prima di entrarci.
     */
    function elenco_interventi_link(array $interventi, callable $etichetta, int $max = 5): string
    {
        $primi = array_slice($interventi, 0, $max);

        $voci = array_map(
            static fn (array $i): string => '<a href="' . base_url('operativo/interventi/' . $i['id'] . '/edit') . '">'
                . esc($etichetta($i)) . '</a>',
            $primi
        );

        $elenco = implode(', ', $voci);
        $altri  = count($interventi) - count($primi);

        return $altri > 0 ? $elenco . ' e altri ' . $altri . '.' : $elenco . '.';
    }
}
