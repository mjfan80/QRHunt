# QRHunt Software Requirements Specification (SRS)

**Project:** QRHunt

**Document Version:** 0.2 (Draft)

**Plugin Version:** Target 1.0.0

**Status:** Draft

**License:** LGPL-3.0-or-later

---

# 1. Introduzione

## 1.1 Scopo del progetto

QRHunt è un plugin per WordPress che consente di creare esperienze interattive basate su Checkpoint identificati tramite QR Code.

Il plugin è progettato per essere completamente generico e può essere utilizzato, a titolo di esempio, per:

- cacce al tesoro;
- percorsi a tappe;
- eventi;
- musei;
- fiere;
- percorsi didattici;
- parchi tematici;
- visite guidate;
- qualsiasi attività nella quale un partecipante debba trovare uno o più Checkpoint.

QRHunt non è legato ad uno specifico tema, evento o settore applicativo.

---

## 1.2 Obiettivi

Il plugin deve permettere di:

- creare uno o più Percorsi indipendenti;
- creare e gestire i relativi Checkpoint;
- generare automaticamente i QR Code;
- registrare tutte le scansioni effettuate dai partecipanti;
- verificare automaticamente le regole di progressione;
- fornire strumenti di amministrazione;
- esportare i dati raccolti;
- mantenere lo storico delle attività.

---

## 1.3 Filosofia del progetto

QRHunt gestisce esclusivamente la logica del gioco.

WordPress continua a gestire:

- autenticazione degli utenti;
- contenuti;
- media;
- editor;
- permessi;
- traduzioni.

Il plugin dovrà integrarsi con WordPress utilizzando esclusivamente API ufficiali ed evitando la duplicazione di funzionalità già disponibili nel CMS.

---

## 1.4 Ambito della specifica

Il presente documento descrive esclusivamente il comportamento funzionale del plugin.

Le decisioni implementative (database, architettura del codice, API interne, classi e struttura del progetto) saranno documentate separatamente.

---

# 2. Glossario

## Percorso

Esperienza di gioco completa composta da uno o più Checkpoint.

Ogni Percorso è completamente indipendente dagli altri.

---

## Checkpoint

Tappa del Percorso identificata da un QR Code.

Ogni Checkpoint appartiene ad un solo Percorso.

---

## Partecipante

Utente autenticato di WordPress.

QRHunt utilizza il sistema di autenticazione di WordPress e non implementa un proprio sistema di login.

---

## Partecipazione

Relazione tra un Partecipante ed un Percorso.

Per ogni coppia Partecipante/Percorso può esistere una sola Partecipazione.

---

## Tentativo

Ogni richiesta all'URL pubblico di un Checkpoint genera un Tentativo.

Il Tentativo viene sempre registrato, indipendentemente dal suo esito.

Ogni Tentativo registra almeno:

- data e ora complete (timestamp);
- partecipante;
- percorso;
- checkpoint;
- esito.

La registrazione dei timestamp consente di calcolare successivamente:

- durata complessiva del Percorso;
- tempo trascorso tra Checkpoint consecutivi;
- classifiche cronometriche;
- statistiche.

---

## QR Code

Codice bidimensionale generato automaticamente dal plugin.

Ogni QR Code identifica un solo Checkpoint e contiene esclusivamente il relativo URL pubblico.

---

# 3. Modello logico

Le relazioni fondamentali del plugin sono le seguenti.

```
Partecipante
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
Tentativi
```

Ogni Tentativo è sempre riferito ad:

- un Partecipante;
- una Partecipazione;
- un Percorso;
- un Checkpoint.

---

# 4. Gestione delle Partecipazioni

## 4.1 Definizione

Una Partecipazione rappresenta il legame tra un Partecipante e un Percorso.

Per ogni coppia Partecipante/Percorso può esistere una sola Partecipazione.

La Partecipazione rappresenta lo stato di avanzamento del partecipante all'interno del Percorso e viene gestita esclusivamente dal plugin.

---

## 4.2 Creazione

