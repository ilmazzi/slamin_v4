import { createContext, createElement, useContext, useEffect, useRef, useState } from 'react'
import { getApiUrl } from '../api/url'
import { DEFAULT_LOCALE, getClientLocale, localeHeaders, LOCALE_STORAGE_KEY } from '../i18n/locale.js'

const resolveKeyPath = (source, key) => {
  return key.split('.').reduce((acc, part) => acc?.[part], source)
}

const applyReplacements = (value, replacements) => {
  if (!replacements || typeof replacements !== 'object') {
    return value
  }

  return Object.entries(replacements)
    .sort(([a], [b]) => b.length - a.length)
    .reduce(
    (current, [name, replacement]) => current.replaceAll(`:${name}`, String(replacement)),
    value,
  )
}

const staticFallbacks = {
  it: {
    common: {
      loading: 'Caricamento...',
      no_results: 'Nessun risultato',
      groups: 'Gruppi',
    },
    statistics: {
      title: 'Le tue statistiche',
      subtitle: 'Scopri come la community interagisce con ciò che pubblichi.',
      private_label: 'Solo per te',
      back: 'Torna alla dashboard',
      view_all: 'Vedi tutte le statistiche',
      loading: 'Caricamento statistiche...',
      load_error: 'Non è stato possibile caricare le statistiche.',
      retry: 'Riprova',
      metrics_title: 'Interazioni ricevute',
      metrics: {
        views_received: 'visualizzazioni ricevute',
        likes_received: 'mi piace ricevuti',
        comments_received: 'commenti ricevuti',
        followers: 'follower',
      },
      publications_title: 'Le tue pubblicazioni',
      publications_subtitle: 'Sono conteggiati solo i contenuti pubblicati e visibili a tutti.',
      publications: { poems: 'Poesie', articles: 'Articoli', events: 'Eventi', media: 'Media' },
      top_views: 'Più visualizzati',
      top_likes: 'Più apprezzati',
      empty: 'Non ci sono ancora dati per questa classifica.',
      untitled: 'Senza titolo',
      item_metrics: 'Visualizzazioni, mi piace e commenti',
      content_types: { poem: 'Poesia', article: 'Articolo', event: 'Evento', media: 'Media' },
    },
    poems: {
      form: {
        edit_title: 'Modifica poesia',
        edit_lead: 'Aggiorna il testo e le informazioni della tua poesia.',
        error_not_owner: 'Non puoi modificare questa poesia.',
        error_updating_poem: 'Impossibile aggiornare la poesia.',
        success_updating_poem: 'Poesia aggiornata con successo.',
      },
    },
    articles: {
      form: {
        edit_title: 'Modifica articolo',
        edit_lead: 'Aggiorna il testo e le informazioni del tuo articolo.',
        error_not_owner: 'Non puoi modificare questo articolo.',
        error_updating_article: 'Impossibile aggiornare l’articolo.',
        success_updating_article: 'Articolo aggiornato con successo.',
      },
    },
    contact: {
      title: 'Contattaci',
      subtitle: 'Hai domande? Siamo qui per aiutarti',
      intro: 'Per qualsiasi domanda, suggerimento o supporto, non esitare a contattarci.',
      email_label: 'E-mail',
    },
    about: {
      title: 'chi siamo',
      statement_first: 'due entità,',
      statement_second: 'una community open-source',
      entities_label: 'Le realtà dietro Slam In',
      slamin_description: 'Slam In It OÜ è un’azienda estone che ha avuto l’idea di creare una piattaforma mondiale per la poesia e gli eventi.',
      contribute: 'Se ti interessa contribuire alla comunità, contattaci via',
      haevol_description: 'Hævol è un’impresa sociale italiana. Sviluppa infrastrutture open-source per progetti culturali e umanitari',
    },
    support: {
      title: 'Supporto',
      subtitle: 'Hai bisogno di aiuto? Compila il form sottostante e il nostro team ti risponderà il prima possibile.',
      name: 'Nome',
      name_placeholder: 'Il tuo nome completo',
      email: 'E-mail',
      email_placeholder: 'La tua e-mail',
      category: 'Categoria',
      category_placeholder: 'Seleziona una categoria',
      subject: 'Oggetto',
      subject_placeholder: 'Descrivi brevemente il problema',
      message: 'Messaggio',
      message_placeholder: 'Descrivi il tuo problema in dettaglio...',
      message_help: 'Più dettagli fornisci, più velocemente potremo aiutarti.',
      attachments: 'Allegati',
      optional: '(opzionale)',
      choose_files: 'Scegli file',
      no_files: 'Nessun file selezionato',
      attachments_help: 'Puoi allegare fino a 5 screenshot o file (max 10MB per file)',
      cancel: 'Annulla',
      submit: 'Invia richiesta',
      sending: 'Invio...',
      success: 'La richiesta è stata inviata. Ti risponderemo il prima possibile.',
      error: 'Non è stato possibile inviare la richiesta. Riprova più tardi.',
      response_title: 'Tempi di risposta',
      response_text: 'Solitamente rispondiamo entro 24-48 ore nei giorni lavorativi. Per urgenze, contattaci direttamente a',
      categories: {
        technical: 'Problema tecnico',
        account: 'Account e accesso',
        content: 'Contenuti e moderazione',
        events: 'Eventi e ingaggi',
        media: 'Foto e video',
        other: 'Altro',
      },
    },
    profile: {
      page: {
        spoken_languages: 'Lingue parlate',
        spoken_languages_placeholder: 'Italiano, Inglese, Spagnolo',
        spoken_languages_hint: 'Separa le lingue con una virgola. Saranno visibili nel tuo profilo pubblico.',
      },
    },
    chat: {
      ui: {
        new_group: 'Nuova chat di gruppo',
        cancel_group: 'Annulla',
        create_group: 'Crea gruppo',
        group_title_placeholder: 'Nome del gruppo',
        group_members_hint: 'Cerca e aggiungi almeno un membro.',
        group_create_error: 'Impossibile creare la chat di gruppo.',
        members: 'Membri',
        members_count: ':count membri',
        open_members: 'Membri del gruppo',
        close_members: 'Chiudi membri',
        add_member_placeholder: 'Aggiungi un membro…',
        add_member_error: 'Impossibile aggiungere il membro.',
        remove_member: 'Rimuovi',
        remove_member_confirm: 'Rimuovere :name dal gruppo?',
        remove_member_error: 'Impossibile rimuovere il membro.',
        creator: 'Creatore',
      },
    },
    group: {
      visibility: {
        public: 'Pubblico',
        private: 'Privato',
      },
      roles: {
        admin: 'admin',
        editor: 'editor',
        follower: 'follower',
      },
      ui: {
        title: 'gruppi',
        lead_prefix: 'pagine della',
        lead_emphasis: 'community',
        loading: 'Caricamento gruppi...',
        empty: 'Non ci sono ancora gruppi da mostrare.',
        load_error: 'Impossibile caricare i gruppi.',
        create: 'Crea un gruppo',
        create_title: 'nuovo gruppo',
        create_lead_prefix: 'crea un',
        create_lead_emphasis: 'gruppo',
        create_lead: 'Una pagina della community, con staff e follower.',
        edit: 'Modifica',
        edit_title: 'modifica gruppo',
        edit_lead_prefix: 'modifica il',
        edit_lead_emphasis: 'gruppo',
        edit_lead: 'Aggiorna immagine, testi, visibilità e link della pagina.',
        back_to_group: 'Torna al gruppo',
        save_changes: 'Salva modifiche',
        saving_changes: 'Salvataggio...',
        update_error: 'Impossibile aggiornare il gruppo.',
        update_forbidden: 'Solo gli admin del gruppo possono modificarlo.',
        discover: 'Scopri',
        no_description: 'Nessuna descrizione.',
        search_placeholder: 'Cerca un gruppo',
        search_label: 'Cerca gruppi',
        mine: 'I miei gruppi',
        all: 'Tutti i gruppi',
        follow: 'Segui',
        following: 'Segui già',
        follow_pending: 'Richiesta inviata',
        leave: 'Smetti di seguire',
        request_editor: 'Chiedi di diventare editor',
        request_admin: 'Chiedi di diventare admin',
        role_pending: 'Richiesta di ruolo in attesa',
        private_teaser: 'Questo gruppo è privato. Puoi chiedere di seguirlo o di entrare nello staff.',
        back: 'Tutti i gruppi',
        followers: ':count follower',
        membership: 'Partecipazione',
        links: 'Link',
        image: 'Immagine',
        image_help: 'Opzionale. JPG, PNG o WebP, massimo 5 MB.',
        image_remove: 'Rimuovi immagine',
        staff: 'Staff',
        staff_tab_inbox: 'Da fare',
        staff_tab_events: 'Eventi',
        staff_tab_people: 'Persone',
        staff_tab_affiliations: 'Affiliazioni',
        staff_tab_chat: 'Chat',
        staff_chat_lead: 'Accendi o spegni le chat per ruolo. I membri entrano in automatico.',
        chat_title: 'Chat del gruppo',
        chat_lead: 'Sono opzionali. Puoi accenderle o spegnerle dopo dal pannello staff.',
        chat_audience_admin: 'admin',
        chat_audience_editor: 'editor',
        chat_audience_affiliates: 'affiliati',
        chat_audience_public: 'pubblica',
        chat_audience_admin_help: 'Solo gli admin del gruppo.',
        chat_audience_editor_help: 'Admin ed editor.',
        chat_audience_affiliates_help: 'Admin del gruppo e admin dei gruppi figli.',
        chat_audience_public_help: 'Staff e follower.',
        staff_inbox_events: 'Eventi da accettare',
        staff_inbox_affiliations: 'Affiliazioni da accettare',
        staff_affiliation: 'Affiliazione',
        staff_affiliation_connect: 'Collega un gruppo',
        staff_affiliation_lead: 'Il gruppo padre può ricevere gli eventi accettati dal figlio.',
        staff_affiliation_search: 'Gruppo',
        staff_affiliation_placeholder: 'Cerca un gruppo',
        staff_affiliation_ask_parent: 'Chiedi di entrare sotto',
        staff_affiliation_invite_child: 'Invita come gruppo figlio',
        staff_affiliation_incoming_parent: ':name vuole che questo gruppo sia un suo gruppo figlio',
        staff_affiliation_incoming_child: ':name vuole entrare sotto questo gruppo',
        staff_affiliation_outgoing_parent: 'Hai chiesto di entrare sotto :name',
        staff_affiliation_outgoing_child: 'Hai invitato :name come gruppo figlio',
        staff_affiliation_sent: 'Inviata',
        staff_outgoing_affiliations: 'Richieste inviate',
        staff_parents: 'Gruppi padre',
        staff_children: 'Gruppi figlio',
        staff_parent: 'Padre',
        staff_child: 'Figlio',
        staff_empty_parents: 'Questo gruppo non ha ancora un padre.',
        staff_empty_children: 'Questo gruppo non ha ancora gruppi figlio.',
        staff_forwarded_by: 'Inoltrato da :name',
        staff_inbox_people: 'Persone da accettare',
        staff_empty_inbox: 'Niente in attesa.',
        staff_proposed_by: 'Proposto da :name',
        staff_auto_accept_help: 'Le nuove proposte diventano subito visibili in pagina.',
        staff_invite_lead: 'Aggiungi una persona al gruppo e assegna un ruolo.',
        staff_members_count: ':count nel gruppo',
        staff_on_page: 'In pagina',
        staff_requests: 'Richieste',
        staff_people: 'Persone',
        staff_invite: 'Invita',
        staff_empty_requests: 'Nessuna richiesta in attesa.',
        staff_empty_people: 'Nessuna persona nel gruppo.',
        staff_follow_request: 'Chiede di seguire',
        staff_role_request: 'Chiede di diventare :role',
        staff_approve: 'Accetta',
        staff_reject: 'Rifiuta',
        staff_remove: 'Rimuovi',
        staff_remove_confirm: 'Rimuovere :name dal gruppo?',
        staff_role_confirm: 'Cambiare il ruolo di :name in :role?',
        staff_invite_search: 'Persona',
        staff_invite_placeholder: 'Cerca per nome o nickname',
        staff_invite_role: 'Ruolo',
        staff_invite_submit: 'Invita',
        staff_inviting: 'Invito...',
        staff_searching: 'Ricerca...',
        staff_no_users: 'Nessun risultato',
        staff_load_error: 'Impossibile caricare lo staff.',
        staff_pending_count: ':count in attesa',
        staff_you: '(tu)',
        staff_last_admin: 'Ultimo admin',
        staff_events: 'Eventi proposti',
        staff_empty_events: 'Nessun evento in attesa.',
        staff_accepted_events: 'Eventi in pagina',
        staff_empty_accepted_events: 'Nessun evento in pagina.',
        staff_auto_accept: 'Accetta sempre gli eventi proposti',
        staff_event_remove: 'Togli dalla pagina',
        staff_event_remove_confirm: 'Togliere ":title" dalla pagina del gruppo?',
        feed: 'Bacheca',
        feed_compose: 'Nuovo post',
        feed_compose_title: 'Pubblica in bacheca',
        feed_event: 'Evento',
        event_highlight_title_one: 'Fa parte di un gruppo',
        event_highlight_title_other: 'Fa parte di :count gruppi',
        event_highlight_lead: 'Questo evento è sulla pagina della community.',
        poem_highlight_lead: 'Questa poesia è sulla pagina della community.',
        article_highlight_lead: 'Questo articolo è sulla pagina della community.',
        event_highlight_open: 'Vai al gruppo',
        propose_search: 'Cerca un gruppo',
        propose_empty: 'Nessun gruppo trovato',
        propose_selected: 'Proposto a',
        propose_label: 'Proponi a un gruppo',
        feed_poem: 'Poesia',
        feed_article: 'Articolo',
        staff_inbox_publications: 'Poesie e articoli da accettare',
        staff_accepted_publications: 'Poesie e articoli in pagina',
        staff_empty_accepted_publications: 'Nessuna poesia o articolo in pagina.',
        feed_title: 'Titolo',
        feed_content: 'Messaggio',
        feed_publish: 'Pubblica',
        feed_publishing: 'Pubblicazione...',
        feed_publish_error: 'Impossibile pubblicare il post.',
        feed_empty: 'Non ci sono ancora post in bacheca.',
        feed_locked: 'La bacheca è visibile dopo che la richiesta di follow è stata accettata.',
        feed_load_error: 'Impossibile caricare la bacheca.',
        feed_delete: 'Rimuovi',
        feed_delete_confirm: 'Rimuovere il post ":title"?',
        feed_delete_error: 'Impossibile rimuovere il post.',
        feed_pinned: 'In evidenza',
        calendar_title_accent: 'calendario',
        calendar_title_middle: 'eventi',
        calendar_title_tail: 'del gruppo',
      },
    },
  },
  en: {
    common: {
      loading: 'Loading...',
      no_results: 'No results',
      groups: 'Groups',
    },
    statistics: {
      title: 'Your statistics',
      subtitle: 'See how the community interacts with the things you publish.',
      private_label: 'Only for you',
      back: 'Back to dashboard',
      view_all: 'View all statistics',
      loading: 'Loading statistics...',
      load_error: 'Statistics could not be loaded.',
      retry: 'Try again',
      metrics_title: 'Interactions received',
      metrics: {
        views_received: 'views received',
        likes_received: 'likes received',
        comments_received: 'comments received',
        followers: 'followers',
      },
      publications_title: 'Your publications',
      publications_subtitle: 'Only published content visible to everyone is counted.',
      publications: { poems: 'Poems', articles: 'Articles', events: 'Events', media: 'Media' },
      top_views: 'Most viewed',
      top_likes: 'Most liked',
      empty: 'There is no data for this ranking yet.',
      untitled: 'Untitled',
      item_metrics: 'Views, likes and comments',
      content_types: { poem: 'Poem', article: 'Article', event: 'Event', media: 'Media' },
    },
    poems: {
      form: {
        edit_title: 'Edit poem',
        edit_lead: 'Update your poem text and details.',
        error_not_owner: 'You cannot edit this poem.',
        error_updating_poem: 'Unable to update the poem.',
        success_updating_poem: 'Poem updated successfully.',
      },
    },
    articles: {
      form: {
        edit_title: 'Edit article',
        edit_lead: 'Update your article text and details.',
        error_not_owner: 'You cannot edit this article.',
        error_updating_article: 'Unable to update the article.',
        success_updating_article: 'Article updated successfully.',
      },
    },
    contact: {
      title: 'Contact us',
      subtitle: 'Have questions? We are here to help',
      intro: 'For any questions, suggestions or support, please do not hesitate to contact us.',
      email_label: 'Email',
    },
    about: {
      title: 'about us',
      statement_first: 'two organizations,',
      statement_second: 'one open-source community',
      entities_label: 'The organizations behind Slam In',
      slamin_description: 'Slam In It OÜ is an Estonian company that came up with the idea of creating a worldwide platform for poetry and events.',
      contribute: 'If you are interested in contributing to the community, contact us at',
      haevol_description: 'Hævol is an Italian social enterprise. It develops open-source infrastructure for cultural and humanitarian projects.',
    },
    support: {
      title: 'Support',
      subtitle: 'Need help? Fill in the form below and our team will get back to you as soon as possible.',
      name: 'Name',
      name_placeholder: 'Your full name',
      email: 'Email',
      email_placeholder: 'Your email',
      category: 'Category',
      category_placeholder: 'Select a category',
      subject: 'Subject',
      subject_placeholder: 'Briefly describe the problem',
      message: 'Message',
      message_placeholder: 'Describe your problem in detail...',
      message_help: 'The more details you provide, the faster we can help.',
      attachments: 'Attachments',
      optional: '(optional)',
      choose_files: 'Choose files',
      no_files: 'No files selected',
      attachments_help: 'You can attach up to 5 screenshots or files (max 10MB per file)',
      cancel: 'Cancel',
      submit: 'Send request',
      sending: 'Sending...',
      success: 'Your request has been sent. We will reply as soon as possible.',
      error: 'Your request could not be sent. Please try again later.',
      response_title: 'Response times',
      response_text: 'We usually reply within 24-48 hours on business days. For urgent matters, contact us directly at',
      categories: {
        technical: 'Technical issue',
        account: 'Account and access',
        content: 'Content and moderation',
        events: 'Events and opportunities',
        media: 'Photos and videos',
        other: 'Other',
      },
    },
    profile: {
      page: {
        spoken_languages: 'Spoken languages',
        spoken_languages_placeholder: 'Italian, English, Spanish',
        spoken_languages_hint: 'Separate languages with commas. They will appear on your public profile.',
      },
    },
    chat: {
      ui: {
        new_group: 'New group chat',
        cancel_group: 'Cancel',
        create_group: 'Create group',
        group_title_placeholder: 'Group name',
        group_members_hint: 'Search and add at least one member.',
        group_create_error: 'Unable to create the group chat.',
        members: 'Members',
        members_count: ':count members',
        open_members: 'Group members',
        close_members: 'Close members',
        add_member_placeholder: 'Add a member…',
        add_member_error: 'Unable to add the member.',
        remove_member: 'Remove',
        remove_member_confirm: 'Remove :name from the group?',
        remove_member_error: 'Unable to remove the member.',
        creator: 'Creator',
      },
    },
    group: {
      visibility: {
        public: 'Public',
        private: 'Private',
      },
      roles: {
        admin: 'admin',
        editor: 'editor',
        follower: 'follower',
      },
      ui: {
        title: 'groups',
        lead_prefix: 'pages of the',
        lead_emphasis: 'community',
        loading: 'Loading groups...',
        empty: 'There are no groups to show yet.',
        load_error: 'Unable to load groups.',
        create: 'Create a group',
        create_title: 'new group',
        create_lead_prefix: 'create a',
        create_lead_emphasis: 'group',
        create_lead: 'A community page, with staff and followers.',
        edit: 'Edit',
        edit_title: 'edit group',
        edit_lead_prefix: 'edit the',
        edit_lead_emphasis: 'group',
        edit_lead: 'Update the page image, copy, visibility and links.',
        back_to_group: 'Back to group',
        save_changes: 'Save changes',
        saving_changes: 'Saving...',
        update_error: 'Unable to update the group.',
        update_forbidden: 'Only group admins can edit this page.',
        discover: 'Discover',
        no_description: 'No description.',
        search_placeholder: 'Search for a group',
        search_label: 'Search groups',
        mine: 'My groups',
        all: 'All groups',
        follow: 'Follow',
        following: 'Following',
        follow_pending: 'Request sent',
        leave: 'Unfollow',
        request_editor: 'Ask to become editor',
        request_admin: 'Ask to become admin',
        role_pending: 'Role request pending',
        private_teaser: 'This group is private. You can ask to follow it or to join the staff.',
        back: 'All groups',
        followers: ':count followers',
        membership: 'Membership',
        links: 'Links',
        image: 'Image',
        image_help: 'Optional. JPG, PNG or WebP, max 5 MB.',
        image_remove: 'Remove image',
        staff: 'Staff',
        staff_tab_inbox: 'To do',
        staff_tab_events: 'Events',
        staff_tab_people: 'People',
        staff_tab_affiliations: 'Affiliations',
        staff_tab_chat: 'Chat',
        staff_chat_lead: 'Turn role chats on or off. People join automatically from their role.',
        chat_title: 'Group chats',
        chat_lead: 'They are optional. You can turn them on or off later from the staff panel.',
        chat_audience_admin: 'admin',
        chat_audience_editor: 'editor',
        chat_audience_affiliates: 'affiliates',
        chat_audience_public: 'public',
        chat_audience_admin_help: 'Only the group admins.',
        chat_audience_editor_help: 'Admins and editors.',
        chat_audience_affiliates_help: 'Group admins and admins of child groups.',
        chat_audience_public_help: 'Staff and followers.',
        staff_inbox_events: 'Events to review',
        staff_inbox_affiliations: 'Affiliations to review',
        staff_affiliation: 'Affiliation',
        staff_affiliation_connect: 'Connect a group',
        staff_affiliation_lead: 'A parent group can receive events accepted by the child.',
        staff_affiliation_search: 'Group',
        staff_affiliation_placeholder: 'Search for a group',
        staff_affiliation_ask_parent: 'Ask to join under it',
        staff_affiliation_invite_child: 'Invite as child group',
        staff_affiliation_incoming_parent: ':name wants this group as a child',
        staff_affiliation_incoming_child: ':name wants to join under this group',
        staff_affiliation_outgoing_parent: 'You asked to join under :name',
        staff_affiliation_outgoing_child: 'You invited :name as a child group',
        staff_affiliation_sent: 'Sent',
        staff_outgoing_affiliations: 'Sent requests',
        staff_parents: 'Parent groups',
        staff_children: 'Child groups',
        staff_parent: 'Parent',
        staff_child: 'Child',
        staff_empty_parents: 'This group has no parent yet.',
        staff_empty_children: 'This group has no child groups yet.',
        staff_forwarded_by: 'Forwarded from :name',
        staff_inbox_people: 'People to review',
        staff_empty_inbox: 'Nothing waiting.',
        staff_proposed_by: 'Proposed by :name',
        staff_auto_accept_help: 'New proposals appear on the page immediately.',
        staff_invite_lead: 'Add someone to the group and choose a role.',
        staff_members_count: ':count in the group',
        staff_on_page: 'On the page',
        staff_requests: 'Requests',
        staff_people: 'People',
        staff_invite: 'Invite',
        staff_empty_requests: 'There are no pending requests.',
        staff_empty_people: 'There is nobody in this group yet.',
        staff_follow_request: 'Wants to follow',
        staff_role_request: 'Wants to become :role',
        staff_approve: 'Accept',
        staff_reject: 'Decline',
        staff_remove: 'Remove',
        staff_remove_confirm: 'Remove :name from the group?',
        staff_role_confirm: "Change :name's role to :role?",
        staff_invite_search: 'Person',
        staff_invite_placeholder: 'Search by name or nickname',
        staff_invite_role: 'Role',
        staff_invite_submit: 'Invite',
        staff_inviting: 'Inviting...',
        staff_searching: 'Searching...',
        staff_no_users: 'No results',
        staff_load_error: 'Unable to load staff.',
        staff_pending_count: ':count pending',
        staff_you: '(you)',
        staff_last_admin: 'Last admin',
        staff_events: 'Proposed events',
        staff_empty_events: 'There are no pending events.',
        staff_accepted_events: 'Events on the page',
        staff_empty_accepted_events: 'There are no events on the page.',
        staff_auto_accept: 'Always accept proposed events',
        staff_event_remove: 'Remove from page',
        staff_event_remove_confirm: 'Remove ":title" from the group page?',
        feed: 'Board',
        feed_compose: 'New post',
        feed_compose_title: 'Post to the board',
        feed_event: 'Event',
        event_highlight_title_one: 'Part of a group',
        event_highlight_title_other: 'Part of :count groups',
        event_highlight_lead: 'This event is on the community page.',
        poem_highlight_lead: 'This poem is on the community page.',
        article_highlight_lead: 'This article is on the community page.',
        event_highlight_open: 'Go to the group',
        propose_search: 'Search for a group',
        propose_empty: 'No groups found',
        propose_selected: 'Proposed to',
        propose_label: 'Propose to a group',
        feed_poem: 'Poem',
        feed_article: 'Article',
        staff_inbox_publications: 'Poems and articles to review',
        staff_accepted_publications: 'Poems and articles on the page',
        staff_empty_accepted_publications: 'There are no poems or articles on the page.',
        feed_title: 'Title',
        feed_content: 'Message',
        feed_publish: 'Publish',
        feed_publishing: 'Publishing...',
        feed_publish_error: 'Unable to publish the post.',
        feed_empty: 'There are no posts on the board yet.',
        feed_locked: 'The board is visible after the follow request is accepted.',
        feed_load_error: 'Unable to load the board.',
        feed_delete: 'Remove',
        feed_delete_confirm: 'Remove the post ":title"?',
        feed_delete_error: 'Unable to remove the post.',
        feed_pinned: 'Pinned',
        calendar_title_accent: 'calendar',
        calendar_title_middle: 'group',
        calendar_title_tail: 'events',
      },
    },
  },
}

