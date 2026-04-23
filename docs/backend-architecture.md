# Backend Architecture - Slamin v4

## 🧱 Architettura generale

Pattern adottato:

Request → Controller → DTO → Action → ApiResponse

### Obiettivi
- separazione chiara responsabilità
- dominio testabile
- niente logica nei controller
- validazione isolata nelle Request
- business logic nelle Action

---

## 📂 Struttura


App/
Http/
Controllers/Api/V1/
Requests/Api/V1/

Domain/
Auth/
Event/
Group/
Audit/


---

## 🔐 Auth

- Laravel Sanctum
- Token-based

### Endpoint
- POST `/api/v1/register`
- POST `/api/v1/login`
- POST `/api/v1/logout`
- GET `/api/v1/me`

---

## 📜 Audit Log

### Implementazione
- `Domain\Audit\Actions\StoreAuditLogAction`
- helper globale `audit()->log(...)`

### Copertura attuale
- register ✔
- reset password ✔
- verify email ✔
- profile update ✔
- event create/update/delete ✔

---

## 🌍 i18n

- lingua via header `X-Locale`
- tutte le validation e response localizzate

### Frontend (bundle UI)

- `GET /api/v1/i18n/{locale}.json` — serve il catalogo unione di `lang/{locale}/*.php` in `data.translations` (envelope `ApiResponse`), logica in `Domain\I18n\Actions\GetUiTranslationBundleAction`, controller sottile `ShowI18nController`
- i file `lang/*/*_ui.php` sono partial (es. `auth_ui.php`) e non compaiono nel bundle: servono solo a comporre file pubblici

---

# 🎪 DOMAIN: EVENT

## 📦 Modello Event

Un evento può essere:
- standalone
- figlio di un festival

---

## 🧠 Concetto di Festival

Un festival NON è un'entità separata.

👉 È un Event con:

event_category.slug = 'festival'


---

## 🔗 Relazioni

- Event → belongsTo EventCategory
- Event → belongsTo Festival (self relation)
- Event → hasMany childFestivalEvents
- Event → belongsToMany Group

---

## 🔒 Vincoli di dominio (IMPORTANTISSIMI)

### 1. Parent deve essere festival

festival_event_id → deve puntare a evento con categoria 'festival'


---

### 2. No self-link

event.id != festival_event_id


---

### 3. Festival NON può avere parent

if category == festival → festival_event_id deve essere null


---

### 4. Festival con figli NON può cambiare categoria

if has children → category deve restare festival


---

### 5. Festival con figli NON può essere eliminato

if has children → delete bloccata


---

## 🧩 Flussi implementati

### Create Event
- validation request
- DTO mapping
- Action con business logic
- audit log

---

### Update Event
- gestione completa campi
- gestione immagini
- gestione invites
- gestione relazioni
- vincoli festival lato domain

---

### Delete Event
- blocco se festival con figli

---

## 👥 Group integration

- `group_ids` attach su create/update
- controllo ruoli:
  - Owner ✔
  - Admin ✔
  - Moderator ✔
  - Member ❌

---

## 🧪 Test coverage

### File

tests/Feature/Api/V1/Event/EventApiTest.php


### Copertura

#### Core
- create fixed event ✔
- availability flow ✔
- invites ✔
- voting ✔
- finalize ✔

#### Festival
- create child ✔
- reject non-festival parent ✔
- reject self-link ✔
- reject nested festival ✔
- reject category change ✔
- reject delete ✔

#### Group
- attach group ✔
- reject unauthorized group ✔

---

## 🧱 DTO

- `StoreEventData`
- `UpdateEventData`

⚠️ IMPORTANTE:
- mapping request → DTO deve essere sempre coerente
- bug reale trovato e risolto su `event_category_id`

---

## ⚙️ Action principali

- `CreateEventAction`
- `UpdateEventAction`
- `DeleteEventAction`

---

## 📡 Response

Tutte le API usano:


ApiResponse::success()
ApiResponse::error()


Formato errore:

```json
{
  "errors": {
    "message": "...",
    "fields": {
      "field": ["error"]
    }
  }
}