La Partecipazione viene creata automaticamente alla validazione del Checkpoint iniziale.

Non è prevista la creazione manuale di Partecipazioni da parte degli amministratori.

Se il partecipante possiede già una Partecipazione relativa al medesimo Percorso, non ne viene creata una nuova.

---

## 4.3 Stati

Una Partecipazione può assumere esclusivamente uno dei seguenti stati.

### Non iniziata

Il partecipante possiede un account WordPress ma non ha ancora iniziato il Percorso.

Non esiste ancora alcuna Partecipazione nel database.

### In corso

Il partecipante ha validato correttamente il Checkpoint iniziale.

Il Percorso è iniziato e può proseguire nel rispetto delle regole configurate.

### Terminata

Il partecipante ha validato correttamente il Checkpoint finale, rispettando tutte le regole di progressione necessarie per raggiungerlo.

Non risultano però validati tutti i Checkpoint appartenenti al Percorso.

Il Percorso è concluso e non può più proseguire.

### Completata

Il partecipante ha validato correttamente:

- il Checkpoint finale;
- tutti i Checkpoint appartenenti al Percorso.

Il Percorso è concluso integralmente.

### Annullata

Partecipazione invalidata manualmente da un amministratore.

L'annullamento non elimina alcun dato storico.

---

## 4.4 Transizioni

Sono consentite esclusivamente le seguenti transizioni.

```
Non iniziata
      │
      ▼
In corso
      │
      ├─────────────► Terminata
      │
      └─────────────► Completata
```

Da qualunque stato è sempre possibile passare ad **Annullata**.

Non sono consentite altre transizioni.

---

## 4.5 Informazioni registrate

Per ogni Partecipazione devono essere registrati almeno:

- partecipante;
- percorso;
- stato;
- data e ora di creazione;
- data e ora di conclusione, se presente.

La durata del Percorso non viene memorizzata ma calcolata dai Tentativi registrati.

---

## 4.6 Aggiornamento

Lo stato della Partecipazione viene aggiornato automaticamente dal plugin.

L'amministratore non può modificare manualmente la progressione del partecipante.

Può esclusivamente annullare la Partecipazione.

---

# 5. Gestione dei Checkpoint

## 5.1 Definizione

Un Checkpoint rappresenta una tappa del Percorso identificata da un QR Code.

Ogni Checkpoint appartiene ad un solo Percorso.

Ogni Percorso può contenere un numero arbitrario di Checkpoint.

---

## 5.2 Identificazione

Ogni Checkpoint possiede almeno:

- identificativo interno;
- token pubblico;
- URL pubblico;
- QR Code.

L'identificativo interno non deve mai comparire nell'URL pubblico.

L'URL pubblico deve utilizzare esclusivamente il token.

Il token deve essere sufficientemente casuale da impedire l'individuazione dei Checkpoint tramite tentativi sistematici.

---

## 5.3 Contenuto

Ogni Checkpoint è implementato come Custom Post Type di WordPress.

Il contenuto viene gestito tramite l'editor Gutenberg senza limitazioni.

Il plugin non impone alcuna struttura ai contenuti.

---

## 5.4 Checkpoint iniziale

Ogni Percorso deve possedere un solo Checkpoint iniziale.

La sua validazione crea automaticamente la Partecipazione.

Un Percorso non può essere pubblicato senza un Checkpoint iniziale.

---

## 5.5 Checkpoint finale

Ogni Percorso deve possedere un solo Checkpoint finale.

La sua validazione conclude il Percorso.

Un Percorso non può essere pubblicato senza un Checkpoint finale.

---

## 5.6 Regole di progressione

Ogni Checkpoint può definire due regole indipendenti.

Entrambe sono facoltative.

### Prerequisito

Indica quale Checkpoint deve risultare già validato affinché il Checkpoint corrente possa essere validato.

Se il prerequisito non è soddisfatto:

- il Tentativo viene registrato;
- il Checkpoint non viene validato;
- la progressione non viene modificata;
- il partecipante riceve il relativo messaggio.

