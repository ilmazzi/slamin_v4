# Group Domain Blueprint - Slamin

## Obiettivo

Questo documento fissa il disegno del dominio `Group` come dominio completo del backend Slamin.

`Group` non va trattato come semplice tabella di supporto agli eventi.
Deve reggere un ecosistema interno con:
- membership
- ruoli multipli
- richieste di ingresso
- notifiche
- bacheca annunci
- integrazione reale con `Event`

Tutto il dominio deve seguire le regole architetturali gia' fissate nei docs:
- organizzazione per dominio
- controller sottili
- logica in `Action`
- nessuna stringa utente hardcoded
- `ApiResponse`
- `FormRequest`
- `DTO`

## Decisioni di dominio stabili

### 1. Un gruppo puo' avere piu' owner

Decisione stabile:
- non usare `owner_user_id` sulla tabella `groups`
- la ownership vive dentro la membership

Ruoli supportati:
- `owner`
- `admin`
- `moderator`
- `member`

I ruoli sono interni al gruppo e non vanno mescolati con i ruoli staff globali di piattaforma (`admin`, `moderator` di sistema).

### 2. Membership con stato

La membership va modellata con una tabella dedicata, non con un pivot minimale.

Stati supportati:
- `pending`
- `active`
- `rejected`
- `left`
- `removed`

Questo permette di gestire:
- richieste di ingresso
- approvazioni
- rifiuti
- uscita volontaria
- rimozione da parte dello staff del gruppo

### 3. Visibilita' del gruppo

Visibilita' supportate:
- `public_open`
- `private_approval`

Regole:
- `public_open`: l'utente entra direttamente come `member` attivo
- `private_approval`: l'utente crea una membership `pending`

### 4. Annunci separati dal profilo gruppo

La bacheca annunci e' un sottodominio semplice ma interno a `Group`.

Scelta stabile:
- usare `group_announcements`
- autore interno al gruppo
- gestione separata da descrizione e metadata del gruppo

### 5. Integrazione Event

La relazione `Event <-> Group` resta many-to-many.

Regola:
- un evento puo' appartenere a piu' gruppi
- il dominio `Group` deve diventare la fonte reale di validazione dei `group_id` usati da `Event`

## Schema dati proposto

### groups

Campi base:
- `id`
- `name`
- `slug`
- `description`
- `image_path`
- `visibility`
- `website_url`
- `facebook_url`
- `instagram_url`
- `youtube_url`
- `is_active`
- `created_at`
- `updated_at`
- `deleted_at`

Note:
- `slug` unico
- `visibility` come enum stringa applicativa
- social link opzionali
- soft delete consigliato

### group_members

Campi:
- `id`
- `group_id`
- `user_id`
- `role`
- `status`
- `joined_at`
- `handled_by_user_id`
- `handled_at`
- `created_at`
- `updated_at`

Vincoli:
- unique su `group_id + user_id`
- `handled_by_user_id` nullable

Uso:
- `pending`: richiesta in attesa
- `active`: membro effettivo
- `rejected`: richiesta rifiutata
- `left`: utente uscito dal gruppo
- `removed`: rimosso dallo staff del gruppo

### group_announcements

Campi:
- `id`
- `group_id`
- `author_user_id`
- `title`
- `content`
- `is_pinned`
- `published_at`
- `created_at`
- `updated_at`
- `deleted_at`

## Enum da introdurre

### Domain/Group/Enums/GroupVisibility

Valori:
- `public_open`
- `private_approval`

### Domain/Group/Enums/GroupMemberRole

Valori:
- `owner`
- `admin`
- `moderator`
- `member`

### Domain/Group/Enums/GroupMemberStatus

Valori:
- `pending`
- `active`
- `rejected`
- `left`
- `removed`

## Regole di business

### Ownership

Regole stabili:
- un gruppo deve sempre avere almeno un `owner` attivo
- un `owner` puo' promuovere un altro membro a `owner`
- un `owner` puo' declassare o rimuovere altri owner
- non e' possibile rimuovere o declassare l'ultimo `owner` attivo

### Admin panel del gruppo

`owner` e `admin` hanno accesso al pannello staff del gruppo.

Funzioni minime del pannello:
- vedere membri attivi
- vedere richieste pendenti
- approvare richieste
- rifiutare richieste
- promuovere o declassare ruoli
- rimuovere membri

Regole:
- `owner` puo' gestire tutti i ruoli
- `admin` puo' gestire `moderator` e `member`
- `admin` non puo' creare/rimuovere `owner`
- `moderator` non gestisce ruoli

### Join flow

#### public_open

- se l'utente non e' gia' membro attivo, viene creata/aggiornata una membership `active`
- `joined_at` valorizzato subito

#### private_approval

