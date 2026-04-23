<?php

namespace Database\Seeders;

use Database\Factories\UserFactory;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Domain\Poetry\Models\PoemCategory;
use Domain\Poetry\Models\PoemType;
use Domain\User\Models\User;
use Illuminate\Database\Seeder;

class PoemSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first()
            ?? UserFactory::new()->poetOrganizer()->create();

        $categorySlugs = [
            'amore', 'natura', 'sociale', 'politica', 'personale', 'filosofia', 'religione', 'pace',
            'vita', 'amicizia', 'viaggio', 'altro', 'morte', 'guerra', 'lavoro', 'famiglia',
        ];

        $typeSlugs = [
            'verso-libero', 'sonetto', 'haiku', 'limerick', 'ballata', 'ode', 'elegia', 'poema-epico', 'altro',
        ];

        $categories = PoemCategory::query()
            ->whereIn('slug', $categorySlugs)
            ->get()
            ->keyBy('slug');

        $types = PoemType::query()
            ->whereIn('slug', $typeSlugs)
            ->get()
            ->keyBy('slug');

        if ($categories->isEmpty() || $types->isEmpty()) {
            $this->command?->warn('PoemSeeder: eseguire prima PoemCategorySeeder e PoemTypeSeeder.');

            return;
        }

        $rows = [
            ['title' => 'Mattina sul balcone', 'slug' => 'mattina-sul-balcone', 'cat' => 'natura', 'type' => 'haiku', 'body' => "Grigio si leva il cielo\nsul balcone freddo\nun vaso di menta."],
            ['title' => 'Cartoline', 'slug' => 'cartoline', 'cat' => 'viaggio', 'type' => 'verso-libero', 'body' => "Non scrivo più indirizzi\nsolo pieghe sulla carta\ne timbri che non partono."],
            ['title' => 'Due sedie', 'slug' => 'due-sedie', 'cat' => 'amicizia', 'type' => 'limerick', 'body' => "C'erano due sedie nel sole\ne un discorso senza ruolo\nche sapeva di pane\ne di mani sul tavolo."],
            ['title' => 'Metro in ritardo', 'slug' => 'metro-in-ritardo', 'cat' => 'lavoro', 'type' => 'verso-libero', 'body' => "Le porte si aprono in ritardo\ncome promesse tenute a metà\nla città ti regge le spalle\ne tu le reggi in silenzio."],
            ['title' => 'La zuppa del mercoledì', 'slug' => 'la-zuppa-del-mercoledi', 'cat' => 'famiglia', 'type' => 'ballata', 'body' => "Si accende il fornello lento\nil mercoledì ha i suoi colori\nil vapore copre il tavolo\ncome un abbraccio che resta."],
            ['title' => 'Primavera tardiva', 'slug' => 'primavera-tardiva', 'cat' => 'natura', 'type' => 'ode', 'body' => "O foglia che arrivi in ritardo\ninsegnami a non chiedere scusa\nal tempo che non aspetta."],
            ['title' => 'Nota a margine', 'slug' => 'nota-a-margine', 'cat' => 'personale', 'type' => 'elegia', 'body' => "In un libro prestato\nho scritto il tuo nome al confine\ntra due pagine che non tornano."],
            ['title' => 'Sul muro', 'slug' => 'sul-muro', 'cat' => 'sociale', 'type' => 'verso-libero', 'body' => "Il muro non chiede permesso\nregge manifesti e piogge\ne la voce di chi passa\nsenza guardare in alto."],
            ['title' => 'Elezioni di periferia', 'slug' => 'elezioni-di-periferia', 'cat' => 'politica', 'type' => 'verso-libero', 'body' => "Un manifesto stinto al vento\npromette strade e lampioni\nin un incrocio che aspetta\nciò che non ha nome."],
            ['title' => 'Luce in chiesa vuota', 'slug' => 'luce-in-chiesa-vuota', 'cat' => 'religione', 'type' => 'ode', 'body' => "La panchina scricchiola da sola\nla luce taglia in due il pavimento\ne basta, per un attimo, credere al silenzio."],
            ['title' => 'Trattato di respiro', 'slug' => 'trattato-di-respiro', 'cat' => 'pace', 'type' => 'haiku', 'body' => "La pace non bussa\narriva con la finestra aperta\nsul mondo intero."],
            ['title' => 'Cenere e sale', 'slug' => 'cenere-e-sale', 'cat' => 'morte', 'type' => 'elegia', 'body' => "Cenere e sale si somigliano\nnella mano aperta\nche non distingue il finale\nma il gesto, sì."],
            ['title' => 'Bambini in cortile', 'slug' => 'bambini-in-cortile', 'cat' => 'vita', 'type' => 'limerick', 'body' => "C'erano bambini nel cortile\nche chiamavano il vento con nome\ni colori strisciavano\nsulla corda, tra i gridi, senza freno."],
            ['title' => 'Lettera non spedita', 'slug' => 'lettera-non-spedita', 'cat' => 'amore', 'type' => 'verso-libero', 'body' => "Tengo la lettera sotto i libri\nche non mi servono per capire\nche a volte l’attesa\ncura più della risposta."],
            ['title' => 'Filo interrotto', 'slug' => 'filo-interrotto', 'cat' => 'guerra', 'type' => 'elegia', 'body' => "Il filo teso tra due case\nche non si parlano più\ngemette al primo vento\nche scelse la direzione."],
            ['title' => 'Caffè alle sei', 'slug' => 'caffe-alle-sei', 'cat' => 'lavoro', 'type' => 'haiku', 'body' => "Caffè nero, ora persa\ngli occhi hanno un sentiero\nche torna, quasi sempre, indietro."],
            ['title' => 'Dopo la pioggia', 'slug' => 'dopo-la-pioggia', 'cat' => 'natura', 'type' => 'verso-libero', 'body' => "Dopo la pioggia l’asfalto\nriflette un cielo che non c’è\ne cammini dentro un mondo\nche dura poche finestre."],
            ['title' => 'Esercizi di tango', 'slug' => 'esercizi-di-tango', 'cat' => 'amore', 'type' => 'ballata', 'body' => "Insegnami il passo senza chiedere\nla musica: basta un battito\nche tenga fermo il mondo\nmentre noi restiamo in cerchio."],
            ['title' => 'Festival dei vicini', 'slug' => 'festival-dei-vicini', 'cat' => 'sociale', 'type' => 'limerick', 'body' => "Si tenne in strada chiusa al traffico\nche puzzava di cibo e d’orgoglio\ne ognuno offriva storie\nche nessuno finiva, per voglia."],
            ['title' => 'Scale senza fine', 'slug' => 'scale-senza-fine', 'cat' => 'personale', 'type' => 'verso-libero', 'body' => "Ogni piano ha un rumore\nche riconosco senza aprire\nl’occhio sul numero\nincollato, storto, mio."],
            ['title' => 'Cattedrale d’aria', 'slug' => 'cattedrale-d-aria', 'cat' => 'filosofia', 'type' => 'ode', 'body' => "E se il dubbio fosse un tetto\ntrasparente, che non cade\nma insegna a reggere il vento\ncon le mani giunte, come il vetro?"],
            ['title' => 'Bussola nel cassetto', 'slug' => 'bussola-nel-cassetto', 'cat' => 'viaggio', 'type' => 'verso-libero', 'body' => "Tengo la bussola nel cassetto\ndei biglietti non usati\nper ricordare che ogni partenza\naveva almeno due nord."],
            ['title' => 'Resto in ascolto', 'slug' => 'resto-in-ascolto', 'cat' => 'altro', 'type' => 'verso-libero', 'body' => "Chiamami quando il rumore\nche ti circonda prende un nome\nche riesci a sussurrare\nche non sia solo ‘aspetto’."],
            ['title' => 'Stazione di pietra', 'slug' => 'stazione-di-pietra', 'cat' => 'viaggio', 'type' => 'sonetto', 'body' => "Qui ogni treno lascia un’attesa\nche pesa sui banchi: non è fretta\nche attraversa la folla, bensì\nciò che non riparte con noi, intera."],
            ['title' => 'Notturno in tre luci', 'slug' => 'notturno-in-tre-luci', 'cat' => 'natura', 'type' => 'verso-libero', 'body' => "Luna sui tetti, fanale sul fiume\nfinestrino aperto al terzo piano:\ntre luci, tre promesse\nche nessuna chiude a chiave."],
        ];

        foreach ($rows as $index => $row) {
            $cat = $categories->get($row['cat']);
            $type = $types->get($row['type']);
            if (! $cat || ! $type) {
                continue;
            }

            $publishedAt = now()->subDays(40 - (int) $index)->startOfHour();

            Poem::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'user_id' => $user->id,
                    'poem_category_id' => $cat->id,
                    'poem_type_id' => $type->id,
                    'title' => $row['title'],
                    'content' => $row['body'],
                    'description' => 'Testo seeder: breve poesia di esempio per sviluppo e demo.',
                    'image_path' => null,
                    'language' => 'it',
                    'status' => PoemStatus::Published,
                    'is_public' => true,
                    'published_at' => $publishedAt,
                ],
            );
        }
    }
}