### Non valido dopo

Indica il Checkpoint oltre il quale il Checkpoint corrente non può più essere validato.

Se il partecipante ha già validato il Checkpoint indicato:

- il Tentativo viene registrato;
- il Checkpoint non viene validato;
- la progressione non viene modificata;
- il partecipante riceve il relativo messaggio.

Le due regole possono coesistere.

---

## 5.7 Validazione

Ogni apertura dell'URL pubblico di un Checkpoint genera sempre un Tentativo.

L'algoritmo di validazione verifica, nell'ordine:

1. autenticazione del partecipante;
2. esistenza del Checkpoint;
3. appartenenza del Checkpoint al Percorso;
4. eventuale creazione della Partecipazione;
5. stato della Partecipazione;
6. eventuale scansione duplicata;
7. verifica del Prerequisito;
8. verifica della regola "Non valido dopo";
9. validazione del Checkpoint;
10. aggiornamento dello stato della Partecipazione.

Al primo controllo non superato la validazione termina.

Il Tentativo viene comunque registrato.

---

## 5.8 Controllo di coerenza

Il plugin deve impedire la configurazione di regole incoerenti.

Devono essere rilevati almeno:

- prerequisiti inesistenti;
- riferimenti a Checkpoint appartenenti ad altri Percorsi;
- dipendenze circolari;
- assenza del Checkpoint iniziale;
- assenza del Checkpoint finale;
- presenza di più Checkpoint iniziali;
- presenza di più Checkpoint finali.

In presenza di errori il Percorso non può essere pubblicato.

---

## 5.9 Amministrazione

L'amministratore deve poter:

- creare;
- modificare;
- duplicare;
- eliminare;
- scaricare il QR Code;
- rigenerare il QR Code;
- consultare le statistiche del Checkpoint.

---

## 5.10 Visibilità

I Checkpoint non devono comparire:

- nelle sitemap;
- negli archivi del Custom Post Type;
- nei feed RSS;
- nei risultati di ricerca del sito.

Devono essere raggiungibili esclusivamente conoscendo il relativo URL pubblico.

# 6. Gestione dei Tentativi

## 6.1 Definizione

Ogni richiesta ricevuta dall'URL pubblico di un Checkpoint genera un Tentativo.

Il Tentativo viene sempre registrato, indipendentemente dal suo esito.

---

## 6.2 Informazioni registrate

Ogni Tentativo deve registrare almeno:

- partecipante;
- partecipazione;
- percorso;
- checkpoint;
- timestamp completo;
- esito;
- motivazione dell'esito.

La registrazione del timestamp consente il calcolo della durata del Percorso, dei tempi tra i Checkpoint e di eventuali classifiche cronometriche.

La registrazione dell'indirizzo IP e del User Agent deve essere configurabile e può essere disabilitata dall'amministratore.

---

## 6.3 Esiti

Ogni Tentativo appartiene ad una sola categoria.

La versione 1.0 prevede almeno:

- valido;
- duplicato;
- prerequisito non soddisfatto;
- non valido dopo;
- partecipazione annullata;
- partecipazione conclusa;
- partecipante non autenticato;
- checkpoint inesistente.

L'elenco dovrà poter essere esteso nelle versioni future.

---

## 6.4 Consultazione

L'amministratore deve poter consultare tutti i Tentativi.

Devono essere disponibili filtri almeno per:

- percorso;
- partecipante;
- checkpoint;
- esito;
- intervallo temporale.

---

## 6.5 Esportazione

I Tentativi devono poter essere esportati in formato CSV.

L'esportazione deve rispettare gli eventuali filtri applicati.

---

## 6.6 Conservazione

I Tentativi costituiscono lo storico delle attività del partecipante.

Non devono essere eliminati automaticamente.

Eventuali strumenti di eliminazione o anonimizzazione potranno essere introdotti in versioni future.

# 7. Gestione dei Percorsi

## 7.1 Definizione

Un Percorso rappresenta una esperienza di gioco completa.

Ogni Percorso contiene uno o più Checkpoint.

