# Gigs Domain Blueprint - Slamin

## Obiettivo

Questo documento fissa il disegno del dominio `Gig` come sottodominio operativo della piattaforma.

`Gig` non deve essere trattato come semplice tabella accessoria di `Event` o `Poem`.
Nasce sempre da un parent, ma deve supportare un workflow reale:

- richiesta di una o piu posizioni
- candidatura da parte di utenti autenticati
- gestione organizer delle candidature
- annunci community multipli nel tempo
- chiusura del gig quando le posizioni risultano coperte e confermate
- specializzazione lato traduzione quando il parent e `Poem`

Il dominio deve seguire le regole architetturali gia presenti nel backend:

- organizzazione per dominio
- controller sottili
- logica in `Action`
- `FormRequest`
- `DTO`
- `ApiResponse`
- traduzioni dedicate
- audit e notifiche

## Stato implementazione attuale

Gia operativo nel backend:

- `Gig` polimorfico usato su `Event`
- `Gig` polimorfico usato su `Poem` per richieste traduzione
- nested management da `Event`
- nested management da `Poem` per `translation-gigs`
- discovery `GET /gigs/open`
- dettaglio `GET /gigs/{gig}`
- `GigApplication`
- `withdraw`
- aggiornamento candidature organizer
- passaggio automatico a `filled`
- chiusura organizer via `POST /gigs/{gig}/close`
- `GigAnnouncement` multipli nel tempo
- `GigNegotiation`
- `GigNegotiationEvent`
- `TranslationJob`
- `TranslationComment`

Non ancora implementato:

- target/community delivery avanzata per annunci
- reopen
- policy dedicate separate dal controllo diretto sul parent
- refinement del workspace traduzione e locator commenti

Blueprint dedicato ramo traduzione:

- [poem-translation-gig-blueprint.md](C:/Users/dmazz/Herd/slamin_v4/docs/poem-translation-gig-blueprint.md)

## Decisioni di dominio stabili

### 1. Gig sempre collegato a un parent

Decisione stabile:

- un `Gig` non nasce mai da solo
- un `Gig` appartiene sempre a un contenitore padre

Parent supportati:

- `Event`
- `Poem`

Questo implica:

- nessun endpoint top-level per creare un gig scollegato
- la creazione avviene sempre passando dal parent

### 2. Relazione polimorfica

Scelta stabile:

- usare relazione polimorfica `giggable`

Campi:

- `giggable_type`
- `giggable_id`

Questo permette di avere un solo dominio `Gig` riusabile sia per:

- posizioni professionali collegate a un evento
- richieste di traduzione o altre collaborazioni collegate a una poesia

### 3. Gig come bisogno strutturato

Il `Gig` rappresenta il bisogno stabile dell'organizer, non la singola candidatura.

Esempi:

- cerco 2 poeti/artisti per un evento
- cerco 1 MC/Host
- cerco 1 supporto tecnico
- cerco 1 traduttore per una poesia

La candidatura dell'utente va modellata separatamente.

### 4. Candidature separate dal gig

Scelta stabile:

- usare entita figlia `GigApplication`

Questo separa:

- il bisogno pubblicato
- le persone che si candidano
- le decisioni operative dell'organizer

### 5. Annunci community separati dal gig

Scelta stabile:

- non usare un solo campo `community_message` dentro `Gig`
- usare entita figlia `GigAnnouncement`

Motivo:

- un organizer puo voler pubblicare piu messaggi in momenti diversi
- ogni annuncio puo avere il proprio stato, data di pubblicazione e relativo invio notifiche

### 6. Chiusura del gig guidata dalla copertura delle posizioni

Comportamento desiderato:

- il gig resta aperto finche ci sono posizioni libere
- quando il numero di candidature confermate raggiunge `quantity`, il gig e da considerare coperto
- la chiusura puo avvenire automaticamente o semi-automaticamente dopo conferma organizer

Scelta consigliata:

- `filled`: tutte le posizioni risultano coperte
- `closed`: il gig e chiuso operativamente dall'organizer o da una regola automatica finale

### 7. Poem translation come specializzazione del dominio Gig

Scelta stabile:

