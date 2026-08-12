# QRHunt Software Requirements Specification (SRS)

**Project:** QRHunt

**Document Version:** 0.2 (Draft)

**Plugin Version:** Target 1.0.0

**Status:** Draft

**License:** GPL-2.0-or-later

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

## Tentativo / Event

Un Tentativo è una scansione che ha superato le verifiche preliminari del Player Flow (token risolto, utente autenticato, Path disponibile e Participation persistita o creata dopo la validazione iniziale). Viene registrato come Event.

Le richieste che terminano prima di tali verifiche non generano Event. In particolare, una prima validazione fallita del Checkpoint iniziale non crea né Participation né Event.

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

Il nome tecnico del Custom Post Type è `qrhunt_checkpoint`.

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

L'apertura dell'URL pubblico avvia il Player Flow autenticato. Un Tentativo/Event viene registrato solo quando la scansione raggiunge la validazione con una Participation persistita; le verifiche preliminari e una prima validazione iniziale fallita non generano record storico.

L'algoritmo di validazione verifica, nell'ordine:

1. autenticazione del partecipante;
2. esistenza del Checkpoint;
3. appartenenza del Checkpoint al Percorso;
4. disponibilità del Percorso;
5. verifica di una Participation esistente oppure validazione iniziale del Checkpoint iniziale prima della sua creazione;
6. stato della Partecipazione;
7. eventuale scansione duplicata;
8. verifica del Prerequisito;
9. verifica della regola "Non valido dopo";
10. validazione del Checkpoint;
11. aggiornamento dello stato della Partecipazione.

Al primo controllo non superato la validazione termina.

Un Event viene registrato solo se esiste una Participation persistita. Se la prima validazione del Checkpoint iniziale fallisce, non vengono registrati né una Participation né un Event.

---

## 5.8 Controllo di coerenza

Il plugin deve impedire la configurazione di regole incoerenti.

Devono essere rilevati almeno:

- Checkpoint iniziale assente, inesistente o non appartenente al Percorso;
- Checkpoint finale assente, inesistente o non appartenente al Percorso;
- Checkpoint iniziale e finale coincidenti;
- riferimenti di dipendenza inesistenti o appartenenti a un altro Percorso;
- dipendenze circolari che rendono impossibile soddisfare l'ordine.

In presenza di errori il Percorso non può essere pubblicato.

---

## 5.9 Amministrazione

L'amministratore deve poter:

- creare;
- modificare;
- eliminare;
- scaricare il QR Code;

Il QR Code viene generato dal token pubblico esistente. Nella versione 1.0 non sono previste funzioni di rigenerazione del token o del QR Code.

I Checkpoint vengono gestiti all'interno del menu amministrativo del plugin QRHunt e non come voce autonoma del menu di WordPress.

---

## 5.10 Visibilità

I Checkpoint non devono comparire:

- nelle sitemap;
- negli archivi del Custom Post Type;
- nei feed RSS;
- nei risultati di ricerca del sito.

Devono essere raggiungibili esclusivamente conoscendo il relativo URL pubblico.

Il contenuto di un Checkpoint viene visualizzato esclusivamente tramite il router pubblico del plugin utilizzando il token pubblico.

Il permalink del Custom Post Type non viene utilizzato.

## 5.11 Rendering del Checkpoint

Il contenuto del Checkpoint non viene visualizzato tramite il permalink del Custom Post Type.

L'accesso avviene esclusivamente tramite l'URL pubblico generato dal plugin utilizzando il token.

Il plugin intercetta la richiesta, esegue le verifiche previste dal flusso di validazione e rende una pagina dedicata del Checkpoint.

La pagina deve:

- utilizzare il tema WordPress attivo;
- mantenere automaticamente gli elementi grafici del sito (colori, tipografia, favicon, ecc.), salvo diversa configurazione del plugin;
- consentire al plugin di utilizzare un layout dedicato ai Checkpoint, ottimizzato per la visualizzazione su dispositivi mobili;
- visualizzare il contenuto Gutenberg del Checkpoint;
- permettere al plugin di aggiungere automaticamente elementi dell'interfaccia (messaggi, stato della validazione, pulsanti, indicazioni per il passo successivo, ecc.).

