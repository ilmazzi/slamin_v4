# Media Domain Blueprint - Slamin

## Obiettivo

Questo documento fissa il disegno del dominio `Media` come sottodominio operativo per foto e video caricati dagli utenti `poet_organizer`.

Il dominio deve coprire:

- upload foto locali
- upload video con infrastruttura PeerTube
- ownership reale nel DB Slàmin
- limiti upload configurabili da admin
- estensioni premium sui limiti
- interazioni social sui media
- `snap` sulla timeline dei video

Il dominio deve seguire le regole architetturali già usate nel backend:

- organizzazione per dominio
- controller sottili
- logica in `Action`
- `FormRequest`
- `DTO`
- `ApiResponse`
- audit e notifiche

## Decisioni di dominio stabili

### 1. Ownership nel DB Slàmin

Decisione stabile:

- il media appartiene all'utente nel DB Slàmin
- PeerTube non è la source of truth dell'ownership

Questo implica:

- `user_id` sempre salvato nel record locale
- policy e visibilità decise da Slàmin
- PeerTube usato come infrastruttura video, non come dominio utente

### 2. Strategia PeerTube single-account

Decisione stabile:

- i video vengono caricati tramite un solo account tecnico applicativo
- opzionalmente un solo canale o un piccolo set di canali applicativi
- niente account PeerTube per ogni `poet_organizer`

Vantaggi:

- provisioning molto più semplice
- delete e moderation centralizzate
- retry e sync stati più affidabili
- nessun accoppiamento forte tra utente Slàmin e utente PeerTube

### 3. Configurazione PeerTube mai hardcoded

Decisione stabile:

- URL, credenziali, canale di default e policy upload devono essere gestiti da admin
- niente valori PeerTube fissati nel codice applicativo

Questo implica:

- service `PeerTubeService` alimentato da settings persistite
- test di connessione lato admin
- rifiuto chiaro dell'upload video se PeerTube non è configurato

### 4. Foto e video nello stesso dominio, storage diverso

Decisione stabile:

- le foto restano locali
- i video stanno su PeerTube
- il dominio pubblico resta unico: `Media`

### 5. Interazioni social anche sui video

Decisione stabile:

- i media supportano `like`
- i media supportano `comment`
- i video supportano anche `snap`

### 6. `Snap` come dominio dedicato

Decisione stabile:

- lo `snap` non è un semplice commento
- è un marker temporale sulla timeline del video con testo e tipo

Serve per:

- segnalare un momento bello
- segnalare un momento critico
- mostrare marker sulla timeline del player

## Stato target del dominio

### Fase 1

- upload foto locale
- upload video verso PeerTube
- record locale `MediaItem`
- settings PeerTube admin
- limiti upload admin
- likes e comments sui media

### Fase 2

- `snap` timeline video
- sync più ricco stato PeerTube
- premium entitlements per limiti maggiorati

## Schema dati proposto

### media_items

- `id`
- `user_id`
- `type` (`photo`, `video`)
- `title`
- `description`
- `visibility`
- `status`
- `mime_type`
- `file_size`
- `uploaded_at`
- `published_at` nullable
- `created_at`
- `updated_at`
- `deleted_at`

Campi foto:

- `file_path`
- `file_url`
- `width` nullable
- `height` nullable

Campi video:

- `peertube_video_id` nullable
- `peertube_uuid` nullable
- `peertube_short_uuid` nullable
- `peertube_url` nullable
- `peertube_embed_url` nullable
- `peertube_thumbnail_url` nullable
- `duration_seconds` nullable
- `processing_status` nullable
- `upload_error` nullable

### media_snaps

- `id`
- `media_item_id`
- `user_id`
- `timestamp_seconds`
- `kind`
- `comment`
- `created_at`
- `updated_at`

`kind` suggerito:

- `highlight`
- `warning`

### media_limit_profiles

Possibile opzione per limiti base:

- `id`
- `account_type`
- `photo_limit`
- `video_limit`
- `photo_max_size`
- `video_max_size`
- `created_at`
- `updated_at`

### user_entitlements

Telaio premium suggerito:

- `id`
- `user_id`
- `feature_code`
- `source`
- `config`
- `starts_at`
- `ends_at`
- `created_at`
- `updated_at`

## Enum suggeriti

### MediaType

- `photo`
- `video`

### MediaVisibility

- `private`
- `unlisted`
- `public`

### MediaStatus

- `draft`
- `processing`
- `ready`
- `failed`
- `archived`

### MediaSnapKind

- `highlight`
- `warning`

## Configurazione admin richiesta

Settings minime PeerTube:

- `peertube_base_url`
- `peertube_admin_username`
- `peertube_admin_password` oppure token equivalente
- `peertube_upload_channel_id`
- `peertube_default_privacy`
- `peertube_default_category`
- `peertube_default_licence`
- `peertube_comments_policy`
- `peertube_download_enabled`

Settings media generali:

- `photo_max_size`
- `video_max_size`
- `photo_allowed_mimes`
- `video_allowed_mimes`
- `default_photo_limit`
- `default_video_limit`

Regole:

- se PeerTube non è configurato, l'upload video non parte
- le impostazioni vengono lette dal DB, non da valori hardcoded
- le credenziali sensibili vanno cifrate se il progetto ha già un pattern compatibile

