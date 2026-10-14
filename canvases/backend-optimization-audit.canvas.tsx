import {
  Callout,
  Card,
  CardBody,
  CardHeader,
  Code,
  Grid,
  H1,
  H2,
  Pill,
  Row,
  Stack,
  Stat,
  Table,
  Text,
} from "cursor/canvas";

const priorities = [
  [
    "P0",
    "PeerTube fuori dalle GET",
    "GET /media e il dettaglio sincronizzano video pending con HTTP e scritture DB. La lista può processarne 15 in serie.",
    "Job schedulato; GET legge solo il DB.",
  ],
  [
    "P0",
    "Media settings in bulk",
    "ResolveMediaSettingsAction esegue 22 SELECT per chiamata; misurati 22 query / 14,69 ms localmente. I service lo richiamano più volte.",
    "Una query whereIn + cache invalidata dall’admin.",
  ],
  [
    "P0",
    "Mail e notifiche asincrone",
    "Inviti SMTP e notifiche vengono inviati dentro le transazioni evento; il progetto non contiene job, dispatch o scheduler applicativi.",
    "Commit, poi job in coda; batch insert notifiche.",
  ],
  [
    "P0",
    "Indici feed PostgreSQL",
    "Eventi, poesie, articoli e media filtrano stato/visibilità e ordinano per published_at senza un indice composito coerente.",
    "Migration con indici compositi verificati tramite EXPLAIN ANALYZE in produzione.",
  ],
  [
    "P1",
    "Cache HTTP per i18n",
    "Il bundle italiano è circa 64 KB ed è esplicitamente no-store. In produzione: 0,19–0,30 s e cf-cache-status DYNAMIC.",
    "ETag + public max-age; invalidazione tramite versione i18n.",
  ],
  [
    "P1",
    "Ridurre lavoro di bootstrap",
    "AppServiceProvider controlla lo schema e legge SMTP a ogni lifecycle. Misurati 2 query / 2,25 ms per esecuzione.",
    "Configurazione SMTP lazy o cached; niente hasTable nel normale request path.",
  ],
  [
    "P1",
    "Paginare e limitare tutte le collection",
    "Chat messages/threads, help, group events e open gigs non sono paginati. My events/articles e liste pending accettano per_page senza cap.",
    "Summary resource + max 50 + cursor pagination per chat/feed.",
  ],
  [
    "P1",
    "Consolidare dashboard",
    "La dashboard moderazione esegue circa 22 count separati; quella utente usa 8+ aggregate e pagina audit logs senza indice user+created_at.",
    "GROUP BY, cache breve e indice audit_logs(user_id, created_at DESC).",
  ],
  [
    "P2",
    "Ricerca PostgreSQL",
    "Molte ricerche usano LIKE '%term%' su titoli, contenuti, profili e traduzioni: i normali B-tree non aiutano.",
    "pg_trgm/GIN o full-text PostgreSQL dopo misura sui volumi.",
  ],
  [
    "P2",
    "Osservabilità prestazioni",
    "Non risultano slow-query logging applicativo, Server-Timing o strumenti APM/Pulse.",
    "Soglia query lente, query count e p95 per endpoint in produzione.",
  ],
];

const measurements = [
  ["Eventi local", "6 query business", "11,17 ms DB", "0,50–1,18 s HTTP"],
  ["Eventi production", "dataset restituito vuoto", "n/d", "0,19–0,32 s"],
  ["i18n production", "64 KB JSON", "gzip attivo", "0,19–0,30 s"],
  ["Media settings local", "22 query", "14,69 ms DB", "una execute()"],
  ["SMTP bootstrap local", "2 query", "2,25 ms DB", "ogni lifecycle"],
];

export default function BackendOptimizationAudit() {
  return (
    <Stack gap={24} style={{ padding: 24, maxWidth: 1180, margin: "0 auto" }}>
      <Stack gap={8}>
        <Row gap={10} align="center" wrap>
          <H1>Audit ottimizzazione backend</H1>
          <Pill tone="warning">PostgreSQL · Laravel 13</Pill>
        </Row>
        <Text tone="secondary">
          Audit separato dagli N+1: database e indici, bootstrap, cache, API, payload, I/O esterno, code e misure
          locali/produzione. Nessuna modifica applicativa.
        </Text>
      </Stack>

      <Grid columns="repeat(auto-fit, minmax(190px, 1fr))" gap={12}>
        <Stat label="P0" value="4" tone="danger" />
        <Stat label="P1" value="4" tone="warning" />
        <Stat label="P2" value="2" tone="info" />
        <Stat label="Query media settings" value="22" tone="danger" />
      </Grid>

      <Callout tone="danger" title="Il collo di bottiglia strutturale è il lavoro sincrono">
        Il backend configura una queue database, ma il dominio non usa job o scheduler. Upload/sync PeerTube, SMTP,
        OpenProject, notifiche e audit restano nel request path; alcune mail vengono inviate mentre una transazione DB è
        ancora aperta.
      </Callout>

      <Stack gap={12}>
        <H2>Priorità</H2>
        {priorities.map(([level, title, evidence, remediation]) => (
          <Card key={title}>
            <CardHeader
              title={title}
              trailing={<Pill tone={level === "P0" ? "danger" : level === "P1" ? "warning" : "info"}>{level}</Pill>}
            />
            <CardBody>
              <Grid columns="repeat(auto-fit, minmax(280px, 1fr))" gap={16}>
                <Stack gap={4}>
                  <Text size="small" tone="tertiary" weight="semibold">Evidenza</Text>
                  <Text size="small">{evidence}</Text>
                </Stack>
                <Stack gap={4}>
                  <Text size="small" tone="tertiary" weight="semibold">Intervento</Text>
                  <Text size="small">{remediation}</Text>
                </Stack>
              </Grid>
            </CardBody>
          </Card>
        ))}
      </Stack>

      <Stack gap={12}>
        <H2>Misure raccolte</H2>
        <Table
          headers={["Percorso", "Carico", "Database/trasporto", "Tempo"]}
          rows={measurements}
          striped
        />
        <Text size="small" tone="secondary">
          La produzione restituisce attualmente feed pubblici vuoti (89 byte), quindi non consente di misurare il
          comportamento a scala. Gli indici proposti vanno confermati con <Code>EXPLAIN (ANALYZE, BUFFERS)</Code> su
          dati reali.
        </Text>
      </Stack>

      <Stack gap={8}>
        <H2>Sequenza consigliata</H2>
        <Text><Code>1</Code> Queue/scheduler e rimozione PeerTube dalle GET.</Text>
        <Text><Code>2</Code> Resolver media settings bulk + cache; SMTP lazy/cached.</Text>
        <Text><Code>3</Code> Migration indici feed/dashboard, validate con EXPLAIN.</Text>
        <Text><Code>4</Code> Paginazione chat/help/group events e cap universale per_page.</Text>
        <Text><Code>5</Code> ETag/cache HTTP i18n e endpoint pubblici semi-statici.</Text>
        <Text><Code>6</Code> Metriche p95, slow query e query count per endpoint.</Text>
      </Stack>
    </Stack>
  );
}
