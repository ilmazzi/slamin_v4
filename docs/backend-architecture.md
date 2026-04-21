# Backend Architecture — Slamin

## Obiettivo
Questo file documenta le decisioni architetturali prese per il backend Laravel API-first del progetto Slamin, in modo da poter riprendere il lavoro in una nuova chat senza perdere contesto.

---

## Stack scelto
- **Backend:** Laravel
- **API auth:** Laravel Sanctum
- **Frontend previsto:** React separato
- **Database:** PostgreSQL
- **Approccio:** API-first, stateless
- **Lingue:** multilingua già impostato lato backend

---

## Principi architetturali

### 1. Organizzazione per dominio
Non si organizza il progetto solo per tipo tecnico, ma per dominio.

Esempi:
- `Domain/Auth`
- `Domain/User`
- `Domain/Profile`
- `Domain/Poetry`
- `Domain/Event`
- `Domain/Group`
- `Domain/Audit`

### 2. Controller sottili
I controller non devono contenere business logic.
Devono solo:
- ricevere la request
- leggere i dati validati
- costruire DTO oppure usare `DTO::fromRequest(...)`
- chiamare una Action
- restituire `ApiResponse`

### 3. Pattern standard per gli endpoint
Il flusso corretto di esecuzione è:
1. Route
2. Middleware
3. Request
4. Controller
5. DTO
6. Action
7. Model
8. ApiResponse

La formula mentale corretta da mantenere è:
- **Input:** `Request`
- **Dati puliti:** `DTO`
- **Logica:** `Action`
- **Persistenza:** `Model`
- **Output:** `ApiResponse`

Per il codice applicativo dentro l’endpoint, il riferimento mentale è:
`Request -> Controller -> DTO -> Action -> ApiResponse`

### 4. Nessuna stringa hardcoded
Tutte le stringhe utente devono passare da file lingua.

Usare sempre:
```php
__('auth.invalid_credentials')
```
Mai:
```php
'Credenziali non valide.'
```

### 5. Risposte API standardizzate
Le risposte devono seguire questo formato.

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

---

## Struttura cartelle adottata

### Domain
Dentro `app/Domain/{Modulo}` si usano queste cartelle base:
- `Actions`
- `DTOs`
- `Models`
- `Policies`
- `Queries`
- `Services`

Altre cartelle possono essere aggiunte quando servono:
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
Per classi trasversali applicative:
- `app/Support/Responses/ApiResponse.php`
- `app/helpers.php`

---

## Namespace e autoload
In `composer.json` è presente l’autoload PSR-4 del namespace `Domain\\`.

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

Dopo modifiche a namespace o helper:
```bash
composer dump-autoload
```

---

## Scelta sui domini principali

### Auth
Contiene:
- register
- login
- logout
- me
- forgot password
- reset password
- email verification
- resend email verification

### User
Contiene:
- account base
- email
- password
- status
- public_id
- account_type
- ruoli staff

### Profile
Contiene:
- nickname
- display_name
- bio
- preferred_locale
- dati pubblici/social

### Audit
Contiene:
- audit log applicativi
- tracciamento eventi di dominio rilevanti

### Poetry
Contiene il dominio core delle poesie.
Al momento non è ancora implementato il caso d’uso completo perché prima è stato deciso di strutturare bene immagini e audit.

### Regola importante
- **Auth** = autenticazione / recupero accesso / verifica email
- **User** = account e capacità base dell’utente
- **Profile** = profilo pubblico/social
- **Audit** = storico eventi applicativi rilevanti

---

## Database: stato attuale

### users
La tabella `users` contiene solo dati account.

Campi chiave:
- `id`
- `public_id`
- `email`
- `password`
- `status`
- `account_type`
- `email_verified_at`
- `remember_token`
- `deleted_at`
- timestamps

Note:
- `public_id` viene generato automaticamente
- `status` default `active`
- `account_type` default `audience`
- `softDeletes` attivo

### profiles
La tabella `profiles` è separata da `users`.

Campi chiave:
- `id`
- `user_id`
- `public_id`
- `display_name`
- `nickname`
- `bio`
- `location`
- `website_url`
- `preferred_locale`
- `is_public`
- `deleted_at`
- timestamps

Vincoli:
- `user_id` unique
- `nickname` unique
- foreign key verso `users`

### personal_access_tokens
Usata da Sanctum per i token API.

### password_reset_tokens
Usata dal broker Laravel per forgot/reset password.

Struttura corretta:
- `email`
- `token`
- `created_at`

### roles
Tabella ruoli staff.

Ruoli iniziali previsti:
- `moderator`
- `admin`

