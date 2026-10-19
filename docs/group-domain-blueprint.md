# Group Domain Blueprint - Slamin

## Obiettivo

Un gruppo è una **pagina** della piattaforma, non un club chiuso e non una tabella di supporto agli eventi.

Deve reggere:
- ruoli di amministrazione
- follower
- affiliazione tra gruppi (padre / figlio)
- bacheca mista + calendario
- moderazione delle affiliazioni
- chat opzionali per ruolo
- categorie gestite dal pannello admin del sito

Il dominio segue le regole già usate nel backend:
- organizzazione per dominio
- controller sottili
- logica in `Action`
- nessuna stringa utente hardcoded
- `ApiResponse`, `FormRequest`, `DTO`
- policy, audit, notifiche, traduzioni

## Stato attuale

Il modello **pagina** è operativo. Sintesi in [gruppi.md](gruppi.md).

- pagina gruppo: follow, teaser privato, feed, calendario, pannello staff
- ruoli `admin`, `editor`, `follower` (staff = follow)
- visibilità pubblico / privato + listed
- categorie dal pannello admin del sito
- `group_content_links` per eventi, poesie e articoli (pending / accepted / rejected / removed)
- gerarchia padre / figlio con inoltro eventi accepted
- chat opzionali per audience (`admin`, `editor`, `affiliates`, `public`)
- gruppi listed nel catalogo di ricerca, solo per utenti loggati
- `event_group` eliminata, senza backfill
- test `GroupApiTest`, `GroupWorkflowApiTest`, copertura chat e catalogo

## Due modelli: cosa resta e cosa cambia

Il codice attuale e la descrizione prodotto non coincidono. Da qui in avanti vince il modello pagina. Il backend club è la base da evolvere, non da buttare.

| Tema | Oggi (codice) | Target (prodotto) | Decisione |
|---|---|---|---|
| Natura | Circolo con membri | Pagina con staff + follower | Cambia il significato di “membro” |
| Ruoli | `owner`, `admin`, `moderator`, `member` | `admin`, `editor`, `follower`, gruppo affiliato | Rimappare, non aggiungere in parallelo |
| Visibilità | `public_open`, `private_approval` | sezione solo loggati; poi pubblico / privato + listed | Sostituire i due enum attuali |
| Ingresso | join = diventare `member` | follow, oppure richiesta/invito a editor/admin | Due flussi distinti |
| Bacheca | solo `group_announcements` | feed misto + calendario fisso sotto | Estendere, non solo annunci |
| Eventi | attach immediato | affiliazione da accettare/rifiutare/togliere | Serve stato sul link |
| Chat | una, sempre creata | quattro opzionali, per ruolo | Spezzare e rendere opt-in |
| Categorie | nessuna | CRUD nel pannello admin del sito | Nuovo sottodominio |
| Gerarchia | nessuna | padre / figlio con inoltro eventi | Nuovo sottodominio |

Cose **stabili**, da non rivedere:

- niente `owner_user_id` su `groups`: i poteri stanno nella membership
- tabella membership dedicata, non un pivot minimale
- ruoli di gruppo separati dai ruoli staff di piattaforma
- un gruppo ha sempre almeno un `admin` attivo
- più admin e più editor sono ammessi
- soft delete sul gruppo
- link social e sito opzionali
- pubblico/privato modificabile nel tempo dagli admin
- Event resta many-to-many, ma il link guadagna uno stato di affiliazione
- validazione dei `group_id` usati da Event passa dal dominio `Group`

## Modello prodotto

### Ruoli

I ruoli sono interni al gruppo.

- `admin`: pubblica e toglie post dalla bacheca; aggiunge e toglie persone; accetta o rifiuta eventi affiliati; gestisce settings, visibilità, chat e affiliazioni tra gruppi. Non può cancellare un contenuto dal sito: toglie solo l'affiliazione alla pagina.
- `editor`: pubblica ogni tipo di post; accetta o rifiuta proposte della community e degli altri editor; non modera i post degli admin; non gestisce ruoli admin.
- `follower`: segue aggiornamenti, legge la bacheca pubblica, entra nella chat pubblica se esiste. Non pubblica da staff.
- gruppo affiliato (figlio): un altro `Group`. Inoltra in automatico eventi (e quindi ingaggi) accettati verso il padre.

