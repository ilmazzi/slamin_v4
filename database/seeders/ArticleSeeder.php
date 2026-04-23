<?php

namespace Database\Seeders;

use Database\Factories\UserFactory;
use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Article\Models\ArticleCategory;
use Domain\User\Models\User;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first()
            ?? UserFactory::new()->poetOrganizer()->create();

        $categories = ArticleCategory::query()
            ->whereIn('slug', [
                'annunci',
                'poesia',
                'tecniche-di-scrittura',
                'eventi-e-festival',
                'interviste',
                'recensioni',
            ])
            ->get()
            ->keyBy('slug');

        if ($categories->isEmpty()) {
            $this->command?->warn('ArticleSeeder: nessuna categoria trovata. Esegui prima ArticleCategorySeeder.');

            return;
        }

        $pick = static fn (string $slug) => $categories->get($slug)?->id;

        $rows = [
            [
                'title' => 'Aperte le iscrizioni al laboratorio di poesia di primavera',
                'slug' => 'iscrizioni-laboratorio-poesia-primavera',
                'excerpt' => 'Posti limitati, calendario e modalità per partecipare al gruppo in presenza a Milano.',
                'content' => $this->htmlContent(
                    'Il laboratorio si tiene ogni giovedì sera per otto incontri. Sono ammessi massimo 15 partecipanti. Per iscriversi basta inviare una breve presentazione e due componimenti di prova a redazione@example.org entro la fine del mese.'
                ),
                'category' => 'annunci',
            ],
            [
                'title' => 'Cinquanta versi sull’alba: una raccolta che non si arrende',
                'slug' => 'cinquanta-versi-sull-alba',
                'excerpt' => 'Intervista all’autrice sul ritmo, la cura del verso libero e i silenzi tra le strofe.',
                'content' => $this->htmlContent(
                    'Nella seconda edizione, la raccolta raddoppia la sezione centrale: metafore dell’orizzonte e canti brevi. L’autrice racconta come ha tagliato trenta pagine per lasciare solo ciò che “resta sotto pelle al primo ascolto”.'
                ),
                'category' => 'poesia',
            ],
            [
                'title' => 'Punteggiatura drammatica: come il trattino lungo cambia la messa in scena',
                'slug' => 'punteggiatura-drammatica-trattino-lungo',
                'excerpt' => 'Esercizi con monologhi brevi: quando usare em dash, puntini di sospensione e a capo teatrale.',
                'content' => $this->htmlContent(
                    'Iniziamo con un brano di Ibsen e uno contemporaneo. L’em dash sostituisce le pause d’attore, ma non ogni regista accetta le stesse regole. Confronto tra adattamenti italiani recenti e suggerimenti per l’esercitazione in aula.'
                ),
                'category' => 'tecniche-di-scrittura',
            ],
            [
                'title' => 'Festival della parola: date, ospiti e pianta delle sale',
                'slug' => 'festival-della-parola-2026-date-ospiti',
                'excerpt' => 'Torna l’appuntamento estivo: reading, book club notturni, laboratorio bambini e palco aperto.',
                'content' => $this->htmlContent(
                    'Tre giorni tra cortili e piazze, con mappa dei percorsi accessibili e biglietti giornalieri online. L’inaugurazione venerdì 17 luglio con l’orchestrina di tango e due poeti ospiti internazionali. Programma dettagliato in aggiornamento.'
                ),
                'category' => 'eventi-e-festival',
            ],
            [
                'title' => 'Parliamo con M.C.: editrice indipendente e libri sottili ad alta intensità',
                'slug' => 'intervista-mc-editrice-indipendente',
                'excerpt' => 'Cosa cerca oggi un piccolo editore, come si valuta un manoscritto e i tempi del catalogo unico al trimestre.',
                'content' => $this->htmlContent(
                    'Dopo dieci anni in grande distribuzione, M.C. ha aperto una collana dedicata a raccolte sotto le ottanta pagine, stampate su carta avorio. "Cerchiamo voci con un sapore locale che però tengano la scena" racconta in questa chiacchierata.'
                ),
                'category' => 'interviste',
            ],
            [
                'title' => 'Recensione: "La stanza ferma" di L. T.',
                'slug' => 'recensione-stanza-ferma-lt',
                'excerpt' => 'Un romanzo breve sull’inazione che diventa sospensione, tra dialoghi serrati e capitoli da un respiro solo.',
                'content' => $this->htmlContent(
                    'L’intreccio poco convenzionale: per metà strada il romanzo sembra fermo, e proprio lì l’autrice installa l’inquietudine. I finali aperti, qui, non sono un vezzo ma una conseguenza del ritmo, che merita un secondo ascolto in lettura ad alta voce.'
                ),
                'category' => 'recensioni',
            ],
            [
                'title' => 'Cercasi collaboratori per serata di letture benefiche',
                'slug' => 'cercasi-collaboratori-letture-benefiche',
                'excerpt' => 'Bologna, 12 settembre. Servono lettori volontari, accoglienza e un tecnico audio minimo: info per candidarsi.',
                'content' => $this->htmlContent(
                    'L’intero incasso sosterrà un progetto di abbonamenti gratuiti a bibliotechine di quartiere. Cercate persone disposte a 4 ore, compresa prova con microfono venerdì 5. Inviare disponibilità e eventuale repertorio entro 20 agosto.'
                ),
                'category' => 'annunci',
            ],
            [
                'title' => 'Sestine e cadenze: riscaldamento prima di aprire la pagina bianca',
                'slug' => 'sestine-cadenze-riscaldamento',
                'excerpt' => 'Otto minuti al giorno per sbloccare l’orecchio: proposta di lavori guidati (anche a voce) prima di un capitolo o di una poesia.',
                'content' => $this->htmlContent(
                    'Dopo il corso invernale, riproponiamo in forma ridotta: tre esercizi di rima incrociata, un “giro di ripetizione” a tema libero, e una sestina a tema forzato. Non per pubblicare, ma per tenere viva l’attenzione alle fine delle frasi e ai passaggi tonici.'
                ),
                'category' => 'tecniche-di-scrittura',
            ],
            [
                'title' => 'Memorie di tournée: taccuini, sonnellini e treni in ritardo',
                'slug' => 'memorie-tournée-taccuini-treni',
                'excerpt' => 'Quattro poeti in viaggio per venti tappe, tra sonetti improvvisati in stazione e note sparse per il prossimo libro.',
                'content' => $this->htmlContent(
                    'Dalla Toscana al Salento, il taccuino diventa l’archivio dei rumori: il click dei sedili, l’acqua in bottiglia, i nomi strani delle fermate. Un materiale “non ancora poesia” che però, una volta a casa, si scioglie in liriche brevissime.'
                ),
                'category' => 'poesia',
            ],
            [
                'title' => 'Nota dal direttore: chiusure estive della redazione',
                'slug' => 'nota-direttore-chiusure-estive-redazione',
                'excerpt' => 'Riduzione temporanea delle risposte: due settimane in cui accettiamo ancora inediti, con tempi allungati fino a metà settembre.',
                'content' => $this->htmlContent(
                    'Grazie a chi in questi mesi ha inviato testi, segnalazioni e correzioni. L’inbox resta aperta, ma le risposte potranno slittare. Dal 15 al 31 agosto in redazione c’è solo il servizio minimo. Buone letture e buon riposo.'
                ),
                'category' => 'annunci',
            ],
        ];

        foreach ($rows as $index => $row) {
            $categoryId = $pick($row['category']);
            if (! $categoryId) {
                continue;
            }

            $publishedAt = now()->subDays(30 - $index * 2)->startOfHour();

            Article::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'user_id' => $user->id,
                    'article_category_id' => $categoryId,
                    'title' => $row['title'],
                    'excerpt' => $row['excerpt'],
                    'content' => $row['content'],
                    'cover_image_path' => null,
                    'language' => 'it',
                    'status' => ArticleStatus::Published,
                    'is_public' => true,
                    'published_at' => $publishedAt,
                ]
            );
        }
    }

    private function htmlContent(string $body): string
    {
        $paragraphs = array_map(
            static fn (string $p) => '<p>'.e($p).'</p>',
            array_filter(preg_split('/\n\s*\n/', trim($body)) ?: [$body])
        );

        return implode("\n", $paragraphs);
    }
}
