import {
  Button,
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
  useCanvasAction,
  useHostTheme,
} from "cursor/canvas";

const findings = [
  {
    severity: "Critica",
    area: "Lista media / PeerTube",
    path: "app/Domain/Media/Actions/ListMediaAction.php",
    evidence:
      "La GET della lista sincronizza fino a 15 video pending in serie. Ogni elemento esegue una chiamata HTTP e richiama più volte un resolver che legge 22 impostazioni con 22 query separate.",
    growth: "≈ 22 query iniziali + fino a ~132 query DB e 1 HTTP per video",
    fix: "Spostare il sync in coda/scheduler; caricare le impostazioni in una query e memoizzarle per request.",
    tone: "danger" as const,
  },
  {
    severity: "Alta",
    area: "Lista thread chat",
    path: "app/Http/Resources/Api/V1/Chat/ChatThreadResource.php",
    evidence:
      "Il resource esegue messages()->count() per thread. Il latestMessage passa inoltre a ChatMessageResource senza reactions, attachments e replyTo.user.profile eager-loaded.",
    growth: "Almeno 1 query/thread; tipicamente 3–6 query/thread",
    fix: "Unread via subquery/withCount; eager load completo di latestMessage.",
    tone: "danger" as const,
  },
  {
    severity: "Alta",
    area: "Liste eventi complete",
    path: "app/Http/Resources/Api/V1/Event/EventResource.php",
    evidence:
      "My events, moderation, pending review e group events usano EventResource senza tutte le relazioni lette dal resource: recurrence, availability, votes e, per l'owner, inviti e gruppi.",
    growth: "Più query per evento; votes può aggiungere una query per opzione",
    fix: "Usare EventSummaryResource nelle liste o allineare gli eager load al dettaglio.",
    tone: "danger" as const,
  },
  {
    severity: "Alta",
    area: "Sync partecipanti poetry slam",
    path: "app/Domain/PoetrySlam/Actions/SyncPoetrySlamEntriesFromEventAction.php",
    evidence:
      "Carica participantInvites.user, poi nel loop legge user.profile: il profilo viene richiesto separatamente per ogni invitato.",
    growth: "1 query profilo/invitato",
    fix: "Eager load event.participantInvites.user.profile.",
    tone: "warning" as const,
  },
  {
    severity: "Media",
    area: "Validazione gruppi evento",
    path: "app/Http/Requests/Api/V1/Event/StoreEventRequest.php",
    evidence:
      "La validazione filtra group_ids ed esegue exists() per ogni ID, anche nell'update che eredita la request.",
    growth: "1 query/group_id",
    fix: "Una query whereIn e confronto degli ID autorizzati.",
    tone: "warning" as const,
  },
];

const safeAreas = [
  ["Eventi pubblici", "EventSummaryResource + eager load mirato", "Sì"],
  ["Poesie", "user/profile, categoria, tipo e conteggi eager-loaded", "Sì"],
  ["Articoli", "user/profile, categoria e conteggi eager-loaded", "Sì"],
  ["Commenti", "autore e replies.user.profile eager-loaded", "Sì"],
  ["Gruppi", "membership e conteggi caricati in blocco", "Sì"],
  ["Utenti admin", "profile e roles eager-loaded", "Sì"],
  ["Report", "reporter, reviewer e morph reportable eager-loaded", "Sì"],
  ["Messaggi chat", "Percorso lista messaggi completo; problema nella lista thread", "Sì"],
];

function FileButton({ path, line }: { path: string; line?: number }) {
  const dispatch = useCanvasAction();
  return (
    <Button
      variant="ghost"
      size="small"
      onClick={() =>
        dispatch({
          type: "openFile",
          path,
          selection: line
            ? { startLineNumber: line, startColumn: 1, endLineNumber: line, endColumn: 1 }
            : undefined,
        })
      }
    >
      Apri file
    </Button>
  );
}

