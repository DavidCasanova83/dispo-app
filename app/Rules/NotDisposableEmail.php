<?php

namespace App\Rules;

use Closure;
use Fgribreau\MailChecker;
use Illuminate\Contracts\Validation\ValidationRule;

class NotDisposableEmail implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Vérifier si l'email utilise un domaine jetable
        if (!MailChecker::isValid($value)) {
            $fail('Les adresses email temporaires ou jetables ne sont pas autorisées.');
            return;
        }

        // Vérifier si le domaine a des enregistrements MX (serveur de messagerie)
        $domain = substr(strrchr($value, "@"), 1);
        if (!empty($domain) && !checkdnsrr($domain, 'MX')) {
            $fail('Le domaine de l\'adresse email n\'existe pas ou n\'accepte pas les emails.');
        }
    }
}
