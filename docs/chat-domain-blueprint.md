# Chat Domain Blueprint - Slamin

## Obiettivo

Questo documento fissa il perimetro del dominio `Chat` come sottodominio operativo della piattaforma.

La chat in Slamin deve supportare due famiglie di casi:

- conversazioni `direct` tra due utenti
- conversazioni `group`

Le conversazioni `group` oggi esistono in due varianti:

- `group` libera, creata direttamente dentro il dominio `Chat`
- `group` collegata al dominio `Group` della piattaforma

Il dominio deve seguire le stesse regole architetturali gia usate nel backend:

- organizzazione per dominio
- controller sottili
- logica in `Action`
- `FormRequest`
- `DTO`
- `ApiResponse`
- audit e notifiche

## Stato implementazione attuale

Gia operativo nel backend:

- thread `direct` idempotenti tra due utenti
- thread `group` liberi creati nel dominio `Chat`
- thread `group` collegati al dominio `Group`
- auto-creazione del thread chat alla creazione del gruppo
- endpoint esplicito `POST /groups/{group}/chat-thread`
- lista thread e lista messaggi
- invio messaggi testuali
- `mark as read`
- notifiche per nuovi messaggi
- reply ai messaggi
- reaction emoji ai messaggi
- gestione membri delle free group chat da parte del creatore

Non ancora implementato:

- edit messaggi
- delete messaggi
- allegati
- realtime / broadcast
- typing indicators
- permessi granulari aggiuntivi per moderazione chat

## Decisioni di dominio stabili

### 1. Direct thread unico per coppia utenti

Decisione stabile:

- tra due utenti esiste un solo thread `direct`
- la creazione e idempotente

Implementazione:

- chiave logica `direct_key`

### 2. Free group chat distinta dal dominio Group

Decisione stabile:

- un utente puo creare una group chat libera senza dipendere dal dominio `Group`
- il creatore della free group chat gestisce i membri

### 3. Group attached thread

Decisione stabile:

- il dominio `Group` della piattaforma ha il proprio thread chat dedicato
- il thread viene auto-creato alla creazione del gruppo
- esiste anche un endpoint esplicito idempotente per assicurarlo

### 4. Membership gestita in modo diverso per i due casi

Regola stabile:

- free group chat: i membri sono gestiti manualmente dal creatore
- group attached chat: i membri vengono sincronizzati dal lifecycle del dominio `Group`

### 5. Reply e reaction vivono sul messaggio

Decisione stabile:

- il reply usa `reply_to_message_id`
- le reaction emoji sono record separati per `messaggio + utente + emoji`

## Schema dati

### chat_threads

- `id`
- `type` (`direct`, `group`)
- `direct_key` nullable
- `group_id` nullable
- `title` nullable
- `created_by_user_id`
- `last_message_at`
- `created_at`
- `updated_at`

### chat_thread_members

- `id`
- `chat_thread_id`
- `user_id`
- `joined_at`
- `last_read_at`
- `created_at`
- `updated_at`

### chat_messages

- `id`
- `chat_thread_id`
- `reply_to_message_id` nullable
- `user_id`
- `message_type` (`text`)
- `content`
- `edited_at` nullable
- `deleted_at` nullable
- `created_at`
- `updated_at`

### chat_message_reactions

- `id`
- `chat_message_id`
- `user_id`
- `emoji`
- `created_at`
- `updated_at`

## Modelli principali

`ChatThread`

- supporta `direct` e `group`
- puo essere libero o attached al dominio `Group`

`ChatThreadMember`

- membership del thread
- tiene il `last_read_at`

`ChatMessage`

- messaggio testuale
- supporta reply

`ChatMessageReaction`

- reaction emoji per messaggio

## API attive

### Thread

- `GET /chat/threads`
- `POST /chat/direct-threads`
- `POST /chat/group-threads`
- `GET /chat/threads/{thread}`
- `POST /chat/threads/{thread}/read`

### Messaggi

- `GET /chat/threads/{thread}/messages`
- `POST /chat/threads/{thread}/messages`

### Reaction

- `POST /chat/messages/{message}/reactions`
- `DELETE /chat/messages/{message}/reactions`

### Membri free group chat

- `POST /chat/threads/{thread}/members`
- `DELETE /chat/threads/{thread}/members/{user}`

### Group attached thread

- `POST /groups/{group}/chat-thread`

## Regole di business

### Visibilita

- direct: solo i due membri
- free group chat: solo i membri del thread
- group attached chat: solo i membri attivi del gruppo

### Gestione membri

- direct: nessuna gestione membri
- free group chat: solo il creatore puo aggiungere o rimuovere membri
- group attached chat: niente gestione manuale dal dominio `Chat`

### Reply

- il reply deve appartenere allo stesso thread
- il messaggio originale riceve notifica se l'autore e diverso

### Reaction

- una stessa emoji puo essere usata una sola volta per utente sullo stesso messaggio
- la reaction genera notifica solo se l'autore del messaggio e diverso da chi reagisce

### Read state

- il thread salva `last_read_at` per membro
- l'unread count viene calcolato rispetto ai messaggi altrui successivi a `last_read_at`

## Notifiche attive

- `chat_message_received`
- `chat_message_replied`
- `chat_message_reacted`

## Audit attivo

- `chat.direct_thread_created`
- `chat.group_thread_created`
- `chat.group_thread_ensured`
- `chat.message_sent`
- `chat.thread_read`
- `chat.message_reacted`
- `chat.message_reaction_removed`
- `chat.thread_member_added`
- `chat.thread_member_removed`

## Test

Copertura attuale:

- direct thread idempotente
- invio messaggi
- unread / read
- free group chat
- group attached chat
- reply
- reaction emoji
- add/remove membri free group chat
- blocco accesso outsider
- blocco gestione manuale sui thread attached

## Prossimi step consigliati

1. edit messaggi
2. delete messaggi
3. allegati
4. broadcast realtime
5. eventuale moderazione specifica chat
