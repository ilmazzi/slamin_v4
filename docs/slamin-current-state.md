# Slamin - Stato Attuale Backend

## Architettura

- Backend Laravel API-only
- Struttura per dominio sotto `app/Domain`
- API versionata sotto `/api/v1`
- Database PostgreSQL
- Auth con Sanctum
- Risposte standard tramite `ApiResponse`

Domini attivi principali:
- `Auth`
- `User`
- `Profile`
- `Audit`
- `Poetry`
- `Article`
- `Event`
- `Comment`
- `Report`
- `Notification`
- `Moderation`
- `Engagement`

## Autenticazione e permessi

- `auth:sanctum`
- `account.verified`
- `account.type`
- `role`

Modello permessi attuale:
- `audience`: pubblico
- `poet_organizer`: creator / organizer
- ruoli staff separati: `moderator`, `admin`

## Audit Log

L'audit e' persistito su DB in `audit_logs`.

Copertura attuale principale:
- register
- login
- reset password
- verify email
- profile update
- poem create/update/delete
- article create/update/delete
- event create/update/delete
- like / unlike
- bookmark / unbookmark
- moderation approve / reject
- CRUD admin tassonomie
- update publication settings
- mutazioni notifiche
- create / update report
- create / update / delete comment
- risposte agli inviti evento

## Publication Flow

Il publication flow e' admin-managed tramite `content_publication_settings`.

Content type attivi:
- `poem`
- `article`
- `event`

Flow supportati:
- `auto_publish`
- `manual_review`

Stati contenuto usati nei domini editoriali:
- `draft`
- `pending_review`
- `published`
- `rejected`

## Domain: Poetry

Implementato:
- create / update / delete
- list pubblico
- show pubblico
- list my poems
- publication flow
- moderazione
- notifiche approvazione / rifiuto
- audit
- like / bookmark
- commenti
- report

Tassonomie:
- `poem_categories`
- `poem_types`
- traduzioni su DB
- CRUD admin
- endpoint pubblici

`PoemResource` include gia':
- category
- type
- likes_count
- bookmarks_count
- is_liked
- is_bookmarked

## Domain: Article

Implementato:
- create / update / delete
- list pubblico
- show pubblico
- list my articles
- publication flow
- moderazione
- notifiche approvazione / rifiuto
- audit
- like / bookmark
- commenti
- report

Tassonomie:
- `article_categories`
- traduzioni su DB
- CRUD admin
- endpoint pubblici

## Domain: Event

Il dominio `Event` e' il piu' ricco e il core backend e' gia' partito.

Schema core gia' introdotto:
- `events`
- `event_categories`
- `event_category_translations`
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

Funzionalita' attive:
- create / update / delete
- list pubblico
- show pubblico
- list my events
- recent places
- categorie pubbliche
- categorie admin-managed
- publication flow
- moderazione
- inviti participant
- inviti audience
- notifiche invito
- risposta agli inviti
- audit

Modalita' supportate:
- schedule mode: `fixed`, `recurring`, `availability`
- delivery mode: `in_person`, `online`

Scelte di dominio gia' fissate:
- un evento puo' appartenere a piu' gruppi
- un evento puo' appartenere a un solo festival
- il poll availability resta storico e genera una `event_occurrence` definitiva quando si decide la data finale
- `recent_event_places` e' gestito come sottodominio separato user-scoped

## Domain: Comment

Dominio shared e polimorfico.

Contenuti supportati:
- `Poem`
- `Article`

Funzionalita' attive:
- list commenti per contenuto
- create commento
- update proprio commento
- delete proprio commento
- reply a un solo livello
- notifiche
- audit

Non e' presente moderazione interna dei commenti.
Le segnalazioni passano dal dominio `Report`.

## Domain: Report

Dominio shared e polimorfico.

Contenuti supportati:
- `Poem`
- `Article`
- `Comment`

Funzionalita' attive:
- creazione report utente
- anti-duplicato su report aperti / in review
- impossibilita' di segnalare il proprio contenuto
- pannello moderazione report
- aggiornamento stato report
- notifiche a staff su creazione
- notifiche al reporter su `resolved` / `dismissed`
- audit

## Domain: Notification

API utente attive:
- lista notifiche
- dettaglio notifica
- unread count
- mark one as read
- mark all as read
- delete one
- delete all

Le notifiche vengono gia' alimentate da:
- moderation poetry
- moderation article
- moderation event
- reports
- comments
- event invites

## Domain: Moderation

Moderazione attiva per:
- poems
- articles
- events
- reports

Il pannello backend di moderazione ha gia':
- dashboard con stats
- liste filtrabili
- show contenuto
- approve / reject
- history

## Domain: Engagement

Struttura polimorfica attiva:
- `likes`
- `bookmarks`

Riutilizzata gia' su:
- poesie
- articoli
- eventi compatibili a model level

## Traduzioni

Scelta attuale:
- traduzioni statiche UI/API nei file `lang`
- traduzioni di tassonomie su DB tramite tabelle `_translations`

Questo vale gia' per:
- poem categories
- poem types
- article categories
- event categories

## Repository

Repo Git locale configurato:
- branch attivo: `main`
- remote `origin`: `https://github.com/ilmazzi/slamin_v4.git`

## Prossimi passi consigliati

### 1. Chiudere meglio il dominio Events

Blocchi ancora naturali:
- inviti esterni via email reali
- vote / raccolta risposte per `event_availability_polls`
- finalizzazione data availability
- integrazione reale con `Group`
- link festival con regole di dominio piu' forti

### 2. Sottodomini Events

Da aprire separatamente:
- `Poetry Slam`
  - turni
  - giudici
  - punteggi
  - classifiche
- `Gigs`
  - ruoli / ingaggi
  - pipeline di richiesta / conferma

### 3. Domini principali ancora mancanti o da consolidare

- `Group`
- eventuale `Festival` come specializzazione di Event
- Search trasversale solo dopo che i domini core saranno piu' stabili

### 4. Hardening

- piu' test feature end-to-end
- review coerenza audit sugli ultimi flussi Event
- review policy / ownership / staff access

## Nota pratica

Lo stato attuale non e' piu' "solo Poetry".
Il backend oggi ha gia' una base multi-dominio concreta, con publication flow, moderazione, notifiche, commenti e report condivisi tra piu' contenuti.
