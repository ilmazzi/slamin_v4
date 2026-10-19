Slamin - Stato Attuale Backend

## Architettura

- Backend Laravel API-only
- Struttura per dominio sotto `app/Domain`
- API versionata sotto `/api/v1`
- Database PostgreSQL
- Test su SQLite in-memory
- Auth con Sanctum
- Risposte standard tramite `ApiResponse`

Pattern architetturale:

`Request -> DTO -> Action -> Controller -> ApiResponse`

## Domini attivi principali

- Auth
- User
- Profile
- Audit
- Poetry
- Article
- Event
- PoetrySlam
- Gig
- Chat
- Media
- Group
- Comment
- Report
- Notification
- Moderation
- Engagement

## Autenticazione e permessi

Middleware attivi:

- `auth:sanctum`
- `account.verified`
- `account.type`
- `role`

Modello permessi attuale:

- `audience`: pubblico
- `poet_organizer`: creator / organizer
- ruoli staff:
  - `moderator`
  - `admin`

Nota importante:

- molte API `Event`, `PoetrySlam` e `Gig` richiedono `poet_organizer`

## Audit Log

- Persistenza su DB: `audit_logs`
- Copertura ampia gia presente su auth, profilo, contenuti, moderazione, eventi, commenti, report e sottodomini collegati

## Publication Flow

Gestito da:

- `content_publication_settings`

Content type:

- `poem`
- `article`
- `event`

Flow:

- `auto_publish`
- `manual_review`

Stati:

- `draft`
- `pending_review`
- `published`
- `rejected`

## Domain: Poetry

Stato:

- completo

Copertura:

- CRUD
- list pubblico
- show
- my poems
- publication flow
- moderazione
- notifiche
- audit
- like / bookmark
- commenti
- report

Tassonomie:

- `poem_categories`
- `poem_types`
- traduzioni DB
- CRUD admin

## Domain: Article

Stato:

- completo

Copertura:

- CRUD
- list pubblico
- show
- my articles
- publication flow
- moderazione
- notifiche
- audit
- like / bookmark
- commenti
- report

## Domain: Event (CORE)

Stato:

- dominio centrale gia avanzato

Schema principale:

- `events`
- `event_categories`
- `event_occurrences`
- `event_recurrences`
- `event_availability_polls`
- `event_availability_options`
- `event_venue_details`
- `event_online_details`
- `event_group`
- `event_participant_invites`
- `event_audience_invites`
- `recent_event_places`

Funzionalita:

- CRUD completo
- list pubblico
- show
- my events
- categorie pubbliche/admin
- publication flow
- moderazione
- inviti `participant`, `audience`, esterni via email
- availability: vote / unvote / finalizzazione
- notifiche
- audit

Modalita:

- `schedule`: `fixed`, `recurring`, `availability`
- `delivery`: `in_person`, `online`

## Domain: PoetrySlam

Stato:

- backend funzionale completo per il sottodominio base
- pronto per uso API organizer
- chiuso il primo pacchetto core: slam, entries, rounds, scores, advancement framework, leaderboard

Documento complementare:

- [poetry-slam-known-gaps.md](C:/Users/dmazz/Herd/slamin_v4/docs/poetry-slam-known-gaps.md)

Concetto:

`Event (category = poetry slam) -> PoetrySlam -> Entries / Rounds / Scores / Leaderboard`

Nota di dominio:

- non e un tipo autonomo di `Event`
- e un sottodominio specializzato collegato a un `Event` in categoria poetry slam

### Modelli principali

`PoetrySlam`

- `event_id`
- `status` (`draft`, `open`, `closed`)
- `rules_summary`
- `slots_limit`
- `judging_mode`
- `settings` (`json`)

Relazioni:

- `event()`
- `entries()`
- `rounds()`

`PoetrySlamEntry`

Supporta 3 casi:

- utente interno: `user_id`
- partecipante esterno: `display_name`, `email`
- derivato da evento: `event_participant_invite_id`

Campi chiave:

- `entry_source`
- `status`
- `notes`
- `performance_order`