### role_user
Pivot tra utenti e ruoli staff.

### audit_logs
Tabella unica per audit applicativi.

Campi chiave:
- `id`
- `user_id` nullable
- `event`
- `auditable_type` nullable
- `auditable_id` nullable
- `old_values` json nullable
- `new_values` json nullable
- `metadata` json nullable
- `ip_address` nullable
- `user_agent` nullable
- `created_at`

Note:
- nessun `updated_at`
- non vanno mai salvati password, token, signature o altri dati sensibili

---

## Modelli attuali

### `Domain\\User\\Models\\User`
Responsabilità:
- account autenticabile
- generazione automatica `public_id`
- default `status = active`
- default `account_type = audience`
- relazione `profile()`
- relazione `roles()`
- creazione automatica del profilo al `created()`
- implementa `MustVerifyEmail`

Helper previsti nel model:
- `isAudience()`
- `isPoetOrganizer()`
- `hasRole()`
- `isModerator()`
- `isAdmin()`

Note:
- il profilo viene creato automaticamente
- il nickname automatico esiste solo come fallback di sicurezza
- il nickname reale viene poi aggiornato dalla registrazione o dall’update profilo
- `account_type` viene castato a enum
- `password` è castata con `hashed`

### `Domain\\User\\Models\\Role`
Responsabilità:
- rappresenta i ruoli staff (`moderator`, `admin`)
- relazione many-to-many con `User`

### `Domain\\Profile\\Models\\Profile`
Responsabilità:
- dati pubblici/social
- generazione automatica `public_id`
- default `preferred_locale = it`
- relazione `user()`

### `Domain\\Audit\\Models\\AuditLog`
Responsabilità:
- rappresenta lo storico audit applicativo
- cast JSON per `old_values`, `new_values`, `metadata`
- relazione `user()`
- relazione morph `auditable()`

---

## Account type e ruoli

### Account type
Sono identità funzionali della piattaforma.
Valori attuali:
- `audience`
- `poet_organizer`

Regola funzionale:
- `audience` segue la parte social ma non crea contenuti core
- `poet_organizer` può creare poesie, eventi, gestire ingaggi e contenuti creator/professionali

### Ruoli staff
Sono privilegi aggiuntivi.
Valori iniziali:
- `moderator`
- `admin`

Regola importante:
- `moderator` è concettualmente un `poet_organizer` con privilegi extra di moderazione
- `admin` è ruolo staff separato e superiore

Decisione architetturale:
- **account_type** e **staff roles** non vanno mescolati in un unico campo
- `account_type` vive in `users`
- i ruoli staff vivono in `roles` + `role_user`

---

## Auth API: stato attuale

### Sanctum
Installato e funzionante.

Trait aggiunto in `User`:
- `HasApiTokens`

### Endpoint implementati

#### `POST /api/v1/register`
Flusso:
- `RegisterRequest`
- `RegisterUserData`
- `RegisterUserAction`
- `RegisterController`

Fa:
- valida input
- richiede `account_type`
- crea utente
- aggiorna profilo con nickname, display_name, preferred_locale
- dispatcha `Registered($user)`
- genera token
- restituisce user + profile + account_type
- scrive audit `auth.registered`

#### `POST /api/v1/login`
Flusso:
- `LoginRequest`
- `LoginUserData`
- `LoginUserAction`
- `LoginController`

Fa:
- valida input
- verifica credenziali
- genera token
- restituisce user + profile

#### `GET /api/v1/me`
Flusso:
- `MeUserAction`
- `MeController`

Fa:
- legge utente autenticato
- carica profilo
- restituisce dati correnti utente

#### `POST /api/v1/logout`
Flusso:
- `LogoutController`

Fa:
- elimina il token corrente
- restituisce messaggio tradotto

#### `PATCH /api/v1/me/profile`
Flusso:
- `UpdateProfileRequest`
- `UpdateProfileData`
- `UpdateProfileAction`
- `UpdateProfileController`

Fa:
- aggiorna `nickname`
- aggiorna `display_name`
- aggiorna `preferred_locale`
- scrive audit `profile.updated`

#### `POST /api/v1/forgot-password`
Flusso:
- `ForgotPasswordRequest`
- `SendPasswordResetData`
- `SendPasswordResetLinkAction`
- `ForgotPasswordController`

Fa:
- valida email
- invia il link di reset tramite broker Laravel
- non rivela se l’email esiste oppure no

#### `POST /api/v1/reset-password`
Flusso:
- `ResetPasswordRequest`
- `ResetPasswordData`
- `ResetPasswordAction`
- `ResetPasswordController`

