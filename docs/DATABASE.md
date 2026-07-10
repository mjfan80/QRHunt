# QRHunt – Database Design

**Project:** QRHunt

**Document Version:** 0.1 (Draft)

**Plugin Version:** Target 1.0.0

---

# 1. Obiettivi

Il database di QRHunt deve essere:

- semplice;
- normalizzato;
- facilmente estendibile;
- indipendente dai contenuti WordPress.

Devono essere memorizzati esclusivamente i dati necessari al funzionamento del plugin.

Tutti i dati derivabili dovranno essere calcolati e non duplicati.

---

# 2. Entità principali

QRHunt è composto dalle seguenti entità.

- Utente (WordPress)
- Percorso
- Checkpoint
- Partecipazione
- Evento

Le relazioni tra tali entità costituiscono l'intero modello dati del plugin.

```
Utente (WordPress)
        │
        │
        ▼
Partecipazione
        │
        ▼
Percorso
        │
        ▼
Checkpoint
        │
        ▼
Evento
```

---

# 3. Entità WordPress

QRHunt utilizza direttamente le seguenti strutture native di WordPress.

## wp_users

Contiene gli utenti.

QRHunt non implementa un sistema di autenticazione proprio.

---

## wp_posts

Ogni Checkpoint è un Custom Post Type.

Il contenuto del Checkpoint viene gestito completamente da WordPress.

Sono quindi disponibili automaticamente:

- Gutenberg;
- Revisioni;
- Media Library;
- Tassonomie;
- Hook;
- REST API.

---

# 4. Tabelle del plugin

La versione 1.0 prevede quattro tabelle dedicate.

```
wp_qrhunt_paths

wp_qrhunt_checkpoints

wp_qrhunt_participations

wp_qrhunt_events
```

---

# 5. Tabella Percorsi

Contiene esclusivamente i dati propri del Percorso.

Non contiene informazioni sui partecipanti.

Non contiene informazioni sui Checkpoint.

---

Campi principali

- id
- nome
- descrizione
- stato
- data_apertura
- data_chiusura
- created_at
- updated_at

---

# 6. Tabella Checkpoint

Contiene esclusivamente le informazioni di gioco del Checkpoint.

Il contenuto rimane nel Custom Post Type.

---

Campi principali

- post_id
- path_id
- token
- prerequisite_checkpoint_id
- invalid_after_checkpoint_id
- is_start
- is_finish
- created_at
- updated_at

---

Il token identifica pubblicamente il Checkpoint.

L'URL pubblico utilizza esclusivamente il token.

Esempio

```
https://example.com/qrhunt/T6GJ5Q9ZP4M8N2
```

L'identificativo interno non viene mai esposto.

---

# 7. Tabella Partecipazioni

Rappresenta il legame tra un Utente ed un Percorso.

Per ogni coppia Utente/Percorso può esistere una sola Partecipazione.

---

Campi principali

- id
- user_id
- path_id
- stato
- started_at
- finished_at
- cancelled_at
- created_at
- updated_at

---

La durata del Percorso non viene memorizzata.

Viene sempre calcolata dagli Eventi.

---

# 8. Tabella Eventi

Ogni interazione significativa genera un Evento.

Nella versione 1.0 l'unico tipo previsto è la scansione di un QR Code.

Il modello è però progettato per poter essere esteso.

---

Campi principali

- id
- participation_id
- checkpoint_id
- event_type
- result
- ip_address
- user_agent
- created_at

---

La registrazione di IP e User Agent deve poter essere disabilitata dalle impostazioni del plugin.

---

# 9. Dati derivati

I seguenti dati non vengono memorizzati.

Vengono sempre calcolati.

- durata del Percorso;
- numero di Checkpoint validati;
- numero di Eventi validi;
- numero di Eventi duplicati;
- statistiche;
- classifiche.

---

# 10. Relazioni

Utente

1 → N Partecipazioni

---

Percorso

1 → N Checkpoint

1 → N Partecipazioni

---

Checkpoint

1 → N Eventi

---

Partecipazione

1 → N Eventi

---

# 11. Principi progettuali

Il database deve rispettare i seguenti principi.

- nessuna duplicazione dei dati;
- nessun dato derivabile viene memorizzato;
- nessuna dipendenza da plugin esterni;
- utilizzo delle strutture native di WordPress quando appropriato;
- separazione netta tra contenuto editoriale e logica del gioco.

---

# 12. Evoluzioni future

Il modello dati è progettato per consentire l'introduzione di:

- quiz;
- badge;
- punteggi;
- checkpoint GPS;
- NFC;
- percorsi ramificati;
- finali multipli;
- classifiche;
- API pubbliche.

Tali funzionalità non fanno parte della versione 1.0 ma non richiederanno modifiche sostanziali al modello dati.