Valori `entry_source` attivi:

- `event_participant`
- `site_user`
- `external`

`PoetrySlamRound`

Campi chiave:

- `round_number`
- `name`
- `status`
- `judges_count`
- `scoring_type`
- `advancement_mode`
- `advancement_value`
- `advancement_based_on`
- `started_at`
- `completed_at`

`PoetrySlamScore`

Campi chiave:

- `poetry_slam_round_id`
- `poetry_slam_entry_id`
- `judge_number`
- `score`

`PoetrySlamRoundAdvancementDecision`

Campi chiave:

- `poetry_slam_round_id`
- `poetry_slam_entry_id`
- `decision_status`

### Entries

Sync automatico partecipanti:

- alla creazione dello slam gli `event_participant_invites` accettati vengono importati in `poetry_slam_entries`
- implementato con `SyncPoetrySlamEntriesFromEventAction`

Comportamento:

- i partecipanti gia iscritti all'evento entrano automaticamente nello slam
- possono esistere partecipanti manuali non iscritti all'evento
- il sistema e ibrido: automatico + manuale

API disponibili:

- `GET /events/{event}/poetry-slam`
- `POST /events/{event}/poetry-slam`
- `GET /events/{event}/poetry-slam/entries`
- `POST /events/{event}/poetry-slam/entries`
- `PATCH /events/{event}/poetry-slam/entries/{entry}`
- `DELETE /events/{event}/poetry-slam/entries/{entry}`

Regole attive:

- deduplica utenti interni
- deduplica partecipanti esterni
- rispetto di `slots_limit`
- `performance_order` univoco nello slam
- entry derivate da `event_participant` non eliminabili manualmente

### Rounds

API disponibili:

- `GET /events/{event}/poetry-slam/rounds`
- `POST /events/{event}/poetry-slam/rounds`
- `PATCH /events/{event}/poetry-slam/rounds/{round}`
- `DELETE /events/{event}/poetry-slam/rounds/{round}`

Regole attive:

- `round_number` univoco nello slam
- un solo round `active` per slam
- default `judges_count = 5`
- default `scoring_type = trimmed_sum`

Modalita punteggio:

- `trimmed_sum`
- `sum`
- `average`
- `best`

Regola speciale:

- `trimmed_sum` con meno di 3 giudici usa fallback automatico a `sum`
- `effective_scoring_type` e esposto dal round per mostrare la modalita realmente applicata

### Scores

Modello giudici:

- i giudici non sono utenti
- non esiste un'entita persistente `Judge`
- i voti sono anonimi e posizionali tramite `judge_number`

API disponibili:

- `GET /events/{event}/poetry-slam/rounds/{round}/scores`
- `PUT /events/{event}/poetry-slam/rounds/{round}/entries/{entry}/scores`

Calcoli supportati:

- `trimmed_sum`
- `sum`
- `average`
- `best`

Output round result per entry:

- `raw_scores`
- `discarded_scores`
- `counted_scores`
- `result_value`
- `effective_scoring_type`
- `is_complete`

Regole attive:

- niente punteggi su round `draft`
- `judge_number` deve stare tra `1` e `judges_count`
- niente duplicati di `judge_number` nello stesso invio
- entry e round devono appartenere allo stesso slam

### Advancement Framework

Comportamento attuale:

- di default tutti fanno tutti i round
- il backend e pero gia pronto per regole future di avanzamento

Modalita disponibili:

- `all_entries`
- `top_n`
- `top_percentage`
- `manual_selection`

Base di calcolo disponibile:

- `round_result`
- `cumulative_result`

API disponibili:

- `GET /events/{event}/poetry-slam/rounds/{round}/advancement-decisions`
- `PUT /events/{event}/poetry-slam/rounds/{round}/entries/{entry}/advancement-decision`

Nota:

- le decisioni manuali sono abilitate solo se il round usa `manual_selection`

### Leaderboard

API disponibili:

- `GET /events/{event}/poetry-slam/leaderboard`
- `GET /events/{event}/poetry-slam/rounds/{round}/leaderboard`