Il layout della pagina può essere personalizzato dal tema o dal plugin senza modificare il contenuto del Checkpoint.
La visualizzazione deve essere responsive e ottimizzata prioritariamente per smartphone.

# 6. Gestione dei Tentativi

## 6.1 Definizione

Un Tentativo è un Event di scansione registrato dopo le verifiche preliminari e con una Participation persistita. Per una Participation esistente vengono registrati gli esiti accepted, duplicate, after_failed e before_failed; una prima validazione del Checkpoint iniziale fallita non crea né Participation né Event.

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

La registrazione dell'indirizzo IP e del User Agent deve essere configurabile con due impostazioni indipendenti, disattivate per impostazione predefinita. Quando una delle due impostazioni è disattivata, il valore corrispondente non viene raccolto e resta `NULL`; l'attivazione non raccoglie dati retroattivamente.

---

## 6.3 Esiti

Ogni Tentativo appartiene ad una sola categoria.

La versione 1.0 prevede almeno:

- valido;
- duplicato;
- prerequisito non soddisfatto;
- non valido dopo;
- Path chiuso o non disponibile, token inesistente, utente non autenticato e Participation annullata o conclusa terminano prima della registrazione dell'Event.

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

Partecipazioni, Event e stato di avanzamento associati rimangono integralmente disponibili per la consultazione amministrativa e per l'esportazione.

L'archiviazione non modifica Checkpoint, Partecipazioni, stato di avanzamento o Event. Il ripristino riporta il Percorso nello stato **Bozza**; la successiva pubblicazione è esplicita e sottoposta nuovamente alla verifica della configurazione.

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

## 7.7 Verifica configurazione

Prima della pubblicazione il plugin deve verificare automaticamente la coerenza dell'intero Percorso.

La sezione amministrativa **Verifica configurazione** mostra controlli superati, errori bloccanti, warning non bloccanti e il verdetto di pubblicabilità. Gli stessi controlli sono l'unica fonte usata per impedire la pubblicazione.

Devono essere rilevati come errori bloccanti almeno:

- prerequisiti inesistenti;
- dipendenze circolari;
- riferimenti a Checkpoint appartenenti ad altri Percorsi;
- assenza del Checkpoint iniziale;
- assenza del Checkpoint finale;
- riferimenti a Gruppi non validi o appartenenti a un altro Percorso;
- dipendenze con tipo o destinazione non validi;

Le dipendenze BEFORE e AFTER sono vincoli di validazione, non archi di navigazione: l'assenza di una relazione non rende un Checkpoint irraggiungibile e non viene verificata una raggiungibilità dal Checkpoint iniziale.

Un Checkpoint ordinario, diverso da iniziale e finale, che non possiede proprie dipendenze genera un warning non bloccante. Può essere validato indipendentemente dalla progressione precedente; il fatto che sia destinazione di una dipendenza altrui non elimina il warning.

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

## 7.10 Eliminazione

La versione 1.0 non introduce una funzione di eliminazione distruttiva specifica di QRHunt per i Percorsi. L'archiviazione è il meccanismo preferenziale per conservare il Percorso e il relativo storico.

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
- archiviazione;
- esportazione;
- consultazione delle statistiche.

---

## 8.5 Gestione dei Checkpoint

Per ogni Checkpoint devono essere disponibili almeno:

- modifica;
- eliminazione;
- download del QR Code;

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

Lingua, formato data e formato ora usano le impostazioni native di WordPress e non sono configurazioni proprietarie di QRHunt.

### QR Code

Il QR Code è disponibile nei formati PNG e SVG. Dimensione, livello di correzione e logo centrale non sono configurabili nella versione 1.0.

### Privacy

- registrazione indirizzo IP;
- registrazione User Agent.

### Esportazione

- separatore CSV;
- codifica UTF-8 fissa e non configurabile.

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
