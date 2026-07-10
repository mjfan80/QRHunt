# QRHunt Software Requirements Specification (SRS)

**Project:** QRHunt

**Document Version:** 0.2 (Draft)

**Plugin Version:** Target 1.0.0

**Status:** Draft

**License:** LGPL-3.0-or-later

---

# 1. Introduction

## 1.1 Purpose

QRHunt is a self-contained WordPress plugin that allows administrators to create interactive experiences based on QR Code checkpoints.

Participants explore a physical or virtual environment by discovering QR Codes. Each QR Code corresponds to a checkpoint that is validated and recorded by the plugin according to configurable progression rules.

The plugin is designed to be completely generic and independent from any specific type of event.

Typical use cases include:

- Treasure hunts
- Halloween events
- Museums
- Educational activities
- Escape rooms
- Guided tours
- Theme parks
- Trade fairs
- City games
- Team building events

The plugin must not contain assumptions related to any specific scenario.

---

## 1.2 Project Goals

QRHunt shall allow administrators to:

- create one or more independent Paths;
- create Checkpoints belonging to each Path;
- automatically generate QR Codes for each Checkpoint;
- track participant progression;
- enforce configurable progression rules;
- record both valid and invalid attempts;
- provide statistics and administrative reports;
- export collected data.

---

## 1.3 Project Philosophy

QRHunt manages the game logic.

WordPress manages the content.

Whenever possible, the plugin shall reuse native WordPress functionality instead of implementing equivalent custom features.

Examples include:

- authentication;
- user management;
- Gutenberg editor;
- media library;
- revisions;
- localization;
- capabilities;
- roles.

---

# 2. Design Principles

The following principles govern every architectural decision made within QRHunt.

## DP-001 — Self-contained

QRHunt shall work immediately after installation without requiring third-party plugins.

Optional integrations may enhance the plugin but shall never be mandatory.

---

## DP-002 — WordPress First

QRHunt shall rely on official WordPress APIs whenever possible.

Custom implementations shall only be introduced when no suitable native solution exists.

---

## DP-003 — Extensible

Every core component shall be designed to allow future extensions without requiring breaking changes.

---

## DP-004 — Generic

The plugin shall never contain assumptions about a specific event, customer or use case.

All terminology and behaviour must remain generic.

---

## DP-005 — Data Ownership

All data shall remain inside the WordPress installation.

QRHunt shall not require cloud services or external SaaS platforms.

---

## DP-006 — Progressive Enhancement

Optional features such as Social Login or external integrations shall be detected automatically when available.

Their absence shall never prevent the plugin from working.

---

# 3. Glossary

## Path

A Path represents a complete playable experience.

Examples include:

- Halloween 2026
- Museum Tour
- Treasure Hunt
- Escape Room

Each Path is completely independent.

---

## Checkpoint

A Checkpoint represents a discoverable location identified by a QR Code.

Every Checkpoint belongs to exactly one Path.

Each Checkpoint contains:

- content;
- progression rules;
- QR Code;
- public endpoint.

---

## Participant

A Participant is an authenticated WordPress user.

QRHunt does not implement its own authentication system.

---

## Participation

A Participation represents the relationship between one Participant and one Path.

Each Participant may have only one Participation per Path.

The Participation is automatically created when the first Checkpoint of the Path is successfully validated.

---

## Attempt

An Attempt represents every request to validate a Checkpoint.

Attempts are always recorded.

An Attempt may be:

- Valid
- Invalid
- Duplicate

Attempts never disappear from the database.

---

# 4. Core Concepts

QRHunt is based on five core entities.

```
Participant
      │
      ▼
Participation
      │
      ▼
Path
      │
      ▼
Checkpoint
      │
      ▼
Attempt
```

The relationship between these entities defines the entire game logic.

No other entity may modify participant progression directly.

---

# 5. Gestione dei Checkpoint

## 5.1 Definizione

Un Checkpoint rappresenta una tappa del percorso identificata da un QR Code univoco.

Ogni Checkpoint appartiene ad un solo Percorso.

Un Percorso può contenere un numero arbitrario di Checkpoint.

Ogni Checkpoint è indipendente dagli altri ad eccezione delle regole di progressione eventualmente configurate.

---

## 5.2 Identificazione

Ogni Checkpoint possiede:

- un identificativo interno del database;
- un identificativo pubblico (token);
- un QR Code;
- un URL pubblico generato automaticamente dal plugin.