Fa:
- valida token, email e password
- resetta la password tramite broker Laravel
- elimina tutti i token Sanctum dell’utente
- emette evento `PasswordReset`
- scrive audit `auth.password_reset`

#### `POST /api/v1/email/verify`
Flusso:
- `VerifyEmailRequest`
- `VerifyEmailData`
- `VerifyEmailAction`
- `VerifyEmailController`

Fa:
- valida `id`, `hash`, `expires`, `signature`
- verifica il payload firmato custom
- marca `email_verified_at`
- scrive audit `auth.email_verified`

Note:
- il flusso di verifica email è stato costruito in modo **frontend-ready**, senza dipendere dalle signed routes Laravel/Fortify per il consumo finale
- l’errore reale riscontrato durante il test era un problema sul valore di `expires`

#### `POST /api/v1/email/verification-notification`
Flusso:
- `SendEmailVerificationAction`
- `SendEmailVerificationController`

Fa:
- richiede utente autenticato
- reinvia la mail di verifica se l’utente non è ancora verificato

---

## Middleware applicativi attuali

### Locale
Middleware usato:
- `App\\Http\\Middleware\\SetLocale`

Ordine di priorità lingua:
1. Header `X-Locale`
2. `preferred_locale` del profilo utente autenticato
3. `Accept-Language` del browser
4. fallback `it`

Registrazione:
- in `bootstrap/app.php`
- aggiunto sia a `web` sia a `api`

### Email verificata
Middleware custom:
- alias `account.verified`
- classe `EnsureEmailIsVerified`

Serve a bloccare route API con risposta JSON standard se l’utente non ha verificato l’email.

### Account type
Middleware custom:
- alias `account.type`
- classe `EnsureAccountType`

Uso previsto:
```php
Route::middleware(['auth:sanctum', 'account.verified', 'account.type:poet_organizer'])
```

### Ruoli staff
Middleware custom:
- alias `role`
- classe `EnsureRole`

Supporta uno o più ruoli:
```php
Route::middleware(['auth:sanctum', 'role:moderator,admin'])
```

---

## Rate limiting
Sono stati introdotti limiter nominati in `AppServiceProvider` per mantenere coerenza con `ApiResponse`.

Limiter attuali:
- `login`
- `register`
- `forgot-password`
- `reset-password`
- `verify-email`
- `verification-notification`

---

## Traduzioni
Regole:
- niente `common.php`
- file separati per area funzionale

File già usati:
- `lang/it/auth.php`
- `lang/en/auth.php`
- `lang/it/validation.php`

Chiavi auth introdotte nel tempo includono almeno:
- `invalid_credentials`
- `logged_out`
- `too_many_attempts`
- `forgot_password_sent`
- `password_reset_success`
- `email_verified`
- `invalid_verification_link`
- `user_not_found`
- `verification_link_sent`
- `invalid_account_type`
- `insufficient_role`
- `unauthenticated`

---

## Standard errori validation
Il formato standard degli errori validation è centralizzato in `bootstrap/app.php` tramite `withExceptions(...)`.

Per le request JSON, le `ValidationException` vengono trasformate in:
```json
{
  "data": null,
  "meta": [],
  "errors": {
    "message": "...",
    "fields": {
      "field": [
        "..."
      ]
    }
  }
}
```

Questa trasformazione usa:
- `App\\Support\\Responses\\ApiResponse::error(...)`

---

## ApiResponse
Classe:
- `app/Support/Responses/ApiResponse.php`

Metodi attuali:
- `success(...)`
- `error(...)`

`success(...)` restituisce:
- `data`
- `meta`
- `errors = null`

`error(...)` restituisce:
- `data = null`
- `meta`
- `errors.message`
- `errors.fields`

---

## Audit logging

### Obiettivo
Distinguere nettamente:
- log tecnici Laravel
- audit applicativi
- activity feed futuro

### Dominio
È stato introdotto `Domain/Audit` con:
- `Models/AuditLog`
- `DTOs/StoreAuditLogData`
- `Actions/StoreAuditLogAction`
- `Support/AuditLogger`

### Helper globale
Esiste helper `audit()` che risolve `AuditLogger` dal container.

Uso previsto nelle Action rilevanti:
```php
audit()->log(...)
```

### Sanitizzazione
L’`AuditLogger` centralizza la sanitizzazione e rimuove chiavi sensibili come:
- `password`
- `password_confirmation`
- `remember_token`
- `token`
- `current_password`
- `reset_token`
- `signature`

### Eventi audit iniziali già decisi
- `auth.registered`
- `auth.password_reset`
- `auth.email_verified`
- `profile.updated`