## Flussi principali

### Upload foto

1. validazione permessi `poet_organizer`
2. verifica limiti utente
3. salvataggio file su disk pubblico
4. creazione `MediaItem` di tipo `photo`
5. stato finale `ready`

### Upload video

1. validazione permessi `poet_organizer`
2. verifica limiti utente
3. controllo configurazione PeerTube
4. salvataggio temporaneo locale
5. upload verso PeerTube tramite account tecnico
6. creazione `MediaItem` locale di tipo `video`
7. stato iniziale `processing`
8. job di sync per thumbnail, durata e stato finale
9. cleanup file temporanei

### Sync stato video

Un job dedicato deve poter aggiornare:

- `processing_status`
- `duration_seconds`
- `peertube_thumbnail_url`
- `peertube_url`
- `peertube_embed_url`
- `status` finale `ready` o `failed`

## Interazioni

### Like

Scelta consigliata:

- riusare il dominio `Engagement` già esistente rendendo `MediaItem` likeable/bookmarkable

### Comment

Scelta consigliata:

- estendere il dominio `Comment` per supportare `MediaItem`

### Snap

API dedicate solo per video:

- `GET /media/{media}/snaps`
- `POST /media/{media}/snaps`
- `PATCH /media/{media}/snaps/{snap}`
- `DELETE /media/{media}/snaps/{snap}`

Regole:

- consentiti solo su `video`
- timestamp compreso nella durata del video
- testo breve ma obbligatorio
- ordinamento per `timestamp_seconds`

## Limiti upload

### Regole base

- i limiti devono essere gestiti da admin
- il backend calcola il limite effettivo utente

Formula suggerita:

`effective_limit = base_limit + bonus_entitlements + admin_override`

### Esempio

- base: 3 video
- entitlement premium: `+7`
- limite effettivo: 10 video

## Premium features / checkout

Non serve implementare subito il pagamento completo, ma il dominio va pensato per supportarlo.

### Feature pack / entitlement

Esempi:

- `video_upload_bonus_7`
- `photo_upload_bonus_50`
- `premium_profile_badge`
- `featured_media_slot`

### Struttura suggerita

`feature_packs`

- `code`
- `name`
- `type`
- `config`

`checkout_orders`

- `user_id`
- `status`
- `provider`
- `amount`
- `currency`
- `payload`

`user_entitlements`

- `user_id`
- `feature_code`
- `source`
- `config`
- `starts_at`
- `ends_at`

## API candidate MVP

### Media

- `GET /media`
- `GET /my/media`
- `POST /media/photos`
- `POST /media/videos`
- `GET /media/{media}`
- `PATCH /media/{media}`
- `DELETE /media/{media}`

### Video status

- `GET /media/{media}/video-status`
- opzionale admin/job interno: sync PeerTube status

### Snap

- `GET /media/{media}/snaps`
- `POST /media/{media}/snaps`
- `PATCH /media/{media}/snaps/{snap}`
- `DELETE /media/{media}/snaps/{snap}`

### Admin settings

- `GET /admin/settings/media`
- `PATCH /admin/settings/media`
- `POST /admin/settings/media/test-peertube`

### Admin limits / entitlements

- `GET /admin/media/limits`
- `PATCH /admin/media/limits`
- `PATCH /admin/users/{user}/media-limits`
- `POST /admin/users/{user}/entitlements`
- `DELETE /admin/users/{user}/entitlements/{entitlement}`

## Azioni da prevedere

### Media core

- `StorePhotoMediaAction`
- `StoreVideoMediaAction`
- `ShowMediaAction`
- `ListMediaAction`
- `ListMyMediaAction`
- `UpdateMediaAction`
- `DeleteMediaAction`

### PeerTube

- `UploadVideoToPeerTubeAction`
- `SyncPeerTubeVideoStatusAction`
- `DeletePeerTubeVideoAction`
- `TestPeerTubeConnectionAction`

### Snap

- `ListMediaSnapsAction`
- `StoreMediaSnapAction`
- `UpdateMediaSnapAction`
- `DeleteMediaSnapAction`

### Limits / premium

- `ResolveUserMediaLimitsAction`
- `GrantUserEntitlementAction`
- `RevokeUserEntitlementAction`

## Policies

### MediaPolicy

Metodi minimi:

- `view`
- `createPhoto`
- `createVideo`
- `update`
- `delete`

### MediaSnapPolicy

Metodi minimi:

- `view`
- `create`
- `update`
- `delete`

## Audit

Eventi rilevanti:

- `media.photo_uploaded`
- `media.video_uploaded`
- `media.video_processing_started`
- `media.video_ready`
- `media.video_failed`
- `media.updated`
- `media.deleted`
- `media.snap_created`
- `media.snap_updated`
- `media.snap_deleted`
- `media.settings_updated`
- `media.entitlement_granted`
- `media.entitlement_revoked`

## Notifiche

Minimo utile:

- video pronto
- video fallito
- nuovo commento su media
- nuovo snap sul proprio video

## Prossimi step consigliati

1. blueprint e decisioni stabili
2. `MediaItem` base con foto locali
3. integrazione video PeerTube single-account
4. admin settings PeerTube
5. limiti upload admin
6. likes e comments su media
7. `snap` timeline video
8. entitlements premium