L'identificativo interno non dovrà mai comparire nell'URL pubblico.

L'URL dovrà utilizzare esclusivamente il token pubblico.

Esempio:

```
https://example.com/qrhunt/c8fa9d21
```

Il token pubblico dovrà essere sufficientemente casuale da rendere impraticabile l'individuazione di checkpoint tramite tentativi.

---

## 5.3 Contenuto

Ogni Checkpoint è un Custom Post Type di WordPress.

L'amministratore può utilizzare l'editor Gutenberg senza limitazioni.

Il plugin non impone alcuna struttura al contenuto.

Possono quindi essere utilizzati liberamente:

- testo;
- immagini;
- video;
- audio;
- shortcode;
- blocchi personalizzati;
- embed;
- qualsiasi blocco compatibile con WordPress.

---

## 5.4 Stato

Ogni Checkpoint può assumere esclusivamente gli stati previsti da WordPress.

Inoltre il plugin introduce il concetto di:

- Checkpoint Finale

che identifica il punto conclusivo del percorso.

Nella versione 1.0 sarà possibile definire un solo Checkpoint Finale per ciascun Percorso.

---

## 5.5 Regole di progressione

Ogni Checkpoint può definire due regole indipendenti.

Entrambe sono opzionali.

### Prerequisito

Specifica quale Checkpoint deve essere stato validato prima di poter registrare il Checkpoint corrente.

Se il prerequisito non è soddisfatto:

- il tentativo viene registrato;
- il Checkpoint NON viene assegnato;
- non vengono assegnati punti;
- la partecipazione non avanza;
- viene mostrato il messaggio previsto.

### Non valido dopo

Specifica il Checkpoint oltre il quale il Checkpoint corrente non può più essere registrato.

Se il partecipante ha già validato il Checkpoint indicato:

- il tentativo viene registrato;
- il Checkpoint NON viene assegnato;
- non vengono assegnati punti;
- la partecipazione non avanza;
- viene mostrato il messaggio previsto.

Le due regole possono coesistere.

---

## 5.6 Validazione

Ogni richiesta ad un URL di Checkpoint genera sempre un Tentativo.

L'algoritmo di validazione viene eseguito nel seguente ordine.

1. Verifica autenticazione.
2. Verifica esistenza del Checkpoint.
3. Verifica appartenenza al Percorso.
4. Creazione della Partecipazione se il Checkpoint rappresenta l'inizio del Percorso.
5. Verifica che la Partecipazione non sia Annullata.
6. Verifica che la Partecipazione non sia già Completata.
7. Verifica scansione duplicata.
8. Verifica Prerequisito.
9. Verifica regola "Non valido dopo".
10. Registrazione della validazione.
11. Aggiornamento dello stato della Partecipazione.

Al primo controllo fallito l'algoritmo termina.

Il Tentativo viene comunque registrato.

---

## 5.7 Tentativi

Ogni apertura dell'URL del Checkpoint genera un Tentativo.

Anche i tentativi non validi vengono memorizzati.

Ogni Tentativo registra almeno:

- data e ora;
- partecipante;
- percorso;
- checkpoint;
- esito;
- motivazione dell'esito;
- indirizzo IP;
- User Agent.

Nessun Tentativo può essere eliminato automaticamente.

---

## 5.8 Scansioni duplicate

La scansione di un Checkpoint già validato viene registrata come Tentativo Duplicato.

Una scansione duplicata:

- non assegna punti;
- non modifica la progressione;
- non modifica lo stato della Partecipazione;
- viene comunque registrata nel database.

L'interfaccia mostrerà chiaramente al partecipante che il Checkpoint era già stato trovato.

---

## 5.9 Checkpoint Finale

Un Percorso può definire un solo Checkpoint Finale.

La validazione positiva del Checkpoint Finale conclude il Percorso.

Se tutti i Checkpoint del Percorso risultano validati:

→ stato "Completata".

In caso contrario:

→ stato "Terminata".

---

## 5.10 Coerenza delle regole

Il plugin deve impedire la configurazione di regole logicamente incoerenti.

Esempi:

- un Checkpoint non può richiedere sé stesso come Prerequisito;
- un Checkpoint non può essere contemporaneamente precedente e successivo a sé stesso;
- non devono essere consentiti cicli nelle dipendenze;
- il Checkpoint Finale non può avere come regola "Non valido dopo" un altro Checkpoint.

