<?php

namespace App\Controllers\Impostazioni;

use App\Controllers\BaseController;
use App\Libraries\PropostaAbbonamento;

class EmailController extends BaseController
{
    /**
     * Impostazioni dell'email comuni a tutti gli operatori: il server di posta e i testi delle
     * email ai clienti. La password non c'è: ognuno spedisce dalla propria casella, che si
     * configura nel profilo.
     *
     * Server, porta, cifratura e mittente ripiegano su Config\Email, oggetto e testo della
     * proposta sulle costanti di PropostaAbbonamento: la pagina funziona anche prima del
     * primo salvataggio.
     */
    public function index(): string
    {
        return view('impostazioni/email', [
            'smtp_host'       => setting('Email.SMTPHost'),
            'smtp_porta'      => setting('Email.SMTPPort'),
            'smtp_cifratura'  => setting('Email.SMTPCrypto'),
            'email_mittente'  => setting('Email.fromName'),
            'oggetto_email'   => setting('Proposte.oggetto_email') ?? PropostaAbbonamento::OGGETTO_EMAIL_DEFAULT,
            'testo_email'     => setting('Proposte.testo_email') ?? PropostaAbbonamento::TESTO_EMAIL_DEFAULT,
            'invii_massimi'   => PropostaAbbonamento::inviiMassimi(),
            'help_sezione'    => 'impostazioni',
        ]);
    }

    /**
     * Salva le impostazioni dell'email. Tutte obbligatorie: senza, l'invio delle proposte non
     * parte. La validazione precede ogni scrittura, così un valore sbagliato non lascia gli
     * altri salvati a metà.
     */
    public function salva()
    {
        if (! $this->validate([
            'smtp_host'      => 'required|max_length[100]',
            'smtp_porta'     => 'required|is_natural_no_zero|less_than_equal_to[65535]',
            'smtp_cifratura' => 'required|in_list[ssl,tls]',
            'email_mittente' => 'required|max_length[100]',
            'oggetto_email'  => 'required|max_length[200]',
            'testo_email'    => 'required',
            'invii_massimi'  => 'required|is_natural_no_zero|less_than_equal_to[200]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        // Sovrascrivono i valori di Config\Email, che restano il ripiego finché non si salva qui.
        setting()->set('Email.SMTPHost', trim($post['smtp_host']));
        setting()->set('Email.SMTPPort', (int) $post['smtp_porta']);
        setting()->set('Email.SMTPCrypto', $post['smtp_cifratura']);
        setting()->set('Email.fromName', trim($post['email_mittente']));

        setting()->set('Proposte.oggetto_email', trim($post['oggetto_email']));
        setting()->set('Proposte.testo_email', trim($post['testo_email']));
        setting()->set('Proposte.invii_massimi', (int) $post['invii_massimi']);

        return redirect()->to('impostazioni/email')->with('success', 'Impostazioni dell\'email salvate.');
    }
}
