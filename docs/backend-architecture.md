# Backend Architecture - Slamin

## Obiettivo

Questo file raccoglie le decisioni architetturali stabili del backend Laravel API-first di Slamin, cosi' da poter riprendere il lavoro senza perdere il disegno complessivo.

## Stack

- Backend: Laravel
- Auth API: Laravel Sanctum
- Frontend previsto: React separato
- Database: PostgreSQL
- Approccio: API-first, stateless
- Localizzazione: supportata lato backend

## Principi architetturali

### 1. Organizzazione per dominio

Il progetto e' organizzato per dominio e non solo per tipo tecnico.

Esempi:
- `Domain/Auth`
- `Domain/User`
- `Domain/Profile`
- `Domain/Poetry`
- `Domain/Article`
- `Domain/Event`
- `Domain/Comment`
- `Domain/Report`
- `Domain/Notification`
- `Domain/Moderation`
- `Domain/Audit`

### 2. Controller sottili

I controller devono:
- ricevere la request
- leggere i dati validati
- costruire DTO oppure usare `DTO::fromRequest(...)`
- chiamare una Action
- restituire `ApiResponse`

La business logic deve vivere nel dominio.

### 3. Pattern standard endpoint

Flusso corretto:
1. Route
2. Middleware
3. Request
4. Controller
5. DTO
6. Action
7. Model
8. ApiResponse

Formula mentale da mantenere:
- input: `Request`
- dati puliti: `DTO`
- logica: `Action`
- persistenza: `Model`
- output: `ApiResponse`

### 4. Nessuna stringa utente hardcoded

Tutte le stringhe utente devono passare da file lingua.

Usare:

```php
__('auth.invalid_credentials')
```

### 5. Risposte API standardizzate

Success:

```json
{
  "data": {},
  "meta": [],
  "errors": null
}
```

Errore:

```json
{
  "data": null,
  "meta": [],
  "errors": {
    "message": "...",
    "fields": {}
  }
}
```

## Struttura cartelle

### Domain

Dentro `app/Domain/{Modulo}` si usano come base:
- `Actions`
- `DTOs`
- `Models`
- `Policies`
- `Queries`
- `Services`

Cartelle aggiuntive quando servono:
- `Enums`
- `Events`
- `Exceptions`
- `Jobs`
- `Support`

### HTTP

Dentro `app/Http`:
- `Controllers/Api/V1/...`
- `Requests/Api/V1/...`
- `Resources/Api/V1/...`
- `Middleware/...`

### Support

Classi trasversali applicative:
- `app/Support/Responses/ApiResponse.php`
- `app/helpers.php`

## Namespace e autoload

`composer.json` usa PSR-4 per `Domain\\`.

```json
"autoload": {
  "files": [
    "app/helpers.php"
  ],
  "psr-4": {
    "App\\": "app/",
    "Domain\\": "app/Domain/"
  }
}
```

## Permessi e identita'

### Account type

Identita' funzionali attuali:
- `audience`
- `poet_organizer`

Regola:
- `audience` vive la parte social/pubblica
- `poet_organizer` puo' creare contenuti core

### Ruoli staff

Privilegi separati:
- `moderator`
- `admin`

Regola:
- `moderator` e' concettualmente un `poet_organizer` con privilegi extra
- `admin` puo' governare l'intera piattaforma

Decisione stabile:
- `account_type` vive in `users`
- i ruoli staff vivono in `roles` + `role_user`

## Middleware applicativi

Attivi:
- `auth:sanctum`
- `account.verified`
- `account.type`
- `role`
- locale via `SetLocale`

Ordine priorita' lingua:
1. `X-Locale`
2. `preferred_locale` profilo utente autenticato
3. `Accept-Language`
4. fallback `it`

## Domini condivisi stabili

### Audit

`Domain/Audit` centralizza audit applicativi.

Helper globale:

```php
audit()->log(...)
```

Regole:
- audit solo per mutazioni rilevanti
- niente dati sensibili
- niente audit per semplici GET/list

### Notification

Dominio notifiche condiviso per:
- moderazione
- commenti
- reports
- eventi / inviti

API utente standard:
- list
- show
- unread count
- mark read
- delete

### Moderation

Dominio moderazione condiviso per:
- poems
- articles
- events
- reports

Pattern comune:
- dashboard
- list
- show
- history
- approve / reject oppure update stato