- il ramo `Poem` non e un dominio separato dal `Gig`
- usa lo stesso contenitore base con campi e workflow specializzati
- la parte veramente dedicata nasce sopra il gig: trattative, job e commenti

## Schema dati proposto

### gigs

Campi base:

- `id`
- `giggable_type`
- `giggable_id`
- `type`
- `quantity`
- `filled_quantity`
- `language`
- `hospitality_required`
- `description`
- `status`
- `opened_at`
- `filled_at`
- `closed_at`
- `created_by_user_id`
- `updated_by_user_id`
- `created_at`
- `updated_at`
- `deleted_at`

Campi aggiuntivi lato traduzione:

- `proposed_price`
- `currency`
- `deadline`
- `special_requirements`

### gig_applications

Campi:

- `id`
- `gig_id`
- `user_id`
- `message`
- `status`
- `reviewed_by_user_id`
- `reviewed_at`
- `confirmed_at`
- `rejected_at`
- `withdrawn_at`
- `created_at`
- `updated_at`

Vincoli:

- unique su `gig_id + user_id`

### gig_announcements

Campi:

- `id`
- `gig_id`
- `author_user_id`
- `message`
- `status`
- `published_at`
- `created_at`
- `updated_at`
- `deleted_at`

### gig_negotiations

Campi:

- `id`
- `gig_id`
- `gig_application_id`
- `owner_user_id`
- `translator_user_id`
- `status`
- `agreed_price`
- `currency`
- `agreed_deadline`
- `closed_at`
- `created_at`
- `updated_at`

### gig_negotiation_events

Campi:

- `id`
- `gig_negotiation_id`
- `author_user_id`
- `event_type`
- `price`
- `currency`
- `deadline`
- `note`
- `created_at`

### translation_jobs

Campi:

- `id`
- `gig_id`
- `gig_negotiation_id`
- `poem_id`
- `translator_user_id`
- `owner_user_id`
- `source_language`
- `target_language`
- `agreed_price`
- `currency`
- `deadline`
- `translated_content`
- `status`
- `submitted_at`
- `approved_at`
- `created_at`
- `updated_at`

### translation_comments

Campi:

- `id`
- `translation_job_id`
- `author_user_id`
- `comment_target`
- `target_locator`
- `content`
- `status`
- `resolved_at`
- `created_at`
- `updated_at`

## Enum

### Domain/Gig/Enums/GigType

Valori minimi per eventi:

- `poet_artist`
- `mc_host`
- `technical_support`
- `volunteer`

Valori minimi per poesie:

- `translator`

### Domain/Gig/Enums/GigStatus

Valori:

- `draft`
- `open`
- `filled`
- `closed`
- `cancelled`

### Domain/Gig/Enums/GigApplicationStatus

Valori:

- `pending`
- `accepted`
- `rejected`
- `withdrawn`
- `confirmed`

### Domain/Gig/Enums/GigAnnouncementStatus

Valori:

- `draft`
- `published`
- `archived`

## Regole di business

### Parent ownership

Regole stabili:

- solo chi puo gestire il parent puo creare, aggiornare o chiudere i gig del parent
- per `Event` si riusa la logica organizer del dominio evento
- per `Poem` si riusa la logica di ownership o gestione editoriale della poesia

### Creazione gig da Event

Comportamento atteso:

- nel form evento l'organizer puo aggiungere uno o piu gig
- i gig vengono creati prendendo le informazioni compilate nell'evento
- si possono creare quanti gig servono sullo stesso evento

### Creazione gig da Poem

Comportamento atteso:

- una poesia puo aprire richieste di traduzione o collaborazione compatibili
- il primo caso d'uso attivo e la traduzione

Stato attuale:

- implementato in una prima versione operativa
- trattato come ramo specializzato con blueprint dedicato

### Visibilita gigs

Regole:

- i gig `open` devono essere visibili agli utenti autenticati interessati a candidarsi
- i gig `draft` non sono visibili pubblicamente
- i gig `closed` o `cancelled` non devono accettare nuove candidature

### Candidature

Regole:

- solo utenti autenticati possono candidarsi
- una sola candidatura per utente per gig
- l'utente puo aggiungere un messaggio
- l'utente puo ritirare la candidatura finche il gig non e chiuso

