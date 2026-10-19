# Gruppi

Un gruppo è una **pagina** della community: staff, follower, bacheca e calendario. Non è un club chiuso né un campo di supporto degli eventi.

## Ruoli

- **admin** — gestisce pagina, persone, affiliazioni, chat e visibilità. Toglie i contenuti dalla pagina, non dal sito.
- **editor** — pubblica e accetta proposte. Non gestisce gli admin.
- **follower** — segue la pagina e, se esiste, entra nella chat pubblica.

Lo staff implica già il follow: una sola membership. Il creatore nasce come primo admin. Serve sempre almeno un admin attivo.

## Visibilità

La sezione `/groups` è solo per chi è loggato.

- **pubblico** — in elenco e in ricerca; si segue subito
- **privato listed** — in elenco e in ricerca; in pagina si vede solo il teaser finché il follow non è accettato
- **privato unlisted** — fuori da elenco e ricerca; solo invito

I pubblici sono sempre listed.

## Contenuti

In bacheca e in calendario arrivano solo le affiliazioni **accepted**.

Si possono proporre eventi, poesie e articoli. Gli eventi di un gruppo figlio accepted vengono inoltrati al padre.

`group_content_links` è l’unica tabella di affiliazione contenuto.

## Chat

Fino a quattro thread, a scelta dell’admin, in creazione o dal pannello staff:

| Chat | Chi entra |
|---|---|
| admin | solo admin |
| editor | admin + editor |
| affiliati | admin del gruppo + admin dei gruppi figli |
| pubblica | staff + follower |

L’adesione segue il ruolo. Le chat libere in `/chat` non dipendono dai gruppi.

## Social

Sulla pagina del gruppo si possono mettere like, commentare, condividere e segnalare, come su eventi e articoli.

- **like e segnalazione** — chi può vedere la pagina, anche dal teaser di un privato listed
- **commenti** — solo chi vede la bacheca (pubblici, o membri dei privati)
- **condivisione** — link della pagina, senza card grafica

Un like o un commento avvisa gli admin del gruppo, non un proprietario singolo.

## Categorie

Obbligatorie in creazione. Le gestisce l’admin di piattaforma, non il gruppo.