Il creatore del gruppo nasce come primo `admin`. Non serve più il ruolo `owner`: il vincolo “almeno un admin attivo” sostituisce “almeno un owner”.

Mapping dal codice attuale, quando si migrerà:

- `owner` → `admin`
- `admin` → `admin`
- `moderator` → `editor`
- `member` → `follower`

### Visibilità

Due assi, non un solo enum:

1. `visibility`: `public` | `private`
2. `is_listed`: se il gruppo appare in ricerca e in elenco

La **sezione Gruppi del sito** (`/groups`) è visibile solo alle persone loggate, come gli ingaggi. Header, hero e colore restano quelli della pagina (`PageHero` + `--color-groups: #F27483`) e non si cambiano.

Tra gli utenti autenticati:

- **pubblico**: indicizzato, si può seguire subito, si può chiedere di diventare editor o admin
- **privato + listed**: compare in ricerca; cliccando si legge solo che il gruppo è privato e serve una richiesta; si può chiedere di seguirlo e di diventare editor o admin
- **privato + unlisted**: non compare in ricerca; solo gli admin invitano e assegnano ruoli
- gli admin possono cambiare pubblico ↔ privato e listed ↔ unlisted nel tempo

`is_listed` sui gruppi pubblici è sempre `true`.

### Categorie

Un gruppo appartiene a una categoria del sito, gestita dal pannello **admin piattaforma** (non dal pannello del gruppo).

Valori iniziali previsti:
- progetto artistico
- associazione / organizzazione
- luogo
- altro

Stesso schema delle altre categorie (slug + traduzioni).

### Chat opzionali

In creazione, e poi nel pannello del gruppo, gli admin scelgono quali chat creare. Non si crea più una chat unica per tutti i membri.

| Chat | Membri |
|---|---|
| `[nome] admin` | solo admin |
| `[nome] editor` | admin + editor |
| `[nome] affiliati` | admin del gruppo + admin dei gruppi figli |
| `[nome] pubblica` | admin + editor + follower |

Le chat restano thread `group` del dominio `Chat`, collegate al gruppo, ma con uno `audience` (`admin`, `editor`, `affiliates`, `public`). L'adesione segue il ruolo, non una lista manuale.

Le chat **libere** (già in `/chat`, senza `group_id`) restano un'altra cosa e non si toccano.

### Pagina gruppo

Layout:

1. header (nome, categoria, visibilità, follow / richiedi ruolo)
2. bacheca a altezza fissa, scroll interno
3. sotto, calendario degli eventi affiliati accettati

Tipi di post in bacheca:
- messaggio / annuncio
- evento
- ingaggio (via evento)
- poesia (proposta, da implementare)
- articolo (proposta, da implementare)

Permessi sul feed:

- admin: pubblica tutto; accetta/rifiuta/toglie qualsiasi affiliazione (anche quella già accettata). Non cancella il contenuto originale dal sito.
- editor: pubblica tutto; accetta/rifiuta proposte community e di altri editor; non toglie i post degli admin
- follower / community: può affiliare i propri eventi a qualsiasi gruppo pubblico o a cui ha accesso; può proporre poesie e articoli
- gruppo figlio: inoltra in automatico ogni evento (e ingaggio) che ha già accettato

### Affiliazione eventi

Chiunque può proporre il proprio evento a uno o più gruppi, in pubblicazione evento, con ricerca gruppi (non un campo di ID).

Flusso:

1. l'evento nasce con link `pending` verso i gruppi scelti
2. admin ed editor del gruppo ricevono notifica
3. nel pannello del gruppo accettano o rifiutano il singolo evento
4. opzione staff: **accetta sempre ogni evento** (`auto_accept_events`)
5. anche dopo l'accettazione l'evento resta listato e si può togliere dalla pagina

Togliere l'affiliazione ≠ cancellare l'evento.

### Gerarchia tra gruppi

Un gruppo può:

- chiedere di diventare figlio di un altro gruppo
- proporre a un altro gruppo di diventare suo figlio

Se il padre accetta, ogni evento accettato dal figlio viene inoltrato in automatico al padre come nuova proposta (`pending` sul padre). Il padre accetta o rifiuta come per qualsiasi evento.

