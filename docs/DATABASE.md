# QRHunt – Database Design

**Project:** QRHunt

**Document Version:** 0.2 (Draft)

**Plugin Version:** Target 1.0.0

---

# 1. Obiettivi

Il database di QRHunt deve essere:

- semplice;
- normalizzato;
- facilmente estendibile;
- indipendente dai contenuti gestiti da WordPress.

Devono essere memorizzati esclusivamente i dati necessari al funzionamento del plugin.

Tutti i dati derivabili devono essere calcolati e non duplicati.

---

# 2. Principi progettuali

Il database segue i seguenti principi.

- Utilizzare WordPress quando esiste già una struttura adeguata.
- Separare i contenuti dalla logica di gioco.
- Evitare la duplicazione dei dati.
- Rendere il modello facilmente estendibile.
- Mantenere il numero di tabelle ridotto ma con responsabilità ben definite.

---

# 3. Entità

QRHunt utilizza le seguenti entità.

- Utente (WordPress)
- Percorso
- Checkpoint
- Gruppo di Checkpoint
- Dipendenza
- Partecipazione
- Evento

---

# 4. Entità WordPress

## Utenti

Gli utenti sono quelli nativi dell'installazione WordPress.

QRHunt non implementa alcun sistema di autenticazione.

Utilizza esclusivamente gli utenti presenti nelle tabelle:

- wp_users
- wp_usermeta

---

## Checkpoint

Ogni Checkpoint è un Custom Post Type di WordPress.

WordPress gestisce automaticamente:

- titolo;
- contenuto;
- Gutenberg;
- media;
- revisioni;
- permalink;
- REST API.

QRHunt aggiunge esclusivamente la logica di gioco.

---

# 5. Tabelle del plugin

La versione 1.0 prevede le seguenti tabelle.

```
wp_qrhunt_paths

wp_qrhunt_checkpoints

wp_qrhunt_checkpoint_groups

wp_qrhunt_dependencies

wp_qrhunt_participations

wp_qrhunt_events
```

---

# 6. Percorsi

La tabella dei Percorsi contiene esclusivamente le informazioni proprie del Percorso.

Non contiene:

- contenuti editoriali;
- Checkpoint;
- Partecipazioni;
- Eventi.

Ogni Percorso possiede almeno:

- identificativo;
- nome;
- descrizione;
- stato;
- data di apertura;
- data di chiusura;
- riferimento al Checkpoint iniziale;
- riferimento al Checkpoint finale.

Il Checkpoint iniziale ed il Checkpoint finale vengono identificati tramite i rispettivi riferimenti.

Non vengono utilizzati flag all'interno dei Checkpoint.

---

# 7. Checkpoint

La tabella dei Checkpoint contiene esclusivamente le informazioni tecniche necessarie al funzionamento del gioco.

Il contenuto editoriale rimane nel relativo Custom Post Type.

Ogni Checkpoint contiene almeno:

- riferimento al Custom Post Type;
- riferimento al Percorso;
- token pubblico;
- data di creazione;
- data di aggiornamento.

Il Checkpoint non contiene regole di progressione.

---

# 8. Token

Ogni Checkpoint possiede un token pubblico univoco.

Il token viene generato automaticamente dal plugin alla creazione del Checkpoint.

Il token:

- è univoco;
- è casuale;
- non contiene informazioni sul Checkpoint;
- non è modificabile manualmente.

L'URL pubblico utilizza esclusivamente il token.

Esempio

```
https://example.com/qrhunt/T6GJ5Q9ZP4M8N2
```

Durante la duplicazione di un Percorso vengono generati nuovi token per tutti i Checkpoint.

Il token viene utilizzato esclusivamente come identificativo pubblico del Checkpoint.

---

# 9. Gruppi di Checkpoint

Un Gruppo rappresenta un insieme logico di Checkpoint.

Ogni Gruppo appartiene ad un solo Percorso.
Un Gruppo non può contenere Checkpoint appartenenti a Percorsi differenti.

Un Checkpoint può appartenere ad un solo Gruppo oppure a nessun Gruppo.

I Gruppi consentono di modellare situazioni nelle quali più Checkpoint devono essere completati senza imporre un ordine.

Esempio:

```
Checkpoint 4

Checkpoint 5

Checkpoint 6
```

appartengono al medesimo Gruppo.

Il Checkpoint 7 può richiedere il completamento del Gruppo senza imporre un ordine tra i suoi elementi.

---

# 10. Dipendenze

Le regole di progressione sono memorizzate in una tabella dedicata.

Le Dipendenze sono indipendenti dai Checkpoint.

Ogni Dipendenza collega un Checkpoint ad un obiettivo.
Uno stesso Checkpoint può possedere più Dipendenze dello stesso tipo.

L'obiettivo può essere:

- un altro Checkpoint;
- un Gruppo.

Ogni Dipendenza possiede almeno:

- Checkpoint sorgente;
- tipo;
- tipo di destinazione;
- destinazione.

I tipi previsti sono:

- after;
- before.

Più Dipendenze dello stesso tipo vengono valutate in AND.

Una Dipendenza può collegare esclusivamente entità appartenenti allo stesso Percorso.

La logica OR non fa parte della versione 1.0.

---

# 11. Partecipazioni

Una Partecipazione rappresenta il legame tra un Utente ed un Percorso.

Per ogni coppia Utente/Percorso può esistere una sola Partecipazione.

La Partecipazione contiene esclusivamente:

- utente;
- percorso;
- stato;
- data di inizio;
- data di conclusione;
- data di annullamento;
- date tecniche.

La durata del Percorso non viene memorizzata.

Viene sempre calcolata dagli Eventi.

---

# 12. Eventi

Ogni richiesta elaborata dal plugin genera un Evento.
Nella versione 1.0 l'unico tipo previsto è la scansione di un QR Code.

La struttura è progettata per poter gestire in futuro ulteriori tipologie di Evento.

Ogni Evento contiene almeno:

- Partecipazione;
- Checkpoint;
- tipo;
- esito;
- timestamp.

La registrazione di indirizzo IP e User Agent è configurabile e può essere disabilitata.

---

# 13. Dati derivati

I seguenti dati non vengono memorizzati nel database.

Sono sempre calcolati.

- durata del Percorso;
- numero di Checkpoint validati;
- numero di Eventi validi;
- numero di Eventi non validi;
- numero di Eventi duplicati;
- statistiche;
- classifiche.

---

# 14. Relazioni

```
                wp_users
                    │
                    ▼
         Partecipazioni
          │         │
          │         ▼
          │     Percorsi
          │         │
          │         ▼
          │   Checkpoint
          │      │
          │      ▼
          │   Gruppi
          │
          ├──────────────┐
          ▼              │
       Eventi            │
                         ▼
                    Dipendenze
```

---

# 15. Estendibilità

Il modello dati è progettato per consentire l'introduzione futura di:

- quiz;
- badge;
- punti;
- GPS;
- NFC;
- classifiche;
- API pubbliche;
- percorsi ramificati.

L'introduzione di tali funzionalità non dovrà richiedere modifiche sostanziali alla struttura del database.
