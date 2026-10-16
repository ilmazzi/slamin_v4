import { Link } from 'react-router-dom'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './TermsPage.module.css'

const serviceTerms = [
  {
    title: '1. Chi può usare questo servizio?',
    paragraphs: [
      `Chiunque può utilizzare questo servizio. Essendo una comunità artistica, non v'è limite d'età perché vi si possa accedere. Questo implica anche la responsabilità della comunità di tutelare persone minori o sensibili, perché possano godere della piattaforma in un ambiente libero e sicuro.`,
    ],
  },
  {
    title: '2. Limiti di spazio su disco',
    paragraphs: [
      `Le risorse sono limitate (e il mondo è uno: produrre scarto, consumare energia e la noncuranza, affliggono chiunque), e in questo sistema capitalista, sono anche costose (per coloro che non vogliono possedere o "stare sopra" altre persone). Ricorda che caricare file inutili e "spammare" può limitare banda e spazio sul server, cosa che può limitare le possibilità altrui.`,
    ],
    emphasis: 'Non sprecare, per favore!',
  },
  {
    title: '3. Scadenza account',
    paragraphs: [
      'Per ragioni di sicurezza, slamin.it non elimina gli account inutilizzati. Questa pratica potrebbe evolversi nel tempo, se dovessimo recuperare spazio su disco per altre utenze. Questi cambiamenti nei termini di servizio verranno annunciati per tempo attraverso ogni possibile canale.',
      `Apprezziamo la cura di chi si prende la responsabilità del proprio account e dell'impatto che può avere sulle risorse di Slam in, e che preferisce eliminare il proprio account al momento della decisione di non utilizzare più i nostri servizi, eliminando anche i file inutilizzati.`,
    ],
  },
  {
    title: '4. Password',
    paragraphs: [
      `Non teniamo traccia di chiunque richieda l'attivazione di un account su Slam in. Se al momento della creazione dell'account inserisci una mail valida, potrai sempre reimpostare la password di accesso.`,
      'Se non dovessi più avere accesso al tuo account email, non saremo in grado di aiutarti ad accedere a Slam in. Ricorda inoltre di modificare periodicamente la password: puoi farlo nella pagina del tuo profilo.',
    ],
    warning: '! ATTENZIONE ! Lo smarrimento della password può portare alla perdita dei diritti sui propri file.',
  },
  {
    title: '5. SPAM e virus',
    paragraphs: [
      `Perché i nostri servizi non vengano inseriti nelle blacklist, non permettiamo l'uso di Slam in per inviare SPAM. Qualsiasi account utilizzato a questo scopo verrà disabilitato senza possibilità di recupero.`,
      'Se dovessi notare anomalie del sistema o ricevere messaggi insoliti o inappropriati, ti preghiamo di segnalarcelo, così che si possa investigare. Grazie!',
    ],
  },
  {
    title: '6. Copyright',
    paragraphs: [
      'Uno dei principali obiettivi di Slam in è di promuovere il software libero e aperto, e, soprattutto, il copyleft sui contenuti. Accogli e supporta artiste/i e progetti che rilasciano il proprio lavoro sotto questa licenza.',
      `Caricare e condividere pubblicamente materiale sotto copyright su slamin.it può mettere in serio pericolo legale sia chi gestisce, che chi utilizza la piattaforma, oltre che portare a una chiusura dell'intero servizio. Ti preghiamo di non farlo.`,
    ],
  },
  {
    title: '7. Attività non consentite',
    paragraphs: ['Ti preghiamo di non mettere in essere, su slamin.it, le seguenti attività:'],
    list: [
      'molestie o abusi in forma di minaccia, stalking o qualsiasi altro contatto inappropriato;',
      `dare vita, incitare o contribuire (anche con l'inazione quando si assiste a un fatto o un comportamento) a discriminazione, molestia o danno contro persone o gruppi. Ciò include la diffusione di odio e bigottismo attraverso razzismo, etnofobia, antisemitismo, sessismo, omo-transfobia, abilismo e qualsiasi altra forma di comportamento discriminatorio;`,
      `dare vita, incitare o contribuire (anche con l'inazione quando si assiste a un fatto o un comportamento) all'abuso attraverso la distribuzione di materiale per la cui produzione è stata implicata violenza o abuso sessuale o di altra natura, contro persone o animali;`,
      `deridere o insultare altre persone, impersonandole o delineandole in maniera distorta o confondibile, a meno di non dichiararne chiaramente l'intenzione parodistica o satirica;`,
      'pubblicare dati personali altrui. Questa pratica non è concessa perché non ne possiamo gestire motivazioni e conseguenze per le persone coinvolte, ed è un rischio legale troppo oneroso per slamin.it;',
      `l'utilizzo inappropriato dei servizi, comprensivo di distribuzione di virus e malware, attacco denial of service, o il tentativo di ottenere accesso non autorizzato a qualsiasi computer, compreso questo stesso;`,
      'qualsiasi azione non permessa dalla legge nazionale e internazionale.',
    ],
  },
  {
    title: '8. Utilizzo commerciale dei servizi di slamin',
    paragraphs: [
      `Slam in è una comunità che fornisce servizi e promuove l'arte. L'intento è coadiuvare e permettere lo scambio e la conoscenza. Ogni attività commerciale è da compiersi nei limiti offerti dalla piattaforma e consentiti dalla legge, e da considerarsi come donazione, non come contratto vincolante.`,
      `Il trading o l'utilizzo dei servizi per speculazioni di ogni forma sono severamente vietati, pena l'eliminazione dell'account e possibili azioni legali.`,
      `L'utilizzo del meccanismo di donazione per il riciclo di denaro. Un team di contabili segue ogni transazione su slamin.it e ricerca pattern o azioni ricorsive. Slam In It OU non è responsabile e declina ogni responsabilità legale ed economica per questo tipo di utilizzo, o per la perdita di denaro di qualsiasi utenza, a causa di attacchi o truffe informatiche.`,
      `Nel caso non si avesse la certezza che l'utilizzo commerciale della piattaforma sia nei limiti della legalità, o si dovesse assistere o venire a conoscenza di abusi di natura economica, consigliamo caldamente di segnalarlo immediatamente a chi amministra la piattaforma.`,
      `L'utilizzo commerciale di Slam in, in casi sospetti, verrà analizzato e la decisione di chiusura degli account coinvolti verrà comunicata chiarendo il più possibile possibili abusi.`,
      'Per qualsiasi dubbio sulla terminologia utilizzata in questi punti, è possibile contattare chi gestisce la piattaforma.',
    ],
  },
  {
    title: '9. Possesso e responsabilità dei contenuti',
    paragraphs: [
      `Slamin.it non è responsabile dei contenuti pubblicati o di qualsiasi altro servizio. Chi utilizza la piattaforma è responsabile dei propri dati e delle proprie azioni, e per qualsiasi conseguenza da esse derivanti. In ogni caso, per preservare l'esistenza della piattaforma, ci riserviamo il diritto di eliminare qualsiasi contenuto che violi questi termini e le leggi.`,
    ],
  },
  {
    title: '10. Responsabilità legali',
    paragraphs: [
      'Per qualsiasi altra attività, ricordiamo che slamin.it non è responsabile di ciò che le sue utenze scrivono o pubblicano, né della salvaguardia della loro privacy. Invitiamo quindi a usare ogni strumento possibile per difendere i propri diritti.',
    ],
    privacy: true,
  },
  {
    title: '11. Leggi',
    paragraphs: [
      `Slam In It OU è un'azienda con base in Estonia, quindi risponde delle leggi di questo paese. Slamin.it è ospitato su server tedeschi, quindi risponde delle leggi di questo paese.`,
    ],
  },
  {
    title: '12. Chiusura account',
    paragraphs: [
      `Slamin.it può chiudere qualsiasi account in qualsiasi momento, all'insorgere di queste condizioni:`,
    ],
    list: [
      `l'account è stato segnalato o scoperto a inviare SPAM (eccessiva quantità di messaggi non richiesti);`,
      `l'account ha commesso una delle attività illecite elencate in questo documento;`,
    ],
    after: 'Le condizioni qui riportare sono e saranno sempre in evoluzione e quindi passibili di cambiamento.',
  },
  {
    title: '13. Niente garanzia',
    paragraphs: [
      'È dato per compreso e accettato che slamin.it offre un servizio online; per questo motivo, i servizi vengono offerti come possibile, ovvero soggetti a disponibilità e senza obblighi verso nessuno. Nonostante si speri di non deludere nessuna, non possiamo dare garanzia di affidabilità, accessibilità o qualità del servizio.',
      `Viene considerato accettato che l'utilizzo dei servizi è a vostro rischio e vostra responsabilità.`,
    ],
  },
]

