# Poem Translation Gig Blueprint - Slamin

## Obiettivo

Questo documento fissa il disegno del ramo `Poem -> Gig` dedicato alle richieste di traduzione.

Questo ramo non va trattato come semplice variante del `Gig` evento.
Qui il problema non e solo pubblicare un bisogno e raccogliere candidature, ma governare un workflow completo:

- apertura richiesta traduzione
- candidature di piu traduttori
- trattative parallele con offerte e controfferte
- scelta della trattativa vincente
- esecuzione della traduzione online
- commenti diretti sui testi
- revisione, conferme e chiusura lavoro

Il dominio va quindi pensato come sottosistema editoriale-collaborativo, non come semplice vacancy.

## Stato implementazione attuale

Gia operativo nel backend:

- richiesta traduzione nested su `Poem`
- candidature su `GigApplication`
- trattative parallele tramite `GigNegotiation`
- cronologia eventi trattativa tramite `GigNegotiationEvent`
- accordo finale con chiusura delle candidature concorrenti
- creazione automatica di `TranslationJob`
- aggiornamento contenuto tradotto
- `submit`, `request changes`, `approve`
- commenti sul job con `comment_target`, `target_locator` e `resolve`

Non ancora chiuso del tutto:

- refinement del locator testuale
- storico revisioni del testo tradotto
- gestione piu ricca di stati e timeout di trattativa
- eventuale pubblicazione automatica della traduzione come contenuto finale

## Decisioni di dominio stabili

### 1. La richiesta traduzione nasce sempre da una poesia

Decisione stabile:

- la richiesta e sempre collegata a `Poem`
- non esiste una richiesta traduzione scollegata

### 2. La lingua target e libera

Decisione stabile:

- la `target_language` non deve essere limitata alle lingue di interfaccia del sito
- il sistema deve supportare qualsiasi lingua utile alla traduzione

### 3. La richiesta traduzione e un `Gig` specializzato

Scelta stabile:

- il ramo `Poem` continua a usare il sottodominio `Gig`
- ma con campi e regole specializzate per traduzione

Forma concettuale:

`Poem -> Translation Gig -> Applications -> Negotiations -> Translation Job`

### 4. Una candidatura non equivale a una trattativa

Scelta stabile:

- la candidatura del traduttore e solo l'ingresso nel funnel
- la trattativa vera e un'entita separata

### 5. La trattativa deve essere storicizzata

Decisione stabile:

- ogni modifica economica o operativa deve essere persistita come evento di negoziazione
- non basta salvare solo il prezzo finale

### 6. La traduzione online e un blocco separato dalla trattativa

Scelta stabile:

- quando si raggiunge l'accordo nasce un `TranslationJob`
- il lavoro di traduzione non vive dentro la trattativa

### 7. Commenti direttamente sui testi

Decisione stabile:

- entrambe le parti devono poter commentare il testo originale e il testo tradotto
- i commenti non sono semplici chat generiche
- vanno pensati come annotazioni collegate a una porzione o sezione del contenuto

## Schema concettuale proposto

### 1. Translation Gig

E la richiesta iniziale aperta dal proprietario della poesia.

Campi minimi dichiarati:

- `poem_id`
- `target_language`
- `proposed_price`
- `currency`
- `deadline` nullable
- `special_requirements` nullable
- `status`
- `opened_at`
- `closed_at`
- `created_by_user_id`
- `updated_by_user_id`

### 2. Translation Application

E la candidatura del traduttore.

Campi:

- `translation_gig_id`
- `user_id`
- `message`
- `status`
- `created_at`
- `updated_at`

### 3. Translation Negotiation

E la trattativa aperta con un candidato specifico.

Campi:

- `translation_gig_id`
- `application_id`
- `owner_user_id`
- `translator_user_id`
- `status`
- `agreed_price` nullable
- `currency`
- `agreed_deadline` nullable
- `closed_at` nullable
- `created_at`
- `updated_at`

Nota:

- piu trattative possono essere `open` contemporaneamente
- quando una va in `agreed`, le altre vanno chiuse o archiviate

### 4. Negotiation Events

Sono gli eventi puntuali dentro la trattativa.

Campi:

- `translation_negotiation_id`
- `author_user_id`
- `event_type`
- `price` nullable
- `currency` nullable
- `deadline` nullable
- `note` nullable
- `created_at`

Tipi suggeriti:

- `owner_offer`
- `translator_offer`
- `counter_offer`
- `note`
- `agreement`
- `rejection`
- `withdrawal`

### 5. Translation Job

Nasce solo quando una trattativa arriva a `agreed`.

Campi:

- `translation_gig_id`
- `translation_negotiation_id`
- `poem_id`
- `translator_user_id`
- `owner_user_id`
- `source_language`
- `target_language`
- `agreed_price`
- `currency`
- `deadline` nullable
- `translated_content` nullable
- `status`
- `submitted_at` nullable
- `approved_at` nullable
- `created_at`
- `updated_at`