La validazione della configurazione deve avvenire durante il salvataggio del Percorso.

---

## 5.11 Amministrazione

L'amministratore deve poter:

- creare Checkpoint;
- modificare Checkpoint;
- duplicare Checkpoint;
- eliminare Checkpoint;
- rigenerare il QR Code;
- scaricare il QR Code;
- visualizzare statistiche;
- consultare il numero di scansioni;
- consultare il numero di tentativi non validi;
- consultare il numero di scansioni duplicate.

---

## 5.12 Requisiti implementativi

I Checkpoint non devono comparire:

- nei risultati di ricerca del sito;
- nelle sitemap XML;
- negli archivi del Custom Post Type;
- nei feed RSS.

I Checkpoint devono essere raggiungibili esclusivamente conoscendo il relativo URL.

# 6. Gestione delle Partecipazioni

## 6.1 Definizione

Una Partecipazione rappresenta il legame tra un Partecipante ed un Percorso.

La Partecipazione contiene tutte le informazioni necessarie a determinare lo stato di avanzamento del partecipante all'interno del Percorso.

Ogni Partecipazione appartiene ad un solo Partecipante.

Ogni Partecipazione appartiene ad un solo Percorso.

Per ogni coppia Partecipante/Percorso può esistere una sola Partecipazione.

---

## 6.2 Creazione

La Partecipazione viene creata automaticamente dal plugin.

L'amministratore non crea manualmente una Partecipazione.

La creazione avviene esclusivamente quando il partecipante valida correttamente il Checkpoint iniziale del Percorso.

Se il partecipante possiede già una Partecipazione relativa al medesimo Percorso non viene creata una nuova Partecipazione.

---

## 6.3 Stati

Una Partecipazione può assumere esclusivamente uno dei seguenti stati.

### Non iniziata

Il partecipante possiede un account WordPress ma non ha ancora iniziato il Percorso.

Non esiste ancora una Partecipazione nel database.

---

### In corso

Il primo Checkpoint è stato validato.

Il partecipante può proseguire il Percorso.

---

### Terminata

Il partecipante ha validato correttamente il Checkpoint Finale.

Non risultano però validati tutti i Checkpoint appartenenti al Percorso.

Il Percorso è concluso.

Non sono consentite ulteriori validazioni.

---

### Completata

Il partecipante ha validato:

- il Checkpoint Finale;
- tutti i Checkpoint appartenenti al Percorso.

Il Percorso è concluso.

Non sono consentite ulteriori validazioni.

---

### Annullata

L'amministratore ha invalidato manualmente la Partecipazione.

I dati storici non vengono eliminati.

I Tentativi restano consultabili.

Una Partecipazione annullata non può essere riattivata nella versione 1.0.

---

## 6.4 Transizioni di stato

Le transizioni consentite sono esclusivamente le seguenti.

Non iniziata

↓

In corso

↓

Terminata

oppure

↓

Completata

Qualunque stato può diventare:

↓

Annullata

Non sono consentite altre transizioni.

---

## 6.5 Aggiornamento dello stato

Lo stato viene aggiornato automaticamente dal plugin.

Nessun utente può modificarlo manualmente.

L'unica eccezione è lo stato "Annullata", impostabile esclusivamente dagli amministratori.

---

## 6.6 Completamento del Percorso

Un Percorso viene considerato concluso esclusivamente quando il partecipante valida correttamente il Checkpoint Finale.

La validazione del solo ultimo Checkpoint non è sufficiente.

Devono essere soddisfatte tutte le regole di progressione previste.

---

## 6.7 Checkpoint mancanti

Il raggiungimento del Checkpoint Finale non implica necessariamente che tutti i Checkpoint siano stati trovati.

Se il partecipante conclude il Percorso senza aver validato tutti i Checkpoint:

lo stato diventa "Terminata".

Se invece risultano validati tutti i Checkpoint:

lo stato diventa "Completata".

---

## 6.8 Tentativi successivi alla conclusione

Se una Partecipazione è Terminata o Completata:

ogni ulteriore scansione produce comunque un Tentativo.

Il Tentativo viene registrato.

Nessuna progressione viene modificata.

Nessun punto viene assegnato.

---

## 6.9 Duplicazione

Una Partecipazione non può essere duplicata.

Il partecipante può partecipare una sola volta ad ogni Percorso.

