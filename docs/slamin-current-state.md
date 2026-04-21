# 🚀 Slamin — Stato Attuale Backend

## 🧱 Architettura

- Laravel (API only)
- Domain-driven structure:
  - Domain/
    - Poetry
    - Engagement
    - User
    - Audit
- API versionata: `/api/v1`

---

## 🔐 Autenticazione

- Laravel Sanctum (Bearer Token)
- Middleware:
  - `auth:sanctum`
  - `account.verified`

---

## 📊 Audit Log

- Salvato su DB (`audit_logs`)
- Copertura attuale:
  - register ✔
  - login ✔
  - reset password ✔
  - verify email ✔
  - profile update ✔
  - poem create/update ✔
  - like / unlike ✔
  - bookmark / unbookmark ✔

---

## ✍️ Domain: Poetry

### Features implementate

- Create poem ✔
- Update poem ✔
- Delete poem ✔
- List poems (pubblico) ✔
- List my poems ✔
- Show poem ✔

---

### Campi attuali `poems`

- id
- user_id
- title
- slug
- content
- description
- image_path
- language
- status (enum: draft/published)
- is_public
- published_at
- timestamps
- soft deletes

---

### Logica publish

- draft → published
- published_at gestito automaticamente
- nessuno stato incoerente

---

### Immagini

- 1 immagine per poesia
- store / replace / remove ✔
- cleanup file vecchi ✔

---

## ❤️ Domain: Engagement (NUOVO)

### Struttura

#### `likes`
- user_id
- likeable_type
- likeable_id
- unique (user + target)

#### `bookmarks`
- user_id
- bookmarkable_type
- bookmarkable_id
- unique (user + target)

👉 Polimorfico → riusabile per:
- poesie
- eventi (futuro)
- articoli (futuro)
- media (futuro)

---

### Features

- Like ✔
- Unlike ✔
- Bookmark ✔
- Unbookmark ✔

---

## 🔐 Policy

### PoemPolicy

Regole:

- owner → sempre accesso
- altri utenti:
  - solo se `published`
  - e `is_public = true`

---

## 📤 API Output

### PoemResource

Include:

- likes_count ✔
- bookmarks_count ✔
- is_liked ✔
- is_bookmarked ✔

---

## 📡 Endpoint principali

### Poetry

- GET `/api/v1/poems`
- GET `/api/v1/poems/{slug}`
- POST `/api/v1/poems`
- PATCH `/api/v1/poems/{poem}`
- DELETE `/api/v1/poems/{poem}`
- GET `/api/v1/my/poems`

### Engagement

- POST `/api/v1/poems/{poem}/like`
- DELETE `/api/v1/poems/{poem}/like`
- POST `/api/v1/poems/{poem}/bookmark`
- DELETE `/api/v1/poems/{poem}/bookmark`

---

## ⚠️ Limitazioni attuali

- category ❌ (da rifattorizzare)
- poem_type ❌ (da rifattorizzare)
- tags ❌
- feed ❌
- notifiche ❌

---

## 🎯 Prossimo step deciso

👉 Tassonomie admin-managed:

- poem_categories
- poem_types

con FK su poems

---

## 💡 Note tecniche

- API già pronta per frontend reale
- Domain Engagement già scalabile
- Nessun coupling con Poetry