Comportamento:

- leaderboard di round con ranking per `result_value`
- leaderboard cumulata dello slam con somma dei risultati round
- entry complete sopra entry incomplete
- rank condiviso in caso di parita di punteggio

### Audit e traduzioni

Copertura attiva:

- eventi audit per slam, entry, round, scores, advancement decisions
- traduzioni dedicate in:
  - `lang/en/poetry_slam.php`
  - `lang/it/poetry_slam.php`

### Test PoetrySlam

Stato:

- verdi

Copertura principale:

- create slam
- import automatico partecipanti evento
- reject evento non valido
- reject duplicato slam
- show / not found
- CRUD entries manuali
- update campi operativi entry
- CRUD rounds
- scoring modes e fallback
- scores anonimi per round
- advancement decisions
- leaderboard round
- leaderboard cumulata

## Domain: Gig

Stato:

- backend funzionale sul ramo `Event -> Gig`
- backend attivo anche sul ramo `Poem -> translation gig`
- discovery autenticati attiva
- workflow candidature gia operativo
- annunci multipli e chiusura organizer gia implementati
- workflow traduzione gia attivo: trattative, accordo, job e commenti

Documenti complementari:

- [gigs-domain-blueprint.md](C:/Users/dmazz/Herd/slamin_v4/docs/gigs-domain-blueprint.md)
- [poem-translation-gig-blueprint.md](C:/Users/dmazz/Herd/slamin_v4/docs/poem-translation-gig-blueprint.md)

Concetto:

`Event -> Gig -> Applications / Announcements`

`Poem -> Translation Gig -> Applications / Negotiations / TranslationJob`

Nota di dominio:

- `Gig` e un sottodominio collegato a un parent
- i parent operativi sono `Event` e `Poem`
- il ramo `Poem` usa lo stesso dominio base `Gig`, ma con regole specializzate per la traduzione

### Modelli principali

`Gig`

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
- `proposed_price`
- `currency`
- `deadline`
- `special_requirements`

`GigApplication`

- `gig_id`
- `user_id`
- `message`
- `status`
- `reviewed_by_user_id`
- `reviewed_at`
- `confirmed_at`
- `rejected_at`
- `withdrawn_at`

`GigAnnouncement`

- `gig_id`
- `author_user_id`
- `message`
- `status`
- `published_at`

`GigNegotiation`

- `gig_id`
- `gig_application_id`
- `owner_user_id`
- `translator_user_id`
- `status`
- `agreed_price`
- `currency`
- `agreed_deadline`
- `closed_at`

`GigNegotiationEvent`

- `gig_negotiation_id`
- `author_user_id`
- `event_type`
- `price`
- `currency`
- `deadline`
- `note`

`TranslationJob`

- `gig_id`
- `gig_negotiation_id`
- `poem_id`
- `owner_user_id`
- `translator_user_id`
- `source_language`
- `target_language`
- `agreed_price`
- `currency`
- `deadline`
- `translated_content`
- `status`
- `submitted_at`
- `approved_at`

`TranslationComment`

- `translation_job_id`
- `author_user_id`
- `comment_target`
- `target_locator`
- `content`
- `status`
- `resolved_at`

### Event gigs

API disponibili:

- `GET /events/{event}/gigs`
- `POST /events/{event}/gigs`
- `PATCH /events/{event}/gigs/{gig}`
- `DELETE /events/{event}/gigs/{gig}`

Regole attive:

- organizer-only sul parent `Event`
- gig nested nel contesto evento
- nessuna creazione top-level scollegata

### Poem translation gigs

API disponibili:

- `GET /poems/{poem}/translation-gigs`
- `POST /poems/{poem}/translation-gigs`
- `PATCH /poems/{poem}/translation-gigs/{gig}`
- `DELETE /poems/{poem}/translation-gigs/{gig}`

Campi minimi attivi:

- `language`
- `proposed_price`
- `currency`
- `deadline`
- `special_requirements`

Regole attive:

- solo il proprietario della poesia puo aprire o modificare la richiesta
- una sola richiesta attiva per coppia `poem + language`
- il gig resta nel dominio `Gig`, ma e specializzato come richiesta traduzione