La versione 1.0 non prevede il riavvio di una Partecipazione.

---

## 6.10 Amministrazione

L'amministratore deve poter:

- visualizzare tutte le Partecipazioni;
- filtrarle per Percorso;
- filtrarle per stato;
- filtrarle per partecipante;
- visualizzare tutti i Tentativi;
- visualizzare solo i Tentativi non validi;
- visualizzare solo le scansioni duplicate;
- annullare una Partecipazione;
- esportare i dati in formato CSV.

L'amministratore non può modificare manualmente la progressione del partecipante.

# 7. Gestione dei Tentativi

## 7.1 Definizione

Ogni richiesta ricevuta dall'URL pubblico di un Checkpoint genera un Tentativo.

Non esistono eccezioni.

Indipendentemente dall'esito finale, il Tentativo viene sempre registrato nel database.

Lo scopo della registrazione completa dei Tentativi è:

- ricostruire la cronologia delle azioni del partecipante;
- consentire analisi statistiche;
- individuare comportamenti anomali;
- fornire strumenti di verifica agli amministratori.

---

## 7.2 Informazioni registrate

Ogni Tentativo deve memorizzare almeno:

- identificativo del Tentativo;
- data e ora;
- partecipante;
- percorso;
- checkpoint;
- partecipazione;
- esito;
- motivazione dell'esito;
- indirizzo IP;
- User Agent.

Ulteriori informazioni potranno essere aggiunte nelle versioni future senza modificare il significato del Tentativo.

---

## 7.3 Esiti possibili

Ogni Tentativo deve appartenere ad una ed una sola categoria.

Gli esiti previsti nella versione 1.0 sono:

- Valido
- Duplicato
- Checkpoint già superato
- Prerequisito non soddisfatto
- Partecipazione completata
- Partecipazione annullata
- Partecipante non autenticato
- Checkpoint inesistente
- Checkpoint non appartenente al Percorso

L'elenco dovrà essere facilmente estendibile nelle versioni future.

---

## 7.4 Tentativo valido

Un Tentativo è considerato valido quando:

- il partecipante è autenticato;
- il Checkpoint esiste;
- tutte le regole di progressione risultano soddisfatte;
- il Checkpoint non era già stato validato;
- la Partecipazione consente ancora l'avanzamento.

Un Tentativo valido:

- registra il Checkpoint;
- aggiorna la progressione;
- aggiorna lo stato della Partecipazione se necessario.

---

## 7.5 Tentativo duplicato

Una scansione di un Checkpoint già validato produce un Tentativo Duplicato.

Il sistema:

- registra il Tentativo;
- non modifica la progressione;
- non assegna punti;
- informa il partecipante che il Checkpoint era già stato trovato.

---

## 7.6 Tentativo non valido

Qualunque violazione delle regole di progressione produce un Tentativo non valido.

Esempi:

- scansione effettuata troppo presto;
- scansione effettuata troppo tardi;
- scansione di un Checkpoint finale con prerequisiti mancanti;
- scansione dopo il completamento del Percorso.

Il sistema registra comunque il Tentativo.

---

## 7.7 Visualizzazione

L'amministratore deve poter consultare:

- tutti i Tentativi;
- solo i Tentativi validi;
- solo i Tentativi non validi;
- solo i Tentativi duplicati.

Per ogni Tentativo deve essere possibile visualizzare almeno:

- partecipante;
- percorso;
- checkpoint;
- data e ora;
- esito;
- motivazione.

---

## 7.8 Statistiche

Per ogni Percorso il plugin deve calcolare almeno:

- numero totale di Tentativi;
- numero di Tentativi validi;
- numero di Tentativi non validi;
- numero di Tentativi duplicati;
- percentuale di successo.

Per ogni Checkpoint devono essere disponibili almeno:

- numero di scansioni;
- numero di validazioni;
- numero di duplicati;
- numero di Tentativi non validi.

---

## 7.9 Esportazione

L'amministratore deve poter esportare i Tentativi in formato CSV.

L'esportazione dovrà consentire almeno il filtraggio per:

- percorso;
- partecipante;
- intervallo temporale;
- esito.

---

## 7.10 Conservazione

I Tentativi costituiscono lo storico dell'attività del partecipante.

Non devono essere eliminati automaticamente.

Eventuali strumenti di cancellazione saranno valutati in una versione futura del plugin.

# 8. Gestione dei Percorsi