### Regola pratica
Audit solo per action rilevanti.
Non auditare:
- semplici GET/listing
- query di lettura
- dati sensibili

---

## Comando custom `make:domain`
È stato creato un comando artisan custom per generare rapidamente lo scheletro del caso d’uso.

Obiettivo:
```bash
php artisan make:domain Auth ResetPassword
```

Genera automaticamente:
- `Request`
- `DTO`
- `Action`
- `Controller`

Supporta anche overwrite controllato tramite `--force`.

Nota importante:
per casi particolari come `VerifyEmail`, lo scheletro generato può richiedere adattamenti manuali perché non tutti i casi d’uso hanno bisogno di una `FormRequest` standard o dello stesso pattern esatto.

---

## Stato deciso sui nickname
Regola attuale:
- l’utente dovrebbe scegliere `nickname` in registrazione
- esiste comunque un fallback automatico per sicurezza

Il fallback serve per:
- import
- social login futuri
- creazioni admin
- casi incompleti

Significato dei campi:
- `nickname` = identificativo pubblico
- `display_name` = nome libero leggibile

---

## Stato tecnico verificato
È già stato verificato che funzionano:
- connessione PostgreSQL
- migration
- autoload `Domain\\`
- registrazione utente
- creazione automatica profilo
- login con token Sanctum
- logout
- endpoint `/me`
- update profilo
- forgot password
- reset password
- email verification
- resend email verification
- localizzazione tramite `X-Locale`
- validazione tradotta
- formato standard errori validation
- `account_type` in registrazione
- middleware custom `account.verified`
- middleware custom `account.type`
- middleware custom `role`
- audit log di base

---

## Stato di Poetry
Il dominio `Poetry` non è ancora partito realmente lato implementazione finale.

È stato però deciso che:
- una poesia potrà avere **0 o 1 immagine**
- se non ha immagine propria, verrà usata una **fallback image**
- la fallback **non deve essere salvata nel database su ogni record**
- nel DB va salvata solo l’immagine reale dell’utente, se presente

Decisione attuale:
- per partire si userà un campo nullable tipo `image_path`
- niente sistema media complesso o gallery multiple in questa fase

Prima di procedere con `Poetry`, si è deciso di strutturare correttamente audit e permessi.

---

## Regole pratiche da mantenere
1. Ogni endpoint nuovo va progettato per dominio
2. Niente business logic nei controller
3. Niente stringhe hardcoded
4. Niente risposte JSON “a mano”: usare sempre `ApiResponse`
5. Niente validazione nel controller: usare `FormRequest`
6. Se il caso d’uso ha più di pochi campi, creare DTO
7. Le Action rappresentano casi d’uso, non helper generici
8. I Services vanno usati solo per logica riusabile trasversale
9. I Model non devono diventare “controller nascosti”
10. Permessi e autorizzazione vanno separati in:
   - middleware per blocchi generali
   - policy per ownership e controlli fini
11. `account_type` e ruoli staff non vanno mescolati
12. Audit sì, ma solo su eventi rilevanti

---

## Prossimo passo consigliato
A questo punto il blocco `Auth + Profile + Account types + Roles middleware + Audit base` è ben avviato.

Ordine consigliato dei prossimi lavori:
1. partire col dominio `Poetry`
2. definire bene migration/model della poesia con `image_path` nullable
3. implementare `StorePoem`
4. applicare middleware:
   - `auth:sanctum`
   - `account.verified`
   - `account.type:poet_organizer`
5. aggiungere audit `poem.created`
6. poi introdurre ownership con policy su update/delete

Motivo:
il progetto è una piattaforma social della poesia, quindi il cuore vero del dominio resta `Poetry`.

---

## Nota finale per riprendere in una nuova chat
Se si riprende il lavoro in una nuova chat, ricordare che:
- il progetto usa Laravel API-first
- frontend sarà React separato
- auth è con Sanctum
- lingua API è gestita da `X-Locale`
- tutte le risposte devono usare `ApiResponse`
- tutti i controller devono essere sottili
- tutti gli endpoint nuovi devono usare `Request + DTO + Action`
- email verification è stata resa frontend-ready con payload firmato custom
- esiste `account_type` (`audience` / `poet_organizer`)
- esistono ruoli staff separati (`moderator`, `admin`)
- esistono middleware custom `account.verified`, `account.type`, `role`
- esiste dominio `Audit` con helper globale `audit()`

Il punto più importante da non perdere è questo:
**il controller non deve contenere il lavoro vero. Il lavoro vero vive nel dominio.**
