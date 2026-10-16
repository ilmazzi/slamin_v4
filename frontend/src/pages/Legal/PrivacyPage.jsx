import { Link } from 'react-router-dom'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './TermsPage.module.css'

const definitions = [
  ['GDPR', 'General Data Protection Regulation, EU 2016/679'],
  ['Dati', `secondo il GDPR, i dati sono tutte le informazioni che possono essere utilizzate per identificare una persona, sia direttamente (nome, numero di telefono, indirizzo IP, ecc.) o indirettamente (qualsiasi combinazione dei precedenti oltre a parametri biometrici del dispositivo, cookies, ecc.). Nello specifico contesto dell'utilizzo sulla nostra piattaforma, sono le minime informazioni necessarie perché il sistema funzioni. Ogni informazione accessoria è superflua, quindi non acquisita dal sistema;`],
  ['Servizi', `l'insieme delle funzionalità della piattaforma gestite da slamin.it, e lo scambio d'informazioni tra di esse;`],
  ['Utenza o tu', 'qualsiasi persona o entità esterna che acceda o utilizzi i servizi offerti da slamin.it;'],
  ['Slam in, slamin.it o noi', 'Slam In It OU e chiunque lavori od offra manodopera volontaria per suo conto;'],
  ['Piattaforma', `l'insieme dei servizi offerti da slamin.it`],
  ['Credenziali', `l'insieme di dati di accesso creati e utilizzati dall'utenza per accedere ai nostri servizi;`],
  ['Attacchi', 'qualsiasi tentativo di accesso informatico ai servizi forniti'],
]

const excludedUses = [
  <>raccogliamo qualsiasi altro dato che non serva per offrire i nostri servizi;</>,
  <>processiamo o analizziamo in alcun modo i tuoi comportamenti o le tue caratteristiche per una profilazione tua o dell&apos;uso che fai dei nostri servizi; <strong>NON</strong> facciamo pubblicità, <strong>NON</strong> ospitiamo pubblicità e <strong>NON</strong> abbiamo, o vogliamo avere, rapporti con enti che si occupino di pubblicità;</>,
  <>cediamo o vendiamo i tuoi dati a nessuno;</>,
  <>condividiamo i tuoi dati con terze parti se non in caso d&apos;identificazione per pagamenti o altri servizi esterni che ne richiedano l&apos;utilizzo;</>,
  <>richiediamo informazioni addizionali che non servano per il funzionamento dei servizi (nel caso venissero richiesti, potrebbe trattarsi di un tentativo di frode, consigliamo di contattarci o verificare in altro modo l&apos;autenticità della richiesta);</>,
  <>leggiamo o processiamo, manualmente o automaticamente, i dati e i file inseriti nella piattaforma se non per motivazioni necessarie al funzionamento del servizio, per risoluzione dei problemi o per disambiguazione in caso di sospetta infrazione dei <Link to="/terms">Termini di utilizzo</Link>, ma in questo caso provvederemo a comunicarlo per tempo o a richiedere il permesso, fornendo conto di qualsiasi azione intrapresa in un rapporto di trasparenza inviato all&apos;utenza.</>,
]

const rights = [
  ['diritto di accesso', 'il diritto, in qualsiasi momento, di richiedere copia dei dati personali E di accedere a qualsiasi informazione inserita nel nostro sistema e che possediamo;'],
  ['diritto di correzione', 'il diritto di rettificare i dati in caso di inaccuratezza o incompletezza;'],
  ['diritto di cancellazione', `il diritto di richiedere l'eliminazione o rimozione dei dati dai nostri server*;`],
  [`diritto di limitare l'uso dei tuoi dati`, `il diritto di limitare la profilazione e l'utilizzo che facciamo dei tuoi dati;`],
  ['diritto di portabilità dei dati', 'il diritto di spostare, copiare o trasferire i tuoi dati;'],
  ['diritto di obiezione', 'il diritto di obiettare al nostro utilizzo dei tuoi dati;'],
]