## 8.1 Definizione

Un Percorso rappresenta una esperienza di gioco completa.

Ogni Percorso contiene uno o più Checkpoint.

Tutti i Checkpoint appartengono obbligatoriamente ad un solo Percorso.

Un Percorso è indipendente da qualsiasi altro Percorso presente nel sito.

---

## 8.2 Creazione

L'amministratore può creare un nuovo Percorso dalla dashboard del plugin.

Durante la creazione devono essere configurabili almeno i seguenti campi:

- Nome
- Descrizione
- Stato
- Data di apertura (opzionale)
- Data di chiusura (opzionale)

Il plugin potrà introdurre ulteriori impostazioni nelle versioni successive.

---

## 8.3 Stati del Percorso

Un Percorso può assumere uno dei seguenti stati.

### Bozza

Il Percorso è in fase di preparazione.

Non può essere iniziato dai partecipanti.

---

### Pubblicato

Il Percorso è disponibile.

I partecipanti possono iniziare il gioco.

---

### Chiuso

Il Percorso non accetta nuove partecipazioni.

Le Partecipazioni già iniziate restano consultabili.

---

### Archiviato

Il Percorso viene mantenuto esclusivamente per finalità storiche.

Non può essere modificato.

---

## 8.4 Checkpoint iniziale

Ogni Percorso deve avere un solo Checkpoint iniziale.

La validazione corretta del Checkpoint iniziale crea automaticamente la Partecipazione.

Un Percorso non può essere pubblicato senza un Checkpoint iniziale.

---

## 8.5 Checkpoint finale

Ogni Percorso può avere un solo Checkpoint finale.

La validazione positiva del Checkpoint finale conclude il Percorso.

Il plugin dovrà impedire la presenza di più Checkpoint finali.

---

## 8.6 Ordinamento

Il plugin non impone un ordinamento numerico dei Checkpoint.

L'ordine di gioco è determinato esclusivamente dalle regole di progressione configurate.

L'ordinamento visualizzato nella dashboard ha esclusivamente finalità amministrative.

---

## 8.7 Regole di progressione

Ogni Percorso può contenere contemporaneamente:

- Checkpoint completamente liberi;
- Checkpoint con prerequisiti;
- Checkpoint con vincoli "Non valido dopo";
- combinazioni delle due regole.

Il plugin non deve imporre schemi di gioco predefiniti.

L'amministratore costruisce liberamente la logica del Percorso.

---

## 8.8 Verifica di coerenza

Prima della pubblicazione il plugin deve verificare automaticamente la coerenza dell'intero Percorso.

Devono essere rilevati almeno:

- prerequisiti inesistenti;
- riferimenti circolari;
- dipendenze impossibili;
- checkpoint iniziale non raggiungibile;
- checkpoint finale non raggiungibile;
- checkpoint isolati;
- presenza di più checkpoint iniziali;
- presenza di più checkpoint finali.

In presenza di errori il Percorso non può essere pubblicato.

---

## 8.9 Statistiche

Per ogni Percorso devono essere disponibili almeno:

- numero totale dei partecipanti;
- partecipazioni in corso;
- partecipazioni terminate;
- partecipazioni completate;
- partecipazioni annullate;
- numero totale dei Tentativi;
- numero totale delle validazioni;
- numero dei Tentativi non validi;
- numero dei duplicati.

---

## 8.10 Esportazione

Per ogni Percorso l'amministratore deve poter esportare almeno:

- elenco partecipanti;
- elenco Partecipazioni;
- elenco Tentativi;
- statistiche.

L'esportazione deve essere disponibile almeno nel formato CSV.

---

## 8.11 Duplicazione

L'amministratore deve poter duplicare qualsiasi Percorso, indipendentemente dal suo stato.

Devono poter essere duplicati anche Percorsi Archiviati.

La duplicazione crea un nuovo Percorso completamente indipendente dall'originale.

Devono essere duplicati:

- impostazioni del Percorso;
- Checkpoint;
- contenuti dei Checkpoint;
- regole di progressione;
- configurazione dei QR Code;
- eventuali immagini associate ai Checkpoint.

Non devono essere duplicati:

- Partecipazioni;
- Tentativi;
- statistiche;
- log;
- dati storici.

Il nuovo Percorso viene creato nello stato "Bozza".

Durante la duplicazione tutti i Checkpoint devono ricevere:

