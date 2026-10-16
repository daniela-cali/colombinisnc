<?php

namespace App\Libraries;

use App\Models\PersonaleCaselleEmailModel;
use App\Models\PersonaleModel;
use App\Models\UserModel;
use CodeIgniter\Email\Email;
use RuntimeException;

/**
 * Spedisce un'email dalla casella dell'operatore: mittente «Colombini S.n.c. - Nome Cognome»
 * con il suo indirizzo, la sua firma in coda e lui stesso in copia nascosta, che fa da archivio
 * perché via SMTP Aruba non salva nella cartella «Inviata». Non sa niente di cosa spedisce:
 * proposte di abbonamento oggi, preventivi domani.
 *
 * Server, porta e cifratura vengono da Impostazioni → Parametri, con i valori di Config\Email
 * come ripiego; utente SMTP è l'email di login, la password quella salvata nel profilo.
 */
class EmailOperatore
{
    private string $indirizzo;
    private string $nome;
    private string $password;
    private string $firma;

    /**
     * @throws RuntimeException se l'operatore non ha una scheda personale, un'email di login o
     *                          la password della casella: il messaggio dice cosa fare
     */
    public function __construct(int $userId)
    {
        $persona = (new PersonaleModel())->perUtente($userId);
        if (! $persona) {
            throw new RuntimeException('Il tuo account non ha una scheda personale: non puoi inviare email dal gestionale.');
        }

        // In Shield l'email sta fra le identità, non sulla riga di users: come nel profilo.
        $indirizzo = model(UserModel::class)->findById($userId)?->getEmailIdentity()?->secret;
        if (! $indirizzo) {
            throw new RuntimeException('Il tuo account non ha un indirizzo email.');
        }

        $caselle  = new PersonaleCaselleEmailModel();
        $password = $caselle->password((int) $persona['id']);
        if ($password === null) {
            throw new RuntimeException('Inserisci la password della tua casella email nel profilo, poi riprova.');
        }

        $this->indirizzo = $indirizzo;
        $this->nome      = trim($persona['nome'] . ' ' . $persona['cognome']);
        $this->password  = $password;
        $this->firma     = (string) ($caselle->perPersonale((int) $persona['id'])['firma_email'] ?? '');
    }

    /**
     * La firma così com'è salvata, con le immagini data:, per l'anteprima nella finestra
     * d'invio: nel browser si vedono, a differenza di molti programmi di posta.
     */
    public function firma(): string
    {
        return $this->firma;
    }

    /**
     * Spedisce l'email. Il testo è quello scritto dall'operatore, in testo semplice: diventa
     * HTML con escape e a capo, seguito dalla firma.
     *
     * @param array<string, string> $allegati nome del file => contenuto
     *
     * @throws RuntimeException se il server di posta rifiuta l'invio, con il suo messaggio
     */
    public function invia(string $destinatario, string $oggetto, string $testo, array $allegati = []): void
    {
        $email = $this->email();

        $email->setFrom($this->indirizzo, setting('Email.fromName') . ' - ' . $this->nome);
        $email->setTo($destinatario);
        $email->setBCC($this->indirizzo);
        $email->setSubject($oggetto);

        foreach ($allegati as $nome => $contenuto) {
            $email->attach($contenuto, 'attachment', $nome, $this->mime($nome));
        }

        $corpo = '<div style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #222222;">'
            . nl2br(esc($testo)) . '</div><br>' . $this->firmaConCid($email);
        $email->setMessage($corpo);
        $email->setAltMessage($testo . "\n\n" . $this->nome . "\n" . setting('Email.fromName'));

        // false: senza pulire, printDebugger() conserva la conversazione con il server
        if (! $email->send(false)) {
            $this->errore($email);
        }
    }

    /**
     * La classe Email configurata con il server delle Impostazioni e la casella dell'operatore.
     */
    private function email(): Email
    {
        return service('email', null, false)->initialize([
            'protocol'   => 'smtp',
            'SMTPHost'   => setting('Email.SMTPHost'),
            'SMTPPort'   => (int) setting('Email.SMTPPort'),
            'SMTPCrypto' => setting('Email.SMTPCrypto'),
            'SMTPUser'   => $this->indirizzo,
            'SMTPPass'   => $this->password,
            'mailType'   => 'html',
        ]);
    }

    /**
     * La firma con le immagini data: trasformate in allegati inline richiamati con cid:, che i
     * programmi di posta mostrano; le immagini data: molti le scartano.
     */
    private function firmaConCid(Email $email): string
    {
        $n = 0;

        return preg_replace_callback(
            '/src="data:(image\/(png|jpe?g|gif));base64,([^"]+)"/i',
            static function (array $m) use ($email, &$n): string {
                $nome = 'firma-' . ++$n . '.' . str_replace('jpeg', 'jpg', strtolower($m[2]));
                $email->attach(base64_decode($m[3]), 'inline', $nome, strtolower($m[1]));

                return 'src="cid:' . $email->setAttachmentCID($nome) . '"';
            },
            $this->firma
        );
    }

    /**
     * Il tipo MIME di un allegato dall'estensione: oggi solo PDF.
     */
    private function mime(string $nome): string
    {
        return match (strtolower(pathinfo($nome, PATHINFO_EXTENSION))) {
            'pdf'   => 'application/pdf',
            'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };
    }

    /**
     * Registra nel log la conversazione con il server e lancia l'errore con l'ultima risposta
     * negativa del server (es. «535 5.7.0 authentication failed»), che dice più di un
     * messaggio generico.
     *
     * @throws RuntimeException sempre
     */
    private function errore(Email $email): never
    {
        $debug = strip_tags($email->printDebugger(['headers']));
        log_message('error', 'EmailOperatore: invio da {indirizzo} non riuscito: {debug}', [
            'indirizzo' => $this->indirizzo, 'debug' => $debug,
        ]);

        $risposta = preg_match_all('/^\s*([45]\d\d[ -].*)$/m', $debug, $m) ? trim(end($m[1])) : '';

        throw new RuntimeException('Il server di posta non ha accettato l\'invio'
            . ($risposta !== '' ? ': ' . $risposta : '.') . ' Controlla la password della casella nel profilo.');
    }
}
