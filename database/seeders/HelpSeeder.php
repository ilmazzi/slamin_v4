<?php

namespace Database\Seeders;

use Domain\Help\Models\HelpArticle;
use Domain\Help\Models\HelpCategory;
use Illuminate\Database\Seeder;

class HelpSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'account',
                'sort_order' => 1,
                'translations' => [
                    ['locale' => 'it', 'name' => 'Account'],
                    ['locale' => 'en', 'name' => 'Account'],
                ],
            ],
            [
                'slug' => 'events',
                'sort_order' => 2,
                'translations' => [
                    ['locale' => 'it', 'name' => 'Eventi'],
                    ['locale' => 'en', 'name' => 'Events'],
                ],
            ],
            [
                'slug' => 'media',
                'sort_order' => 3,
                'translations' => [
                    ['locale' => 'it', 'name' => 'Media'],
                    ['locale' => 'en', 'name' => 'Media'],
                ],
            ],
            [
                'slug' => 'poems',
                'sort_order' => 4,
                'translations' => [
                    ['locale' => 'it', 'name' => 'Poesie'],
                    ['locale' => 'en', 'name' => 'Poems'],
                ],
            ],
            [
                'slug' => 'articles',
                'sort_order' => 5,
                'translations' => [
                    ['locale' => 'it', 'name' => 'Articoli'],
                    ['locale' => 'en', 'name' => 'Articles'],
                ],
            ],
        ];

        $categoryIds = [];

        foreach ($categories as $item) {
            $category = HelpCategory::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'is_active' => true,
                    'sort_order' => $item['sort_order'],
                ],
            );

            foreach ($item['translations'] as $translation) {
                $category->translations()->updateOrCreate(
                    ['locale' => $translation['locale']],
                    ['name' => $translation['name']],
                );
            }

            $categoryIds[$item['slug']] = $category->id;
        }

        $articles = [
            [
                'slug' => 'come-creare-un-evento',
                'category' => 'events',
                'sort_order' => 1,
                'translations' => [
                    [
                        'locale' => 'it',
                        'title' => 'Come creare un evento',
                        'body' => "Dopo l'accesso, apri Eventi e scegli Crea evento.\nCompila titolo, descrizione, data, luogo o link online e la categoria. Puoi aggiungere un'immagine e scegliere se l'evento e pubblico.\nQuando sei pronto, salva. A seconda delle impostazioni della piattaforma, l'evento va online subito oppure resta in attesa di revisione.",
                    ],
                    [
                        'locale' => 'en',
                        'title' => 'How to create an event',
                        'body' => "After signing in, open Events and choose Create event.\nFill in the title, description, date, venue or online link, and category. You can add an image and choose whether the event is public.\nWhen you are ready, save. Depending on platform settings, the event goes live immediately or waits for review.",
                    ],
                ],
            ],
            [
                'slug' => 'come-creare-un-account',
                'category' => 'account',
                'sort_order' => 2,
                'translations' => [
                    [
                        'locale' => 'it',
                        'title' => 'Come creare un account',
                        'body' => "Grazie per essere qui su Slam In!\nIl sito e visitabile senza registrazione: puoi vedere eventi, leggere poesie e articoli, guardare video e scoprire nuove persone, ma non potrai interagire o caricare contenuti.\nPer creare un account fai clic sul pulsante Registrati in alto a destra.\nSulla pagina di registrazione puoi scegliere la lingua preferita per l'interfaccia (potrai cambiarla in seguito). Se non trovi la tua lingua e vuoi contribuire a migliorare Slam In, scriveteci: troveremo il modo di collaborare e rendere la piattaforma accessibile a piu persone.\nQuando hai una lingua comprensibile, inserisci il tuo nome completo, un nickname se lo desideri, e un indirizzo e-mail attivo: ti invieremo un messaggio di conferma con un link per attivare l'account.\nScegli una password e confermala nel campo \"Conferma password\".\nSeleziona uno o piu ruoli nel mondo della poesia (potrai modificarli in seguito: servono per aprire le aree giuste e i permessi specifici).\nAlla fine fai clic su \"Unisciti a Slam In\" e attendi la mail di conferma. Apri il link di attivazione e accedi con e-mail e password.",
                    ],
                    [
                        'locale' => 'en',
                        'title' => 'How to create an account',
                        'body' => "Thanks for being here on Slam In!\nThe site can be visited without an account: you can see events, read poems and articles, watch videos and discover people, but you cannot interact or upload content.\nTo create an account, click Register at the top right.\nOn the registration page you can choose your preferred interface language (you can change it later). If your language is missing and you want to help improve Slam In, write to us and we will find a way to collaborate.\nEnter your full name, an optional nickname, and an active email address: we will send a confirmation message with a link to activate the account.\nChoose a password and confirm it in the Confirm password field.\nSelect one or more roles in the poetry world (you can change them later; they unlock the right areas and permissions).\nThen click Join Slam In and wait for the confirmation email. Open the activation link and sign in with your email and password.",
                    ],
                ],
            ],
            [
                'slug' => 'come-caricare-un-video',
                'category' => 'media',
                'sort_order' => 3,
                'translations' => [
                    [
                        'locale' => 'it',
                        'title' => 'Come caricare un video',
                        'body' => "Accedi e apri Media, poi Carica.\nScegli un file video nei formati consentiti, aggiungi titolo e descrizione, e conferma. Il video viene inviato al server PeerTube della piattaforma.\nDopo l'elaborazione comparira nel catalogo. Se il caricamento non parte, controlla dimensione, formato e i limiti del tuo account.",
                    ],
                    [
                        'locale' => 'en',
                        'title' => 'How to upload a video',
                        'body' => "Sign in, open Media, then Upload.\nChoose a video file in an allowed format, add a title and description, and confirm. The video is sent to the platform PeerTube server.\nAfter processing it appears in the catalog. If the upload does not start, check size, format and your account limits.",
                    ],
                ],
            ],
            [
                'slug' => 'come-pubblicare-una-poesia',
                'category' => 'poems',
                'sort_order' => 4,
                'translations' => [
                    [
                        'locale' => 'it',
                        'title' => 'Come pubblicare una poesia',
                        'body' => "Dalla dashboard o da Poesie scegli Crea poesia.\nInserisci titolo, testo, lingua e categoria. Puoi aggiungere una descrizione e un'immagine.\nSalva come bozza oppure pubblica. Se la piattaforma richiede revisione, la poesia resta in attesa finche un moderatore non la approva.",
                    ],
                    [
                        'locale' => 'en',
                        'title' => 'How to publish a poem',
                        'body' => "From the dashboard or Poems, choose Create poem.\nEnter title, text, language and category. You can add a description and an image.\nSave as a draft or publish. If the platform requires review, the poem stays pending until a moderator approves it.",
                    ],
                ],
            ],
            [
                'slug' => 'come-pubblicare-un-articolo',
                'category' => 'articles',
                'sort_order' => 5,
                'translations' => [
                    [
                        'locale' => 'it',
                        'title' => 'Come pubblicare un articolo',
                        'body' => "Apri Articoli e scegli Crea articolo.\nCompila titolo, estratto, contenuto e categoria. Puoi aggiungere un'immagine di copertina.\nQuando pubblichi, l'articolo va online subito oppure entra in coda di moderazione, a seconda delle impostazioni admin.",
                    ],
                    [
                        'locale' => 'en',
                        'title' => 'How to publish an article',
                        'body' => "Open Articles and choose Create article.\nFill in title, excerpt, content and category. You can add a cover image.\nWhen you publish, the article goes live immediately or enters the moderation queue, depending on admin settings.",
                    ],
                ],
            ],
        ];

        foreach ($articles as $item) {
            $article = HelpArticle::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'help_category_id' => $categoryIds[$item['category']],
                    'is_published' => true,
                    'sort_order' => $item['sort_order'],
                ],
            );

            foreach ($item['translations'] as $translation) {
                $article->translations()->updateOrCreate(
                    ['locale' => $translation['locale']],
                    [
                        'title' => $translation['title'],
                        'body' => $translation['body'],
                    ],
                );
            }
        }
    }
}
