<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Seeders\Concerns\ResolvesSeedAuthor;
use Domain\Event\Enums\EventDeliveryMode;
use Domain\Event\Enums\EventScheduleMode;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Enums\GigType;
use Domain\PoetrySlam\Enums\PoetrySlamEntrySource;
use Domain\PoetrySlam\Enums\PoetrySlamEntryStatus;
use Domain\PoetrySlam\Enums\PoetrySlamStatus;
use Domain\PoetrySlam\Models\PoetrySlam;
use Domain\PoetrySlam\Models\PoetrySlamEntry;
use Domain\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class EventSeeder extends Seeder
{
    use ResolvesSeedAuthor;

    public function run(): void
    {
        $organizer = $this->resolveSeedAuthor();

        $bySlug = EventCategory::query()
            ->where('is_active', true)
            ->get()
            ->keyBy('slug');

        if ($bySlug->isEmpty()) {
            $this->command?->warn('EventSeeder: eseguire prima EventCategorySeeder.');

            return;
        }

        $base = Carbon::parse(now())->addDays(7)->startOfDay();

        foreach ($this->eventTemplates($base) as $i => $tpl) {
            $this->upsert($tpl, $bySlug, $organizer, (int) $i);
        }

        $this->command?->info('EventSeeder: 30 eventi (gig e poetry slam ove presenti).');
    }

    /**
     * @return list<array{
     *   slug: string, title: string, category: string, description: string, in_person: bool,
     *   starts: Carbon, ends: Carbon,
     *   venue: array{place: string, city: string, postal: ?string, address: string, lat: ?float, lng: ?float}|null,
     *   online: array{url: string, tz: string}|null,
     *   with_slam: bool, slam: array{judging: string, slots: int}|array{},
     *   gigs: list<array{type: GigType, desc: string, qty: int, hospitality: bool, price: float}>
     * }>
     */
    private function eventTemplates(CarbonInterface $base): array
    {
        $d = static fn (int $n, int $h, int $m = 0) => $base->copy()->addDays($n)->setTime($h, $m);

        return [
            [
                'slug' => 'serata-voci-sottovoce-torino', 'title' => 'Serata «Voci sottovoce»', 'category' => 'poesia-piu-altra-arte',
                'description' => 'Reading intimo: poeti emergenti, musica al pianoforte, calice di vino a fine serata. Posti limitati, silenzio di sala richiesto nelle letture.',
                'in_person' => true, 'starts' => $d(5, 20, 30), 'ends' => $d(5, 22, 30),
                'venue' => ['place' => 'Centro Einaudi', 'city' => 'Torino', 'postal' => '10123', 'address' => 'Via Maria Vittoria 4', 'lat' => 45.0708, 'lng' => 7.6806], 'online' => null,
                'with_slam' => false, 'slam' => [],
                'gigs' => [
                    ['type' => GigType::McHost, 'desc' => 'Conduzione serata, scaletta e collegamento con il pubblico.', 'qty' => 1, 'hospitality' => false, 'price' => 120.0],
                    ['type' => GigType::PoetArtist, 'desc' => "Lettura in anteprima (15') in coda alla serata.", 'qty' => 1, 'hospitality' => false, 'price' => 90.0],
                ],
            ],
            [
                'slug' => 'festival-tra-i-libri-2026', 'title' => 'Festival «Tra i libri»', 'category' => 'festival',
                'description' => 'Giornate tra tavole, incontri e spazi dedicati: non una fiera rumorosa, un luogo in cui fermarsi tra uno stand e l’altra, con caffè e chiacchierate.',
                'in_person' => true, 'starts' => $d(18, 10, 0), 'ends' => $d(18, 22, 0),
                'venue' => ['place' => 'Cavallerizza Reale', 'city' => 'Reggio Emilia', 'postal' => '42124', 'address' => 'Piazzale Fiume 1', 'lat' => 44.7007, 'lng' => 10.6355], 'online' => null,
                'with_slam' => false, 'slam' => [],
                'gigs' => [
                    ['type' => GigType::PoetArtist, 'desc' => 'Letture brevi in area dedicata (turni pomeridiani).', 'qty' => 2, 'hospitality' => true, 'price' => 200.0],
                ],
            ],
            [
                'slug' => 'laboratorio-respiro-e-verso', 'title' => 'Laboratorio: respiro e verso', 'category' => 'laboratorio-corso',
                'description' => 'Ciclo su voce, pause e punteggiatura. Gruppo ridotto, materiali in carta riciclata, ambiente rispettoso.',
                'in_person' => true, 'starts' => $d(25, 16, 0), 'ends' => $d(25, 18, 30),
                'venue' => ['place' => 'Casa del Quartiere', 'city' => 'Milano', 'postal' => '20154', 'address' => 'Via Padova 21', 'lat' => 45.4965, 'lng' => 9.2014], 'online' => null,
                'with_slam' => false, 'slam' => [],
                'gigs' => [
                    ['type' => GigType::McHost, 'desc' => 'Coordinamento incontro introduttivo (un pomeriggio).', 'qty' => 1, 'hospitality' => false, 'price' => 100.0],
                ],
            ],
            [
                'slug' => 'open-mic-giardino-oltremura', 'title' => 'Open mic in giardino', 'category' => 'open-mic',
                'description' => 'Sotto il pergolato, tre microfoni, lista al banco. Chi arriva tardi aspetta. Nessun premio, solo ascolto.',
                'in_person' => true, 'starts' => $d(8, 20, 0), 'ends' => $d(8, 23, 0),
                'venue' => ['place' => 'Spazio 19', 'city' => 'Bologna', 'postal' => '40131', 'address' => 'Via Valdonio 2', 'lat' => 44.5006, 'lng' => 11.3506], 'online' => null,
                'with_slam' => false, 'slam' => [],
                'gigs' => [
                    ['type' => GigType::PoetArtist, 'desc' => "Ospite di apertura (20').", 'qty' => 1, 'hospitality' => false, 'price' => 60.0],
                ],
            ],
            [
                'slug' => 'slam-urbano-prima-tappa', 'title' => 'Slam urbano — prima tappa', 'category' => 'poetry-slam',
                'description' => 'Formato in tre turni, giuria mista, regolamento in una pagina. Traduzione in LIS su richiesta, con preavviso.',
                'in_person' => true, 'starts' => $d(12, 21, 0), 'ends' => $d(12, 23, 30),
                'venue' => ['place' => 'Teatro Smeraldo', 'city' => 'Milano', 'postal' => '20100', 'address' => 'Via Savona 15', 'lat' => 45.4567, 'lng' => 9.1605], 'online' => null,
                'with_slam' => true, 'slam' => ['judging' => 'mixed_panel', 'slots' => 20],
                'gigs' => [
                    ['type' => GigType::TechnicalSupport, 'desc' => 'Audio, luci, microfoni, mixaggio.', 'qty' => 1, 'hospitality' => false, 'price' => 150.0],
                ],
            ],
            [
                'slug' => 'slam-riva-estate', 'title' => 'Slam sotto le vele', 'category' => 'poetry-slam',
                'description' => 'Serata all’aperto, coperti in caso di pioggia. Posti a sedere e in piedi, tempi umani tra un turno e l’altro.',
                'in_person' => true, 'starts' => $d(35, 21, 30), 'ends' => $d(35, 23, 45),
                'venue' => ['place' => 'Banchina Molo', 'city' => 'Genova', 'postal' => '16128', 'address' => 'Calata Cattaneo 5', 'lat' => 44.4046, 'lng' => 8.9364], 'online' => null,
                'with_slam' => true, 'slam' => ['judging' => 'jury_five', 'slots' => 24], 'gigs' => [],
            ],
            [
                'slug' => 'caffe-letterario-mattina', 'title' => 'Caffè letterario in mattinata', 'category' => 'reading',
                'description' => 'Un’ora e mezza tra libri, carta cialda e letture a bassa voce. Accessibile, ingresso gradito ma non vincolante.',
                'in_person' => true, 'starts' => $d(3, 9, 30), 'ends' => $d(3, 11, 0),
                'venue' => ['place' => 'Libreria Corso', 'city' => 'Firenze', 'postal' => '50100', 'address' => 'Corso Italia 4', 'lat' => 43.7686, 'lng' => 11.2569], 'online' => null,
                'with_slam' => false, 'slam' => [], 'gigs' => [],
            ],
            [
                'slug' => 'concorso-inediti-voci-giovani', 'title' => 'Concorso inediti «Voci giovani»', 'category' => 'concorso',
                'description' => 'Premiazione composta, letture di estratti, niente urlo al microfono. I finalisti vengono avvisati con largo anticipo.',
                'in_person' => true, 'starts' => $d(45, 16, 0), 'ends' => $d(45, 19, 0),
                'venue' => ['place' => 'Municipio Sala Rossa', 'city' => 'Perugia', 'postal' => '06121', 'address' => 'Piazza Matilde 3', 'lat' => 43.1107, 'lng' => 12.3908], 'online' => null,
                'with_slam' => false, 'slam' => [], 'gigs' => [],
            ],
            [
                'slug' => 'slam-virtuale-martedi', 'title' => 'Slam in videoconferenza', 'category' => 'poetry-slam',
                'description' => 'Sala d’aspetto 15 minuti prima, respiri tra i turni. L’accesso al link non viene ridiffuso in chat.',
                'in_person' => false, 'starts' => $d(10, 20, 0), 'ends' => $d(10, 22, 0),
                'venue' => null, 'online' => ['url' => 'https://meet.example.org/slam-virtuale-2026', 'tz' => 'Europe/Rome'],
                'with_slam' => true, 'slam' => ['judging' => 'audience_poll', 'slots' => 16], 'gigs' => [],
            ],
            [
                'slug' => 'incontro-tavola-rotonda-cura', 'title' => 'Tavola rotonda: la cura delle parole', 'category' => 'conferenza-tavola-rotonda',
                'description' => 'Interpreti e tempi rispettati, domande scritte a metà incontro per chi preferisce non parlare a voce.',
                'in_person' => true, 'starts' => $d(22, 15, 0), 'ends' => $d(22, 17, 30),
                'venue' => ['place' => 'Aula magna Sapienza', 'city' => 'Roma', 'postal' => '00100', 'address' => 'Piazzale Aldo Moro 5', 'lat' => 41.9007, 'lng' => 12.5125], 'online' => null,
                'with_slam' => false, 'slam' => [], 'gigs' => [],
            ],
            [
                'slug' => 'residenza-lenta-colline', 'title' => 'Residenza lenta in collina', 'category' => 'residenza',
                'description' => 'Giorni di scrittura condivisa, pasti in comune, spazi in silenzio. Posti ridotti, selezione su progetto breve.',
                'in_person' => true, 'starts' => $d(60, 9, 0), 'ends' => $d(67, 18, 0),
                'venue' => ['place' => 'Casa Boccaccio', 'city' => 'Scarperia', 'postal' => '50038', 'address' => 'Via dei Mulini 3', 'lat' => 43.9995, 'lng' => 11.3502], 'online' => null,
                'with_slam' => false, 'slam' => [],
                'gigs' => [
                    ['type' => GigType::PoetArtist, 'desc' => "Tutor: lettura e revisione (blocchi da 90').", 'qty' => 1, 'hospitality' => true, 'price' => 800.0],
                ],
            ],
            [
                'slug' => 'concerto-poesia-e-archetto', 'title' => 'Poesia e archetto', 'category' => 'concerto-musica',
                'description' => 'Letture alternate a brani per quartetto d’archi. Tono soffuso, pause tra le parti rispettate.',
                'in_person' => true, 'starts' => $d(14, 19, 0), 'ends' => $d(14, 20, 45),
                'venue' => ['place' => 'Auditorium San Rocco', 'city' => 'Padova', 'postal' => '35121', 'address' => 'Via San Rocco 1', 'lat' => 45.4003, 'lng' => 11.8757], 'online' => null,
                'with_slam' => false, 'slam' => [], 'gigs' => [],
            ],
            [
                'slug' => 'slam-aurora-urbana', 'title' => 'Slam all’alba in piazzetta', 'category' => 'poetry-slam',
                'description' => 'Sperimentale, tempi brevi, caffè offerto. Temperatura fresca: cuscini o coperte consigliate.',
                'in_person' => true, 'starts' => $d(40, 6, 0), 'ends' => $d(40, 7, 30),
                'venue' => ['place' => 'Piazzetta del Carmine', 'city' => 'Pisa', 'postal' => '56127', 'address' => 'Largo Ciro Menotti 1', 'lat' => 43.7216, 'lng' => 10.4005], 'online' => null,
                'with_slam' => true, 'slam' => ['judging' => 'jury_five', 'slots' => 12],
                'gigs' => [
                    ['type' => GigType::Volunteer, 'desc' => 'Accoglienza, lista iscritti, supporto a richiesta.', 'qty' => 2, 'hospitality' => false, 'price' => 0.0],
                ],
            ],
            [
                'slug' => 'presentazione-romanzo-vento', 'title' => 'Presentazione: «Vento sull’erba secca»', 'category' => 'presentazione-libro',
                'description' => 'Dialogo con l’autrice, brani a turni, spazio per domande. Firmacopie con turno, senza ressa.',
                'in_person' => true, 'starts' => $d(6, 18, 0), 'ends' => $d(6, 19, 30),
                'venue' => ['place' => 'Librerie Coop', 'city' => 'Bari', 'postal' => '70121', 'address' => 'Strada Cavour 1', 'lat' => 41.1264, 'lng' => 16.8706], 'online' => null,
                'with_slam' => false, 'slam' => [], 'gigs' => [],
            ],
            [
                'slug' => 'spoke-word-ferro-vecchio', 'title' => 'Spoken word «Ferro e voce»', 'category' => 'spoken-word',
                'description' => 'Line-up breve, niente punteggi, luce e microfono. Chi preferisce, ascolto da seduto con cuscini.',
                'in_person' => true, 'starts' => $d(16, 22, 0), 'ends' => $d(16, 23, 30),
                'venue' => ['place' => 'Csa Lambretta', 'city' => 'Napoli', 'postal' => '80100', 'address' => 'Via Santa Maria 12', 'lat' => 40.8507, 'lng' => 14.2565], 'online' => null,
                'with_slam' => false, 'slam' => [],
                'gigs' => [
                    ['type' => GigType::PoetArtist, 'desc' => 'Ospite: lettura bilingue, breve introduzione al contesto.', 'qty' => 1, 'hospitality' => true, 'price' => 250.0],
                ],
            ],
            [
                'slug' => 'workshop-scrivere-con-chiarezza', 'title' => 'Workshop: scrivere con chiarezza', 'category' => 'laboratorio-corso',
                'description' => 'Videoconferenza: poche slide, esercizi brevi, pausa in programma. Chat moderata, domande a fine blocco.',
                'in_person' => false, 'starts' => $d(20, 15, 0), 'ends' => $d(20, 17, 0),
                'venue' => null, 'online' => ['url' => 'https://meet.example.org/workshop-chiarezza', 'tz' => 'Europe/Rome'],
                'with_slam' => false, 'slam' => [],
                'gigs' => [
                    ['type' => GigType::PoetArtist, 'desc' => 'Sessione “feedback gentile” in piccoli gruppi (breakout).', 'qty' => 1, 'hospitality' => false, 'price' => 70.0],
                ],
            ],
            [
                'slug' => 'slam-amicizia-rossa', 'title' => 'Slam notturno «L’amico del microfono»', 'category' => 'poetry-slam',
                'description' => 'Chiusura entro mezzanotte, volume monitorato. Giuria popolare con schede cartacee, niente corse in scale.',
                'in_person' => true, 'starts' => $d(28, 22, 30), 'ends' => $d(29, 0, 0),
                'venue' => ['place' => 'Lanificio 25', 'city' => 'Bergamo', 'postal' => '24122', 'address' => 'Via Corridoni 25', 'lat' => 45.7005, 'lng' => 9.6765], 'online' => null,
                'with_slam' => true, 'slam' => ['judging' => 'audience_poll', 'slots' => 30],
                'gigs' => [
                    ['type' => GigType::McHost, 'desc' => 'MC, tempi, linguaggio rispettoso per tuttə.', 'qty' => 1, 'hospitality' => false, 'price' => 110.0],
                ],
            ],
            [
                'slug' => 'festival-riverberi-inverno', 'title' => 'Festival «Riverberi d’inverno»', 'category' => 'festival',
                'description' => 'Giornate con laboratori e serate, limiti acustici in sala rispettati, niente altoparlanti oltre soglia discusse.',
                'in_person' => true, 'starts' => $d(50, 11, 0), 'ends' => $d(52, 23, 0),
                'venue' => ['place' => 'Museo Diocesano', 'city' => 'Trento', 'postal' => '38100', 'address' => 'Via S. Pietro 12', 'lat' => 46.0703, 'lng' => 11.1215], 'online' => null,
                'with_slam' => false, 'slam' => [],
                'gigs' => [
                    ['type' => GigType::Volunteer, 'desc' => 'Accoglienza a turni (badge in ingresso).', 'qty' => 2, 'hospitality' => false, 'price' => 0.0],
                ],
            ],
            [
                'slug' => 'reading-fantasma-invernale', 'title' => 'Reading «Fantasma d’inverno»', 'category' => 'reading',
                'description' => 'Poche sedute, voci basse, tè a metà serata. Un ambiente intimo, senza fretta.',
                'in_person' => true, 'starts' => $d(2, 18, 30), 'ends' => $d(2, 20, 0),
                'venue' => ['place' => 'Bottega Testori', 'city' => 'Como', 'postal' => '22100', 'address' => 'Via Milano 2', 'lat' => 45.8081, 'lng' => 9.0852], 'online' => null,
                'with_slam' => false, 'slam' => [], 'gigs' => [],
            ],
            [
                'slug' => 'poesia-e-vino-chiusura', 'title' => 'Poesia e vino: chiusura rituale', 'category' => 'poesia-piu-altra-arte',
                'description' => 'Degustazione leggera, zero obbligo. Non bevitori: alternative segnalate in scheda il giorno stesso.',
                'in_person' => true, 'starts' => $d(30, 19, 0), 'ends' => $d(30, 21, 0),
                'venue' => ['place' => 'Enoteca Le vele', 'city' => 'Trieste', 'postal' => '34100', 'address' => 'Via Malcanton 1', 'lat' => 45.6495, 'lng' => 13.7687], 'online' => null,
                'with_slam' => false, 'slam' => [],
                'gigs' => [
                    ['type' => GigType::McHost, 'desc' => 'Conduzione sobria, tempi scanditi.', 'qty' => 1, 'hospitality' => false, 'price' => 85.0],
                ],
            ],
            [
                'slug' => 'tavola-educare-all-ascolto', 'title' => 'Tavola: educare all’ascolto', 'category' => 'conferenza-tavola-rotonda',
                'description' => 'Incontro con scuole e famiglie, materiali in PDF dopo. Sottotitoli in diretta per chi ne fa richiesta.',
                'in_person' => false, 'starts' => $d(24, 10, 0), 'ends' => $d(24, 12, 0),
                'venue' => null, 'online' => ['url' => 'https://meet.example.org/ascolto-2026', 'tz' => 'Europe/Rome'],
                'with_slam' => false, 'slam' => [], 'gigs' => [],
            ],
            [
                'slug' => 'open-mic-piccolo-teatro', 'title' => 'Open mic a piccolo teatro', 'category' => 'open-mic',
                'description' => 'Gradini segnalati, lista chiusa poco prima dell’inizio. Accesso: due microfoni, prova in orario fissato.',
                'in_person' => true, 'starts' => $d(9, 20, 30), 'ends' => $d(9, 22, 45),
                'venue' => ['place' => 'Teatro Aperto', 'city' => 'Venezia', 'postal' => '30100', 'address' => 'Calle de la Misericordia 8', 'lat' => 45.4442, 'lng' => 12.3334], 'online' => null,
                'with_slam' => false, 'slam' => [],
                'gigs' => [
                    ['type' => GigType::TechnicalSupport, 'desc' => 'Piano semplice, prova 18:00, due microfoni.', 'qty' => 1, 'hospitality' => false, 'price' => 95.0],
                ],
            ],
            [
                'slug' => 'laboratorio-ritmica-parole', 'title' => 'Laboratorio: ritmica e parole', 'category' => 'laboratorio-corso',
                'description' => 'Solo voci e tappeto sonoro, nessuna gara, gruppo fino a 12 persone, età adulti.',
                'in_person' => true, 'starts' => $d(32, 18, 0), 'ends' => $d(32, 20, 0),
                'venue' => ['place' => 'Casa Luzzati', 'city' => 'Genova', 'postal' => '16100', 'address' => 'Piazza della Lepre 3', 'lat' => 44.4046, 'lng' => 8.9340], 'online' => null,
                'with_slam' => false, 'slam' => [], 'gigs' => [],
            ],
            [
                'slug' => 'concerto-voci-e-arpa', 'title' => 'Voci d’inverno e arpa', 'category' => 'concerto-musica',
                'description' => 'Sala poco amplificata, segnaletica in uscita, un accompagnamento calmo in caso di affollamento lieve.',
                'in_person' => true, 'starts' => $d(11, 20, 45), 'ends' => $d(11, 22, 15),
                'venue' => ['place' => 'Sala Mieli', 'city' => 'Parma', 'postal' => '43121', 'address' => 'Borgo degli Studi 1', 'lat' => 44.8007, 'lng' => 10.3333], 'online' => null,
                'with_slam' => false, 'slam' => [], 'gigs' => [],
            ],
            [
                'slug' => 'slam-virtuale-mattina', 'title' => 'Slam in mattina (online)', 'category' => 'poetry-slam',
                'description' => 'Per fusi scomodi: inizio puntuale, pausa 5 minuti, regolamento in allegato. Accesso: una sola email per stanza, se possibile.',
                'in_person' => false, 'starts' => $d(15, 7, 0), 'ends' => $d(15, 8, 15),
                'venue' => null, 'online' => ['url' => 'https://meet.example.org/slam-mattina', 'tz' => 'Europe/Rome'],
                'with_slam' => true, 'slam' => ['judging' => 'jury_five', 'slots' => 10], 'gigs' => [],
            ],
            [
                'slug' => 'spoke-word-aurora-urbana-2', 'title' => 'Spoken word: linea notturna', 'category' => 'spoken-word',
                'description' => 'Dopo l’ascolto, niente flash in sala; foto solo con consenso di tuttə. Volume contenuto a fine serata.',
                'in_person' => true, 'starts' => $d(19, 22, 30), 'ends' => $d(19, 23, 50),
                'venue' => ['place' => 'Base Milano', 'city' => 'Milano', 'postal' => '20144', 'address' => 'Via Bergognone 26', 'lat' => 45.4567, 'lng' => 9.1650], 'online' => null,
                'with_slam' => false, 'slam' => [],
                'gigs' => [
                    ['type' => GigType::PoetArtist, 'desc' => 'Chiusura serata, quattro testi, setup microfono a filo.', 'qty' => 1, 'hospitality' => false, 'price' => 130.0],
                ],
            ],
            [
                'slug' => 'presentazione-novella-meriggio', 'title' => 'Presentazione: raccolta «Meriggio»', 'category' => 'presentazione-libro',
                'description' => 'Lettura, domande a turno, aria ricambiata, corridoi con spazio per uscire con calma se serve.',
                'in_person' => true, 'starts' => $d(4, 18, 0), 'ends' => $d(4, 19, 0),
                'venue' => ['place' => 'Feltrinelli Red', 'city' => 'Catania', 'postal' => '95121', 'address' => 'Via Etnea 200', 'lat' => 37.5123, 'lng' => 15.0905], 'online' => null,
                'with_slam' => false, 'slam' => [], 'gigs' => [],
            ],
            [
                'slug' => 'residenza-breve-poesia', 'title' => 'Micro-residenza a palazzo', 'category' => 'residenza',
                'description' => 'Due giorni e mezzo, spazi in silenzio, telefoni in aereo salvo necessità, concordate in anticipo con lo staff.',
                'in_person' => true, 'starts' => $d(55, 9, 0), 'ends' => $d(57, 18, 0),
                'venue' => ['place' => 'Palazzo Sersale', 'city' => 'Lecce', 'postal' => '73100', 'address' => 'Via degli Ammirati 1', 'lat' => 40.3508, 'lng' => 18.1703], 'online' => null,
                'with_slam' => false, 'slam' => [],
                'gigs' => [
                    ['type' => GigType::PoetArtist, 'desc' => 'Tutor: mattino di lettura e commento, senza valutare “in voto”.', 'qty' => 1, 'hospitality' => true, 'price' => 500.0],
                ],
            ],
            [
                'slug' => 'reading-arcobaleno-tardo', 'title' => 'Reading «Oltre l’arcobaleno» (sera)', 'category' => 'reading',
                'description' => 'Testi sull’identità, un codice d’ingaggio letto a voce bassa a inizio serata, spazio rispettoso per tuttə.',
                'in_person' => true, 'starts' => $d(7, 20, 0), 'ends' => $d(7, 21, 15),
                'venue' => ['place' => 'Casa del Popolo', 'city' => 'Rimini', 'postal' => '47921', 'address' => 'Viale Tiberio 1', 'lat' => 44.0606, 'lng' => 12.5555], 'online' => null,
                'with_slam' => false, 'slam' => [], 'gigs' => [],
            ],
            [
                'slug' => 'festival-una-notte-poesia', 'title' => 'Una notte (quasi) di poesia', 'category' => 'festival',
                'description' => 'Programma scaglionato, ultimo slot con orario d’arrivo, pause e acqua a disposizione, nessuna gara a chi resta in piedi.',
                'in_person' => true, 'starts' => $d(42, 18, 0), 'ends' => $d(43, 1, 0),
                'venue' => ['place' => 'ExFadda', 'city' => 'Sesto San Giovanni', 'postal' => '20099', 'address' => 'Via Mazzacurati 3', 'lat' => 45.5306, 'lng' => 9.2404], 'online' => null,
                'with_slam' => false, 'slam' => [],
                'gigs' => [
                    ['type' => GigType::PoetArtist, 'desc' => 'Chiusura notturna, set brevissimo (12’).', 'qty' => 1, 'hospitality' => false, 'price' => 100.0],
                ],
            ],
        ];
    }

    /**
     * @param  Collection<string, EventCategory>  $bySlug
     */
    private function upsert(array $tpl, $bySlug, User $organizer, int $index): void
    {
        $cat = $bySlug->get($tpl['category'])?->id;
        if ($cat === null) {
            $this->command?->warn("EventSeeder: categoria ‘{$tpl['category']}’ assente, salto {$tpl['slug']}.");

            return;
        }

        $starts = $tpl['starts'];
        $ends = $tpl['ends'];
        if ($ends->lessThan($starts)) {
            $ends = $starts->copy()->addHours(2);
        }

        $publishedAt = $starts->copy()->subDays(8);

        $inPerson = (bool) $tpl['in_person'];
        if ($inPerson && ($tpl['venue'] === null || $tpl['venue'] === [])) {
            $this->command?->warn("EventSeeder: venue mancante per {$tpl['slug']}.");

            return;
        }
        if (! $inPerson && ($tpl['online'] === null || $tpl['online'] === [])) {
            $this->command?->warn("EventSeeder: online mancante per {$tpl['slug']}.");

            return;
        }

        $event = Event::query()->updateOrCreate(
            ['slug' => $tpl['slug']],
            [
                'user_id' => $organizer->id,
                'event_category_id' => $cat,
                'festival_event_id' => null,
                'title' => $tpl['title'],
                'subtitle' => $inPerson
                    ? 'Incontro curato, con tempi rispettati e spazi per l’ascolto.'
                    : 'Sessione online: dettagli d’accesso in anticipo, con pausa in programma.',
                'description' => $tpl['description'],
                'requirements' => 'Per esigenze di accessibilità, scrivere con almeno 5 giorni di preavviso. Ambiente rispettoso, linguaggio non discriminatorio richiesto.',
                'schedule_mode' => EventScheduleMode::Fixed,
                'delivery_mode' => $inPerson ? EventDeliveryMode::InPerson : EventDeliveryMode::Online,
                'cover_image_path' => null,
                'has_cost' => false,
                'price' => null,
                'currency' => null,
                'promo_video_url' => null,
                'registration_deadline' => $starts->copy()->subDays(2),
                'public_invite_enabled' => $inPerson,
                'audience_capacity_enabled' => false,
                'audience_capacity' => null,
                'language' => 'it',
                'status' => EventStatus::Published,
                'is_public' => true,
                'published_at' => $publishedAt,
            ],
        );

        $event->occurrences()->delete();
        $event->occurrences()->create([
            'starts_at' => $starts,
            'ends_at' => $ends,
            'timezone' => $inPerson ? 'Europe/Rome' : $tpl['online']['tz'],
            'is_primary' => true,
        ]);

        $event->venueDetail?->delete();
        $event->onlineDetail?->delete();

        if ($inPerson) {
            $v = $tpl['venue'];
            $event->venueDetail()->create([
                'place_name' => $v['place'],
                'country_code' => 'IT',
                'city' => $v['city'],
                'postal_code' => $v['postal'] ?? null,
                'address_line' => $v['address'],
                'latitude' => $v['lat'] ?? null,
                'longitude' => $v['lng'] ?? null,
            ]);
        } else {
            $event->onlineDetail()->create([
                'meeting_url' => $tpl['online']['url'],
                'timezone' => $tpl['online']['tz'],
            ]);
        }

        $event->gigs()->get()->each->forceDelete();

        $deadline = $starts->copy()->subDay();
        foreach ($tpl['gigs'] as $g) {
            $gig = $event->gigs()->create([
                'type' => $g['type'],
                'quantity' => (int) $g['qty'],
                'filled_quantity' => 0,
                'language' => 'it',
                'hospitality_required' => (bool) $g['hospitality'],
                'description' => (string) $g['desc'],
                'proposed_price' => (float) $g['price'] > 0 ? (float) $g['price'] : null,
                'currency' => (float) $g['price'] > 0 ? 'EUR' : null,
                'deadline' => $deadline,
                'special_requirements' => null,
                'status' => GigStatus::Open,
                'opened_at' => now(),
                'filled_at' => null,
                'closed_at' => null,
                'created_by_user_id' => $organizer->id,
                'updated_by_user_id' => $organizer->id,
            ]);
            if ((float) $g['price'] <= 0) {
                $gig->update(['proposed_price' => null, 'currency' => null]);
            }
        }

        if ($tpl['with_slam']) {
            $slam = PoetrySlam::query()->updateOrCreate(
                ['event_id' => $event->id],
                [
                    'status' => PoetrySlamStatus::Open,
                    'rules_summary' => 'Iscrizioni: anticipo 48h se possibile. Tre minuti a performance, punteggio: si scartano min e max, media sui restanti. Rispetto tra il palco e la sala, sempre.',
                    'slots_limit' => (int) ($tpl['slam']['slots'] ?? 20),
                    'judging_mode' => (string) ($tpl['slam']['judging'] ?? 'jury_five'),
                    'settings' => [
                        'time_limit_minutes' => 3,
                        'break_minutes' => 5,
                    ],
                ],
            );
            if ($index % 3 === 0) {
                PoetrySlamEntry::query()
                    ->where('poetry_slam_id', $slam->id)
                    ->where('user_id', $organizer->id)
                    ->delete();
                PoetrySlamEntry::query()->create([
                    'poetry_slam_id' => $slam->id,
                    'entry_source' => PoetrySlamEntrySource::SiteUser,
                    'event_participant_invite_id' => null,
                    'user_id' => $organizer->id,
                    'display_name' => $organizer->profile?->display_name
                        ?? $organizer->profile?->nickname
                        ?? 'Partecipante',
                    'email' => $organizer->email,
                    'notes' => 'Iscrizione seeder: solo ambiente di sviluppo.',
                    'status' => PoetrySlamEntryStatus::Registered,
                    'performance_order' => 1,
                ]);
            }
        } else {
            $ps = $event->poetrySlam;
            if ($ps !== null) {
                $ps->entries()->delete();
                $ps->rounds()->get()->each->delete();
                $ps->delete();
            }
        }
    }
}