### Discovery e dettaglio

API disponibili:

- `GET /gigs/open`
- `GET /gigs/{gig}`

Comportamento:

- gli utenti autenticati vedono i gig `open`
- il dettaglio gig espone il contesto parent e il conteggio candidature

### Applications

API disponibili:

- `GET /my/gig-applications`
- `POST /gigs/{gig}/applications`
- `GET /gigs/{gig}/applications`
- `PATCH /gigs/{gig}/applications/{application}`
- `POST /gigs/{gig}/applications/{application}/withdraw`

Regole attive:

- una candidatura per utente per gig
- solo utenti autenticati possono candidarsi
- l'organizer del parent puo vedere e aggiornare le candidature
- il candidato puo ritirare la propria candidatura

Stati attivi candidatura:

- `pending`
- `accepted`
- `rejected`
- `withdrawn`
- `confirmed`

Sul ramo traduzione:

- la candidatura e il punto d'ingresso del traduttore
- una candidatura puo essere trasformata in trattativa
- quando una trattativa arriva ad accordo, la candidatura vincente va in `confirmed`
- le candidature concorrenti vengono respinte

### Negotiations

API disponibili:

- `GET /gigs/{gig}/negotiations`
- `POST /gigs/{gig}/applications/{application}/negotiations`
- `GET /gig-negotiations/{negotiation}`
- `POST /gig-negotiations/{negotiation}/events`
- `POST /gig-negotiations/{negotiation}/agree`

Comportamento:

- possono esistere piu trattative in parallelo sullo stesso gig traduzione
- ogni trattativa ha una cronologia di eventi persistita
- eventi supportati: offerte, controfferte, note operative e accordo finale
- quando una trattativa va in `agreed`, le altre vengono chiuse come non selezionate

### Coverage e chiusura

Comportamento:

- quando le candidature `confirmed` coprono `quantity`, il gig passa a `filled`
- `filled_quantity` viene aggiornato automaticamente
- l'organizer puo chiudere il gig operativamente

API disponibile:

- `POST /gigs/{gig}/close`

Regola attiva:

- il gig si puo chiudere solo quando tutte le posizioni sono coperte

### Announcements

API disponibili:

- `GET /gigs/{gig}/announcements`
- `POST /gigs/{gig}/announcements`
- `PATCH /gigs/{gig}/announcements/{announcement}`
- `DELETE /gigs/{gig}/announcements/{announcement}`

Stati attivi annuncio:

- `draft`
- `published`
- `archived`

Comportamento:

- un gig puo avere piu annunci nel tempo
- gli annunci sono separati dal record principale del gig
- la timeline annunci e gia disponibile via API

### Translation Job

API disponibili:

- `GET /translation-jobs/{job}`
- `PATCH /translation-jobs/{job}`
- `POST /translation-jobs/{job}/submit`
- `POST /translation-jobs/{job}/request-changes`
- `POST /translation-jobs/{job}/approve`

Comportamento:

- il `TranslationJob` nasce automaticamente quando una trattativa viene accettata
- il traduttore aggiorna il contenuto tradotto online
- il proprietario puo richiedere modifiche
- il proprietario puo approvare la traduzione finale
- all'approvazione il job passa a `approved` e il gig viene chiuso

### Translation Comments

API disponibili:

- `GET /translation-jobs/{job}/comments`
- `POST /translation-jobs/{job}/comments`
- `PATCH /translation-jobs/{job}/comments/{comment}`
- `POST /translation-jobs/{job}/comments/{comment}/resolve`

Comportamento:

- commenti disponibili per proprietario e traduttore
- supporto a `comment_target` (`source_text`, `translated_text`, `general`)
- `target_locator` disponibile per agganciare il commento a una porzione del testo
- commenti risolvibili nel workflow di revisione

### Audit e traduzioni

Copertura attiva:

- audit per gig, candidature, annunci, trattative, job e commenti traduzione
- traduzioni dedicate in:
  - `lang/en/gig.php`
  - `lang/it/gig.php`