export default function BackendNPlusOneAudit() {
  const theme = useHostTheme();

  return (
    <Stack gap={24} style={{ padding: 24, maxWidth: 1180, margin: "0 auto" }}>
      <Stack gap={8}>
        <Row align="center" gap={10} wrap>
          <H1>Audit N+1 del backend</H1>
          <Pill tone="warning">5 finding confermati</Pill>
        </Row>
        <Text tone="secondary">
          Analisi statica dei controller, action, query, resource e loop del backend Laravel. Il database locale non
          contiene thread chat sufficienti per una misura dinamica rappresentativa; i finding indicati sono comunque
          confermati dal percorso di esecuzione.
        </Text>
      </Stack>

      <Grid columns="repeat(auto-fit, minmax(210px, 1fr))" gap={12}>
        <Stat label="Critici" value="1" tone="danger" />
        <Stat label="Alti" value="3" tone="warning" />
        <Stat label="Medi" value="1" tone="warning" />
        <Stat label="Aree principali sicure" value="8" tone="success" />
      </Grid>

      <Callout tone="danger" title="Prima priorità: rimuovere il sync PeerTube dalla GET">
        La lista media svolge lavoro di sincronizzazione remoto in modo sincrono. Oltre al fan-out HTTP, il resolver
        delle impostazioni legge una riga alla volta e viene richiamato ripetutamente durante ogni sync. Questo può
        produrre latenze molto superiori a un normale N+1 Eloquent.
      </Callout>

      <Stack gap={12}>
        <H2>Finding ordinati per impatto</H2>
        {findings.map((finding, index) => (
          <Card key={finding.area}>
            <CardHeader
              title={`${index + 1}. ${finding.area}`}
              subtitle={finding.path}
              trailing={<Pill tone={finding.tone}>{finding.severity}</Pill>}
            />
            <CardBody>
              <Stack gap={10}>
                <Text>{finding.evidence}</Text>
                <Grid columns="repeat(auto-fit, minmax(260px, 1fr))" gap={12}>
                  <Stack gap={4}>
                    <Text size="small" tone="tertiary" weight="semibold">Crescita</Text>
                    <Text size="small">{finding.growth}</Text>
                  </Stack>
                  <Stack gap={4}>
                    <Text size="small" tone="tertiary" weight="semibold">Correzione</Text>
                    <Text size="small">{finding.fix}</Text>
                  </Stack>
                </Grid>
                <Row justify="end">
                  <FileButton path={finding.path} />
                </Row>
              </Stack>
            </CardBody>
          </Card>
        ))}
      </Stack>

      <Stack gap={12}>
        <H2>Percorsi verificati senza N+1</H2>
        <Table
          headers={["Area", "Evidenza", "Eager load coerente"]}
          rows={safeAreas}
          rowTone={safeAreas.map(() => "success")}
          striped
        />
      </Stack>

      <Stack gap={8}>
        <H2>Ordine di intervento consigliato</H2>
        <Text><Code>1</Code> Lista media: job asincrono + caricamento bulk/cache delle impostazioni.</Text>
        <Text><Code>2</Code> Lista thread chat: unread aggregate + relazioni del latest message.</Text>
        <Text><Code>3</Code> Liste eventi: summary resource oppure eager load completi.</Text>
        <Text><Code>4</Code> Poetry slam e validazione group_ids: due correzioni isolate e a basso rischio.</Text>
        <Text tone="secondary" size="small">
          Esclusi dal conteggio: loop di scrittura intenzionali per notifiche, email e import, che non sono N+1 di
          lettura ma possono essere successivamente ottimizzati con insert/job bulk.
        </Text>
      </Stack>

      <div style={{ borderTop: `1px solid ${theme.stroke.tertiary}`, paddingTop: 12 }}>
        <Text tone="tertiary" size="small">
          Audit del 14 ottobre 2026 · nessuna modifica applicata al codice applicativo.
        </Text>
      </div>
    </Stack>
  );
}
