# Fullstack Coverage Checklist

Aggiornato: 2026-09-03

Obiettivo: capire cosa e' **realmente implementato e funzionante** tra backend e frontend.

Legenda stato:
- `OK`: implementato end-to-end (API + UI/flow)
- `PARZIALE`: presente ma incompleto o con gap funzionali
- `MANCANTE`: backend pronto ma frontend assente, oppure dominio non implementato

## Stato globale

- Backend: molto avanzato e testato.
- Frontend: copertura parziale, con diversi placeholder e domini ancora senza UI reale.
- Test backend attuali: `222 passed`, `0 failed`.
- Smoke frontend automatizzato (route/login/auth pages principali): passato.

## Matrice dominio per dominio

- `Auth`: `PARZIALE`
  - Fatto: login, register, forgot password, logout, bootstrap da `/api/v1/me`, dashboard widgets.
  - Manca: reset password UI, verifica email UI, gestione esplicita errori `account.verified`.

- `Event`: `PARZIALE`
  - Fatto: list, detail, create, map, calendario personale.
  - Manca: edit/delete, voting availability, invite workflow completo, booking reale.

- `Article`: `PARZIALE`
  - Fatto: list, detail, featured, like, commenti su detail (list/create/reply/edit/delete), report submit da detail.
  - Manca: create/edit/delete reali (create era placeholder), comment/report estesi a tutte le card/context.

- `Poetry`: `PARZIALE`
  - Fatto: list, featured, detail, create, like (in parte), commenti su detail (list/create/reply/edit/delete), report submit da detail.
  - Manca: edit/delete, my poems avanzato, translation workflow lato UI, copertura comment/report in tutti i contesti.

- `Gig`: `MANCANTE`
  - Backend ricco, frontend quasi nullo (pagina shell, flussi non presenti).

- `Group`: `MANCANTE`
  - Backend completo, frontend senza pagine e flussi membership/announcements.

- `Chat`: `MANCANTE`
  - Backend presente, frontend assente (thread/messages/reactions non disponibili in UI).

- `Media`: `PARZIALE`
  - Fatto: pagina media con list reale da API, detail media, like/comment/report su detail, report su list.
  - Manca: upload/management, snaps/checkout end-to-end, UX avanzata.

- `Comment`: `PARZIALE` (lato frontend)
  - Backend completo e testato.
  - Frontend: flow completo su `PoemDetail` e `ArticleDetail`; manca estensione a media e ad altre superfici UI.

- `Report`: `PARZIALE` (lato frontend)
  - Backend completo.
  - Frontend: submit reale su `PoemDetail` e `ArticleDetail` implementato; manca copertura uniforme su media/comment e altri entrypoint.

- `Notification`: `MANCANTE`
  - Backend pronto, frontend inbox/bell non implementati realmente.

- `Moderation`: `MANCANTE`
  - Backend pronto, frontend in placeholder.

- `Engagement`: `PARZIALE`
  - Fatto: like su alcuni contenuti.
  - Manca: bookmark e altri comportamenti coerenti su tutte le pagine.

- `Profile`: `MANCANTE`
  - Route/profilo UI non completo, edit profile non operativo end-to-end.

- `Search`: `MANCANTE`
  - Ricerca globale non operativa.

- `Forum`: `MANCANTE`
  - Nessun flow reale.

## Buchi critici oggi (priorita alta)

1. Commenti/Report non ancora uniformi su tutti i contesti UI (coperti su poem/article/media detail).
2. Gigs completamente senza flusso utente reale.
3. Chat e Group non disponibili in frontend.
4. Media upload/management non disponibili in frontend.
5. Notification assente (bell/inbox non operativo).
6. Moderation assente in frontend.
7. Profile assente end-to-end.
8. Ricerca globale assente.
9. Mismatch UX-permessi (`verified`, `account_type`) non sempre gestiti bene in UI.

## Checklist per dichiarare un dominio "realmente implementato"

Per ogni dominio devono essere tutti `true`:
- API principali disponibili e testate.
- Almeno un percorso utente completo in frontend (list -> detail -> create/update se previsti).
- Error handling UX (401/403/422/500) visibile e comprensibile.
- Nessun link/CTA verso placeholder o route mancanti.
- Smoke test automatizzato del dominio verde.

## Prossime 5 priorita consigliate

1. Estendere `Comment` + `Report` agli altri entrypoint (card/list e comment report diretto da UI).
2. Implementare MVP `Gig` (list open gigs, detail, apply).
3. Implementare MVP `Notification` (list + unread + mark as read).
4. Implementare MVP `Profile` (view/edit) e allineare UX permessi.
5. Implementare MVP `Group/Chat` (lista gruppi, thread base, messaggi).

## Documenti collegati

- `docs/backend-audit-progress.md`
- `docs/project-status.md`
- `docs/backend-architecture.md`