Esempio: evento → Slam Lombardia (accettato) → inoltrato a LIPS → se LIPS accetta, inoltrato a WPSO.

L'inoltro non salta livelli e non pubblica da solo sul padre.

## Schema dati target

### group_categories

- `id`
- `slug` unico
- `is_active`
- `sort_order`
- timestamps
- traduzioni (`name`) come le altre categorie del sito

### groups

Campi da tenere:
- `id`, `name`, `slug`, `description`, `image_path`
- `website_url`, `facebook_url`, `instagram_url`, `youtube_url`
- `is_active`, timestamps, `deleted_at`

Campi da sostituire / aggiungere:
- `visibility`: `public` | `private` (al posto di `public_open` / `private_approval`)
- `is_listed` boolean
- `group_category_id` obbligatorio in creazione
- `auto_accept_events` boolean, default false

### group_members

Si tiene la tabella, si cambiano i ruoli.

- `id`, `group_id`, `user_id`
- `role`: `admin` | `editor` | `follower`
- `status`: `pending` | `active` | `rejected` | `left` | `removed`
- `requested_role` nullable (`admin` | `editor` | `follower`) per le richieste di ingresso/promozione
- `joined_at`, `handled_by_user_id`, `handled_at`, timestamps
- unique `group_id + user_id`

Un follower attivo non è staff. Una richiesta “voglio essere editor” può partire da un utente esterno o da un follower già attivo.

Lo staff implica già il follow: admin ed editor hanno **una sola riga** membership, con il loro ruolo. Non esiste un secondo follow parallelo. Se un admin viene declassato a follower, quella stessa riga diventa `follower`.

### group_affiliations

Nuova tabella padre / figlio.

- `id`
- `parent_group_id`
- `child_group_id`
- `status`: `pending` | `accepted` | `rejected` | `revoked`
- `initiated_by_group_id` (chi ha proposto)
- `handled_by_user_id`, timestamps
- unique `parent_group_id + child_group_id`

### group_content_links

Sostituisce l'uso nudo di `event_group` come “già pubblicato”.

- `id`
- `group_id`
- `content_type`: `announcement` | `event` | `gig` | `poem` | `article`
- `content_id`
- `status`: `pending` | `accepted` | `rejected` | `removed`
- `source`: `direct` | `child_forward` | `staff`
- `proposed_by_user_id`
- `source_group_id` nullable (inoltro dal figlio)
- `handled_by_user_id`, `handled_at`, timestamps
- unique `group_id + content_type + content_id`

`event_group` oggi è solo “questo evento sta in questo gruppo”, senza pending/accepted, e in pratica non è mai stata usata (gruppi non in produzione, tabella vuota). Si elimina senza migrare i dati. Unica fonte di verità: `group_content_links`.

### group_announcements / post nativi

I messaggi nativi della bacheca restano un contenuto del gruppo (`group_announcements` o rename a `group_posts`). Entrano nel feed come `content_type = announcement`. Non sono eventi né poesie.

### chat_threads collegati al gruppo

Oggi: un thread per `group_id`.

Target: fino a quattro thread per gruppo, con `audience` distinto. `group_id` da solo non basta: serve `group_id + audience` unique.

## Enum target

### GroupVisibility
- `public`
- `private`

### GroupMemberRole
- `admin`
- `editor`
- `follower`

### GroupMemberStatus
- invariati: `pending`, `active`, `rejected`, `left`, `removed`

### GroupAffiliationStatus
- `pending`, `accepted`, `rejected`, `revoked`

### GroupContentLinkStatus
- `pending`, `accepted`, `rejected`, `removed`

### GroupChatAudience
- `admin`, `editor`, `affiliates`, `public`

## Regole di business

### Staff

- un gruppo ha sempre almeno un `admin` attivo
- un admin può promuovere follower/editor ad admin
- un admin può declassare o rimuovere altri admin solo se ne resta almeno uno
- un editor non crea, non rimuove e non declassa admin
- un editor gestisce follower e, nei limiti del feed, altri editor

### Join / follow

**Gruppo pubblico**
- follow immediato → membership `follower` + `active`
- richiesta ruolo `editor` o `admin` → `pending` con `requested_role`

**Privato listed**
- richiesta follow, editor o admin → `pending`
- finché non è accettato, la pagina mostra solo il messaggio di gruppo privato