### Gestione organizer delle candidature

Regole:

- l'organizer puo vedere tutte le candidature del gig
- puo accettare o rifiutare
- puo confermare i candidati scelti fino al raggiungimento di `quantity`
- ogni conferma incrementa `filled_quantity`

### Chiusura automatica e semi-automatica

Regole:

- quando `filled_quantity >= quantity`, il gig passa a `filled`
- l'organizer puo poi chiuderlo esplicitamente con passaggio a `closed`

### Annunci community

Regole:

- un gig puo avere piu annunci
- ogni annuncio puo essere pubblicato in tempi diversi
- gli annunci devono essere visibili nel dettaglio del gig

### Trattative e translation workflow

Regole:

- la candidatura del traduttore non equivale alla trattativa
- possono esistere piu trattative in parallelo
- ogni trattativa ha una cronologia eventi
- quando una trattativa va in `agreed`, le altre vanno chiuse
- dall'accordo nasce automaticamente un `TranslationJob`
- il lavoro si chiude con approvazione finale del proprietario

## API

### Parent-managed gig endpoints

Event:

- `GET /events/{event}/gigs`
- `POST /events/{event}/gigs`
- `PATCH /events/{event}/gigs/{gig}`
- `DELETE /events/{event}/gigs/{gig}`

Poem:

- `GET /poems/{poem}/translation-gigs`
- `POST /poems/{poem}/translation-gigs`
- `PATCH /poems/{poem}/translation-gigs/{gig}`
- `DELETE /poems/{poem}/translation-gigs/{gig}`

Nota:

- non esiste `POST /gigs` come creazione indipendente

### Discovery per autenticati

- `GET /gigs/open`
- `GET /gigs/{gig}`

### Applications

- `GET /gigs/{gig}/applications`
- `POST /gigs/{gig}/applications`
- `PATCH /gigs/{gig}/applications/{application}`
- `POST /gigs/{gig}/applications/{application}/withdraw`

### Announcements

- `GET /gigs/{gig}/announcements`
- `POST /gigs/{gig}/announcements`
- `PATCH /gigs/{gig}/announcements/{announcement}`
- `DELETE /gigs/{gig}/announcements/{announcement}`

### Status / close

- `POST /gigs/{gig}/close`
- opzionale dopo: `POST /gigs/{gig}/reopen`

### Negotiations

- `GET /gigs/{gig}/negotiations`
- `POST /gigs/{gig}/applications/{application}/negotiations`
- `GET /gig-negotiations/{negotiation}`
- `POST /gig-negotiations/{negotiation}/events`
- `POST /gig-negotiations/{negotiation}/agree`

### Translation job

- `GET /translation-jobs/{job}`
- `PATCH /translation-jobs/{job}`
- `POST /translation-jobs/{job}/submit`
- `POST /translation-jobs/{job}/request-changes`
- `POST /translation-jobs/{job}/approve`

### Translation comments

- `GET /translation-jobs/{job}/comments`
- `POST /translation-jobs/{job}/comments`
- `PATCH /translation-jobs/{job}/comments/{comment}`
- `POST /translation-jobs/{job}/comments/{comment}/resolve`

## Actions da prevedere

### Gig core

- `ListEventGigsAction`
- `StoreEventGigAction`
- `UpdateEventGigAction`
- `DeleteEventGigAction`
- `ListPoemTranslationGigsAction`
- `StorePoemTranslationGigAction`
- `UpdatePoemTranslationGigAction`
- `DeletePoemTranslationGigAction`
- `ListOpenGigsAction`
- `ShowGigAction`
- `CloseGigAction`
- `RefreshGigStatusAction`

### Applications

- `ListGigApplicationsAction`
- `StoreGigApplicationAction`
- `UpdateGigApplicationStatusAction`
- `WithdrawGigApplicationAction`

### Announcements

- `ListGigAnnouncementsAction`
- `StoreGigAnnouncementAction`
- `UpdateGigAnnouncementAction`
- `DeleteGigAnnouncementAction`

### Negotiations e translation work

