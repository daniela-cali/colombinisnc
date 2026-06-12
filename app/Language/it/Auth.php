<?php

declare(strict_types=1);

return [
    // Exceptions
    'unknownAuthenticator'  => '{0} non è un autenticatore valido.',
    'unknownUserProvider'   => 'Impossibile determinare il provider utente da utilizzare.',
    'invalidUser'           => 'Impossibile trovare l\'utente specificato.',
    'bannedUser'            => 'Accesso non consentito: l\'account è stato sospeso.',
    'logOutBannedUser'      => 'Sei stato disconnesso perché il tuo account è stato sospeso.',
    'badAttempt'            => 'Accesso non riuscito. Verifica le credenziali inserite.',
    'noPassword'            => 'Impossibile validare un utente senza password.',
    'invalidPassword'       => 'Accesso non riuscito. Verifica la password inserita.',
    'noToken'               => 'Ogni richiesta deve includere un bearer token nell\'intestazione {0}.',
    'badToken'              => 'Il token di accesso non è valido.',
    'oldToken'              => 'Il token di accesso è scaduto.',
    'noUserEntity'          => 'È necessario fornire l\'entità utente per la validazione della password.',
    'invalidEmail'          => 'Impossibile verificare che l\'indirizzo email "{0}" corrisponda a quello registrato.',
    'unableSendEmailToUser' => 'Si è verificato un problema nell\'invio dell\'email. Impossibile inviare un\'email a "{0}".',
    'throttled'             => 'Troppe richieste da questo indirizzo IP. Riprova tra {0} secondi.',
    'notEnoughPrivilege'    => 'Non disponi dei permessi necessari per eseguire questa operazione.',
    // JWT Exceptions
    'invalidJWT'     => 'Il token non è valido.',
    'expiredJWT'     => 'Il token è scaduto.',
    'beforeValidJWT' => 'Il token non è ancora disponibile.',

    'email'           => 'Indirizzo email',
    'username'        => 'Nome utente',
    'password'        => 'Password',
    'passwordConfirm' => 'Password (di nuovo)',
    'haveAccount'     => 'Hai già un account?',
    'token'           => 'Token',

    // Buttons
    'confirm' => 'Conferma',
    'send'    => 'Invia',

    // Registration
    'register'         => 'Registrati',
    'registerDisabled' => 'La registrazione non è attualmente consentita.',
    'registerSuccess'  => 'Benvenuto!',

    // Login
    'login'              => 'Accedi',
    'needAccount'        => 'Non hai un account?',
    'rememberMe'         => 'Ricordami',
    'forgotPassword'     => 'Hai dimenticato la password?',
    'useMagicLink'       => 'Usa un link di accesso',
    'magicLinkSubject'   => 'Il tuo link di accesso',
    'magicTokenNotFound' => 'Impossibile verificare il link.',
    'magicLinkExpired'   => 'Il link è scaduto.',
    'checkYourEmail'     => 'Controlla la tua email!',
    'magicLinkDetails'   => 'Ti abbiamo inviato un\'email con un link di accesso. È valido per {0} minuti.',
    'magicLinkDisabled'  => 'L\'uso del MagicLink non è attualmente consentito.',
    'successLogout'      => 'Disconnessione avvenuta con successo.',
    'backToLogin'        => 'Torna al login',

    // Passwords
    'errorPasswordLength'       => 'La password deve essere lunga almeno {0, number} caratteri.',
    'suggestPasswordLength'     => 'Le passphrase — fino a 255 caratteri — sono password più sicure e facili da ricordare.',
    'errorPasswordCommon'       => 'La password non deve essere una password comune.',
    'suggestPasswordCommon'     => 'La password è stata confrontata con oltre 65.000 password comuni o compromesse.',
    'errorPasswordPersonal'     => 'La password non può contenere informazioni personali.',
    'suggestPasswordPersonal'   => 'Non usare varianti del tuo indirizzo email o nome utente come password.',
    'errorPasswordTooSimilar'   => 'La password è troppo simile al nome utente.',
    'suggestPasswordTooSimilar' => 'Non usare parti del tuo nome utente nella password.',
    'errorPasswordPwned'        => 'La password {0} è stata esposta a seguito di una violazione dei dati ed è stata trovata {1, number} volte in {2} di password compromesse.',
    'suggestPasswordPwned'      => '{0} non dovrebbe mai essere usata come password. Se la stai usando altrove, cambiala immediatamente.',
    'errorPasswordEmpty'        => 'La password è obbligatoria.',
    'errorPasswordTooLongBytes' => 'La password non può superare {param} byte.',
    'passwordChangeSuccess'     => 'Password modificata con successo.',
    'userDoesNotExist'          => 'Password non modificata. L\'utente non esiste.',
    'resetTokenExpired'         => 'Il token di reset è scaduto.',

    // Email Globals
    'emailInfo'      => 'Informazioni sull\'utente:',
    'emailIpAddress' => 'Indirizzo IP:',
    'emailDevice'    => 'Dispositivo:',
    'emailDate'      => 'Data:',

    // 2FA
    'email2FATitle'       => 'Autenticazione a due fattori',
    'confirmEmailAddress' => 'Conferma il tuo indirizzo email.',
    'emailEnterCode'      => 'Conferma la tua email',
    'emailConfirmCode'    => 'Inserisci il codice a 6 cifre che ti abbiamo inviato via email.',
    'email2FASubject'     => 'Il tuo codice di autenticazione',
    'email2FAMailBody'    => 'Il tuo codice di autenticazione è:',
    'invalid2FAToken'     => 'Il codice inserito non è corretto.',
    'need2FA'             => 'Devi completare la verifica a due fattori.',
    'needVerification'    => 'Controlla la tua email per completare l\'attivazione dell\'account.',

    // Activate
    'emailActivateTitle'    => 'Attivazione email',
    'emailActivateBody'     => 'Ti abbiamo inviato un\'email con un codice per confermare il tuo indirizzo. Copia il codice e incollalo qui sotto.',
    'emailActivateSubject'  => 'Il tuo codice di attivazione',
    'emailActivateMailBody' => 'Usa il codice seguente per attivare il tuo account.',
    'invalidActivateToken'  => 'Il codice inserito non è corretto.',
    'needActivate'          => 'Devi completare la registrazione confermando il codice inviato al tuo indirizzo email.',
    'activationBlocked'     => 'Devi attivare il tuo account prima di effettuare l\'accesso.',

    // Groups
    'unknownGroup' => '{0} non è un gruppo valido.',
    'missingTitle' => 'I gruppi devono avere un titolo.',

    // Permissions
    'unknownPermission' => '{0} non è un permesso valido.',
];