export default function PrivacyPage() {
  return (
    <MainLayout>
      <main className={styles.page}>
        <header className={styles.header}>
          <h1>Normativa sulla Privacy</h1>
          <p className={styles.version}>v1.0 - Dicembre 2025</p>
        </header>

        <section className={styles.introSection}>
          <h2>Riguardo a questo documento</h2>
          <p>Questo documento è stato scritto in inglese e italiano (da una persona madrelingua italiana), queste due lingue sono le uniche per cui slamin.it può essere ritenuta responsabile.</p>
          <p>Qualsiasi traduzione di questa Normativa sulla privacy è da considerarsi uno sforzo della comunità per rendere le informazioni accessibili in altre lingue, e di conseguenza dovrà essere presa in questi termini, con nessun altro valore se non quello informativo.</p>
        </section>

        <section className={styles.introSection}>
          <h2>Definizioni usate in questo documento</h2>
          <dl className={styles.definitions}>
            {definitions.map(([term, description]) => (
              <div key={term}>
                <dt>{term}:</dt>
                <dd>{description}</dd>
              </div>
            ))}
          </dl>
        </section>

        <section className={styles.introSection}>
          <h2>I dati tutelati da questa Informativa</h2>
          <p>Questa informativa si applica a tutti i servizi offerti da slamin.it e da tutti i suoi sottodomini. Non si estende ad altri domini o servizi esterni accessibili dalla piattaforma. Questi servizi possono utilizzare protocolli che, per funzionare, necessitano uno scambio di dati: questo scambio non è contemplato in questa informativa.</p>
          <p>È importante notare che lo scambio di dati con servizi esterni è una scelta dell&apos;utenza (consultare 1. Quali dati raccogliamo?) ed è configurabile dalle utenze tramite le impostazioni dei servizi medesimi, inclusa la scelta di quali dati condividere e con chi.</p>
        </section>

        <section className={styles.introSection}>
          <h2>1. Quali dati raccogliamo?</h2>
          <p>Se una persona o un&apos;organizzazione decide di utilizzare i nostri servizi, saranno necessari (e verranno custoditi da slamin.it) i seguenti dati:</p>
          <ul>
            <li><strong>un indirizzo di posta valido e attivo:</strong> necessario per la creazione dell&apos;account sulla piattaforma. L&apos;indirizzo sarà necessario per il recupero di dati e password, per questo motivo viene archiviato e gestito da slamin.it, solamente per uso interno e gestionale;</li>
            <li><strong>una password:</strong> necessaria per identificare e certificare l&apos;utenza, così che possa accedere alla piattaforma e utilizzarla.</li>
            <li><strong>l’indirizzo email fornito volontariamente per la newsletter:</strong> viene utilizzato esclusivamente per inviare le comunicazioni confermate tramite double opt-in. Non inseriamo pixel di tracciamento e l’iscrizione può essere revocata in qualsiasi momento dal link presente in ogni email.</li>
          </ul>
          <p>Qualsiasi ulteriore dato, compresi quelli di pagamento, che un&apos;utenza sceglie d&apos;inserire e/o condividere sulla piattaforma, sono opzionali, consensuali e non vengono utilizzati per profilazione o altro fine (leggi oltre per qualsiasi chiarimento su questo aspetto)</p>
        </section>

        <section className={`${styles.termSection} ${styles.subsection}`}>
          <h3>1.1 Che facciamo dei dati?</h3>
          <p>L&apos;utilizzo dei dati è unicamente finalizzato al funzionamento della piattaforma.</p>
          <p>Teniamo traccia delle attività compiute dalle utenze durante l&apos;utilizzo della piattaforma, puramente a scopo statistico/ludico interno (parametri utili alla gamificazione), per una continua diagnosi del sistema, per mantenere sicura la piattaforma contro intrusioni e per monitorare la salute del software.</p>
        </section>

        <section className={`${styles.termSection} ${styles.subsection}`}>
          <h3>1.2 Come archiviamo i dati?</h3>
          <p>Per proteggere i tuoi dati utilizziamo le seguenti misure di sicurezza:</p>
          <ul>
            <li>ci affidiamo alla crittazione dei dati sugli hard disk di Hetzner, per prevenire fughe di dati in caso di furto, confisca o manipolazione dei server;</li>
            <li>forniamo e pretendiamo la crittazione SSL/TLS su tutte le comunicazioni &quot;utenza-server&quot; e &quot;server-server&quot;;</li>
            <li>utilizziamo la crittazione &quot;end-to-end&quot; e/o &quot;server-side&quot; quando disponibile dai servizi che la permettono, per fornire la massima sicurezza alle utenze.</li>
          </ul>
        </section>

        <section className={`${styles.termSection} ${styles.subsection}`}>
          <h3>1.3 Come facciamo il backup dei tuoi dati?</h3>
          <p>Per tentare il recupero dei dati in caso di disastri o problematiche, creiamo dei backup su server remoti gestiti da Hetzner. I backup sono giornalieri e vengono mantenuti attivi per un periodo di 7 giorni.</p>
        </section>

        <section className={styles.introSection}>
          <h2>2. Cosa NON facciamo con i tuoi dati!</h2>
          <ul>
            {excludedUses.map((entry, index) => (
              <li key={index}><strong>NON</strong> {entry}</li>
            ))}
          </ul>
        </section>

        <section className={styles.introSection}>
          <h2>3. Dove sono archiviati i dati?</h2>
          <p>Tutti i dati sono archiviati nei server di Hetzner, in Germania.</p>
        </section>

        <section className={styles.introSection}>
          <h2>4. I tuoi diritti</h2>
          <p>Sotto il GDPR si hanno una serie di diritti in merito ai propri dati:</p>
          <ul>
            {rights.map(([right, description]) => (
              <li key={right}><strong>{right}:</strong> {description}</li>
            ))}
          </ul>
          <p className={styles.footnote}>*Nonostante al momento dell&apos;eliminazione di un account, ogni tuo dato in nostro possesso venga eliminato, la mail utilizzata per il login subisce un soft remove, ovvero resta nei nostri database, per evitare che in futuro altre persone malintenzionate possano tentare di impersonificare o rubare i dati di chi non è più sulla piattaforma.</p>
          <p>Hai il diritto di inviarci critiche o richieste, porre domande, esercitare qualsiasi diritto summenzionato, o togliere il consenso all&apos;utilizzo dei tuoi dati (dato che il tuo consenso è la base legale per l&apos;utilizzo dei dati), contattandoci all&apos;indirizzo <a href="mailto:mail@slamin.it">mail@slamin.it</a></p>
          <p>Per gli obiettivi del GDPR, slamin.it è il &quot;gestore dei dati&quot;. Ciò significa che Slam In It OU determina gli scopi e i mezzi con cui vengono trattati i tuoi dati.</p>
        </section>

        <aside className={styles.vatCard}>
          <strong>Slam In It OÜ (17118119)</strong>
          <span>Camera di commercio estone. VAT n: EE102810884</span>
        </aside>

        <section className={`${styles.termSection} ${styles.subsection}`}>
          <h3>4.1 Accesso alle tue informazioni</h3>
          <p>L&apos;accesso ai tuoi dati personali, file caricati e altre informazioni da te fornite, è sotto il tuo diretto controllo. Ciò significa che ogni dato legato a informazioni personali inserito nei nostri servizi che richiedono accesso, è a tua disposizione per essere scaricato per archiviazione o migrazione verso altro servizio.</p>
          <p>Puoi accedere a tutti i tuoi dati nella sezione Modifica profilo, nel menu che appare facendo clic sul tuo nome o il tuo avatar in alto a destra.</p>
        </section>

        <section className={styles.introSection}>
          <h2>5. Modifiche a questa informativa</h2>
          <p>Qualsiasi modifica a questa informativa sulla privacy sarà pubblicamente disponibile e comunicata a tutte le utenze tramite la piattaforma e altri canali. Raccomandiamo di controllare periodicamente questo documento per tenere traccia dei cambiamenti.</p>
          <p>Le differenti versioni verranno archiviate ed elencate qui sotto per consulta, trasparenza e sicurezza.</p>
        </section>

        <p className={styles.updated}>Ultimo aggiornamento: Dicembre 2025</p>
      </main>
    </MainLayout>
  )
}