- `ListGigNegotiationsAction`
- `OpenGigNegotiationAction`
- `ShowGigNegotiationAction`
- `StoreGigNegotiationEventAction`
- `AgreeGigNegotiationAction`
- `ShowTranslationJobAction`
- `UpdateTranslationJobContentAction`
- `SubmitTranslationJobAction`
- `RequestTranslationChangesAction`
- `ApproveTranslationJobAction`
- `ListTranslationCommentsAction`
- `StoreTranslationCommentAction`
- `UpdateTranslationCommentAction`
- `ResolveTranslationCommentAction`

## Policies da introdurre o consolidare

### GigPolicy

Metodi minimi:

- `view`
- `apply`
- `manage`
- `close`
- `viewApplications`
- `manageApplications`
- `publishAnnouncements`

### GigApplicationPolicy

Metodi minimi:

- `withdraw`
- `update`

### GigAnnouncementPolicy

Metodi minimi:

- `update`
- `delete`
- `publish`

## Notifiche

Tipi notifiche minimi:

- `gig.created`
- `gig.application_received`
- `gig.application_accepted`
- `gig.application_rejected`
- `gig.application_confirmed`
- `gig.application_withdrawn`
- `gig.announcement_published`
- `gig.filled`
- `gig.closed`
- `gig.negotiation_opened`
- `gig.negotiation_updated`
- `gig.negotiation_agreed`
- `translation.submitted`
- `translation.changes_requested`
- `translation.approved`
- `translation.comment_created`

## Audit

Audit rilevanti:

- `gig.created`
- `gig.updated`
- `gig.deleted`
- `gig.closed`
- `gig.filled`
- `gig.application_created`
- `gig.application_updated`
- `gig.application_withdrawn`
- `gig.application_confirmed`
- `gig.announcement_created`
- `gig.announcement_updated`
- `gig.announcement_deleted`
- `gig.negotiation_opened`
- `gig.negotiation_updated`
- `gig.negotiation_agreed`
- `translation.job_created`
- `translation.job_updated`
- `translation.job_submitted`
- `translation.job_changes_requested`
- `translation.job_approved`
- `translation.comment_created`
- `translation.comment_updated`
- `translation.comment_resolved`

## Traduzioni

File richiesti:

- `lang/it/gig.php`
- `lang/en/gig.php`

## Struttura codice prevista

- `app/Domain/Gig/Actions`
- `app/Domain/Gig/DTOs`
- `app/Domain/Gig/Enums`
- `app/Domain/Gig/Models`
- `app/Policies/GigPolicy.php`
- `app/Policies/GigApplicationPolicy.php`
- `app/Policies/GigAnnouncementPolicy.php`

- `app/Http/Controllers/Api/V1/Gig`
- `app/Http/Requests/Api/V1/Gig`

## Ordine di implementazione consigliato

1. migrazione `gigs`
2. enum e model `Gig`
3. integrazione nested con `Event`
4. integrazione nested con `Poem`
5. discovery `GET /gigs/open`
6. migrazione `gig_applications`
7. candidatura utenti autenticati
8. gestione organizer delle candidature
9. chiusura e refresh automatico dello stato
10. migrazione `gig_announcements`
11. annunci multipli e notifiche
12. migrazione `gig_negotiations`
13. `TranslationJob` e commenti
14. audit completo
15. test feature completi
16. aggiornamento docs stato progetto

## MVP consigliato

Per chiudere un primo blocco utile:

- `Gig` polimorfico
- nested management da `Event`
- nested management da `Poem`
- `GET /gigs/open`
- `GigApplication`
- confirm / reject / withdraw
- stato `filled` automatico
- chiusura organizer
- trattativa e `TranslationJob` nel ramo `Poem`

Aggiornamento:

- il MVP `Event -> Gig` e gia stato superato
- il ramo `Poem -> translation gig` e gia operativo in una prima versione
- i prossimi step riguardano hardening, policy dedicate e refinement del workflow traduzione

## Nota finale

`Gig` non e un dominio CRUD top-level puro.

La sua natura corretta in Slamin e:

- dominio figlio di `Event` e `Poem`
- discovery trasversale per utenti autenticati
- workflow reale di candidatura e copertura posizioni
- comunicazione community attraverso annunci multipli nel tempo
- specializzazione editoriale-commerciale nel ramo traduzione