const communityGuidelines = [
  {
    title: '1. Rispetta se vuoi ricevere rispetto',
    paragraphs: [
      `Insulti, commenti e discriminazione verso un'altra persona o un gruppo, riguardo a genere o condizioni sociali, razziali, fisiche, mentali o ideologiche, sono considerati comportamenti violenti e non sono tollerati. In questo vengono annoverate anche minacce e molestie.`,
      `La giustizia trasformativa che seguiamo e promuoviamo, accetta, accoglie e promuove il dibattito e la cura reciproca, l'ascolto e l'accoglienza, perché chiunque, persona abusata o abusante, possa sentirsi accolta, tutelata e al sicuro in quanto vittima e libera di comprendere e migliorare in quanto consapevole o inconsapevole abusante.`,
      'I comportamenti considerati maligni, portano a una sospensione parziale o totale del rispetto che è diritto di ogni essere vivente.',
    ],
  },
  {
    title: '2. Non perpetrare, alimentare o permettere abusi',
    paragraphs: [
      'La creazione e diffusione di materiale la cui produzione ha generato violenza, aggressione sessuale o sfruttamento di persone adulte o minori non è ammessa in questa comunità.',
    ],
  },
  {
    title: '3. Non promuovere o incitare alla violenza',
    paragraphs: [
      'Fascismo, nazismo e qualsiasi altra idea politica o religiosa che promuove o incita alla violenza contro gruppi o singole persone non è tollerata in questa comunità.',
      `Nel caso qualsiasi membro della comunità non rispetti o violi intenzionalmente una di queste linee guida, si agirà di conseguenza bloccando o eliminando immediatamente e senza preavviso l'account.`,
    ],
  },
  {
    title: '4. Prendiamoci cura di tempo e spazio di chiunque',
    paragraphs: [
      `La natura aperta e onnicomprensiva della piattaforma offre molte possibilità d'interazione con persone differenti, aumentando così le possibilità di incontrare bot o persone con cui sarà difficile interagire. Per questo motivo chiunque faccia parte della comunità ha a disposizione uno strumento di moderazione e segnalazione che permette tutela e cura.`,
      'È possibile segnalare qualsiasi abuso o infrazione di Termini e condizioni di servizio tramite il form, oppure contattando amministratrici e amministratori della piattaforma.',
      `Ogni utenza ha la possibilità di silenziare le notifiche nella chat, oppure direttamente bloccare un'altra utenza. Così come chiunque ha la libertà di parlare, chiunque ha la libertà di ascoltare o meno. Se una persona non vuole interagire, ne ha il pieno diritto e nessun'altra persona può precluderglielo.`,
      `Segnalare un'utenza è una risorsa da utilizzare solo e soltanto se si ha la piena consapevolezza e certezza che si stiano violando i presenti Termini di servizio e le linee guida della comunità, oppure se gli strumenti a disposizione non siano sufficienti alla gestione del caso.`,
      `Chi amministra la piattaforma ha il diritto d'intervenire per qualsiasi necessità, ed applicare le misure adatte.`,
      'Il rispetto e la cura della comunità sono una responsabilità collettiva di chiunque ne faccia parte.',
    ],
  },
]