**Privato unlisted**
- niente richieste pubbliche
- solo invito admin, con ruolo già scelto

### Leave / remove

- follower, editor e admin possono lasciare
- un admin lascia solo se resta un altro admin attivo
- lo staff rimuove secondo i vincoli di ruolo
- rimuovere una persona non cancella i suoi contenuti dal sito; toglie al massimo le affiliazioni che lo staff decide di togliere

### Feed e calendario

- in bacheca appaiono solo link `accepted` (e i post nativi pubblicati dallo staff)
- chi ottiene l'accesso (follow accettato su privato listed, oppure staff) vede tutta la bacheca storica, non solo i post successivi all'ingresso
- il calendario mostra solo eventi `accepted`
- `auto_accept_events` accetta in automatico le nuove proposte evento, che restano comunque listate e rimovibili

### Inoltro padre / figlio

- solo eventi `accepted` sul figlio vengono proposti al padre
- sul padre nascono come `pending` e `source = child_forward`
- se il padre ha `auto_accept_events`, l'evento del figlio viene accettato e, se il padre è a sua volta figlio, l'inoltro continua

## API target

Si tengono i CRUD gruppo, si rinomina il significato di join, si aggiungono feed, affiliazioni e categorie.

### Pubblico
- `GET /groups`
- `GET /groups/{slug}`
- `GET /groups/{group}/feed`
- `GET /groups/{group}/events` (solo accepted, + calendario)

### Autenticato
- `GET /my/groups`
- `POST /groups`
- `PATCH /groups/{group}`
- `DELETE /groups/{group}`

### Membership
- `GET /groups/{group}/members`
- `GET /groups/{group}/membership`
- `POST /groups/{group}/follow`
- `POST /groups/{group}/role-requests` (`requested_role`)
- `POST /groups/{group}/leave`
- `POST /groups/{group}/invites`
- `GET /groups/{group}/requests`
- `POST /groups/{group}/requests/{member}/approve`
- `POST /groups/{group}/requests/{member}/reject`
- `PATCH /groups/{group}/members/{member}/role`
- `DELETE /groups/{group}/members/{member}`

### Contenuti
- `GET /groups/{group}/announcements` (o `/posts`)
- `POST /groups/{group}/posts`
- `PATCH` / `DELETE` post nativi
- `GET /groups/{group}/content-links`
- `POST /groups/{group}/content-links/{link}/accept`
- `POST /groups/{group}/content-links/{link}/reject`
- `DELETE /groups/{group}/content-links/{link}` (toglie affiliazione)

### Gerarchia
- `GET /groups/{group}/affiliations`
- `POST /groups/{group}/affiliations`
- `POST /groups/{group}/affiliations/{affiliation}/accept`
- `POST /groups/{group}/affiliations/{affiliation}/reject`

### Chat
- `PATCH /groups/{group}/chat-threads` (accende/spegne le audience)
- niente più auto-create obbligatoria in `StoreGroupAction`

### Admin sito
- CRUD `/admin/group-categories`

### Event / Poem / Article
L'associazione parte dai form contenuto (“vuoi proporre a un gruppo?” + ricerca gruppi visibili), validata dal dominio `Group`. Non si espongono ID crudi.

## Policies

### GroupPolicy
- `view` (pubblico: tutti; privato: staff/follower attivi, oppure teaser listed)
- `update`, `delete`
- `follow`, `requestRole`, `invite`
- `viewMembers`, `manageMembers`, `approveRequests`, `manageRoles`
- `publishPosts`, `moderateFeed`
- `manageAffiliations`
- `manageChatThreads`

### GroupAnnouncementPolicy / GroupPostPolicy
- `update`, `delete` (solo contenuto nativo del gruppo)

## Notifiche

Tipi minimi:
- `group_follow_requested`
- `group_role_requested`
- `group_join_approved`
- `group_join_rejected`
- `group_member_role_updated`
- `group_member_removed`
- `group_content_proposed`
- `group_content_accepted`
- `group_content_rejected`
- `group_announcement_published`
- `group_affiliation_requested`
- `group_affiliation_accepted`

Destinatari:
- admin/editor per richieste e proposte contenuto
- richiedente per esito
- follower/staff per nuovi post accettati, secondo visibilità della chat/bacheca