Tutti i Checkpoint appartengono obbligatoriamente ad un solo Percorso.

I Percorsi sono completamente indipendenti tra loro.

---

## 7.2 Creazione

L'amministratore può creare un nuovo Percorso dalla dashboard del plugin.

Durante la creazione devono essere configurabili almeno:

- nome;
- descrizione;
- stato;
- data di apertura (opzionale);
- data di chiusura (opzionale).

Ulteriori impostazioni potranno essere introdotte nelle versioni successive.

---

## 7.3 Stati del Percorso

Un Percorso può assumere uno dei seguenti stati.

### Bozza

Il Percorso è in fase di preparazione.

Non può essere iniziato dai partecipanti.

---

### Pubblicato

Il Percorso è disponibile.

I partecipanti possono iniziarlo.

---

### Chiuso

Il Percorso non accetta nuove Partecipazioni.

Le Partecipazioni già iniziate rimangono consultabili.

---

### Archiviato

Il Percorso viene conservato a fini storici.

Non accetta nuove Partecipazioni.

Può essere modificato e duplicato dagli amministratori.

---

## 7.4 Checkpoint iniziale

Ogni Percorso deve possedere un solo Checkpoint iniziale.

Un Percorso non può essere pubblicato senza un Checkpoint iniziale.

---

## 7.5 Checkpoint finale

Ogni Percorso deve possedere un solo Checkpoint finale.

Un Percorso non può essere pubblicato senza un Checkpoint finale.

---

## 7.6 Regole di progressione

Ogni Percorso può contenere contemporaneamente:

- Checkpoint liberi;
- Checkpoint con Prerequisito;
- Checkpoint con regola "Non valido dopo";
- qualunque combinazione delle due regole.

Il plugin non impone alcuno schema di gioco.

---

## 7.7 Verifica di coerenza

Prima della pubblicazione il plugin deve verificare automaticamente la coerenza dell'intero Percorso.

Devono essere rilevati almeno:

- prerequisiti inesistenti;
- dipendenze circolari;
- riferimenti a Checkpoint appartenenti ad altri Percorsi;
- assenza del Checkpoint iniziale;
- assenza del Checkpoint finale;
- presenza di più Checkpoint iniziali;
- presenza di più Checkpoint finali;
- Checkpoint irraggiungibili.

In presenza di errori il Percorso non può essere pubblicato.

---

## 7.8 Statistiche

Per ogni Percorso devono essere disponibili almeno:

- numero di Partecipazioni;
- Partecipazioni in corso;
- Partecipazioni terminate;
- Partecipazioni completate;
- Partecipazioni annullate;
- numero totale dei Tentativi;
- numero di Tentativi validi;
- numero di Tentativi non validi;
- numero di Tentativi duplicati.

---

## 7.9 Esportazione

Per ogni Percorso l'amministratore deve poter esportare almeno:

- Partecipazioni;
- Tentativi;
- statistiche.

Il formato minimo supportato è CSV.

---

## 7.10 Duplicazione

L'amministratore deve poter duplicare qualsiasi Percorso, indipendentemente dal suo stato.

Devono essere duplicati:

- impostazioni;
- Checkpoint;
- contenuti;
- regole di progressione.

Non devono essere duplicati:

- Partecipazioni;
- Tentativi;
- statistiche;
- dati storici.

Il nuovo Percorso viene creato nello stato **Bozza**.

Durante la duplicazione tutti i Checkpoint ricevono:

- un nuovo identificativo interno;
- un nuovo token pubblico;
- un nuovo QR Code.

Nessun URL del Percorso originale deve rimanere valido nel nuovo Percorso.

---

## 7.11 Eliminazione

L'eliminazione di un Percorso deve richiedere una conferma esplicita.

Il comportamento definitivo verrà definito durante la progettazione del database.

---

# 8. Dashboard di amministrazione

## 8.1 Obiettivi

La Dashboard costituisce il principale strumento di amministrazione del plugin.

