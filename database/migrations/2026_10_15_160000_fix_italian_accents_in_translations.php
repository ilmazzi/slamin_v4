<?php

use Domain\I18n\Support\I18nCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Only rows still holding the old text are updated, so edits made from the translations panel are kept.
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    private const FIXES = [
        ['admin.settings.password_set', 'Password gia salvata. Inserisci un valore solo per aggiornarla.', 'Password già salvata. Inserisci un valore solo per aggiornarla.'],
        ['article.errors.category_in_use', 'Non puoi eliminare una categoria gia usata da uno o piu articoli.', 'Non puoi eliminare una categoria già usata da uno o più articoli.'],
        ['comment.errors.single_reply_level', 'E consentito un solo livello di risposta.', 'È consentito un solo livello di risposta.'],
        ['event.errors.availability_only', 'Questa operazione e disponibile solo per eventi con pianificazione basata su disponibilita.', 'Questa operazione è disponibile solo per eventi con pianificazione basata su disponibilità.'],
        ['event.errors.availability_poll_closed', 'Il sondaggio disponibilita e chiuso e non accetta nuovi voti.', 'Il sondaggio disponibilità è chiuso e non accetta nuovi voti.'],
        ['event.errors.availability_already_finalized', 'La disponibilita e gia stata finalizzata per questo evento.', 'La disponibilità è già stata finalizzata per questo evento.'],
        ['event.errors.category_in_use', 'Non puoi eliminare una categoria gia usata da uno o piu eventi.', 'Non puoi eliminare una categoria già usata da uno o più eventi.'],
        ['event.validation.availability_deadline_required', 'La scadenza per le risposte alla disponibilita e obbligatoria.', 'La scadenza per le risposte alla disponibilità è obbligatoria.'],
        ['event.validation.availability_options_required', 'E richiesta almeno una opzione di disponibilita.', 'È richiesta almeno una opzione di disponibilità.'],
        ['event.validation.price_required', 'Il prezzo e obbligatorio quando l\'evento e a pagamento.', 'Il prezzo è obbligatorio quando l\'evento è a pagamento.'],
        ['event.validation.currency_required', 'La valuta e obbligatoria quando l\'evento e a pagamento.', 'La valuta è obbligatoria quando l\'evento è a pagamento.'],
        ['event.validation.audience_capacity_required', 'La capienza pubblico e obbligatoria quando il controllo capienza e attivo.', 'La capienza pubblico è obbligatoria quando il controllo capienza è attivo.'],
        ['event.validation.internal_participant_user_required', 'Per un invitato interno e obbligatorio selezionare un utente.', 'Per un invitato interno è obbligatorio selezionare un utente.'],
        ['event.validation.external_participant_email_required', 'Per un invitato esterno l\'email e obbligatoria.', 'Per un invitato esterno l\'email è obbligatoria.'],
        ['event.validation.external_participant_name_required', 'Per un invitato esterno il nome e obbligatorio.', 'Per un invitato esterno il nome è obbligatorio.'],
        ['event.notifications.availability_finalized_message', 'La data dell\'evento ":title" e stata confermata per :starts_at (:timezone).', 'La data dell\'evento ":title" è stata confermata per :starts_at (:timezone).'],
        ['event.notifications.updated_message', 'L\'evento ":title" e stato modificato. Controlla i nuovi dettagli.', 'L\'evento ":title" è stato modificato. Controlla i nuovi dettagli.'],
        ['events.detail_page.schedule_modes.availability', 'Basato su disponibilita', 'Basato su disponibilità'],
        ['events.detail_page.sections.participation.description', 'Come partecipare, costi e disponibilita', 'Come partecipare, costi e disponibilità'],
        ['events.detail_page.sections.useful.description', 'Dettagli aggiuntivi sull evento', 'Dettagli aggiuntivi sull\'evento'],
        ['group.errors.not_visible', 'Questo gruppo non e disponibile.', 'Questo gruppo non è disponibile.'],
        ['group.errors.already_member', 'Sei gia membro attivo di questo gruppo.', 'Sei già membro attivo di questo gruppo.'],
        ['group.errors.cannot_promote_to_owner', 'Solo un owner puo promuovere un membro a owner.', 'Solo un owner può promuovere un membro a owner.'],
        ['group.errors.request_not_pending', 'La richiesta non e piu in stato pending.', 'La richiesta non è più in stato pending.'],
        ['group.errors.membership_not_active', 'L\'utente non e un membro attivo del gruppo.', 'L\'utente non è un membro attivo del gruppo.'],
        ['group.notifications.join_approved_message', 'La tua richiesta di ingresso al gruppo ":group" e stata approvata.', 'La tua richiesta di ingresso al gruppo ":group" è stata approvata.'],
        ['group.notifications.join_rejected_message', 'La tua richiesta di ingresso al gruppo ":group" e stata rifiutata.', 'La tua richiesta di ingresso al gruppo ":group" è stata rifiutata.'],
        ['group.notifications.announcement_published_message', 'Nel gruppo ":group" e stato pubblicato un nuovo annuncio: ":title".', 'Nel gruppo ":group" è stato pubblicato un nuovo annuncio: ":title".'],
        ['help.errors.category_in_use', 'Non puoi eliminare una categoria gia usata da una o piu guide.', 'Non puoi eliminare una categoria già usata da una o più guide.'],
        ['home.hero.subcopy', 'Condividi la tua creativita, scopri nuova arte.', 'Condividi la tua creatività, scopri nuova arte.'],
        ['media.validation.photo_mime_not_allowed', 'Il formato della foto non e consentito.', 'Il formato della foto non è consentito.'],
        ['media.validation.video_mime_not_allowed', 'Il formato del video non e consentito.', 'Il formato del video non è consentito.'],
        ['media.validation.peertube_not_configured', 'PeerTube non e configurato correttamente.', 'PeerTube non è configurato correttamente.'],
        ['media.validation.video_file_missing', 'Il file video selezionato non e disponibile.', 'Il file video selezionato non è disponibile.'],
        ['media.validation.invalid_snap_timestamp', 'Il timestamp dello snap non e valido.', 'Il timestamp dello snap non è valido.'],
        ['media.validation.video_status_only_for_videos', 'Lo stato video e disponibile solo per i media video.', 'Lo stato video è disponibile solo per i media video.'],
        ['media.validation.feature_pack_not_available', 'Il feature pack selezionato non e disponibile.', 'Il feature pack selezionato non è disponibile.'],
        ['media.validation.stripe_not_configured', 'Stripe non e configurato correttamente.', 'Stripe non è configurato correttamente.'],
        ['media.validation.checkout_order_not_pending', 'L ordine di checkout non e piu in stato pending.', 'L\'ordine di checkout non è più in stato pending.'],
        ['media.notifications.video_ready_message', 'Il video ":title" e pronto per essere visto.', 'Il video ":title" è pronto per essere visto.'],
        ['media.notifications.video_failed_message', 'C e stato un problema con il video ":title".', 'C\'è stato un problema con il video ":title".'],
        ['media.upload.error_file_type', 'Questo formato non e consentito.', 'Questo formato non è consentito.'],
        ['media.upload.peertube_missing', 'PeerTube non e ancora configurato. Un amministratore deve impostare il server prima di caricare video.', 'PeerTube non è ancora configurato. Un amministratore deve impostare il server prima di caricare video.'],
        ['moderation.ui.already_decided', 'Questo contenuto e gia stato revisionato.', 'Questo contenuto è già stato revisionato.'],
        ['moderation.notifications.poem_approved_message', 'La tua poesia ":title" e stata approvata e pubblicata.', 'La tua poesia ":title" è stata approvata e pubblicata.'],
        ['moderation.notifications.poem_rejected_message', 'La tua poesia ":title" non e stata approvata.', 'La tua poesia ":title" non è stata approvata.'],
        ['moderation.notifications.article_approved_message', 'Il tuo articolo ":title" e stato approvato e pubblicato.', 'Il tuo articolo ":title" è stato approvato e pubblicato.'],
        ['moderation.notifications.article_rejected_message', 'Il tuo articolo ":title" non e stato approvato.', 'Il tuo articolo ":title" non è stato approvato.'],
        ['moderation.notifications.event_approved_message', 'Il tuo evento ":title" e stato approvato e pubblicato.', 'Il tuo evento ":title" è stato approvato e pubblicato.'],
        ['moderation.notifications.event_rejected_message', 'Il tuo evento ":title" non e stato approvato.', 'Il tuo evento ":title" non è stato approvato.'],
        ['poetry.errors.category_in_use', 'Non puoi eliminare una categoria gia usata da una o piu poesie.', 'Non puoi eliminare una categoria già usata da una o più poesie.'],
        ['poetry.errors.type_in_use', 'Non puoi eliminare una tipologia gia usata da una o piu poesie.', 'Non puoi eliminare una tipologia già usata da una o più poesie.'],
        ['profile.page.private', 'Questo profilo e privato.', 'Questo profilo è privato.'],
        ['profile.page.not_found', 'Questo profilo non e disponibile.', 'Questo profilo non è disponibile.'],
        ['profile.works.confirm_delete', 'Eliminare questo contenuto? L\'azione non si puo annullare.', 'Eliminare questo contenuto? L\'azione non si può annullare.'],
        ['report.errors.duplicate_open', 'Hai gia una segnalazione aperta per questo contenuto.', 'Hai già una segnalazione aperta per questo contenuto.'],
        ['report.notifications.created_message', 'E stata inviata una nuova segnalazione su un contenuto di tipo :type.', 'È stata inviata una nuova segnalazione su un contenuto di tipo :type.'],
        ['report.notifications.resolved_message', 'La tua segnalazione e stata presa in carico e risolta.', 'La tua segnalazione è stata presa in carico e risolta.'],
        ['report.notifications.dismissed_message', 'La tua segnalazione e stata valutata e archiviata.', 'La tua segnalazione è stata valutata e archiviata.'],
    ];

    public function up(): void
    {
        $languageId = DB::table('i18n_languages')->where('code', 'it')->value('id');
        if ($languageId === null) {
            return;
        }

        foreach (self::FIXES as [$key, $old, $new]) {
            DB::table('i18n_translations')
                ->where('language_id', $languageId)
                ->where('value', $old)
                ->whereIn('translation_key_id', DB::table('i18n_translation_keys')->select('id')->where('key', $key))
                ->update(['value' => $new, 'updated_at' => now()]);
        }

        I18nCache::bump();
    }

    public function down(): void {}
};
