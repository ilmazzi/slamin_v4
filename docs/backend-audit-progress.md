# Backend Audit Progress

Aggiornato: 2026-09-03

Questo documento traccia in modo operativo:
- cosa e' stato completato finora;
- quali fix sono stati applicati;
- cosa manca ancora da auditare dominio per dominio.

## Stato generale

- Frontend attivo: `frontend/` (legacy Inertia non piu' baseline operativa).
- Backend: audit strutturato "dominio per dominio" in corso.
- Test feature attuali: `222 passed`, `0 failed`.

## Domini completati (audit + fix + test)

- `Auth`
- `Event`
- `Article`
- `Group`
- `Poetry`
- `Gig`
- `Chat`
- `Media`
- `Comment`
- `Report`

## Fix principali applicati finora

- **Auth**
  - Corretto mismatch rate limiter su resend verifica email.
  - Spostato resend verifica email sotto middleware autenticato.
- **Event**
  - Corretto mapping DTO update (`gigs` mancante).
  - Robustezza `prepareForValidation` su boolean string e array multipart.
- **Article**
  - Corretto filtro `article_category_id` su query featured.
- **Group**
  - Normalizzazione `remove_image` stringa -> boolean in update.
- **Poetry**
  - Normalizzazione boolean string (`remove_image`, `is_public`).
  - Aggiunta validazione `per_page` su endpoint my poems.
- **Gig**
  - Chiuso bug di autorizzazione su listing negoziazioni (scoping per owner/translator).
- **Chat**
  - Chiuso bug visibilita' thread di gruppo per membri non piu' attivi.
- **Media**
  - Fix update con `null` esplicito (campi nullable realmente aggiornabili).
- **Comment**
  - Aggiunti test su edge-case policy e coerenza parent/commentable.
- **Report**
  - Chiuso bug: report su commenti di `MediaItem` pubblico/ready (prima `404`).

## Test e regressioni aggiunte

- Nuove suite/workflow test su domini principali (Auth/Event/Article/Group/Poetry/Media/Comment/Report + regressioni Gig/Chat).
- Stato corrente backend feature suite: verde (`220 passed`).

## Domini ancora da auditare

Ordine consigliato (dal piu' impattante):

1. `Notification`
2. `Moderation`
3. `PoetrySlam`
4. `Engagement`
5. `User`
6. `Profile`
7. `Reaction`
8. `Activity`
9. `Search`
10. `Forum`
11. `Newsletter`
12. `Gamification`
13. `Admin`
14. `Audit` (hardening finale e consistenza cross-domain)
15. `I18n` (coerenza chiavi/coverage)

## Definizione di "dominio completato"

Un dominio e' considerato completato quando:
- endpoint e action principali sono verificati;
- permessi/policy risultano coerenti;
- edge-case critici hanno regressione test;
- la suite dedicata passa;
- non emergono bug bloccanti o leak di accesso.

## Note operative

- Evitare regressioni su controlli di accesso (ownership, membership, staff-role).
- Continuare con approccio: audit -> fix -> test dominio -> test feature globale.
- Aggiornare questo file a ogni dominio chiuso.

## Copertura frontend collegata

Per la fotografia aggiornata della copertura reale fullstack (backend + frontend), vedi:
- `docs/fullstack-coverage-checklist.md`
