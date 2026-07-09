<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsletterTranslateController extends Controller
{
    /**
     * Langues de traduction supportées (code Google Translate).
     */
    protected const SUPPORTED_LANGUAGES = ['en', 'it', 'de'];

    /**
     * Langue source de la newsletter (français).
     */
    protected const SOURCE_LANGUAGE = 'fr';

    /**
     * Redirige vers la version traduite (Google Translate) de la newsletter.
     *
     * Le permalien Mailjet est fourni dans le paramètre `url` (via [[PERMALINK]]
     * dans Mailjet). Il est transformé à la volée en URL translate.goog :
     *   https://07xwt.mjt.lu/nl3/XXXX
     *     → https://07xwt-mjt-lu.translate.goog/nl3/XXXX?_x_tr_sl=fr&_x_tr_tl=<lang>&_x_tr_hl=fr&_x_tr_pto=wapp
     */
    public function __invoke(Request $request, string $lang): RedirectResponse
    {
        // Langue cible validée par whitelist
        if (!in_array($lang, self::SUPPORTED_LANGUAGES, true)) {
            abort(404);
        }

        $permalink = trim((string) $request->query('url', ''));

        if ($permalink === '') {
            abort(400, 'Lien de newsletter manquant.');
        }

        $parts = parse_url($permalink);

        // Le permalien doit être une URL http(s) valide avec un host
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            abort(400, 'Lien de newsletter invalide.');
        }

        // Anti open-redirect : on n'accepte que les permaliens Mailjet (*.mjt.lu)
        $host = strtolower($parts['host']);
        if ($host !== 'mjt.lu' && !str_ends_with($host, '.mjt.lu')) {
            abort(400, 'Lien de newsletter non autorisé.');
        }

        // Transformation du host : les points deviennent des tirets, puis .translate.goog
        // ex : 07xwt.mjt.lu → 07xwt-mjt-lu.translate.goog
        $translatedHost = str_replace('.', '-', $host) . '.translate.goog';

        // On conserve le chemin et l'éventuelle query d'origine du permalien
        $path = $parts['path'] ?? '/';

        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        // Paramètres Google Translate (la query d'origine est préservée puis complétée)
        $query['_x_tr_sl'] = self::SOURCE_LANGUAGE;
        $query['_x_tr_tl'] = $lang;
        $query['_x_tr_hl'] = self::SOURCE_LANGUAGE;
        $query['_x_tr_pto'] = 'wapp';

        $translatedUrl = 'https://' . $translatedHost . $path . '?' . http_build_query($query);

        return redirect()->away($translatedUrl, 302);
    }
}