## Audit

Oltre agli eventi già usati, aggiungere:
- `group.visibility_updated`
- `group.follow_requested`
- `group.role_requested`
- `group.invited`
- `group_content.proposed`
- `group_content.accepted`
- `group_content.rejected`
- `group_content.removed`
- `group_affiliation.requested`
- `group_affiliation.accepted`
- `group_affiliation.rejected`
- `group_chat.enabled`
- `group_chat.disabled`

## Traduzioni

Tenere `lang/it/group.php` e `lang/en/group.php`.

Sezioni:
- `errors`, `validation`, `notifications`
- `roles` (`admin`, `editor`, `follower`)
- `statuses`, `visibility`
- `ui` (pagina, pannello, feed, calendario, richieste)

## Struttura codice

Invariata:
- `app/Domain/Group/Actions|DTOs|Enums|Models|Policies`
- `app/Http/Controllers|Requests|Resources/Api/V1/Group`

Da aggiungere:
- `GroupCategory` + traduzioni
- `GroupAffiliation`
- `GroupContentLink`
- audience sulle chat di gruppo in `Domain/Chat`

## Ordine di implementazione

Ordine pensato per non costruire UI sul modello club e non spezzare Event/Chat due volte.

1. **Allineare il modello dati al prodotto**
   - categorie gruppo (admin sito)
   - visibility `public`/`private` + `is_listed`
   - ruoli `admin`/`editor`/`follower` + migrazione dai ruoli attuali
   - `auto_accept_events`
   - `requested_role`
2. **Follow, inviti, richieste di ruolo**
   - split follow vs staff
   - policy e notifiche nuove
   - spegnere l'ingresso “diventi member” dei gruppi pubblici
3. **UI minima della pagina gruppo**
   - lista / scheda / follow
   - teaser per privato listed
   - i miei gruppi in dashboard (togliere il placeholder `0`)
4. **Pannello staff del gruppo**
   - membri, richieste, ruoli, settings visibilità
5. **Feed nativo + calendario vuoto**
   - post messaggio/annuncio
   - layout altezza fissa + calendario sotto
6. **Affiliazione eventi**
   - `group_content_links`
   - drop di `event_group` (vuota, niente backfill)
   - ricerca gruppi nel form evento
   - accept / reject / rimuovi / accetta sempre
   - il calendario si riempie
7. **Gerarchia gruppi**
   - richieste padre/figlio
   - inoltro eventi accepted
8. **Proposte poesia e articolo**
   - stesso campo “vuoi proporre a un gruppo?”
9. **Chat opzionali per audience**
   - togliere l'auto-create unica
   - quattro thread, sync membri per ruolo
10. **Ricerca catalogo + test feature + docs stato progetto**

Le chat libere in `/chat` restano fuori da questa sequenza: sono già vive e non dipendono dal dominio Group.

## Criterio di chiusura

Il dominio Group non è chiuso quando “si può creare un gruppo via API”.

È chiuso quando:
- una pagina gruppo è usabile (follow, feed, calendario)
- admin ed editor moderano affiliazioni senza cancellare contenuti dal sito
- un gruppo figlio inoltra eventi al padre
- le chat di ruolo sono opt-in
- le categorie si gestiscono dal pannello admin del sito
- la community propone eventi (e poi poesie/articoli) con ricerca gruppi

## Decisioni confermate

1. Gli admin stanno in **tutte** le chat del gruppo (admin, editor, affiliati, pubblica). La chat editor è admin + editor.
2. Follow accettato su privato listed: si vede **tutta** la bacheca storica.
3. Categoria **obbligatoria** alla creazione del gruppo.
4. Una sola tabella per le affiliazioni contenuto: `group_content_links`. `event_group` si **elimina**, senza migrare i dati: i gruppi non erano in uso e quella tabella è vuota.
5. Lo staff **implica già il follow**: una sola membership, ruolo `admin` o `editor`. Niente seconda riga follower.

### Nota sulla decisione 4

Oggi un evento “sta in un gruppo” con `event_group`: due colonne, niente da accettare o rifiutare. Nel prodotto nuovo serve anche pending / accepted / rejected / removed, e la stessa cosa varrà per poesie e articoli.

Si crea `group_content_links` e si droppa `event_group`. Niente copia dei vecchi link.