### Test Gig

Stato:

- verdi

Copertura principale:

- CRUD event gigs
- discovery `open gigs`
- candidatura utente autenticato
- deduplica candidature
- lista organizer candidature
- conferma candidatura con passaggio a `filled`
- withdraw candidatura
- chiusura organizer
- create/update/delete annunci
- CRUD poem translation gigs
- negoziazioni parallele e agreement
- creazione automatica `TranslationJob`
- submit / request changes / approve
- commenti e resolve sul job

## Domain: Chat

Stato:

- backend funzionale per direct chat e group chat
- supporta sia free group chat sia chat collegate al dominio `Group`
- notifiche, reply e reaction emoji gia attive

Documento complementare:

- [chat-domain-blueprint.md](C:/Users/dmazz/Herd/slamin_v4/docs/chat-domain-blueprint.md)

Concetto:

`User <-> User -> Direct Chat`

`User -> Free Group Chat -> Members / Messages / Reactions`

`Group -> Attached Group Chat -> Members / Messages / Reactions`

### Modelli principali

`ChatThread`

- `type`
- `direct_key`
- `group_id`
- `audience`
- `is_enabled`
- `title`
- `created_by_user_id`
- `last_message_at`

`ChatThreadMember`

- `chat_thread_id`
- `user_id`
- `joined_at`
- `last_read_at`

`ChatMessage`

- `chat_thread_id`
- `reply_to_message_id`
- `user_id`
- `message_type`
- `content`
- `edited_at`

`ChatMessageReaction`

- `chat_message_id`
- `user_id`
- `emoji`

### Thread e membership

API disponibili:

- `GET /chat/threads`
- `POST /chat/direct-threads`
- `POST /chat/group-threads`
- `GET /chat/threads/{thread}`
- `POST /chat/threads/{thread}/read`
- `PATCH /groups/{group}/chat-threads`

Comportamento:

- direct chat unica per coppia utenti
- free group chat create liberamente nel dominio `Chat`
- attached group chat opt-in per audience (`admin`, `editor`, `affiliates`, `public`)
- nessuna chat unica creata in automatico alla creazione del gruppo

### Messaggi, reply e reaction

API disponibili:

- `GET /chat/threads/{thread}/messages`
- `POST /chat/threads/{thread}/messages`
- `POST /chat/messages/{message}/reactions`
- `DELETE /chat/messages/{message}/reactions`

Comportamento:

- messaggi testuali
- `reply_to_message_id` per risposte nello stesso thread
- reaction emoji aggregate per messaggio
- conteggio unread per thread

### Gestione membri

API disponibili:

- `POST /chat/threads/{thread}/members`
- `DELETE /chat/threads/{thread}/members/{user}`

Regole attive:

- solo il creatore della free group chat puo gestire i membri
- nessuna gestione membri sulle direct chat
- nessuna gestione manuale sui thread attached al dominio `Group`

### Notifiche e audit

Copertura attiva:

- notifiche per nuovi messaggi
- notifiche per reply
- notifiche per reaction
- audit per creazione thread, lettura, invio messaggi, reaction e membership changes

### Test Chat

Stato:

- verdi

Copertura principale:

- direct thread idempotente
- send message + read
- free group chat
- attached group chat
- reply
- reaction emoji
- add/remove membri free group chat
- blocco outsider
- blocco gestione manuale thread attached

## Domain: Media

Stato:

- blueprint definito
- non ancora implementato nel nuovo backend
- perimetro già fissato per foto locali e video PeerTube

Documento complementare:

- [media-domain-blueprint.md](C:/Users/dmazz/Herd/slamin_v4/docs/media-domain-blueprint.md)

Concetto:

`MediaItem (photo|video) -> ownership locale -> interactions -> video snaps`

Decisioni già fissate:

- ownership nel DB Slàmin
- upload video tramite un solo account tecnico PeerTube
- configurazione PeerTube interamente gestita da admin
- niente valori hardcoded PeerTube
- foto locali, video remoti su PeerTube
- `like` e `comment` anche sui video
- `snap` come marker timeline dedicato

