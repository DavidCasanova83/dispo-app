<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoSpamContent implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Pas de validation si le champ est vide
        }

        // Détecter les URLs dans le contenu (spam courant)
        if (preg_match('/(https?:\/\/|www\.)/i', $value)) {
            $fail('Les liens ne sont pas autorisés dans ce champ.');
            return;
        }

        // Détecter les mots-clés spam courants
        $spamKeywords = [
            'viagra', 'cialis', 'casino', 'poker', 'lottery', 'winner',
            'congratulations', 'click here', 'free money', 'earn money',
            'work from home', 'buy now', 'limited offer', 'act now',
            'bitcoin', 'cryptocurrency', 'investment opportunity',
            'make money fast', 'get rich', 'discount', 'prize'
        ];

        $lowerValue = strtolower($value);
        foreach ($spamKeywords as $keyword) {
            if (str_contains($lowerValue, $keyword)) {
                $fail('Le contenu contient des mots suspects. Veuillez reformuler votre message.');
                return;
            }
        }

        // Détecter la répétition excessive de caractères (spam pattern)
        if (preg_match('/(.)\1{10,}/', $value)) {
            $fail('Le contenu contient trop de caractères répétés.');
            return;
        }

        // Détecter trop de majuscules (crier = spam)
        $uppercaseCount = preg_match_all('/[A-Z]/', $value);
        $totalChars = strlen(preg_replace('/\s/', '', $value));
        if ($totalChars > 20 && ($uppercaseCount / $totalChars) > 0.5) {
            $fail('Veuillez utiliser moins de majuscules dans votre message.');
        }
    }
}
