Questo serve per riprendere il progetto in altre chat senza perdere contesto.

# Slamin v4 - Project Status

## Stato attuale

### Backend
- Laravel
- PostgreSQL (dev)
- Sanctum auth
- Domain architecture

## Auth
- completata
- testata
- con i18n

## Audit
- implementato
- integrato nei flussi principali

## Event Domain
- completato

### Funzionalita
- create event
- update event
- delete event
- availability flow
- invites
- voting
- finalize
- group integration

### Festival
- completamente implementato

Vincoli:
- parent deve essere festival
- no self-link
- festival non puo avere parent
- no cambio categoria se ha figli
- no delete se ha figli

### Test
- EventApiTest completo
- edge cases coperti

## Group integration
- attach group
- validazione ruoli

## Testing
- feature test completi su Event
- dominio stabile

## Bug risolti importanti

1. DTO mapping (`event_category_id`)
2. ordine validazioni (self-link vs festival)
3. gestione `ValidationException` vs `ApiResponse`

## Poetry Slam
- completato nel core backend

Copertura:
- slam
- entries
- rounds
- scores
- advancement framework
- leaderboard

## Gig
- attivo su `Event -> Gig`
- attivo su `Poem -> translation gig`

Copertura Event:
- CRUD nested su `Event`
- discovery `open gigs`
- candidatura utenti autenticati
- gestione organizer candidature
- passaggio automatico a `filled`
- chiusura organizer
- annunci multipli nel tempo

Copertura Poem:
- richiesta traduzione nested su `Poem`
- candidature su gig traduzione
- trattative parallele
- cronologia eventi di negoziazione
- accordo finale con chiusura concorrenti
- creazione automatica `TranslationJob`
- submit / request changes / approve
- commenti e resolve sul workspace di traduzione

## Chat
- attiva su `direct`
- attiva su `group` libero
- attiva su `group` collegato al dominio `Group`

Copertura:
- thread direct idempotente
- free group chat
- attached group chat
- messaggi testuali
- unread / read
- notifiche
- reply
- reaction emoji
- gestione membri free group chat

## Media
- blueprint definito
- ownership locale confermata
- strategia PeerTube single-account confermata

Copertura target:
- foto locali
- video PeerTube
- limiti upload admin
- premium entitlements
- like
- comment
- snap timeline video

## Prossimi step

### Priorita alta
- hardening del ramo `Poem -> translation gig`
- allineamento finale policy, audit e notifiche sul workflow traduzione
- implementazione dominio `Media`

### Priorita media
- miglioramenti list/filter Event
- hardening policy/audit `Gig`
- affinamento commenti testuali e locator nel `TranslationJob`
- hardening del dominio `Chat`

### Priorita bassa
- UI / frontend
- performance tuning

## Nota architetturale

`Event` e `Poem` sono i due parent principali di workflow specializzati:
- `PoetrySlam` si appoggia a `Event`
- `Gig` evento si appoggia a `Event`
- `Gig` traduzione si appoggia a `Poem`
- `Group` si integra con `Event`

`Chat` invece e un dominio trasversale:
- direct tra utenti
- free group chat
- integrazione con `Group`

`Media` sara un dominio trasversale:
- ownership locale in Slàmin
- video appoggiati a PeerTube
- interazioni social e timeline snaps

## Documenti collegati

- [slamin-current-state.md](C:/Users/dmazz/Herd/slamin_v4/docs/slamin-current-state.md)
- [gigs-domain-blueprint.md](C:/Users/dmazz/Herd/slamin_v4/docs/gigs-domain-blueprint.md)
- [poem-translation-gig-blueprint.md](C:/Users/dmazz/Herd/slamin_v4/docs/poem-translation-gig-blueprint.md)
- [chat-domain-blueprint.md](C:/Users/dmazz/Herd/slamin_v4/docs/chat-domain-blueprint.md)
- [media-domain-blueprint.md](C:/Users/dmazz/Herd/slamin_v4/docs/media-domain-blueprint.md)
