<?php

declare(strict_types=1);

namespace App\Auth;

enum LoginResult: string
{
    case Success = 'success';
    /** Credenziali errate (messaggio generico: non rivela se l'email esiste). */
    case Invalid = 'invalid';
    case Throttled = 'throttled';
    /** Password corretta ma account non attivo (sospeso, disabilitato, invito non accettato). */
    case Inactive = 'inactive';
    /** Password corretta ma nessun ruolo valido o organizzazione non abilitata. */
    case NoAccess = 'no_access';
}