Direzioni implementative già confermate:

- limiti upload configurabili da admin
- premium entitlements per aumentare i limiti
- supporto futuro a checkout/feature packs

## Domain: Comment

Stato:

- polimorfico

Supporta:

- `Poem`
- `Article`

Funzioni:

- list
- create
- update
- delete
- reply a 1 livello
- notifiche
- audit

## Domain: Report

Stato:

- polimorfico

Supporta:

- `Poem`
- `Article`
- `Comment`

Funzioni:

- create
- anti-duplicato
- no self-report
- gestione moderazione
- notifiche
- audit

## Domain: Notification

Stato:

- attivo

Funzioni:

- list
- show
- unread count
- mark read
- delete

Fonti:

- moderation
- reports
- comments
- event invites
- gigs
- poetry translation workflow
- chat
- media

## Domain: Moderation

Stato:

- attivo

Copertura:

- poems
- articles
- events
- reports

Include:

- dashboard
- filtri
- approve / reject
- history

## Domain: Engagement

Stato:

- polimorfico

Funzioni:

- likes
- bookmarks

Usato su:

- poems
- articles
- eventi

## Domain: Group

Stato:

- operativo come pagina della community (non più modello club)

Documento complementare:

- [gruppi.md](gruppi.md)
- [group-domain-blueprint.md](group-domain-blueprint.md)

Concetto:

`Group -> staff (admin/editor) + follower`

`Group -> bacheca + calendario`

`Group <-> Group` (affiliazione padre/figlio)

`Group -> chat opzionali per audience`

### Visibilità e ruoli

- sezione `/groups` solo autenticati
- `visibility`: `public` | `private`
- `is_listed`: i pubblici sono sempre listed; i privati unlisted restano fuori da elenco e catalogo
- ruoli: `admin`, `editor`, `follower`
- staff implica follow; vincolo almeno un admin attivo

### Contenuti

- `group_content_links` unica fonte per evento, poesia, articolo
- stati `pending` | `accepted` | `rejected` | `removed`
- proposte dalla community; auto-accept solo per eventi se attivo
- inoltro one-hop degli eventi accepted dal figlio al padre
- feed nativo (`group_announcements`) + calendario accepted

### Chat collegate

- niente auto-create unica
- fino a quattro thread: `admin`, `editor`, `affiliates`, `public`
- `PATCH /groups/{group}/chat-threads`
- membership sincronizzata dal ruolo
- le chat libere in `/chat` restano nel dominio Chat

### Catalogo

- i gruppi listed compaiono in `GET /search` per utenti verificati
- “vedi tutti” va a `/groups?q=`

### Test Group

Stato:

- verdi

Copertura principale:

- CRUD e visibilità
- follow / inviti / ruoli
- feed e proposte evento, poesia, articolo
- gerarchia e inoltro eventi
- chat per audience
- catalogo listed / unlisted

## Traduzioni

- UI/API -> `lang`
- tassonomie -> DB `_translations`

## Repository

- branch: `main`
- repo: `slamin_v4`

## Prossimi Step

1. Event
- rafforzare logiche festival
- pulizia domain rules

2. Hardening
- test end-to-end
- audit consistency
- policy review
- documentazione API di dominio

3. Sottodomini futuri
- Poetry Slam Championship (multi-evento)
- evoluzioni avanzate del ramo traduzione (`TranslationJob` e commenti piu ricchi)
- evoluzioni avanzate del dominio `Chat` (edit/delete, allegati, realtime)
- implementazione dominio `Media`

## Nota finale

Il backend non e piu un prototipo.

Oggi e gia un sistema multi-dominio con:

- publication flow
- moderazione
- notifiche
- audit
- relazioni complesse tra `Event`, `PoetrySlam`, `Gig`, `Poem`, utenti e gruppi

`PoetrySlam`, `Gig`, `Chat`, `Media` e `Group` sono sottodomini operativi. Il gruppo è una pagina (staff, follower, feed, calendario, chat opt-in, catalogo).