### 6. Translation Comments

Commenti e annotazioni durante il lavoro.

Campi:

- `translation_job_id`
- `author_user_id`
- `comment_target`
- `target_locator`
- `content`
- `status`
- `resolved_at` nullable
- `created_at`
- `updated_at`

## Regole di business

### Apertura richiesta

Il proprietario della poesia puo aprire una richiesta traduzione con questi campi minimi:

- lingua di destinazione
- compenso proposto
- scadenza opzionale
- requisiti speciali opzionali

Regole:

- solo il proprietario della poesia puo aprire o modificare la richiesta
- una sola richiesta attiva per coppia `poem + target_language`

### Candidature

Regole:

- solo utenti autenticati possono candidarsi
- una candidatura per utente per richiesta attiva
- il candidato puo ritirarsi

### Apertura trattativa

Regole:

- il proprietario puo trasformare una candidatura in trattativa
- la trattativa puo partire anche da una proposta economica diversa da quella iniziale
- piu trattative possono essere aperte in parallelo

### Accordo

Regole:

- quando una trattativa va a `agreed`, diventa la trattativa vincente
- le altre trattative aperte devono essere chiuse come non selezionate
- il `Translation Gig` passa nella fase di lavoro

### Lavoro di traduzione

Regole:

- il lavoro nasce solo da una trattativa concordata
- il testo tradotto e modificabile online
- entrambe le parti possono commentare
- il proprietario puo chiedere revisioni
- il traduttore puo ripresentare il testo aggiornato
- l'approvazione finale chiude il lavoro

### Notifiche

Minimo utile:

- nuova candidatura
- apertura trattativa
- nuova offerta o controfferta
- accordo raggiunto
- nuovo commento sul testo
- richiesta modifiche
- traduzione inviata
- traduzione approvata

## API

### Translation gig su Poem

- `GET /poems/{poem}/translation-gigs`
- `POST /poems/{poem}/translation-gigs`
- `PATCH /poems/{poem}/translation-gigs/{gig}`
- `DELETE /poems/{poem}/translation-gigs/{gig}`

### Discovery / candidature

- `GET /gigs/open`
- `GET /gigs/{gig}`
- `POST /gigs/{gig}/applications`
- `GET /my/gig-applications`

### Negotiations

- `GET /gigs/{gig}/negotiations`
- `POST /gigs/{gig}/applications/{application}/negotiations`
- `GET /gig-negotiations/{negotiation}`
- `POST /gig-negotiations/{negotiation}/events`
- `POST /gig-negotiations/{negotiation}/agree`
- `POST /gig-negotiations/{negotiation}/reject`

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

### Gig lato Poem

- `ListPoemTranslationGigsAction`
- `StorePoemTranslationGigAction`
- `UpdatePoemTranslationGigAction`
- `DeletePoemTranslationGigAction`

### Applications

- `StorePoemTranslationApplicationAction`
- `ListMyPoemTranslationApplicationsAction`
- `WithdrawPoemTranslationApplicationAction`

### Negotiations

- `OpenTranslationNegotiationAction`
- `ListTranslationNegotiationsAction`
- `ShowTranslationNegotiationAction`
- `StoreTranslationNegotiationEventAction`
- `AgreeTranslationNegotiationAction`
- `RejectTranslationNegotiationAction`
- `CloseCompetingNegotiationsAction`

### Translation work

- `StartTranslationJobAction`
- `UpdateTranslationJobContentAction`
- `SubmitTranslationJobAction`
- `RequestTranslationChangesAction`
- `ApproveTranslationJobAction`

### Comments

- `ListTranslationCommentsAction`
- `StoreTranslationCommentAction`
- `UpdateTranslationCommentAction`
- `ResolveTranslationCommentAction`

## Ordine di implementazione consigliato

1. blueprint e decisioni stabili
2. `Poem -> Translation Gig` con campi minimi
3. candidature base
4. trattative e cronologia offerte
5. selezione trattativa vincente
6. nascita `Translation Job`
7. editing online del testo tradotto
8. commenti e risoluzione annotazioni
9. notifiche e audit completi
10. test feature end-to-end

## MVP consigliato

Per un primo blocco utile e non troppo grande:

- richiesta traduzione su `Poem`
- candidature
- trattative con cronologia offerte/note
- accordo finale
- nascita `Translation Job`

Aggiornamento:

- questo MVP e gia stato superato
- oggi esistono gia anche commenti e risoluzione annotazioni nel `TranslationJob`
- il blocco successivo sensato e il refinement del workspace, non la sua prima introduzione

## Nota finale

Il ramo `Poem -> Gig` e un sottodominio specializzato di traduzione collaborativa.

Non basta riusare pari pari il workflow dei `Gig` evento, perche qui servono:

- negoziazioni parallele
- accordo economico
- workspace editoriale
- commenti sul testo
- approvazione finale della traduzione

Quindi va trattato come estensione specializzata del dominio `Gig`, con lifecycle proprio.
