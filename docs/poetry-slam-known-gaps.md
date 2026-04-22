PoetrySlam - Gap Noti e Prossime Estensioni

## Scopo

Questo documento raccoglie ciò che non è bloccante per il backend attuale di `PoetrySlam`, ma che resta aperto come hardening, raffinamento di dominio o futura estensione funzionale.

Va letto insieme a [slamin-current-state.md](C:/Users/dmazz/Herd/slamin_v4/docs/slamin-current-state.md), che descrive invece lo stato operativo già disponibile.

## Stato attuale

Il backend `PoetrySlam` è operativo sul core:

- creazione slam
- sync partecipanti da `Event`
- gestione entries manuali
- rounds
- scores anonimi
- advancement framework
- leaderboard round e cumulata

I punti qui sotto non impediscono l'uso del dominio, ma definiscono il perimetro di ciò che non è ancora chiuso in modo definitivo.

## Gap di dominio

### 1. Lifecycle più rigoroso di slam e round

Oggi esistono gli stati principali, ma il dominio può essere irrigidito meglio.

Possibili estensioni:

- blocco modifiche quando `PoetrySlam` è `closed`
- blocco modifiche ai `round` completati
- regole più forti sulle transizioni di stato
- policy esplicite per riapertura di round o slam

### 2. Workflow degli status entry

Le `entries` hanno già campi operativi, ma non c'è ancora una macchina a stati davvero forte.

Possibili estensioni:

- definizione esplicita dei valori supportati per `status`
- transizioni consentite tra `registered`, `withdrawn`, `disqualified`, altri eventuali stati
- vincoli su chi può cambiare cosa e in quale fase dello slam

### 3. Regole avanzate su performance order

L'unicità di `performance_order` è già coperta, ma non esiste ancora un workflow completo di gestione ordine esibizione.

Possibili estensioni:

- assegnazione automatica dell'ordine
- ricalcolo dopo ritiri o squalifiche
- blocco dell'ordine una volta iniziato il round

## Gap di scoring

### 4. Validazione più stretta dei valori score

Il sistema gestisce struttura e algoritmo, ma non è ancora formalizzato un regolamento numerico di gara.

Possibili estensioni:

- range minimo e massimo dei voti
- numero di decimali ammessi
- arrotondamenti ufficiali
- vincoli specifici per diversi regolamenti di slam

### 5. Tie-breaker espliciti

La leaderboard gestisce i pari merito condividendo il rank, ma non esiste ancora una strategia formale di spareggio.

Possibili estensioni:

- spareggio sul miglior round
- spareggio sull'ultimo round
- spareggio sul miglior voto singolo
- spareggio manuale organizer

### 6. Materializzazione risultati

Oggi i risultati sono calcolati in modo derivato, che è corretto per il perimetro attuale.

Possibili estensioni:

- snapshot persistiti dei risultati round
- snapshot persistiti della classifica finale
- audit più granulare su ricalcoli e rettifiche

## Gap di advancement

### 7. Advancement automatico non ancora eseguito end-to-end

Il framework di avanzamento è pronto, ma oggi è soprattutto un telaio di dominio.

Già presenti:

- `all_entries`
- `top_n`
- `top_percentage`
- `manual_selection`
- base di calcolo `round_result` e `cumulative_result`

Da completare se servirà:

- applicazione automatica completa delle decisioni di avanzamento
- esclusione operativa delle entry non qualificate dai round successivi
- override manuali e riconciliazione con ranking automatico

Nota:

- attualmente il comportamento voluto resta: tutti fanno tutti i round

## Gap API e prodotto

### 8. Documentazione API formale

Le API esistono e sono testate, ma non c'è ancora una documentazione consumer-facing strutturata.

Possibili estensioni:

- schema endpoint dedicato
- payload example per frontend
- error matrix per i casi principali

### 9. Risorse/presenter dedicati

Il dominio è già leggibile, ma si può ancora rifinire la coerenza dell'output JSON.

Possibili estensioni:

- resource classes dedicate per slam, entry, round, score result, leaderboard row
- shape condivise e più stabili per frontend/admin

### 10. Hardening policy e audit

Il dominio usa già autorizzazioni e audit, ma vale un pass finale mirato.

Possibili estensioni:

- review completa delle policy su tutte le mutate API
- verifica copertura audit per tutti i rami di update e delete
- allineamento naming eventi audit

## Gap test

### 11. Test end-to-end di flusso completo

La feature coverage del dominio è già buona, ma manca ancora una suite narrativa end-to-end.

Possibili estensioni:

- scenario completo da creazione `Event` a classifica finale
- scenario multi-round con advancement manuale
- scenario con correzione voti e riclassifica

### 12. Test edge case

Possibili estensioni:

- parità multiple in leaderboard cumulata
- round con score parziali su molte entry
- mix di entry evento, `site_user` ed `external`
- cambi di stato in momenti limite del flusso

## Possibili evoluzioni future

Fuori dal perimetro del core attuale, ma coerenti con l'architettura:

- Poetry Slam Championship multi-evento
- finali/semifinali con advancement effettivo
- reportistica e statistiche storiche
- esportazioni risultati
- integrazione realtime per inserimento punteggi live

## Conclusione

Il sottodominio `PoetrySlam` backend è abbastanza maturo da essere considerato operativo.

Questo file non descrive mancanze critiche: descrive soprattutto scelte non ancora irrigidite, ottimizzazioni e capacità future che possiamo affrontare a blocchi senza dover rifare il core.
