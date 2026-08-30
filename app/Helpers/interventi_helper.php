<?php

/**
 * Helper interventi — formattazione di elenchi di interventi dentro i messaggi flash
 * e calcolo dei periodi usati dai filtri delle tabelle.
 */

if (! function_exists('periodi_intervento')) {
    /**
     * Elenca i periodi a cui un intervento appartiene, separati da spazio
     * ("scaduto", "oggi", "settimana", "mese", "prossimi30").
     *
     * Serve alla colonna nascosta su cui lavorano le tendine "Periodo": scrivendo qui i
     * secchielli, il filtro resta una ricerca di parola come tutti gli altri e non serve
     * una funzione di ricerca custom in JavaScript.
     *
     * La data di riferimento è la pianificata se c'è, altrimenti la scadenza — cioè
     * "quando cade" l'intervento: quelli da pianificare hanno solo la seconda, e filtrando
     * sulla sola pianificata sparirebbero tutti. Chi non ha nessuna delle due non appartiene
     * a nessun periodo e si vede solo scegliendo "Tutti".
     *
     * I secchielli si sovrappongono di proposito: un intervento di oggi sta anche in
     * settimana, mese e prossimi30, così "Questo mese" comprende anche quelli di oggi.
     * Le soglie si calcolano una volta per richiesta e restano ferme: una pagina lasciata
     * aperta oltre la mezzanotte va ricaricata.
     */
    function periodi_intervento(array $intervento): string
    {
        static $oggi = null, $inizioSettimana = null, $fineSettimana = null, $fra30Giorni = null;

        if ($oggi === null) {
            $oggi            = new DateTimeImmutable('today');
            $inizioSettimana = $oggi->modify('monday this week');
            $fineSettimana   = $inizioSettimana->modify('+6 days');
            $fra30Giorni     = $oggi->modify('+30 days');
        }

        $riferimento = $intervento['data_pianificata'] ?: $intervento['data_scadenza'];
        if (! $riferimento) {
            return '';
        }

        $data       = new DateTimeImmutable(substr($riferimento, 0, 10));
        $secchielli = [];

        // Scaduto solo se c'è ancora qualcosa da fare: un completato in ritardo è fatto, non scaduto
        $statiAperti = ['da_pianificare', 'pianificato', 'in_corso', 'sospeso'];
        if ($data < $oggi && in_array($intervento['stato'], $statiAperti, true)) {
            $secchielli[] = 'scaduto';
        }
        if ($data->format('Y-m-d') === $oggi->format('Y-m-d')) {
            $secchielli[] = 'oggi';
        }
        if ($data >= $inizioSettimana && $data <= $fineSettimana) {
            $secchielli[] = 'settimana';
        }
        if ($data->format('Y-m') === $oggi->format('Y-m')) {
            $secchielli[] = 'mese';
        }
        if ($data >= $oggi && $data <= $fra30Giorni) {
            $secchielli[] = 'prossimi30';
        }

        return implode(' ', $secchielli);
    }
}

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