- viene creata o riaperta una membership `pending`
- owner/admin ricevono notifica

### Leave / remove

- un `member`, `moderator` o `admin` puo' lasciare il gruppo
- un `owner` puo' lasciare solo se resta almeno un altro `owner` attivo
- lo staff puo' rimuovere membri secondo i vincoli di ruolo

### Annunci

Possono pubblicare annunci:
- `owner`
- `admin`
- `moderator`

I `member` leggono soltanto.

## API proposte

### Group public

- `GET /groups`
- `GET /groups/{slug}`

### Group authenticated

- `GET /my/groups`
- `POST /groups`
- `PATCH /groups/{group}`
- `DELETE /groups/{group}`

### Membership

- `GET /groups/{group}/members`
- `GET /groups/{group}/membership`
- `POST /groups/{group}/join`
- `POST /groups/{group}/leave`
- `GET /groups/{group}/requests`
- `POST /groups/{group}/requests/{member}/approve`
- `POST /groups/{group}/requests/{member}/reject`
- `PATCH /groups/{group}/members/{member}/role`
- `DELETE /groups/{group}/members/{member}`

### Announcements

- `GET /groups/{group}/announcements`
- `POST /groups/{group}/announcements`
- `PATCH /groups/{group}/announcements/{announcement}`
- `DELETE /groups/{group}/announcements/{announcement}`

### Event integration

- `GET /groups/{group}/events`

L'associazione evento-gruppo puo' essere gestita dal dominio `Event`, ma la validazione deve passare dal dominio `Group`.

## Actions da prevedere

### Group core

- `StoreGroupAction`
- `UpdateGroupAction`
- `DeleteGroupAction`
- `ShowGroupAction`
- `ListGroupsAction`
- `ListMyGroupsAction`

### Membership

- `ShowMyGroupMembershipAction`
- `RequestToJoinGroupAction`
- `LeaveGroupAction`
- `ListGroupMembersAction`
- `ListPendingGroupRequestsAction`
- `ApproveGroupRequestAction`
- `RejectGroupRequestAction`
- `UpdateGroupMemberRoleAction`
- `RemoveGroupMemberAction`

### Announcements

- `ListGroupAnnouncementsAction`
- `StoreGroupAnnouncementAction`
- `UpdateGroupAnnouncementAction`
- `DeleteGroupAnnouncementAction`

## Policies da introdurre

### GroupPolicy

Metodi minimi:
- `view`
- `update`
- `delete`
- `requestJoin`
- `viewMembers`
- `manageMembers`
- `approveRequests`
- `manageRoles`
- `publishAnnouncements`

### GroupAnnouncementPolicy

Metodi minimi:
- `update`
- `delete`

## Notifiche da introdurre

Usare il dominio shared `Notification`.

Tipi notifiche minimi:
- `group_join_requested`
- `group_join_approved`
- `group_join_rejected`
- `group_member_role_updated`
- `group_member_removed`
- `group_announcement_published`

Destinatari:
- owner/admin per richieste di ingresso
- utente richiedente per approvazione/rifiuto
- utente interessato per ruolo aggiornato o rimozione
- membri attivi per annunci

## Audit

Audit rilevanti:
- `group.created`
- `group.updated`
- `group.deleted`
- `group.join_requested`
- `group.join_approved`
- `group.join_rejected`
- `group.member_role_updated`
- `group.member_removed`
- `group.left`
- `group_announcement.created`
- `group_announcement.updated`
- `group_announcement.deleted`

## Traduzioni

Il dominio `Group` deve nascere gia' tradotto.

File richiesti:
- `lang/it/group.php`
- `lang/en/group.php`

Sezioni minime:
- `errors`
- `validation`
- `notifications`
- `roles`
- `statuses`
- `visibility`

## Struttura codice prevista

- `app/Domain/Group/Actions`
- `app/Domain/Group/DTOs`
- `app/Domain/Group/Enums`
- `app/Domain/Group/Models`
- `app/Domain/Group/Policies`

- `app/Http/Controllers/Api/V1/Group`
- `app/Http/Requests/Api/V1/Group`
- `app/Http/Resources/Api/V1/Group`

## Ordine di implementazione consigliato

1. migrazioni `groups`, `group_members`, `group_announcements`
2. enum e model
3. CRUD `Group`
4. membership + richieste di ingresso
5. policy e pannello staff backend
6. notifiche + audit
7. annunci
8. integrazione reale con `Event`
9. test feature completi
10. aggiornamento docs stato progetto

## Nota finale

La chiusura del dominio `Group` non e' "creare un gruppo".
La chiusura vera e':
- ruoli
- membership
- richieste
- pannello staff
- annunci
- notifiche
- integrazione con `Event`

Il dominio va quindi implementato come modulo principale della piattaforma, non come accessorio.