- un nuovo identificativo interno;
- un nuovo token pubblico;
- un nuovo QR Code.

Nessun URL del Percorso originale deve rimanere valido nel nuovo Percorso.

---

## 8.12 Eliminazione

L'eliminazione di un Percorso deve richiedere una conferma esplicita.

Nella versione 1.0 il comportamento verrà definito durante la progettazione del database.

Dovrà essere valutata la possibilità di impedire l'eliminazione di Percorsi contenenti dati storici.

# 9. Dashboard di amministrazione

## 9.1 Obiettivi

La Dashboard costituisce il principale strumento di amministrazione del plugin.

Tutte le funzionalità principali devono essere raggiungibili senza dover modificare direttamente il database o utilizzare strumenti esterni.

L'interfaccia deve essere coerente con lo stile dell'amministrazione di WordPress.

---

## 9.2 Menu

Il plugin aggiunge un menu principale "QRHunt" contenente almeno le seguenti voci.

- Dashboard
- Percorsi
- Checkpoint
- Partecipazioni
- Tentativi
- Esportazioni
- Impostazioni

Versioni future potranno aggiungere ulteriori sezioni.

---

## 9.3 Dashboard

La Dashboard mostra una panoramica generale.

Devono essere visualizzati almeno:

- numero di Percorsi;
- Percorsi attivi;
- numero totale di Partecipazioni;
- numero totale di Tentativi;
- numero di Tentativi non validi;
- numero di scansioni duplicate.

Devono inoltre essere mostrati gli ultimi Tentativi registrati.

---

## 9.4 Gestione Percorsi

La schermata Percorsi deve consentire almeno:

- creazione;
- modifica;
- duplicazione;
- archiviazione;
- eliminazione;
- esportazione;
- apertura delle statistiche.

La lista deve poter essere ordinata e filtrata.

---

## 9.5 Gestione Checkpoint

Per ogni Percorso deve essere disponibile la lista dei relativi Checkpoint.

Per ogni Checkpoint devono essere mostrati almeno:

- nome;
- percorso;
- stato;
- prerequisito;
- non valido dopo;
- numero di validazioni;
- numero di tentativi non validi;
- numero di duplicati.

Da questa schermata devono essere disponibili almeno le seguenti operazioni.

- modifica;
- duplicazione;
- eliminazione;
- download del QR Code;
- rigenerazione del QR Code.

---

## 9.6 Gestione Partecipazioni

L'elenco delle Partecipazioni deve essere filtrabile almeno per:

- Percorso;
- partecipante;
- stato.

Aprendo una Partecipazione devono essere visibili:

- dati del partecipante;
- stato corrente;
- data di inizio;
- data di conclusione;
- elenco dei Checkpoint validati;
- elenco cronologico dei Tentativi.

L'amministratore può annullare una Partecipazione.

---

## 9.7 Gestione Tentativi

La schermata Tentativi rappresenta il registro completo delle scansioni.

Ogni riga deve rappresentare un Tentativo.

Devono essere visualizzati almeno:

- data e ora;
- partecipante;
- percorso;
- checkpoint;
- esito;
- motivazione.

L'elenco deve essere filtrabile almeno per:

- Percorso;
- partecipante;
- esito;
- intervallo temporale.

---

## 9.8 Esportazioni

Il plugin deve consentire l'esportazione in formato CSV di almeno:

- Percorsi;
- Partecipazioni;
- Tentativi.

Ogni esportazione deve poter essere filtrata prima della generazione.

---

## 9.9 Impostazioni

Le impostazioni del plugin devono essere suddivise in sezioni logiche.

La versione 1.0 dovrà prevedere almeno:

### Generale

- lingua del plugin;
- formato data;
- formato ora.

### QR Code

- formato immagine;
- livello di correzione;
- dimensione;
- logo centrale.

### Sicurezza

- lunghezza del token pubblico;
- durata dei nonce;
- impostazioni di cache.

### Esportazione

- separatore CSV;
- codifica caratteri.

Ulteriori impostazioni potranno essere introdotte nelle versioni successive.

---

## 9.10 Permessi

Il plugin deve utilizzare il sistema di ruoli e capacità di WordPress.

Non devono essere introdotti utenti o ruoli proprietari.

Nella versione 1.0 le funzionalità amministrative saranno riservate agli utenti con capacità di amministrazione del plugin.

Una futura versione potrà introdurre capacità più granulari.
