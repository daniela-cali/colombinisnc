<?php

namespace App\Libraries;

use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;

/**
 * Compila un modello Word (.docx) con i segnaposto di PhpWord e ne restituisce il contenuto.
 *
 * È il motore condiviso dei documenti del gestionale: nasce con la proposta di abbonamento e
 * lo riuseranno i preventivi. Fa solo ciò che serve oggi — segnaposto semplici ed elenchi di
 * righe — e non sa niente di abbonamenti: quali valori mettere lo decide chi lo usa.
 *
 * Nel modello un segnaposto si scrive `${nome}`; un elenco è un blocco di tre paragrafi,
 * ciascun marcatore su una riga sua:
 *
 *     ${apparecchiature}
 *     • ${apparecchiatura}
 *     ${/apparecchiature}
 *
 * Il nome del blocco deve essere diverso da quello della riga che contiene, altrimenti
 * PhpWord scambia la riga per un marcatore. Per un elenco puntato la riga è un elemento di
 * un elenco di Word, non un trattino scritto a mano: ogni copia eredita simbolo e rientro
 * sporgente, così una voce lunga va a capo allineata al testo e non sotto il simbolo.
 */
class DocumentoWord
{
    /**
     * Tetto ai passaggi di elenco(): un modello ha al più qualche copia dello stesso blocco
     * (la proposta ne ha due, una per pagina). Superarlo vuol dire che il blocco non viene
     * consumato, e senza tetto il ciclo non finirebbe.
     */
    private const MASSIMO_PASSAGGI = 10;

    private TemplateProcessor $modello;

    /**
     * Apre il modello. L'escape dei valori va attivato prima: di default PhpWord inserisce il
     * testo così com'è, e una ragione sociale con «&» produrrebbe un file che Word non apre.
     */
    public function __construct(string $percorsoModello)
    {
        Settings::setOutputEscapingEnabled(true);
        $this->modello = new TemplateProcessor($percorsoModello);
    }

    /**
     * Sostituisce i segnaposto semplici in tutte le occorrenze, intestazioni comprese.
     *
     * @param array<string, string> $valori nome del segnaposto (senza `${}`) => testo
     */
    public function valori(array $valori): static
    {
        $this->modello->setValues($valori);

        return $this;
    }

    /**
     * Ripete la riga del blocco una volta per voce; con zero voci il blocco sparisce, ed è
     * così che si toglie una riga facoltativa (un telefono che non c'è).
     *
     * cloneBlock() consuma un blocco per chiamata (tutte le copie identiche insieme), quindi si
     * ripete finché il blocco è presente: le copie di un modello potrebbero non essere uguali
     * byte per byte. L'escape si fa qui perché PhpWord non lo applica ai valori dei blocchi,
     * nemmeno con l'opzione attiva.
     *
     * @param list<string> $voci
     *
     * @throws RuntimeException se il blocco non si esaurisce entro MASSIMO_PASSAGGI
     */
    public function elenco(string $blocco, string $segnaposto, array $voci): static
    {
        $sostituzioni = array_map(
            static fn ($voce) => [$segnaposto => htmlspecialchars((string) $voce, ENT_XML1 | ENT_QUOTES, 'UTF-8')],
            array_values($voci)
        );

        $passaggi = 0;
        while ($this->modello->cloneBlock($blocco, 0, true, false, $sostituzioni) !== null) {
            if (++$passaggi > self::MASSIMO_PASSAGGI) {
                throw new RuntimeException("Il blocco \"{$blocco}\" del modello Word non si esaurisce.");
            }
        }

        return $this;
    }

    /**
     * Il documento compilato, come stringa da passare a download() o a uno zip.
     *
     * Prima verifica che non sia rimasto nessun segnaposto: un nome sbagliato nel codice o un
     * segnaposto aggiunto al modello lascerebbe `${...}` scritto nel documento, senza errori.
     * Il file lo salva PhpWord nella cartella temporanea di sistema; si legge e si cancella
     * subito, così sul server non resta niente. Il controllo dei segnaposto arriva dopo la
     * cancellazione: PhpWord crea la copia temporanea già all'apertura del modello e non la
     * toglie da sé, quindi un errore lanciato prima del save() la lascerebbe sul disco.
     *
     * @throws RuntimeException se restano segnaposto non compilati
     */
    public function contenuto(): string
    {
        $rimasti = array_unique($this->modello->getVariables());

        $file      = $this->modello->save();
        $contenuto = file_get_contents($file);
        unlink($file);

        if ($rimasti !== []) {
            throw new RuntimeException('Segnaposto non compilati nel modello Word: ' . implode(', ', $rimasti) . '.');
        }

        if ($contenuto === false) {
            throw new RuntimeException('Impossibile leggere il documento Word generato.');
        }

        return $contenuto;
    }
}