function LegalSection({ item }) {
  return (
    <section className={styles.termSection}>
      <h3>{item.title}</h3>
      {item.paragraphs.map((paragraph) => <p key={paragraph}>{paragraph}</p>)}
      {item.list ? (
        <ul>
          {item.list.map((entry) => <li key={entry}>{entry}</li>)}
        </ul>
      ) : null}
      {item.emphasis ? <p className={styles.emphasis}>{item.emphasis}</p> : null}
      {item.warning ? <p className={styles.warning}>{item.warning}</p> : null}
      {item.privacy ? (
        <p>
          Per conoscere quali informazioni vengono raccolte e come vengono utilizzate, è possibile consultare la{' '}
          <Link to="/privacy">Privacy policy</Link>.
        </p>
      ) : null}
      {item.after ? <p>{item.after}</p> : null}
    </section>
  )
}

export default function TermsPage() {
  return (
    <MainLayout>
      <main className={styles.page}>
        <header className={styles.header}>
          <h1>Termini di Servizio</h1>
          <p className={styles.version}>v1.0 - Dicembre 2025</p>
          <p className={styles.lead}>
            Utilizzando i servizi offerti da slamin.it si accettano i seguenti Termini di servizio:
          </p>
        </header>

        <section className={styles.introSection}>
          <h2>Riguardo a questo documento</h2>
          <p>Questo documento è stato scritto in inglese e italiano (da una persona madrelingua italiana), queste due lingue sono le uniche per cui slamin.it può essere ritenuta responsabile.</p>
          <p>Qualsiasi traduzione di questi Termini di servizio è da considerarsi uno sforzo della comunità per rendere le informazioni accessibili in altre lingue, e di conseguenza dovrà essere presa in questi termini, con nessun altro valore se non quello informativo.</p>
        </section>

        <section className={styles.introSection}>
          <h2>Accettazione dei Termini</h2>
          <p>Con l&apos;utilizzo dei servizi offerti da slamin.it si accetta di essere legati a questi Termini di servizio. È perciò consigliata la lettura di questi termini prima del primo utilizzo della piattaforma.</p>
        </section>

        <section className={styles.introSection}>
          <h2>Avviso di comunità</h2>
          <p>Slamin è una comunità e la nostra piattaforma aderisce ed è parte dell&apos;ecosistema del software libero e open-source. Condividiamo le risorse materiali dei server, collaboriamo e usiamo i servizi di slamin.it insieme. È importante che chiunque comprenda e si senta equamente responsabile per essa. Perciò chiediamo gentilmente che si tengano a mente e seguano queste linee guida, così da convivere felicemente.</p>
        </section>

        <section className={styles.documentSection}>
          <h2>Termini di servizio</h2>
          {serviceTerms.map((item) => <LegalSection key={item.title} item={item} />)}
        </section>

        <section className={styles.documentSection}>
          <h2>Linee guida di base per la coesistenza in una comunità</h2>
          <div className={styles.communityIntro}>
            <p>Slam in è una comunità, ciò significa un gruppo di persone che condividono cose in comune. E la più comune delle cose che condividiamo, oltre all&apos;arte e all&apos;essere utenze della piattaforma, è la differenza, sia nell&apos;essere che nel pensare, nel sentire e nel comunicare.</p>
            <p>Questi Termini di servizio gestiscono genericamente tutti i servizi, ma in quelli costruiti per l&apos;interazione tra persone è necessario garantire l&apos;esercizio delle nostre differenze in condizioni di rispetto e sicurezza, così che chiunque possa esprimersi senza paura di giudizio o maltrattamento di qualsiasi tipo.</p>
            <p>Per tentare di ottenere questo abbiamo stabilito (e mutuato - principalmente da disroot.org e altre realtà anarchiche e autonome) una serie di condizioni di base per la cooperazione. Queste condizioni non pretendono di essere perfette e monolitiche, quindi speriamo possano cambiare per il meglio. Quando necessario, la comunità verrà informata di suddetti cambiamenti.</p>
            <p>Nel caso considerassi che una qualsiasi di queste condizioni contraddica o non si adatti ai tuoi principi oppure ti limiti in qualsiasi forma o condizione, puoi decidere di non utilizzare i nostri servizi, cercarne altri in cui puoi sentire più libertà e comunicarci rispettosamente qualsiasi critica costruttiva credi rilevante. Ci riserviamo la possibilità di accogliere, applicare e promuovere, o meno, qualsiasi di queste comunicazioni.</p>
          </div>
          {communityGuidelines.map((item) => <LegalSection key={item.title} item={item} />)}
        </section>
      </main>
    </MainLayout>
  )
}