### Comment

Dominio shared e polimorfico.

Contenuti attuali:
- `Poem`
- `Article`

Regole:
- un solo livello di reply
- niente moderazione interna
- ownership stretta su update/delete

### Report

Dominio shared e polimorfico.

Contenuti attuali:
- `Poem`
- `Article`
- `Comment`

La moderazione dei report e' separata dal dominio `Comment`.

### Engagement

Dominio polimorfico per:
- `likes`
- `bookmarks`

## Publication Flow condiviso

Publication flow centralizzato in `content_publication_settings`.

Content type attivi:
- `poem`
- `article`
- `event`

Flow:
- `auto_publish`
- `manual_review`

Pattern adottato:
- il client puo' chiedere `published`
- il backend decide se pubblicare o mettere in `pending_review`

## Tassonomie e traduzioni

Scelta attuale:
- traduzioni di dominio statiche nei file `lang`
- tassonomie dinamiche tradotte su DB con tabelle `_translations`

Tassonomie gia' attive:
- `poem_categories`
- `poem_types`
- `article_categories`
- `event_categories`

Pattern stabile:
- tabella principale con `slug`, `is_active`, `sort_order`
- tabella translation con `locale` + `name`
- CRUD admin
- endpoint pubblici read-only

## Stato attuale dei domini principali

### Poetry

Gia' attivo con:
- CRUD autore
- list/show pubblici
- my poems
- tassonomie
- publication flow
- moderazione
- notifiche
- audit
- like/bookmark
- commenti
- report

### Article

Allineato a Poetry per:
- CRUD
- categorie tradotte
- publication flow
- moderazione
- notifiche
- audit
- engagement
- commenti
- report

### Event

Dominio piu' complesso, con core gia' avviato.

Pattern stabile:
- `schedule_mode`: `fixed`, `recurring`, `availability`
- `delivery_mode`: `in_person`, `online`
- multi-group via pivot
- festival unico opzionale come self-reference
- recent places separati
- inviti participant e audience distinti

Stato attuale:
- schema core presente
- CRUD autore/pubblico
- categorie pubbliche e admin
- recent places
- publication flow
- moderazione
- inviti e notifiche base

## Event: decisioni di dominio gia' fissate

- usare solo `is_public`
- un evento puo' appartenere a piu' gruppi
- un evento puo' appartenere a un solo festival
- availability poll resta storico
- quando si decide la data finale si crea una `event_occurrence` definitiva
- invitees esterni nel primo blocco: `name`, `email`, `role`, `status`
- `Poetry Slam` va trattato come sottodominio separato agganciato agli eventi
- `Gigs` va trattato come sottodominio separato, alimentato dal dominio Event

## Regole pratiche da mantenere

1. Ogni endpoint nuovo va progettato per dominio
2. Niente business logic nei controller
3. Niente stringhe hardcoded
4. Niente JSON costruiti a mano: usare `ApiResponse`
5. Niente validazione nel controller: usare `FormRequest`
6. Se i campi non sono banali, creare DTO
7. Le Action rappresentano casi d'uso
8. I Services vanno usati solo per logica riusabile trasversale
9. I Model non devono diventare controller nascosti
10. Permessi:
    - middleware per blocchi generali
    - policy per ownership e controlli fini
11. `account_type` e ruoli staff non vanno mescolati
12. Audit si', ma solo su eventi rilevanti

## Prossimi passi consigliati

### 1. Completare Event

Blocchi naturali ancora aperti:
- availability votes
- finalizzazione data availability
- inviti esterni via email reali
- integrazione reale con `Group`
- regole festival piu' forti

### 2. Sottodomini Event

Da fare separatamente:
- `Poetry Slam`
- `Gigs`

### 3. Ricerca trasversale

La `Search` va affrontata dopo che i domini core saranno piu' stabili.

### 4. Hardening

- piu' test feature
- review coerenza permessi e audit
- consolidamento pannelli admin/moderation

## Nota finale

Il backend oggi non e' piu' in fase "solo auth + poetry".
La piattaforma ha gia' un nucleo multi-dominio reale, con componenti shared forti che vanno preservate:
- publication flow
- moderation
- notifications
- comments
- reports
- engagement

Il punto piu' importante da non perdere resta questo:
**il lavoro vero vive nel dominio, non nel controller.**