Tutte le funzionalità devono essere raggiungibili dall'interfaccia di WordPress senza richiedere strumenti esterni.

---

## 8.2 Menu

Il plugin aggiunge un menu principale "QRHunt" contenente almeno:

- Dashboard;
- Percorsi;
- Checkpoint;
- Partecipazioni;
- Tentativi;
- Esportazioni;
- Impostazioni.

---

## 8.3 Dashboard

La schermata iniziale deve mostrare almeno:

- numero dei Percorsi;
- Percorsi attivi;
- numero delle Partecipazioni;
- numero totale dei Tentativi;
- numero dei Tentativi non validi;
- numero dei Tentativi duplicati;
- ultimi Tentativi registrati.

---

## 8.4 Gestione dei Percorsi

Per ogni Percorso devono essere disponibili almeno le operazioni di:

- creazione;
- modifica;
- duplicazione;
- archiviazione;
- eliminazione;
- esportazione;
- consultazione delle statistiche.

---

## 8.5 Gestione dei Checkpoint

Per ogni Checkpoint devono essere disponibili almeno:

- modifica;
- duplicazione;
- eliminazione;
- download del QR Code;
- rigenerazione del QR Code;
- statistiche.

---

## 8.6 Gestione delle Partecipazioni

L'amministratore deve poter:

- consultare tutte le Partecipazioni;
- filtrarle per Percorso;
- filtrarle per partecipante;
- filtrarle per stato;
- visualizzare il dettaglio completo della cronologia;
- annullare una Partecipazione.

---

## 8.7 Gestione dei Tentativi

Ogni Tentativo deve poter essere consultato e filtrato almeno per:

- Percorso;
- partecipante;
- Checkpoint;
- esito;
- intervallo temporale.

---

## 8.8 Esportazioni

Il plugin deve consentire almeno l'esportazione CSV di:

- Partecipazioni;
- Tentativi;
- statistiche dei Percorsi.

---

## 8.9 Impostazioni

La versione 1.0 dovrà prevedere almeno:

### Generali

- lingua;
- formato data;
- formato ora.

### QR Code

- formato;
- dimensione;
- livello di correzione;
- logo centrale.

### Privacy

- registrazione indirizzo IP;
- registrazione User Agent.

### Esportazione

- separatore CSV;
- codifica dei caratteri.

---

## 8.10 Permessi

Il plugin utilizza esclusivamente il sistema di ruoli e capacità di WordPress.

Non vengono introdotti ruoli proprietari.

Le funzionalità amministrative sono disponibili esclusivamente agli utenti autorizzati.

---

# 9. Esperienza del partecipante

## 9.1 Accesso

Il partecipante deve essere autenticato tramite WordPress prima di poter iniziare un Percorso.

QRHunt non implementa un proprio sistema di autenticazione.

Può integrarsi con eventuali plugin di Social Login compatibili.

---

## 9.2 Avvio del Percorso

La scansione del Checkpoint iniziale crea automaticamente la Partecipazione.

Se il partecipante ha già iniziato o concluso il Percorso, il plugin mostra lo stato attuale senza creare una nuova Partecipazione.

---

## 9.3 Validazione di un Checkpoint

Dopo ogni scansione il partecipante riceve una risposta chiara che indica almeno:

- esito della validazione;
- titolo del Checkpoint;
- contenuto del Checkpoint;
- stato di avanzamento del Percorso.

---

## 9.4 Scansione duplicata

Se il Checkpoint era già stato validato:

- il Tentativo viene registrato;
- la progressione non cambia;
- il partecipante viene informato che il Checkpoint era già stato trovato.

---

## 9.5 Scansione non valida

Quando una scansione non rispetta le regole di progressione:

- il Tentativo viene registrato;
- la progressione non cambia;
- viene mostrato il motivo della mancata validazione.

---

## 9.6 Conclusione del Percorso

Alla validazione del Checkpoint finale il partecipante viene informato dell'esito finale del Percorso.

Il sistema deve distinguere chiaramente tra:

- Percorso Terminato;
- Percorso Completato.