const I18nContext = createContext(null)

function useI18nState() {
  const [locale, setLocale] = useState(getClientLocale)
  const [translations, setTranslations] = useState({})
  const [fallbackLocale, setFallbackLocale] = useState(DEFAULT_LOCALE)
  const fallbackLocaleRef = useRef(DEFAULT_LOCALE)
  const [availableLocales, setAvailableLocales] = useState([])

  const getLocaleCode = (entry) => (typeof entry === 'string' ? entry : entry?.code)

  useEffect(() => {
    const controller = new AbortController()

    const loadTranslations = async () => {
      try {
        const response = await fetch(getApiUrl(`/api/v1/i18n/${locale}.json`), {
          signal: controller.signal,
          headers: { Accept: 'application/json', ...localeHeaders() },
          cache: 'no-store',
        })

        if (!response.ok) {
          if (locale !== fallbackLocaleRef.current) {
            setLocale(fallbackLocaleRef.current)
          }
          return
        }

        const payload = await response.json()
        const bundle = payload?.data != null ? payload.data : payload
        setTranslations(bundle.translations || {})
        const nextFallbackLocale = bundle.default_locale || fallbackLocaleRef.current
        fallbackLocaleRef.current = nextFallbackLocale
        setFallbackLocale(nextFallbackLocale)

        const nextAvailableLocales = bundle.available_languages?.length
          ? bundle.available_languages
          : bundle.available_locales || []

        setAvailableLocales(nextAvailableLocales)

        const availableCodes = nextAvailableLocales.map(getLocaleCode).filter(Boolean)

        if (!availableCodes.includes(locale) && locale !== nextFallbackLocale) {
          setLocale(nextFallbackLocale)
        }
      } catch {
        // Keep previous translations if request fails.
      }
    }

    loadTranslations()

    return () => controller.abort()
  }, [locale])

  useEffect(() => {
    if (typeof document !== 'undefined') {
      document.documentElement.lang = locale
    }
    if (typeof window !== 'undefined') {
      window.localStorage.setItem(LOCALE_STORAGE_KEY, locale)
    }
  }, [locale])

  const t = (key, replacements) => {
    const value = resolveKeyPath(translations, key)
    if (typeof value === 'string') {
      return applyReplacements(value, replacements)
    }

    const localeFallback = resolveKeyPath(staticFallbacks[locale] || {}, key)
    if (typeof localeFallback === 'string') {
      return applyReplacements(localeFallback, replacements)
    }

    const defaultFallback = resolveKeyPath(staticFallbacks[fallbackLocale] || {}, key)
    if (typeof defaultFallback === 'string') {
      return applyReplacements(defaultFallback, replacements)
    }

    return key
  }

  return {
    t,
    locale,
    setLocale,
    availableLocales,
  }
}

export function I18nProvider({ children }) {
  const value = useI18nState()

  return createElement(I18nContext.Provider, { value }, children)
}

export function useI18n() {
  const context = useContext(I18nContext)

  if (!context) {
    throw new Error('useI18n must be used within an I18nProvider')
  }

  return context
}
